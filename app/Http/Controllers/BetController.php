<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Bet;
use App\Models\Lottery;
use App\Models\Result;
use App\Models\Wallet;
use Carbon\Carbon;
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

        return response()->json([
            'current_time' => $now,
            'session' => $currentSession,
            'result_id' => $result_id
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
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }
        
        $currentTimestamp = Carbon::now()->format('Y-m-d H:i:s');
        $user_id = $request->user_id;

        $allowedPoints = [1, 5, 15, 10, 20, 30, 50, 70, 100, 150, 200, 300, 500, 700, 1000];
        foreach ($request->bets as $bet) {
            if (!in_array($bet['points'], $allowedPoints)) {
                return response()->json([
                    'message' => "Invalid bet denomination: {$bet['points']}. Allowed denominations are: " . implode(', ', $allowedPoints)
                ], 422);
            }
        }

        $user = User::where('user_id', $user_id)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $sessions = Lottery::orderBy('time')->get();

        if ($sessions->isEmpty()) {
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
            $result_id = 'RES' . date('Ymd') . '-000' . $currentSession->lottery_id;

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
        $result_id = $request->has('result_id') ? $request->result_id : $result_id;

        $result = Result::where('result_id', $result_id)->first();
        if ($result) {
            return response()->json(['message' => 'Bet cannot placed. Draw has been already done!'], 403);
        }

        $existingBets = DB::table('bets')
            ->where('user_id', $request->user_id)
            ->where('result_id', $request->result_id)
            ->where('created_at', $currentTimestamp)
            ->get(['number', 'points']);

        // Convert both to comparable arrays
        $submittedSet = collect($request->bets)->map(fn($b) => "{$b['number']}:{$b['points']}")->sort()->values();
        $existingSet  = $existingBets->map(fn($b) => "{$b->number}:{$b->points}")->sort()->values();

        if ($submittedSet->equals($existingSet)) {
            throw ValidationException::withMessages([
                'message' => ['This exact set of bets already exists for the given result and timestamp.']
            ]);
        }


        // Validate bet limit for each number
        $numbers = collect($request->bets)->pluck('number')->unique();
        foreach ($numbers as $number) {
            // Sum of existing points for this number and result_id
            $currentPoints = Bet::where('number', $number)
                ->where('result_id', $result_id)
                ->sum('points');
            // Sum of incoming points for this number (from request)
            $incomingPoints = collect($request->bets)
                ->where('number', $number)
                ->sum('points');

            // Check if total exceeds 1000
            if (($currentPoints + $incomingPoints) > 1000) {
                return response()->json([
                    'number' => $number,
                    'status' => 0,
                    'message' => "Bet point limit exceeded for number {$number}. Only 1000 points allowed per number."
                ], 403);
            }
        }

        // Calculate total points
        $totalPoints = collect($request->bets)->sum('points');
        // Check if user has enough points in wallet
        $userWallet = Wallet::where('user_id', $user_id)->sum('points');
        if ($userWallet < $totalPoints) {
            return response()->json([
                'message' => 'Insufficient points in wallet. You have ' . $userWallet . ' points, but need ' . $totalPoints . ' points to place these bets.'
            ], 403);
        }
        



        $noOfBet = 1;
        // Place bets
        foreach ($request->bets as $bet) {

            $existingBet = Bet::where('result_id', $result_id)
                ->where('user_id', $user_id)
                ->where('number', $bet['number'])
                ->first();

            if ($existingBet) {
                // Update the existing bet by adding points
                $existingBet->points += $bet['points'];
                $existingBet->save();
            } else {
                Bet::create([
                    'result_id' => $result_id,
                    'user_id' => $user_id,
                    'number' => $bet['number'],
                    'points' => $bet['points'],
                    // 'Datetime' => now(),
                ]);
            }

            Wallet::create([
                'wallet_id' => uniqid('WLT') . '-' . substr($user_id, 10) . date('YmdHis'),
                'user_id' => $user_id,
                'points' => -abs($bet['points']),
                'ref_id' => uniqid('BET') . '-' . substr($user_id, 10) . date('YmdHis') . '-' . $noOfBet,
                'withdrawableFlag' => false,
                'confirmFlag' => true,
                'source' => 'BET',
            ]);

            $noOfBet++;
        }

        $bets = Bet::where('user_id', $user_id)->where('result_id', $result_id)->get();

        if ($bets->isEmpty()) {
            return response()->json(['message' => 'No bets found'], 404);
        }

        // Count the number of unique result_ids the user has placed bets on
        $betGroupCount = Bet::where('user_id', $user_id)
            ->select('result_id')
            ->distinct()
            ->count();

        $cashback = 0;
        $consecutiveBets = collect();
        if ($betGroupCount > 0 && $betGroupCount % 5 == 0) {
            // Get the last 5 result_ids for this user
            $lastFiveResultIds = Bet::where('user_id', $user_id)
                ->orderBy('created_at', 'desc')
                ->groupBy('result_id')
                ->limit(5)
                ->pluck('result_id');

            // Sum all points for these 5 result_ids
            $totalPoints = Bet::where('user_id', $user_id)
                ->whereIn('result_id', $lastFiveResultIds)
                ->sum('points');

            $cashback = max(5, round($totalPoints * 0.01));

            Wallet::create([
                'wallet_id' => uniqid('WLT') . '-' . substr($user_id, 10) . date('YmdHis'),
                'user_id' => $user_id,
                'points' => $cashback,
                'ref_id' => uniqid('CBK') . '-' . substr($user_id, 10) . date('YmdHis'),
                'withdrawableFlag' => false,
                'confirmFlag' => true,
                'source' => 'CBK', // Bonus type
            ]);

            // For message
            $consecutiveBets = Bet::where('user_id', $user_id)
                ->whereIn('result_id', $lastFiveResultIds)
                ->get();
        }


        $message = 'Bet has been successfully placed.';
        if ($consecutiveBets->count() == 5) {
            $message .= ' You have placed 5 consecutive bets. Cashback of ' . $cashback . ' points has been added to your wallet.';
        }

        return response()->json([
            'status' => 1,
            'message' => $message,
            'bets' => $bets
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
     * Display all bets for a given user_id and optional result_id.
     * If result_id is not provided, it returns the latest bet for the user.
     */
    public function showBetByUserIDandResultID(Request $request, $result_id = null)
    {
        if (!$request->has('user_id')) {
            return response()->json(['message' => 'User ID is required'], 400);
        }
        Log::info('Request to show bets by user ID and result ID', [
            'user_id' => $request->user_id,
            'result_id' => $result_id
        ]);

        $date = $request->has('date') ? $request->date : now()->format('Y-d-m');
        $time = $request->has('time') ? $request->time : now()->format('H:i:s');

        if ($result_id !== null && preg_match('/-000(\d+)$/', $result_id, $matches)) {
            $lottery_id = $matches[1];
            $session = Lottery::where('lottery_id', $lottery_id)->first();
            if ($session) {
                $time = $session->time;
                Log::info('Session found for result_id', ['result_id' => $result_id, 'session' => $session]);
            } else {
                Log::warning('No session found for result_id', ['result_id' => $result_id]);
            }
        }

        $status = 0;
        $user_id = $request->user_id;

        if ($result_id !== null) {
            $bets = Bet::where('user_id', $user_id)
                ->where('result_id', $result_id)
                ->get();
        } else {
            $bets = Bet::where('user_id', $user_id)
                ->orderBy('created_at', 'desc')
                ->limit(1)
                ->get();
        }

        $result = Result::where('result_id', $result_id)->first();

        if ($bets->count() > 0) {
            if ($result) {
                $winningNumber = $result->number;
                $userBet = Bet::where('user_id', $user_id)
                    ->where('result_id', $result_id)
                    ->where('number', $winningNumber)
                    ->first();

                $status = $userBet ? 1 : 2;
            } else {
                $status = -1;
            }

            return response()->json([
                'date' => $date,
                'session' => $session,
                'status' => $status,
                'message' => $status == 1 ? 'Congratulations! You have won this session.' : ($status == 2 ? 'Sorry, you did not win this session.' : 'No result yet, Session is on going.'),
                'result' => $result,
                'bets' => $bets
            ], 201);
        } else {

            return response()->json([
                'date' => $date,
                'session' => $session,
                'status' => $status,
                'message' => 'No Bet found for ' . ($result_id !== null ? $result_id : $bets->result_id) . '. ' . ($status < 0 ?? 'And result yet, Session is on going.'),
                'result' => $result,
                'bets' => $bets
            ], 404);
        }
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

            if (!($time >= $sessionStart)) {
                Log::info('Bet is ready for session: ' . $currentSession->lottery_session);
                $isReady = true;
                break;
            }

            // If current time is after the last session, set to the first (morning) session of the next day
            if ($time > $sessions->last()->time) {
                Log::info('Current time is after the last session, setting to first session of the next day.');
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

        Log::info('Generated result_id: ' . 'RES' . date('Ymd') . '-000' . $currentSession->lottery_id);
        $result_id = 'RES' . date('Ymd') . '-000' . $currentSession->lottery_id;

        return response()->json([
            'current_date' => $date,
            'current_time' => $time,
            'result_id' => $result_id,
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
