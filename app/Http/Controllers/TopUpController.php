<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TopUp;
use App\Models\Wallet;
use GuzzleHttp\Psr7\Message;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class TopUpController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $topups = TopUp::all();
        if ($topups->isEmpty()) {
            return response()->json(['message' => 'No top-ups found'], 404);
        }
        return response()->json($topups);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!$request->has('user_id')) {
            return response()->json(['message' => 'User ID is required'], 400);
        }
        if (!$request->has('points')) {
            return response()->json(['message' => 'Points is required'], 400);
        }
        if (!$request->has('gcash_ref_no')) {
            return response()->json(['message' => 'GCASH Referrence No. is required'], 400);
        }
        // Validate the request data
        try {
            $request->validate([
                'user_id' => 'required|string|exists:users,user_id',
                'points' => 'required|numeric|min:1',
                'withdrawableFlag' => 'boolean|nullable|default:1',
                'confirmFlag' => 'boolean|nullable|default:1',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }

        $topup_id = $request->has('topup_id') ? $request->input('topup_id') : uniqid('TOPUP') . date('YmdHis');
        $user_id = $request->input('user_id');
        $points = $request->input('points');
        $gcash_ref_no = $request->input('gcash_ref_no');

        if (TopUp::where('gcash_ref_no', $gcash_ref_no)->exists()) {
            return response()->json(['message' => 'GCASH Reference No. already exists'], 409);
        }

        $topup = TopUp::create([
            'topup_id' => $topup_id,
            'user_id' => $user_id,
            'points' => $points,
            'gcash_ref_no' => $gcash_ref_no,
        ]);

        log::info('TopUp created', [
            'topup_id' => $topup_id,
            'user_id' => $user_id,
            'points' => $points,
            'gcash_ref_no' => $gcash_ref_no,
        ]);

        $wallet = Wallet::create([
            'wallet_id' => uniqid('WLT') . '-' . substr($request->input('user_id'), 3) . date('YmdHis'),
            'user_id' => $user_id,
            'points' => $points,
            'withdrawableFlag' => true,
            'ref_id' => $topup_id,
            'source' => 'TOP',
        ]);


        // Count the number of unique topup_ids the user has made
        $topupGroupCount = TopUp::where('user_id', $user_id)
            ->select('topup_id')
            ->distinct()
            ->count();

        $topupBonus = 0;
        $consecutiveTopUps = collect();
        if ($topupGroupCount > 0 && $topupGroupCount % 5 == 0) {
            // Get the last 5 topup_ids for this user
            $lastFiveTopupIds = TopUp::where('user_id', $user_id)
                ->select('topup_id')
                ->distinct()
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->pluck('topup_id');

            // Sum all points for these 5 topup_ids
            $totalTopUpPoints = TopUp::where('user_id', $user_id)
                ->whereIn('topup_id', $lastFiveTopupIds)
                ->sum('points');

            $topupBonus = max(5, round($totalTopUpPoints * 0.01));

            Wallet::create([
                'wallet_id' => uniqid('WLT') . '-' . substr($user_id, 3) . date('YmdHis'),
                'user_id' => $user_id,
                'points' => $topupBonus,
                'ref_id' => uniqid('TUPBONUS') . '-' . substr($user_id, 3) . date('YmdHis'),
                'withdrawableFlag' => false,
                'confirmFlag' => true,
                'source' => 'BUN', // Bonus type
            ]);

            // For message
            $consecutiveTopUps = TopUp::where('user_id', $user_id)
                ->whereIn('topup_id', $lastFiveTopupIds)
                ->get();
        }

        $message = 'Top up successful. ' . $points . ' points have been added to your wallet.';
        if ($consecutiveTopUps->count() == 5) {
            $message .= ' You have 5 consecutive topup. Cashback of ' . $topupBonus . ' points has been added to your wallet.';
        }



        return response()->json([
            'message' => $message,
            'GCASH Reference No.' => $request->input('gcash_ref_no'),
            'wallet' => $wallet
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    /**
     * Update the confirmFlag of a TopUp record by topup_id.
     */
    public function confirmTopUpFlagByTopupId(Request $request, string $topup_id)
    {
        if (!$topup_id) {
            return response()->json(['message' => 'TopUp ID is required'], 400);
        }
        if (!$request->has('confirmFlag')) {
            return response()->json(['message' => 'confirmFlag is required'], 400);
        }

        $confirmFlag = (bool) $request->input('confirmFlag');

        $topup = Wallet::where('ref_id', $topup_id)->first();

        if (!$topup) {
            return response()->json(['message' => 'TopUp not found'], 404);
        }
        if ($topup->confirmFlag) {
            return response()->json(['message' => 'TopUp has already been confirmed'], 409);
        }

        $topup->confirmFlag = $confirmFlag;
        $topup->save();

        return response()->json([
            'message' => 'TopUp confirmed successfully',
            'topup' => $topup
        ]);
    }


    /**
     * Show all wallets with source 'TOP' (TopUp wallets), or filter by a specific topup (ref_id).
     */
    public function showTopUpWallets(Request $request, $user_id)
    {
        $user_id = $request->has('user_id') ? $request->input('user_id') : $user_id;
        if (!$user_id) {
            return response()->json(['message' => 'User ID is required'], 400);
        }

        $wallets = Wallet::where('user_id', $user_id)
            ->where('source', 'TOP')
            ->get();

        if ($wallets->isEmpty()) {
            return response()->json(['message' => 'No TopUp wallets found'], 404);
        }

        return response()->json($wallets);
    }
}
