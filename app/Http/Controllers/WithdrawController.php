<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TopUp;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Withdraw;
use GuzzleHttp\Psr7\Message;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class WithdrawController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Log::info('Function: ' . 'WithdrawIndex');
        $withdraws = Withdraw::orderBy('created_at', 'desc')->get();;
        if ($withdraws->isEmpty()) {
            return response()->json(['message' => 'No Withdraws found'], 404);
        }
        // Modify Withdraw data to include Wallet details'
        $withdraws = $withdraws->map(function ($withdraw) {
            $wallet = Wallet::where('ref_id', $withdraw->withdraw_id)->get();
            if (!$wallet->isEmpty()) {
                // $withdraw->wallets_details = $wallet ?: null;
                $withdraw->confirmFlag = $wallet[0]->confirmFlag;
            }
            $user = User::where('user_id', $withdraw->user_id)->get();
            if (!$user->isEmpty()) {
                // $withdraw->user_details = $user ?: null;
                $withdraw->avatar = $user[0]->avatar;
                $withdraw->fullname = $user[0]->firstname . ' ' . $user[0]->lastname;
            }

            return $withdraw;
        });


        return response()->json($withdraws);
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
        if (!$request->has('contactno')) {
            return response()->json(['message' => 'Contact No. is required'], 400);
        }
        // Validate the request data
        try {
            $request->validate([
                'user_id' => 'required|string|exists:users,user_id',
                'points' => 'required|numeric|min:1',
                'contactno' => 'required|numeric|',
                'withdrawableFlag' => 'boolean|nullable|default:1',
                'confirmFlag' => 'boolean|nullable|default:1',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }

        $user_id = $request->input('user_id');

        $wallets = Wallet::where('user_id', $user_id)
            // ->whereNotIn('source', ['BET', 'WTH'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalPoints = $wallets->where('confirmFlag', 1)->sum('points');
        $totalUnwithdrawable = $wallets->where('confirmFlag', 1)->whereIn('source', ['CBK', 'BUN', 'REF',])->sum('points');
        $totalWithdrawable = $totalPoints -  $totalUnwithdrawable;

        $withdraw_id = $request->has('withdraw_id') ? $request->input('withdraw_id') : uniqid('WTH') . date('YmdHis');
        $points = $request->input('points');
        $contactno = $request->input('contactno');

        if ($points > $totalWithdrawable) {
            return response()->json(['message' => ($totalWithdrawable == 0 ? 'Nothing' : 'Insuficient points') . ' to withdraw.'], 403);
        }

        $withdraw = Withdraw::create([
            'withdraw_id' => $withdraw_id,
            'user_id' => $user_id,
            'points' => $points,
            'contactno' => $contactno,
        ]);

        log::info('Withdraw created', [
            'withdraw_id' => $withdraw_id,
            'user_id' => $user_id,
            'points' => $points,
            'contactno' => $contactno,
        ]);

        $wallet = Wallet::create([
            'wallet_id' => uniqid('WLT') . '-' . substr($request->input('user_id'), 10) . date('YmdHis'),
            'user_id' => $user_id,
            'points' => $points,
            'withdrawableFlag' => false,
            'ref_id' => $withdraw_id,
            'source' => 'WTH',
        ]);


        // Count the number of unique withdraw_ids the user has made
        $withdrawGroupCount = Withdraw::where('user_id', $user_id)
            ->select('withdraw_id')
            ->distinct()
            ->count();

        return response()->json([
            'message' => 'Withdraw . ' . $points . ' points have been added to request list. We will notify you once approved.',
            'contactno' => $request->input('contactno'),
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
     * Update the confirmFlag of a Withdraw record by withdraw_id.
     */
    public function confirmWithdrawFlagByWithdrawID(Request $request, string $withdraw_id)
    {
        if (!$withdraw_id) {
            return response()->json(['message' => 'Withdraw ID is required'], 400);
        }
        if (!$request->has('confirmFlag')) {
            return response()->json(['message' => 'confirmFlag is required'], 400);
        }

        $confirmFlag = (bool) $request->input('confirmFlag');

        $withdraw = Wallet::where('ref_id', $withdraw_id)->first();

        if (!$withdraw) {
            return response()->json(['message' => 'Withdraw not found'], 404);
        }
        if ($withdraw->confirmFlag) {
            return response()->json(['message' => 'Withdraw has already been confirmed'], 409);
        }

        $wallets = Wallet::where('user_id', $withdraw->user_id)->where('confirmFlag', 1)->get();

        Log::info('Wallets', [
            'wallets' => $wallets
        ]);

        $totalPoints = 0;
        $totalUnwithdrawable = 0;
        $totalWithdrawable = 0;

        if ($wallets->isEmpty()) {
            return response()->json(['message' => 'No wallets found for this user'], 404);
        } else {
            $totalPoints = $wallets->sum('points');
            $totalUnwithdrawable = $wallets->whereIn('source', ['CBK', 'BUN', 'REF'])->sum('points');
            $totalWithdrawable = $totalPoints -  $totalUnwithdrawable;
        }

        // Check if the points for this withdraw exceed the total withdrawable points
        if ($withdraw->points > $totalWithdrawable) {
            return response()->json(['message' => ($totalWithdrawable == 0 ? 'Nothing' : 'Insuficient points') . ' to withdraw.'], 403);
        }

        $withdraw->confirmFlag = $confirmFlag;
        $withdraw->save();

        return response()->json([
            'message' => 'Withdraw confirmed successfully',
            'withdraw' => $withdraw
        ]);
    }


    /**
     * Show all wallets with source 'WITH' (Withdraw wallets), or filter by a specific withdraw (ref_id).
     */
    public function showWithdrawWallets(Request $request, $user_id)
    {
        $user_id = $request->has('user_id') ? $request->input('user_id') : $user_id;
        if (!$user_id) {
            return response()->json(['message' => 'User ID is required'], 400);
        }

        $wallets = Wallet::where('user_id', $user_id)
            ->where('source', 'WTH')
            ->get();

        if ($wallets->isEmpty()) {
            return response()->json(['message' => 'No Withdraw wallets found'], 404);
        }

        return response()->json($wallets);
    }
}
