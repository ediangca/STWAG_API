<?php

namespace App\Services;


use App\Models\Bet;
use App\Models\Result;
use App\Models\Lottery;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Carbon\Carbon;

class SpinningService
{
    public function generateResult(): string
    {
        $sessions = Lottery::orderBy('time')->get();

        if ($sessions->isEmpty()) {
            return response()->json(['message' => 'No lottery sessions found'], 404);
        }

        // Simulate a spinning result based on a random number
        $now = now()->format('H:i:s');
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

        // Log::info('Lottery Session: ' . $currentSession);



        if ($isReady) {
            // Log::info('Betting is on process : ' .  date('Ymd') . ' - ' . $currentSession->lottery_session);
            return 'Betting is on process for session ' . date('Y-m-d') . ' - ' . $currentSession->lottery_session. '(' . $currentSession->time . '). ' .
                'Please wait for the result.';  
        }


        // Check if result already exists
        $existing = Result::where('result_id', $result_id)->first();
        if ($existing) {
            // Log::info('Result already exists for result ID: ' . $result_id . ' for session ' . date('Ymd') . ' - ' . $currentSession->lottery_session);
            return 'Result already exists for this session ' .  date('Y-m-d') . ' - ' . $currentSession->lottery_session . '( ' . $currentSession->time . '). ' .
                'Please wait for the result.';
            // return response()->json(['message' => 'Result already exists for this session'], 409);
        }

        $bets = Bet::where('result_id', $result_id)->get();

        if ($bets->isEmpty()) {
            Log::info('No bets found for result ID: ' . $result_id);
            // return response()->json(['message' => 'No bets found for this result'], 404);
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
        // $result = Result::create([
        //     'result_id' => $result_id,
        //     'lottery_id' => $currentSession->lottery_id,
        //     'number' => $winningNumber,
        //     'winning_points' => $totalPot,
        // ]);

        // Calculate winnings for users
        $winners = $bets->where('number', $winningNumber);
        foreach ($winners as $winner) {
            $winner->user->increment('points', $winner->points * 7);
            // Optionally, log or notify winner
        }


        // return response()->json([
        //     'result' => $result,
        //     'lottery_id' => $currentSession->lottery_id,
        //     'winning_number' => $winningNumber,
        //     'spin1' => $spin1,
        //     'spin2' => $spin2,
        //     'total_pot' => $totalPot,
        //     'winners' => $winners->pluck('user_id'),
        //     'mechanic' => $totalPot < 9000 ? 'Win Low' : 'Win High',
        //     'bets' => $bets->pluck('number')->unique()->values(),
        // ]);

        // Log::info('Result Data:', [
        //     'result' => $result,
        //     'lottery_id' => $currentSession->lottery_id,
        //     'winning_number' => $winningNumber,
        //     'spin1' => $spin1,
        //     'spin2' => $spin2,
        //     'total_pot' => $totalPot,
        //     'winners' => $winners->pluck('user_id'),
        //     'mechanic' => $totalPot < 9000 ? 'Win Low' : 'Win High',
        //     'bets' => $bets->pluck('number')->unique()->values(),
        // ]);

        return 'Generate Result for ' . $result_id . ' with winning number ' . $winningNumber .
            ' (Spin1: ' . $spin1 . ', Spin2: ' . $spin2 . ')';

        // Simulate a spinning result based on time
        // $timeNow = Carbon::now();

        // Sample time-based spin logic
        // $seed = $timeNow->timestamp % 1000;
        // $options = ['Gold', 'Silver', 'Bronze', 'Try Again'];
        // $index = $seed % count($options);


        // return $options[$index];
    }
}
