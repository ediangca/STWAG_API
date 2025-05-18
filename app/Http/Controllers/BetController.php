<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Bet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {



        $bets = Bet::all();

        if ($bets->isEmpty()) {
            return response()->json(['message' => 'No bets found'], 404);
        }
        return response()->json($bets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        Log::info('Bet request received', $request->all());

        if (!$request->has('result_id') || !$request->has('user_id') || !$request->has('number')) {
            return response()->json(['message' => 'result_id, user and number are required'], 400);
        }

        $user = User::where('user_id', $request->user_id)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'result_id' => 'nullable|string', //RES<YEAR><MM><DD><SESSION>
            'user_id' => 'required|string',
            'number' => 'required|string',
            'points' => 'nullable|numeric',
            'Datetime' => 'required|date',
        ]);

        $bet = Bet::create($request->all());
        return response()->json($bet, 201);
    }

    /**
     * Spin and determine the winning number based on the algorithm.
     */
    public function spinResult(Request $request)
    {
        // Validate the request data
        if (!$request->has('time')) {
            return response()->json(['message' => 'time is required'], 400);
        }

        // Check if the time is in the correct format
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $request->time)) {
            return response()->json(['message' => 'Invalid time format'], 400);
        }

        // Get sessions from Lottery table, ordered by start time
        $sessions = \App\Models\Lottery::orderBy('time')->get();

        // $now = DB::raw("TIME(NOW())");
        // $now = DB::select("SELECT TIME(NOW()) as now")[0]->now;
        $now = $request->time;

        $currentSession = null;

        foreach ($sessions as $session) {
            Log::info('Current time: ' . $now);
            Log::info('Session time: ' . $session->time);

            $sessionStart = date('H:i:s', strtotime($session->time) - 30 * 60);
            $sessionEnd = $session->time;

            // Check if current time is within the 30-minute window before session time up to session time
            if ($now >= $sessionStart && $now <= $sessionEnd) {
                return response()->json([
                    'message' => 'Draw has been started and processing.'
                ]);
            }

            if (!($now >= $sessionStart)) {
                $currentSession = $session;
                break;
            }
        }

        if (!$currentSession) {
            // If not in any session, pick the next closest session
            foreach ($sessions as $session) {
                if ($now < $session->time) {
                    $currentSession = $session;
                    break;
                }
            }
            // If still not found, fallback to first session
            if (!$currentSession && $sessions->count() > 0) {
                $currentSession = $sessions->first();
            }
        }

        return response()->json([
            'current_time' => $now,
            'session' => $currentSession
        ]);



        // // Get all bets for the result_id
        // $bets = Bet::where('result_id', $request->result_id)->get();

        // if ($bets->isEmpty()) {
        //     return response()->json(['message' => 'No bets found for this result ID'], 404);
        // }

        // // Calculate total bet amount and group by number
        // $grouped = $bets->groupBy('number')->map(function ($group) {
        //     return $group->sum('points');
        // });

        // $totalBet = $grouped->sum();

        // // Find numbers with min and max bet
        // $minBet = $grouped->min();
        // $maxBet = $grouped->max();

        // $numbersWithMinBet = $grouped->filter(function ($points) use ($minBet) {
        //     return $points == $minBet;
        // })->keys()->toArray();

        // $numbersWithMaxBet = $grouped->filter(function ($points) use ($maxBet) {
        //     return $points == $maxBet;
        // })->keys()->toArray();

        // // Numbers with no bets
        // $allNumbers = range(1, 10);
        // $numbersWithNoBets = array_diff($allNumbers, $grouped->keys()->map('intval')->toArray());

        // // Win Low Mechanic
        // if ($totalBet < 9000) {
        //     $candidates = !empty($numbersWithNoBets) ? $numbersWithNoBets : $numbersWithMinBet;
        //     $winningNumber = $candidates[array_rand($candidates)];
        // } else {
        //     // Win High Mechanic
        //     // Check if max bet is 1000 and 70% of total pot is at least 7000
        //     if ($maxBet == 1000 && ($totalBet * 0.7) < 7000) {
        //         // Exclude numbers with 1000 points from winning
        //         $eligibleNumbers = $grouped->filter(function ($points) {
        //             return $points < 1000;
        //         })->keys()->toArray();
        //         if (empty($eligibleNumbers)) {
        //             // fallback to all numbers
        //             $eligibleNumbers = $grouped->keys()->toArray();
        //         }
        //         $winningNumber = $eligibleNumbers[array_rand($eligibleNumbers)];
        //     } else {
        //         $winningNumber = $numbersWithMaxBet[array_rand($numbersWithMaxBet)];
        //     }
        // }

        // // Simulate 2 spinning wheels for each digit (for fun, not affecting result)
        // $spin1 = rand(0, 9);
        // $spin2 = rand(0, 9);

        // // Calculate winners and payouts
        // $winners = $bets->where('number', $winningNumber)->values();
        // $payouts = [];
        // foreach ($winners as $winner) {
        //     $payout = $winner->points * 7;
        //     $payouts[] = [
        //         'user_id' => $winner->user_id,
        //         'number' => $winner->number,
        //         'bet_points' => $winner->points,
        //         'payout' => $payout
        //     ];
        // }

        // return response()->json([
        //     'result_id' => $request->result_id,
        //     'winning_number' => $winningNumber,
        //     'spin' => [$spin1, $spin2],
        //     'total_bet' => $totalBet,
        //     'payouts' => $payouts
        // ]);
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $bet = Bet::find($id);

        if (!$bet) {
            return response()->json(['message' => 'Bet not found'], 404);
        }

        return response()->json($bet);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $bet = Bet::find($id);

        if (!$bet) {
            return response()->json(['message' => 'Bet not found'], 404);
        }

        return response()->json($bet);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $bet = Bet::find($id);

        if (!$bet) {
            return response()->json(['message' => 'Bet not found'], 404);
        }

        $request->validate([
            'result_id' => 'nullable|string',
            'user_id' => 'required|string|exists:users,id',
            'number' => 'required|string',
            'points' => 'nullable|numeric',
            'Datetime' => 'required|date',
        ]);

        $bet->update($request->all());
        return response()->json($bet);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $bet = Bet::find($id);

        if (!$bet) {
            return response()->json(['message' => 'Bet not found'], 404);
        }

        $bet->delete();
        return response()->json(['message' => 'Bet deleted successfully']);
    }

    /**
     * Get bets by result_id.
     */
    public function getBetsByResultId($result_id)
    {
        $bets = Bet::where('result_id', $result_id)->get();

        if ($bets->isEmpty()) {
            return response()->json(['message' => 'No bets found for this result ID'], 404);
        }

        return response()->json($bets);
    }
    /**
     * Get bets by user_id.
     */
    public function getBetsByUserId(Request $request, $user_id)
    {
        $bets = Bet::where('user_id', $user_id)->get();

        if ($bets->isEmpty()) {
            return response()->json(['message' => 'No bets found for this user ID'], 404);
        }

        return response()->json($bets);
    }

    /**
     * Get bets by date.
     */
    public function getBetsByDate(Request $request, $date)
    {
        $bets = Bet::where('created_at', $date)->get();

        if ($bets->isEmpty()) {
            return response()->json(['message' => 'No bets found for the select date'], 404);
        }

        return response()->json($bets);
    }
}
