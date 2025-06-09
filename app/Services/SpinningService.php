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
            // return response()->json(['message' => 'No lottery sessions found'], 404);
            return 'No lottery sessions found. Please check the lottery schedule.';
        }

        // Simulate a spinning result based on a random number
        $now = now()->format('H:i:s');
        // $now = date('H:i:s');
        // $now = $request->time;

        $isReady = false;
        $currentSession = null;
        $result_id = null;
        $foundSession = false;


        foreach ($sessions as $session) {
            // Log::info('Current time: ' . $now);
            // Log::info('Session time: ' . $session->time);

            $sessionStart = date('H:i:s', strtotime($session->time) - 30 * 60);
            $sessionEnd = $session->time;
            $currentSession = $session;

            // Check if current time is within the 30-minute window before session time up to session time
            if ($now > $sessionStart && now()->format('H:i:00') <= $sessionEnd) {
                // If current time is within the session window, set to the current session
                $currentSession = $session;
                $isReady = true;
                $foundSession = true;
                break;
            }

            if (!($now >= $sessionStart)) {
                // If current time is before the session start, set to the current session
                $currentSession = $session;
                $isReady = false;
                $foundSession = true;
                break;
            }
        }
        $sessionTime = date('H:i:00', strtotime($currentSession->time));
        $currentTime = now()->format('H:i:00');

        Log::info('Current Time: ' . $currentTime);
        Log::info('Session Time: ' . $sessionTime);


        // Validation: If current time is after the last session, set to the first session of the next day
        if (!$foundSession && $now > $sessions->last()->time) {
            $currentSession = $sessions->first();
            $isReady = false;
            $result_id = 'RES' . date('Ymd', strtotime('+1 day')) . '-000' . $currentSession->lottery_id;
            $sessionTime = date('H:i:00', strtotime($currentSession->time));
            
            // $sessionTimeLastFiveMinutes = date('H:i:s', strtotime($session->time) - 5 * 60);
            $currentTime = now()->format('H:i:00');
            Log::info('Is Ready: ' . ($isReady ? 'true' : 'false'));
            Log::info('Result ID: ' . $result_id);

            return 'Betting is open for next day session ' . date('Y-m-d', strtotime('+1 day')) . ' - ' . $currentSession->lottery_session . '(' . $currentSession->time . '). Make a bet now!';
        }

        $result_id = 'RES' . date('Ymd') . '-000' . $currentSession->lottery_id;

        // Log::info('Is Ready: ' . ($isReady ? 'true' : 'false'));
        Log::info('Result ID: ' . $result_id);

        if ($isReady && $currentTime !==  $sessionTime) {
            // Log::info('Betting is close for session ' . date('Y-m-d') . ' - ' . $currentSession->lottery_session . '(' . $currentSession->time . '). \n ' .
            //     'Please wait for the result.');
            return 'Betting is close for session ' . date('Y-m-d') . ' - ' . $currentSession->lottery_session . '(' . $currentSession->time . '). ' .
                'Please wait for the result.';
        } else if ((!$isReady && $currentTime < $sessionTime) || $currentTime > $sessions->last()->time) { // If the session isn't ready and it's not time yet, or all sessions are over, skip or handle accordingly
            // Log::info('Betting is on process for session ' . date('Y-m-d') . ' - ' . $currentSession->lottery_session . '(' . $currentSession->time . '). \n ' .
            //     'Please wait for the result.');
            return 'Betting is open for session ' . date('Y-m-d') . ' - ' . $currentSession->lottery_session . '(' . $currentSession->time . '). ' .
                'Make a bet now!';
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



        /**
         * Bet Distribution:
         * 70% of the total pot (bet amount) is awarded to the winning user(s).
         * 10% is allocated to admin/operational expenses.
         * 10% goes into the general fund (mother account).
         * 10% is distributed as incentives to upline members.
         */

        $winningShare = $totalPot * 0.7;
        $adminShare = $totalPot * 0.1;
        $motherShare = $totalPot * 0.1;
        $incentivesShare = $totalPot * 0.1;

        // Update or create the result with calculated shares
        $result = Result::updateOrCreate(
            ['result_id' => $result_id],
            [
                'lottery_id' => $currentSession->lottery_id,
                'number' => $winningNumber,
                'winning_points' => $winningShare,
                'incentives_share' => $incentivesShare,
                'mother_share' => $motherShare,
                'admin_share' => $adminShare,
                'other_share' => 0
            ]
        );

        // Calculate winnings for users
        $winners = $bets->where('number', $winningNumber)->where('result_id', $result_id); //Users who bet on the winning number
        if ($winners->isEmpty()) {
            Log::info('No winners found for winning number: ' . $winningNumber);
            // return 'No winners found for winning number: ' . $winningNumber;
        }
        $totalWinningPoints = $winners->sum('points');
        foreach ($winners as $winner) {
            // Distribute 70% of the pot proportionally to winners
            $userShare = $totalWinningPoints > 0 ? ($winner->points / $totalWinningPoints) * $winningShare : 0;

            // Add winning points to user's wallet
            if ($winner->user && method_exists($winner->user, 'wallet')) {
                // If user has a wallet relation, create a wallet transaction of type 'WIN'
                // $winner->user->wallet()->updateOrCreate(
                //     ['type' => 'WIN', 'source' => 'WIN', 'description' => 'Winning points for result ' . $result_id],
                //     ['points' => $userShare]
                // );
                $winner->user->wallet()->create([
                    'wallet_id' => uniqid('WLT') . '-' . substr($winner->user->user_id, 10) . date('YmdHis'),
                    'user_id' => $winner->user->user_id,
                    'points' => $userShare,
                    'ref_id' => $result_id,
                    'withdrawableFlag' => true,
                    'confirmedFlag' => true,
                    'source' => 'WIN',
                ]);
            }
            // elseif ($winner->user) {
            //     // Fallback if no wallet relation, create a wallet transaction of type 'WIN'
            //     $winner->user->wallet()->create([
            //         'points' => $userShare,
            //         'type' => 'WIN',
            //         'source' => 'WIN',
            //         'description' => 'Winning points for result ' . $result_id,
            //     ]);
            // }

            // Distribute 10% incentives to upline if applicable
            // if ($winner->user && method_exists($winner->user, 'upline') && $winner->user->upline) {
            //     $uplineShare = $incentivesShare * ($winner->points / $totalWinningPoints);

            //     // Add incentive points to upline's wallet
            //     if (method_exists($winner->user->upline, 'wallet') && $winner->user->upline->wallet) {
            //         $winner->user->upline->wallet->increment('points', $uplineShare);
            //     } else {
            //         $winner->user->upline->increment('points', $uplineShare);
            //     }
            // }
        }


        // $winners = $bets->where('number', $winningNumber)->where('result_id', $result_id);
        // $totalWinningPoints = $winners->sum('points');
        // foreach ($winners as $winner) {
        //     // Distribute 70% of the pot proportionally to winners
        //     $userShare = $totalWinningPoints > 0 ? ($winner->points / $totalWinningPoints) * $winningShare : 0;
        //     $winner->user->increment('points', $userShare);

        //     // Distribute 10% incentives to upline if applicable
        //     if (method_exists($winner->user, 'upline') && $winner->user->upline) {
        //         $uplineShare = $incentivesShare * ($winner->points / $totalWinningPoints);
        //         $winner->user->upline->increment('points', $uplineShare);
        //     }
        // }

        // Calculate winnings for users
        // $winners = $bets->where('number', $winningNumber)->where('result_id', $result_id);
        // foreach ($winners as $winner) {
        //     $winner->user->increment('points', $winner->points * 7);
        //     // Optionally, log or notify winner
        // }


        // Log::info('Final Result Data:', [
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

        return 'Final Result for Result ID ' . $result_id . '-' .  date('Y-m-d') . ' - ' . $currentSession->lottery_session . '( ' . $currentSession->time . '). ' .
            ' with winning number ' . $winningNumber .
            ' (Spin1: ' . $spin1 . ', Spin2: ' . $spin2 . ')';

    }
}
