<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Bet;
use App\Models\Lottery;
use App\Models\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use function Laravel\Prompts\spin;

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

        // if (!$request->has('result_id') || !$request->has('user_id') || !$request->has('number')) {
        //     return response()->json(['message' => 'result_id, user and number are required'], 400);
        // }

        $user = User::where('user_id', $request->user_id)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }


        // Get sessions from Lottery table, ordered by start time
        $sessions = \App\Models\Lottery::orderBy('time')->get();

        $now = now()->format('H:i:s');

        $isReady = false;
        $currentSession = null;
        $result_id = null;

        foreach ($sessions as $session) {
            Log::info('Current time: ' . $now);
            Log::info('Session time: ' . $session->time);

            $sessionStart = date('H:i:s', strtotime($session->time) - 30 * 60);
            $sessionEnd = $session->time;
            $currentSession = $session;

            // Check if current time is within the 30-minute window before session time up to session time
            if ($now >= $sessionStart && $now <= $sessionEnd) {
                return response()->json([
                    'message' => 'Draw has been started and processing.'
                ]);
            }

            if (!($now >= $sessionStart)) {
                $result_id = 'RES' . date('Ymd') . $session->id;
                break;
            }
        }

        // try{
        // $request->validate([
        //     'result_id' => 'nullable|string', //RES<YEAR><MM><DD><SESSION>
        //     'user_id' => 'required|string',
        //     'number' => 'required|string',
        //     'points' => 'nullable|numeric',
        //     'Datetime' => 'required|date',
        // ]);

        // } catch (ValidationException $e) {
        //     return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        // }

        // $bet = Bet::create($request->all());
        // return response()->json($bet, 201);

        return response()->json([
            'current_time' => $now,
            'session' => $currentSession,
            'result_id' => $result_id
        ]);
    }


    /**
     * Create a result for a given result_id based on bet mechanics.
     * @param Request $request expects 'result_id'
     */
    public function createResult(Request $request)
    {
        $result_id = $request->input('result_id');
        if (!$result_id) {
            return response()->json(['message' => 'result_id is required'], 400);
        }

        // Check if result already exists
        $existing = Result::where('result_id', $result_id)->first();
        if ($existing) {
            return response()->json(['message' => 'Result already exists', 'result' => $existing], 409);
        }

        // Get all bets for this result_id
        $bets = Bet::where('result_id', $result_id)->get();
        if ($bets->isEmpty()) {
            return response()->json(['message' => 'No bets found for this result_id'], 404);
        }

        $sessions = Lottery::orderBy('time')->get();

        if($sessions->isEmpty()) {
            return response()->json(['message' => 'No lottery sessions found'], 404);
        }
      
        // $now = DB::raw("TIME(NOW())");
        // $now = DB::select("SELECT TIME(NOW()) as now")[0]->now;
        $now = $request->has('time') ? $request->time : now()->format('H:i:s');
        $isReady = false;
        $currentSession = null;
        $result_id = null;


        foreach ($sessions as $session) {
            Log::info('Current time: ' . $now);
            Log::info('Session time: ' . $session->time);

            $sessionStart = date('H:i:s', strtotime($session->time) - 30 * 60);
            $sessionEnd = $session->time;
            $currentSession = $session;
            $result_id = 'RES' . date('Ymd') .'-000'. $currentSession->lottery_id;

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

        if ($isReady) {
            return response()->json([
                'result_id' => $result_id,
                'session' => $currentSession,
                'message' => 'Cannot generate result for Session ' . date('Y-m-d').'-'.$currentSession->lottery_session . ' Draw has been started and processing.'
            ], 403);
        }

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
            'mechanic' => $totalPot < 9000 ? 'Win Low' : 'Win High'
        ]);
    }

    /**
     * Place multiple bets for a user.
     * Expects: user_id, bets: [ {number, points}, ... ]
     */
    public function storeMultipleBets(Request $request)
    {
        // Validate the request data\
        if (!$request->has('user_id') || !$request->has('bets')) {
            return response()->json(['message' => 'User ID and Bets are required'], 400);
        }

        try {
            $request->validate([
                'user_id' => 'required|string|exists:users,user_id',
                'bets' => 'required|array|min:1',
                'bets.*.number' => 'required|numeric|min:0|max:99',
                'bets.*.points' => 'required|numeric|min:1|max:1000',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation faileddasdasdasd', 'errors' => $e->errors()], 422);
        }

        $user = User::where('user_id', $request->user_id)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $sessions = Lottery::orderBy('time')->get();

        if($sessions->isEmpty()) {
            return response()->json(['message' => 'No lottery sessions found'], 404);
        }
      
        // $now = DB::raw("TIME(NOW())");
        // $now = DB::select("SELECT TIME(NOW()) as now")[0]->now;
        $now = $request->has('time') ? $request->time : now()->format('H:i:s');
        // $now = date('H:i:s');
        // $now = $request->time;

        $isReady = false;
        $currentSession = null;
        $result_id = null;


        foreach ($sessions as $session) {
            Log::info('Current time: ' . $now);
            Log::info('Session time: ' . $session->time);

            $sessionStart = date('H:i:s', strtotime($session->time) - 30 * 60);
            $sessionEnd = $session->time;
            $currentSession = $session;
            $result_id = 'RES' . date('Ymd') .'-000'. $currentSession->lottery_id;

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

        if (!$isReady) {
            return response()->json([
                'session' => $currentSession,
                'message' => 'Camnnot place bet! \n Session ' . $currentSession->lottery_session . ' Draw has been started and processing.'
            ], 403);
        }

        log::info('Result ID: ' . 'From Request ' . $request->has('result_id') ? $request->result_id : 'Generated from Session: ' . $result_id);

        // date('His', strtotime($currentSession->time))
        $result_id = $request->has('result_id') ? $request->result_id : $result_id ;

        $result = Result::where('result_id', $result_id)->first();
        if ($result) {
            return response()->json(['message' => 'Bet cannot placed. Draw has been already done!'], 403);
        }


        // Validate bet limit for each number
        $numbers = collect($request->bets)->pluck('number')->unique();
        foreach ($numbers as $number) {
            $currentCount = Bet::where('number', $number)->count();
            $incomingCount = collect($request->bets)->where('number', $number)->count();
            if (($currentCount + $incomingCount) > 1000) {
                return response()->json([
                    'message' => "Bet limit exceeded for number {$number}. Only " . (1000 - $currentCount) . " bets allowed."
                ], 403);
            }
        }

        // Place bets
        foreach ($request->bets as $bet) {
            if (Bet::where('result_id', $result_id)
                ->where('user_id', $request->user_id)
                ->where('number', $bet['number'])
                ->exists()
            ) {
                return response()->json([
                    'session' => $currentSession->lottery_session,
                    'time' => date('h:i A', strtotime($currentSession->time)),
                    'message' => "You have already placed a bet for number {$bet['number']}."
                ], 409);
            }
            Bet::create([
                'result_id' => $result_id,
                'user_id' => $request->user_id,
                'number' => $bet['number'],
                'points' => $bet['points'],
                // 'Datetime' => now(),
            ]);
        }

        $bets = Bet::where('user_id', $request->user_id)->where('result_id', $result_id)->get();

        if ($bets->isEmpty()) {
            return response()->json(['message' => 'No bets found'], 404);
        }

        return response()->json([
            'bets' => $bets,
            'message' => 'Bet has been successfully placed.'
        ], 201);
    }

    /**
     * Display all bets for a given result_id.
     */
    public function showBetsByResultId(Request $request, $result_id)
    {
        $bets = Bet::where('result_id', $result_id)->get();

        if ($bets->isEmpty()) {
            return response()->json(['message' => 'No bets found for this result ID'], 404);
        }

        return response()->json($bets);
    }

    /**
     * Spin and determine the winning number based on the algorithm.
     */
    // public function betsignal(Request $request)

    /**
     * Check if the total bet points for a number by result_id exceeds 1000.
     *
     * @param string $result_id
     * @param int|string $number
     * @return bool
     */
    public function isBetLimitExceeded($result_id, $number)
    {
        $totalPoints = Bet::where('result_id', $result_id)
            ->where('number', $number)
            ->sum('points');

        return response()->json(['is_exceeded' => $totalPoints >= 1000]);
    }



    public function betSignal(Request $request)
    {
        // Validate the request data
        // if (!$request->has('time')) {
        //     return response()->json(['message' => 'time is required'], 400);
        // }

        // Check if the time is in the correct format
        if ($request->has('time') && !preg_match('/^\d{2}:\d{2}:\d{2}$/', $request->time)) {
            return response()->json(['message' => 'Invalid time format'], 400);
        }

        // Get sessions from Lottery table, ordered by start time
        $sessions = \App\Models\Lottery::orderBy('time')->get();

        // $now = DB::raw("TIME(NOW())");
        // $now = DB::select("SELECT TIME(NOW()) as now")[0]->now;
        $date = $request->has('date') ? $request->date : now()->format('Y-d-m');
        $time = $request->has('time') ? $request->time : now()->format('H:i:s');
        // $now = date('H:i:s');
        // $now = $request->time;

        $currentSession = null;
        $isReady = false;

        foreach ($sessions as $session) {
            Log::info('Current time: ' . $time);
            Log::info('Session time: ' . $session->time);

            $sessionStart = date('H:i:s', strtotime($session->time) - 30 * 60);
            $sessionEnd = $session->time;
            $currentSession = $session;

            // Check if current time is within the 30-minute window before session time up to session time
            if ($time >= $sessionStart && $time <= $sessionEnd) {
                $isReady = false;
                break;
            }

            if (!($time >= $sessionStart )) {
                $isReady = true;
                break;
            }

            if ($time > $sessions->last()->time) {
                // If current time is after the last session, set to the first (morning) session of the next day
                $currentSession = $sessions->first();
                $isReady = true;
                break;
            }
        }

        if (!$currentSession) {
            // If not in any session, pick the next closest session
            foreach ($sessions as $session) {
                if ($time < $session->time) {
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
            'current_date' => $date,
            'current_time' => $time,
            'isReady' => $isReady,
            'message' => ($isReady ? 'Bet is Ready for ' . $currentSession->lottery_session . ' session ' : 'Draw has been started and processing') . '!',
            'session' => $currentSession
        ]);
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
