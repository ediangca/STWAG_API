<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lottery;
use App\Models\Bet;
use App\Models\Result;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LotteryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // if (! Auth::check()) {
        //     return response()->json([
        //         'status' => 401,
        //         'message' => 'Unauthenticated'
        //     ], 401);
        // }
        $lottery = Lottery::all();

        if ($lottery->isEmpty()) {
            return response()->json(['message' => 'No lottery sessions found'], 404);
        }
        return response()->json($lottery);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the request data\
        if (!$request->has('session') || !$request->has('time')) {
            return response()->json(['message' => 'Session and time are required'], 400);
        }
        // Check if the time is in the correct format
        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $request->time)) {
            return response()->json(['message' => 'Invalid time format'], 400);
        }

        try {
            $request->validate([
                'session' => 'required|string|unique:lottery,lottery_session',
                'time' => 'required|date_format:H:i:s|unique:lottery,time',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }
        // Check if the new session time is at least 3 hours apart from all existing records
        $existingLotteries = Lottery::all();
        $newTime = \Carbon\Carbon::createFromFormat('H:i:s', $request->time);

        foreach ($existingLotteries as $existing) {
            $existingTime = \Carbon\Carbon::createFromFormat('H:i:s', $existing->time);
            $diffInMinutes = abs($newTime->diffInMinutes($existingTime));
            if ($diffInMinutes < 180) { // 180 minutes = 3 hours
                return response()->json(['message' => 'Session time must be at least 3 hours apart from existing sessions'], 409);
            }
        }


        // Create a new lottery session
        $lottery = Lottery::create([
            'lottery_session' => $request->session,
            'time' => $request->time,
        ]);

        return response()->json([
            'message' => 'Lottery created successfully',
            'user' => $lottery,
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
     * Get the result for a specific result_id.
     */
    public function getResult($result_id)
    {
        $result = Result::where('result_id', $result_id)->first();

        if (!$result) {
            return response()->json(['message' => 'Result not found'], 404);
        }

        return response()->json($result);
    }

    /**
     * Create a result for a given result_id based on bet mechanics.
     * @param Request $request expects 'result_id'
     */
    public function createResult(Request $request, $result_id)
    {
        if (!$result_id) {
            return response()->json(['message' => 'Source is required'], 400);
        }

        Log::info('Creating result for result_id: ' . $result_id);

        // Get all bets for this result_id
        // $bets = Bet::where('result_id', $result_id)->get();

        $bets = Bet::where('result_id', $result_id)->get();

        log::info('Bets count: ' . $bets->count());

        if ($bets->isEmpty()) {
            return response()->json(['message' => 'No bets found for this result_idsssssssssss'], 404);
        }
        $firstBet = $bets->first();
        // if ($firstBet && $firstBet->created_at->toDateString() !== now()->toDateString()) {
        //     return response()->json(['message' => 'Bets must be from the current date only.'], 422);
        // }

        // Check if result already exists
        $existing = Result::where('result_id', $result_id)->first();
        if ($existing) {
            return response()->json([
                'result' => $existing,
                'message' => 'Result already exists'
            ]);
        }

        $sessions = Lottery::orderBy('time')->get();

        if ($sessions->isEmpty()) {
            return response()->json(['message' => 'No lottery sessions found'], 404);
        }

        // $now = DB::raw("TIME(NOW())");
        // $now = DB::select("SELECT TIME(NOW()) as now")[0]->now;
        $now = now()->format('H:i:s');
        $isReady = false;
        $currentSession = null;
        // $result_id = null;


        foreach ($sessions as $session) {
            Log::info('Current time: ' . $now);
            Log::info('Session time: ' . $session->time);

            $sessionStart = date('H:i:s', strtotime($session->time) - 30 * 60);
            $sessionEnd = $session->time;
            $currentSession = $session;
            // $result_id = 'RES' . $firstBet->created_at->format('Ymd'). '-000' . $currentSession->lottery_id;
            // $result_id = 'RES' . date('Ymd') . '-000' . $currentSession->lottery_id;

            // Check if current time is within the 30-minute window before session time up to session time
            if ($now >= $sessionStart && $now <= $sessionEnd) {
                $isReady = false;
                break;
            }

            if (!($now >= $sessionStart)) {
                $isReady = true;
                break;
            }

            if ($now > $sessions->last()->time) {
                // If current time is after the last session, set to the first (morning) session of the next day
                $currentSession = $sessions->first();
                $isReady = true;
                break;
            }
        }

        Log::info('Lottery Session: ' . $currentSession);

        // if ($isReady) {
        //     return response()->json([
        //         'result_id' => $result_id,
        //         'session' => $currentSession,
        //         'message' => 'No result yet for Session ' . date('Y-m-d') . '-' . $currentSession->lottery_session 
        //     ], 403);
        // }

        // Calculate total pot
        $totalPot = $bets->sum('points');

        // Group bets by number and sum points
        $betGroups = $bets->groupBy('number')->map(function ($group) {
            return $group->sum('points');
        });

        // Find numbers with max bet (1000 points)
        $maxBetNumbers = $betGroups->filter(function ($points) {
            return $points >= 1000;
        });

        // If any number has max bet, only allow it to win if 70% of pot >= 7000
        $eligibleNumbers = $betGroups;
        if ($maxBetNumbers->count() > 0) {
            if ($totalPot * 0.7 < 7000) {
                // Remove max bet numbers from eligible
                $eligibleNumbers = $betGroups->filter(function ($points) {
                    return $points < 1000;
                });
            }
        }

        // If all numbers are max bet and not eligible, fallback to all numbers
        if ($eligibleNumbers->isEmpty()) {
            $eligibleNumbers = $betGroups;
        }

        // Decide mechanic
        if ($totalPot < 9000) {
            // Win Low Mechanic: pick number with lowest bet or no bet
            $minBet = $eligibleNumbers->min();
            $candidates = $eligibleNumbers->filter(function ($points) use ($minBet) {
                return $points == $minBet;
            })->keys()->toArray();

            // Add numbers with no bets (0-99)
            for ($i = 0; $i <= 99; $i++) {
                if (!$betGroups->has($i)) {
                    $candidates[] = str_pad($i, 2, '0', STR_PAD_LEFT);
                }
            }
        } else {
            // Win High Mechanic: pick number with highest bet
            $maxBet = $eligibleNumbers->max();
            $candidates = $eligibleNumbers->filter(function ($points) use ($maxBet) {
                return $points == $maxBet;
            })->keys()->toArray();
        }

        // Pick winning number randomly from candidates
        if (empty($candidates)) {
            return response()->json(['message' => 'No eligible numbers found for result'], 422);
        }

        // Simulate 2 spinning wheels for each digit
        $winningNumber = $candidates[array_rand($candidates)];
        $winningNumber = str_pad($winningNumber, 2, '0', STR_PAD_LEFT);
        $digits = str_split($winningNumber);
        $spin1 = $digits[0];
        $spin2 = $digits[1];

        // Save result
        $result = Result::create([
            'result_id' => $result_id,
            'lottery_id' => $currentSession->lottery_id,
            'number' => $winningNumber,
            'winning_points' => $totalPot,
        ]);

        // Calculate winnings for users
        $winners = $bets->where('number', $winningNumber);
        foreach ($winners as $winner) {
            $winner->user->increment('points', $winner->points * 7);
            // Optionally, log or notify winner
        }

        return response()->json([
            'result' => $result,
            'lottery_id' => $currentSession->lottery_id,
            'winning_number' => $winningNumber,
            'spin1' => $spin1,
            'spin2' => $spin2,
            'total_pot' => $totalPot,
            'winners' => $winners->pluck('user_id'),
            'mechanic' => $totalPot < 9000 ? 'Win Low' : 'Win High',
            'bets' => $bets->pluck('number')->unique()->values(),
        ]);
    }



}
