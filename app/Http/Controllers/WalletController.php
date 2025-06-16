<?php

namespace App\Http\Controllers;

use App\Models\TopUp;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class WalletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $wallets = Wallet::all();

        if ($wallets->isEmpty()) {
            return response()->json(['message' => 'No wallets found'], 404);
        }
        return response()->json($wallets);
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
        // Validate the request data
        if (!$request->has('user_id')) {
            return response()->json(['message' => 'User ID is required'], 400);
        }
        if (!$request->has('points')) {
            return response()->json(['message' => 'Points is required'], 400);
        }
        if (!$request->has('ref_id')) {
            return response()->json(['message' => 'Reference ID is required'], 400);
        }
        if (!$request->has('source')) {
            return response()->json(['message' => 'Source is required'], 400);
        }
        // if (!in_array($request->input('source'), ['BUN', 'TOP', 'INC', 'CBK', 'WIN', 'WTH', 'BET'])) {
        //     return response()->json(['message' => `Source must be only in this option ['BUN', 'TOP', 'INC', 'CBK', 'WIN', 'WTH', 'BET']`], 422);
        // }

        // Validate the request data
        try {
            $request->validate([
                'user_id' => 'required|string|exists:users,user_id',
                'points' => 'required|numeric',
                'ref_id' => 'required|string',
                'source' => 'required|string|in:BUN,TOP,INC,CBK,WIN,REF,WTH,BET',
                'withdrawableFlag' => 'boolean|nullable|default:0',
                'confirmedFlag' => 'boolean|nullable|default:1',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }
        $withdrawableFlag = false;
        $confirmedFlag =  $request->has('confirmedFlag') ? $request->confirmedFlag : false;

        // Switch based on 'source' and apply additional validation if needed
        switch ($request->input('source')) {
            case 'BUN':
                // Bunos validation
                if ($request->input('points') > 0) {
                    return response()->json(['message' => 'Points must be greatan than 0 for BUNOS'], 403);
                }
                $withdrawableFlag = 1; // Set withdrawableFlag to true for BUNOS
                break;
            case 'TOP':
                // TopUp validation
                if ($request->input('points') > 0) {
                    return response()->json(['message' => 'Points must be greatan than 0 for TOPUP'], 403);
                }
                $withdrawableFlag = true; // Set withdrawableFlag to true for TOPUP
                break;
            case 'INC':
                // Incentives validation
                if ($request->input('points') > 0) {
                    return response()->json(['message' => 'Points must be greatan than 0 for INCENTIVES'], 403);
                }
                $withdrawableFlag = 1; // Set withdrawableFlag to true for INCENTIVES
                break;
            case 'CBK':
                // Cashback validation
                if ($request->input('points') > 0) {
                    return response()->json(['message' => 'Points must be greatan than 0 for CASHBACK'], 403);
                }
                $withdrawableFlag = 0; // Set withdrawableFlag to false for CASHBACK 
                break;
            case 'BET':
                // BET validation
                if ($request->input('points') > 0) {
                    return response()->json(['message' => 'Points must be greatan than 0 for CASHBACK'], 403);
                }
                break;
            case 'WIN':
                // Winning validation
                if ($request->input('points') > 0) {
                    return response()->json(['message' => 'Points must be greatan than 0 for WINNING'], 403);
                }
                break;
            case 'WTH':
                // Withdraw validation
                if ($request->input('points') < 0) {
                    return response()->json(['message' => 'Points must be greatan than 0 for WINNING'], 403);
                }
                break;
            default:
                return response()->json(['message' => 'Invalid source type'], 403);
        }

        $withdrawableFlag = $request->has('withdrawableFlag') ? $request->withdrawableFlag : false;

        $wallet = Wallet::create([
            'user_id' => $request->input('user_id'),
            'points' => $request->input('points'),
            'ref_id' => $request->input('ref_id'),
            'source' => $request->input('source'),
            'withdrawableFlag' => in_array($request->input('source'), ['BET', 'CBK', 'WTH']) ? false : true,
            'confirmedFlag' => $confirmedFlag,
        ]);

        return response()->json([
            'message' => 'Wallet created successfully',
            'user' => $wallet
        ], 201);
    }


    /**
     * Display a listing of the wallets, optionally filtered by source.
     * Example: /api/wallets?source=TOP
     */
    public function walletSource(Request $request)
    {

        if (!$request->has('source')) {
            return response()->json(['message' => 'Source is required'], 400);
        }
        $query = Wallet::query();

        $source = trim($request->input('source'), '"');
        $query->where('source', $source);

        $wallets = $query->get();

        $type = null;

        switch ($source) {
            case 'BUN':
                // Bonus wallets
                $type = 'BUNOS';
                break;
            case 'TOP':
                // TopUp wallets
                $type = 'TOPUP';
                break;
            case 'INC':
                // Incentives wallets
                $type = 'INCETNIVES';
                break;
            case 'CBK':
                // Cashback wallets (Unwithdrawable)
                $type = 'CASH BACK';
                break;
            case 'BET':
                // Bet wallets (Negative)
                $type = 'BET';
                break;
            case 'WIN':
                // Winning wallets
                $type = 'WINNING';
                break;
            case 'WTH':
                // Withdraw wallets (Unwithdrawable)
                $type = 'WITHDRAW';
                break;
            default:
                return response()->json(['message' => 'Invalid source type ' . $type], 400);
        }

        if ($wallets->isEmpty()) {
            return response()->json(['message' => 'No ' . $type . ' found!'], 404);
        }
        return response()->json($wallets);
    }

    /**
     * Top up points to a user's wallet.
     */
    public function topUp(Request $request)
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

        if (TopUp::where('gcash_ref_no', $request->input('gcash_ref_no'))->exists()) {
            return response()->json(['message' => 'GCASH Reference No. already exists'], 409);
        }

        $topup = TopUp::create([
            'topup_id' => $topup_id,
            'user_id' => $request->input('user_id'),
            'points' => $request->input('points'),
            'gcash_ref_no' => $request->input('gcash_ref_no'),
        ]);

        log::info('TopUp created', [
            'topup_id' => $topup_id,
            'user_id' => $request->input('user_id'),
            'points' => $request->input('points'),
            'gcash_ref_no' => $request->input('gcash_ref_no')
        ]);

        $wallet = Wallet::create([
            'wallet_id' => uniqid('WLT') . '-' . substr($request->input('user_id'), 10) . date('YmdHis'),
            'user_id' => $request->input('user_id'),
            'points' => $request->input('points'),
            'withdrawableFlag' => true,
            'ref_id' => $topup_id,
            'source' => 'TOP',
        ]);

        return response()->json([
            'message' => 'Top up successful',
            'GCASH Reference No.' => $request->input('gcash_ref_no'),
            'wallet' => $wallet
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $user_id)
    {
        $userId = $request->has('user_id') ? $request->input('user_id') : $user_id;
        if (!$userId) {
            return response()->json(['message' => 'User ID is required'], 400);
        }

        $wallets = Wallet::where('user_id', $userId)
            // ->whereNotIn('source', ['BET', 'WTH'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalPoints = $wallets->where('confirmFlag', 1)->sum('points');

        if ($wallets->isEmpty()) {
            return response()->json(['message' => 'No wallets found for this user'], 404);
        }

        // Modify wallet data to include Topup details if source == 'TOP'
        $wallets = $wallets->map(function ($wallet) {
            if ($wallet->source === 'TOP') {
                $topup = TopUp::where('topup_id', $wallet->ref_id)->get();
                $wallet->topup_details = $topup;
            }
            return $wallet;
        });

        return response()->json(
            [
                'total_points' => $totalPoints,
                'total_withdrawable' => 0,
                'wallets' => $wallets
            ],
            200
        );
    }

    /**
     * Get all withdrawable sources: BUN, TOP, INC, WIN.
     */
    public function withdrawableSources(Request $request, string $user_id)
    {
        $user_id = $request->has('user_id') ? $request->input('user_id') : $user_id;
        if (!$user_id) {
            return response()->json(['message' => 'User ID is required'], 400);
        }

        $wallets = Wallet::where('user_id', $user_id)
            ->whereIn('source', ['BUN', 'TOP', 'INC', 'WIN'])
            ->where('withdrawableFlag', 1)
            ->where('confirmFlag', 1)
            ->get();

        if ($wallets->isEmpty()) {
            return response()->json(['message' => 'No withdrawable sources found for this user'], 404);
        }

        $sum = Wallet::where('user_id', $user_id)
            ->whereIn('source', ['BUN', 'TOP', 'INC', 'WIN'])
            ->where('withdrawableFlag', 1)
            ->where('confirmFlag', 1)
            ->sum('points');

        return response()->json([
            'user_id' => $user_id,
            'sum_withdrawable_points' => $sum,
            'withdrawable_sources' => $wallets
        ]);
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
    public function update(Request $request, string $user_id)
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
}
