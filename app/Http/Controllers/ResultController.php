<?php

namespace App\Http\Controllers;

use App\Models\Bet;
use App\Models\Lottery;
use Illuminate\Http\Request;
use App\Models\Result;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ResultController extends Controller
{

    /**
     * Display a listing of results with their associated bet status.
     *
     * Retrieves all results ordered by descending result ID. For each result, it checks for associated bets:
     * - If no bets are found for a result, the status is "No bets found".
     * - If bets are found and at least one bet matches the winning number, the status indicates a winning bet was found and returns number of winning.
     * - If bets are found but none match the winning number, the status indicates no winning bet was found.
     *
     */
    public function index()
    {
        // if (! Auth::check()) {
        //     return response()->json([
        //         'status' => 401,
        //         'message' => 'Unauthenticated'
        //     ], 401);
        // }
        $results = Result::orderBy('result_id', 'desc')->get();

        if ($results->isEmpty()) {
            return response()->json(['message' => 'No results found'], 404);
        }

        $resultIds = $results->pluck('result_id');
        $betsGrouped = Bet::whereIn('result_id', $resultIds)->get()->groupBy('result_id');

        $resultWithStatus = $results->map(function ($result) use ($betsGrouped) {
            $bets = $betsGrouped->get($result->result_id, collect());
            $status = 'No bets found';
            $winners = [];

            if ($bets->isNotEmpty()) {
                $hasWinningBet = $bets->contains(function ($bet) use ($result) {
                    return $bet->number == $result->number;
                });

                if ($hasWinningBet) {
                    // Count the number of winning bets for the winning number
                    $winningBetCount = $bets->where('number', $result->number)->count();
                    $status = 'Bet found for winning number: ' . $result->number . ' (' . $winningBetCount . ' winning bet(s))';
                    // $status = 'Bet found for winning number: ' . $result->number;
                    $winners = $bets->where('number', $result->number)
                        ->map(function ($bet) {
                            $user = $bet->user;
                            return [
                                'user_id' => $user->user_id ?? null,
                                'fullname' => isset($user) ? ($user->firstname . ' ' . $user->lastname) : null,
                                'bet_id' => $bet->bet_id,
                                'bet_number' => $bet->number,
                            ];
                        })->values();
                } else {
                    $status = 'Bet found, but no winning number matched: ' . $result->number;
                }
            }

            return [
                'result' => $result,
                'status' => $status,
                'winners' => $winners
            ];
        });

        return response()->json($resultWithStatus->values());
    }

    public function indexPagination(Request $request)
    {

        Log::info('Function: ' . 'ResultIndexPagination');
        // Get 'from' and 'to' query parameters with default values
        $from = $request->query('from', 0);
        $to = $request->query('to', 10);

        // Validate parameters to ensure they are integers and non-negative
        if (!is_numeric($from) || !is_numeric($to) || $from < 0 || $to <= $from) {
            return response()->json(['message' => 'Invalid "from" or "to" parameters'], 400);
        }

        // Calculate the number of records to take
        $take = $to - $from;

        // Get the sliced results
        $results = Result::orderBy('result_id', 'desc')
            ->skip($from)
            ->take($take)
            ->get();

        if ($results->isEmpty()) {
            return response()->json(['message' => 'No results found'], 404);
        }
        // Modify Result data to include Session details'
        // $results = $results->map(function ($result) {
        //     $session = Lottery::where('lottery_id', $result->lottery_id)->get();
        //     if (!$session->isEmpty()) {
        //         $result->session_details = $session;
        //     }
        //     return $result;
        // });

        $resultIds = $results->pluck('result_id');
        $betsGrouped = Bet::whereIn('result_id', $resultIds)
            ->get()
            ->groupBy('result_id');

        $resultWithStatus = $results->map(function ($result) use ($betsGrouped) {
            $bets = $betsGrouped->get($result->result_id, collect());
            $status = 'No bets found';
            $winners = [];

            $session = Lottery::find($result->lottery_id);
            
            if ($bets->isNotEmpty()) {
                $hasWinningBet = $bets->contains(function ($bet) use ($result) {
                    return $bet->number == $result->number;
                });

                if ($hasWinningBet) {
                    $winningBetCount = $bets->where('number', $result->number)->count();
                    $status = 'Bet found for winning number: ' . $result->number . ' (' . $winningBetCount . ' winning bet(s))';

                    $winners = $bets->where('number', $result->number)
                        ->map(function ($bet) {
                            $user = $bet->user;
                            return [
                                'user_id' => $user->user_id ?? null,
                                'fullname' => isset($user) ? ($user->firstname . ' ' . $user->lastname) : null,
                                'bet_id' => $bet->bet_id,
                                'bet_number' => $bet->number,
                            ];
                        })->values();
                } else {
                    $status = 'Bet found, but no winning number matched: ' . $result->number;
                }
            }


            return [
                'session' => $session,
                'result' => $result,
                'status' => $status,
                'winners' => $winners
            ];
        });

        return response()->json($resultWithStatus->values());
    }


    /**
     * Delete a result by its result_id.
     * Route: DELETE /lottery/results/{result_id}
     */
    public function deleteById(Request $request, $result_id)
    {
        $deleted = Result::where('result_id', $result_id)->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'No result found for ' . $result_id], 404);
        }

        return response()->json(['message' => 'Deleted result with id ' . $result_id]);
    }

    /**
     * Delete all results for a given date.
     * Route: DELETE /lottery/results/by-date/{date}
     */
    public function deleteByDate(Request $request, $date)
    {
        $deleted = Result::whereDate('created_at', $date)->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'No results found for ' . $date], 404);
        }

        return response()->json(['message' => 'Deleted ' . $deleted . ' result(s) for ' . $date]);
    }

    /**
     * API endpoint for listing lottery results.
     * Optional: pass result_id to get a specific result, or get the latest.
     * Route: GET /lottery/results/{result_id?}
     */
    public function showRecentOrByRID(Request $request, $result_id = null)
    {
        if ($result_id !== null) {
            $result = Result::where('result_id', $result_id)->first();
            // if (!$result) {
            //     return response()->json(['message' => 'Result not found for ' . $result_id], 404);
            // }
            // return response()->json($result);
        } else {
            $result = Result::orderBy('created_at', 'desc')->first();
            // if (!$result) {
            //     return response()->json(['message' => 'No results found'], 404);
            // }
            // // return response()->json($result);
        }

        if (!$result) {
            return response()->json(['message' => 'Result not found' . ($result_id !== null ? ' for ' . $result_id : '') . '.'], 404);
        } else {

            $winningBets = Bet::where('result_id', $result->result_id)
                ->where('number', $result->number)
                ->with('user') // use the correct relationship name
                ->get();
            // Fetch wallet details where ref_id matches result_id and user_id is among the winners
            $winnerUserIds = $winningBets->pluck('user_id')->unique()->toArray();
            $wallets = Wallet::where('ref_id', $result->result_id)
                ->whereIn('user_id', $winnerUserIds)
                ->get();

            // Attach wallet details to each winner
            $winners = $winningBets->map(function ($bet) use ($wallets) {
                //FAQ: There's an intance that user will bet same number but may differ about time and the points bet, point of this, can we change this part to sum
                $wallet = $wallets->where('user_id', $bet->user->user_id)->first();
                return [
                    'user_id' => $bet->user->user_id ?? null,
                    'avatat' => $bet->user->avatar ?? 0,
                    'fullname' => ($bet->user->firstname . ' ' . $bet->user->lastname) ?? null,
                    'bet_id' => $bet->bet_id,
                    'bet_number' => $bet->number,
                    'wallet' => $wallet ? [
                        'wallet_id' => $wallet->wallet_id,
                        'points' => $wallet->points,
                        'ref_id' => $wallet->ref_id,
                    ] : null,
                ];
            });

            $session = Lottery::find($result->lottery_id);
            if (!$session) {
                return response()->json(['message' => 'Session not found for result ' . $result_id], 404);
            }

            return response()->json([
                'session' => $session,
                'result' => $result,
                'winners' => $winners->isEmpty() ? 'No winners found for this result.' : $winners
            ]);
        }
    }

    /**
     * Show all results for a specific user, optionally filtered by result_id.
     *
     * This endpoint returns the result and winning bet details for a given user.
     * - If result_id is provided, it fetches the result for that ID; otherwise, it fetches the latest result.
     * - Checks if the user exists.
     * - If the user has a winning bet (matching the result number), returns winner details and wallet info.
     * - If not, returns a message indicating the user did not win.
     *
     * Route: GET /lottery/results/user/{user_id}/{result_id?}
     *
     * @param Request $request
     * @param int $user_id
     * @param int|null $result_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showRecentOrByRIDandUID(Request $request, $user_id, $result_id = null)
    {

        if ($user_id == null && $user_id == '') {
            return response()->json(['message' => 'User ID is required'], 400);
        }

        $user = User::find($user_id);
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($result_id !== null) {
            $result = Result::where('result_id', $result_id)->first();
        } else {
            $result = Result::orderBy('created_at', 'desc')->first();
        }

        if (!$result) {
            return response()->json(['message' => 'Result not found' . ($result_id !== null ? ' for ' . $result_id : '') . '.'], 404);
        } else {

            $session = Lottery::find($result->lottery_id);
            if (!$session) {
                return response()->json(['message' => 'Session not found for result ' . $result_id], 404);
            }

            $hasBet = Bet::where('result_id', $result->result_id)
                ->where('user_id', $user_id)
                ->exists();

            if (!$hasBet) {
                return response()->json([
                    // 'message' => 'No bets found for this user in this result.',
                    'message' => 'Patad patad pud bitch!',
                    'status' => 0,
                    'session' => $session,
                    'result' => $result,
                ]);
            }

            // Find winning bets for this result and this user
            $winningBets = Bet::where('result_id', $result->result_id)
                ->where('number', $result->number)
                ->where('user_id', $user_id)
                ->with('user')
                ->get();

            if ($winningBets->isEmpty()) {
                return response()->json([
                    'message' => 'Loser bitch!',
                    'status' => 2,
                    'session' => $session,
                    'result' => $result,
                ]);
            }

            // Fetch wallet details for this user and result
            $wallets = Wallet::where('ref_id', $result->result_id)
                ->where('user_id', $user_id)
                ->get();

            // Attach wallet details to each winner bet
            $winners = $winningBets->map(function ($bet) use ($wallets) {
                $wallet = $wallets->where('user_id', $bet->user_id)->first();
                return [
                    'user_id' => $bet->user->user_id ?? null,
                    'fullname' => ($bet->user->firstname . ' ' . $bet->user->lastname) ?? null,
                    'bet_id' => $bet->bet_id,
                    'bet_number' => $bet->number,
                    'wallet' => $wallet ? [
                        'wallet_id' => $wallet->wallet_id,
                        'points' => $wallet->points,
                        'ref_id' => $wallet->ref_id,
                    ] : null,
                ];
            });


            return response()->json([
                'message' => 'Conratulations you won bitch!',
                'status' => 1,
                'session' => $session,
                'result' => $result,
                'winners' => $winners
            ]);
        }
    }


    public function showByUID(Request $request, $user_id)
    {
        if ($user_id == null && $user_id == '') {
            return response()->json(['message' => 'User ID is required'], 400);
        }

        $user = User::find($user_id);
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $results = Result::orderBy('result_id', 'desc')->get();

        if ($results->isEmpty()) {
            return response()->json(['message' => 'No results found'], 404);
        }

        $resultsWithStatus = $results->map(function ($result) use ($user_id) {
            $session = Lottery::find($result->lottery_id);

            // Get all bets by this user for this result
            $userBets = Bet::where('result_id', $result->result_id)
                ->where('user_id', $user_id)
                ->get();

            $hasBet = $userBets->isNotEmpty();

            // Check if any of the user's bets is a winner
            $isWinner = $userBets->contains(function ($bet) use ($result) {
                return $bet->number == $result->number;
            });

            $status = 0;
            if ($hasBet) {
                $status = $isWinner ? 1 : 2;
            }

            return [
                'session' => $session,
                'result' => $result,
                'status' => $status,
                'bets' => $userBets->map(function ($bet) {
                    return [
                        'bet_id' => $bet->bet_id,
                        'number' => $bet->number,
                        'points' => $bet->points,
                    ];
                })->values(),
            ];
        });

        return response()->json($resultsWithStatus->values());
    }

    public function showByUIDPagination(Request $request, $user_id)
    {

        if ($user_id == null && $user_id == '') {
            return response()->json(['message' => 'User ID is required'], 400);
        }
        
        // Get 'from' and 'to' query parameters with default values
        $from = $request->query('from', 0);
        $to = $request->query('to', 10);

        // Validate parameters to ensure they are integers and non-negative
        if (!is_numeric($from) || !is_numeric($to) || $from < 0 || $to <= $from) {
            return response()->json(['message' => 'Invalid "from" or "to" parameters'], 400);
        }

        // Calculate the number of records to take
        $take = $to - $from;

        $user = User::find($user_id);
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Get the sliced results
        $results = Result::orderBy('result_id', 'desc')
            ->skip($from)
            ->take($take)
            ->get();

        if ($results->isEmpty()) {
            return response()->json(['message' => 'No results found'], 404);
        }
        // Modify Result data to include Session details'
        // $results = $results->map(function ($result) {
        //     $session = Lottery::where('lottery_id', $result->lottery_id)->get();
        //     if (!$session->isEmpty()) {
        //         $result->session_details = $session;
        //     }
        //     return $result;
        // });

        $resultsWithStatus = $results->map(function ($result) use ($user_id) {
            $session = Lottery::find($result->lottery_id);

            // Get all bets by this user for this result
            $userBets = Bet::where('result_id', $result->result_id)
                ->where('user_id', $user_id)
                ->get();

            $hasBet = $userBets->isNotEmpty();

            // Check if any of the user's bets is a winner
            $isWinner = $userBets->contains(function ($bet) use ($result) {
                return $bet->number == $result->number;
            });

            $status = 0;
            if ($hasBet) {
                $status = $isWinner ? 1 : 2;
            }

            return [
                'session' => $session,
                'result' => $result,
                'status' => $status,
                'bets' => $userBets->map(function ($bet) {
                    return [
                        'bet_id' => $bet->bet_id,
                        'number' => $bet->number,
                        'points' => $bet->points,
                    ];
                })->values(),
            ];
        });

        return response()->json($resultsWithStatus->values());
    }


    /**
     * Show all results for today or a given date.
     * Route: GET /lottery/results/by-date/{date?}
     */
    public function showByDate(Request $request, $date = null)
    {
        $date = $date ?? now()->format('Y-m-d');
        $results = Result::whereDate('created_at', $date)->get();

        if ($results->isEmpty()) {
            return response()->json(['message' => 'No results found for ' . $date], 404);
        }

        return response()->json($results);
    }

    public function resultSignal(Request $request)
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
        if ($sessions->isEmpty()) {
            return response()->json(['message' => 'No lottery sessions found'], 404);
        }

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
                $isReady = true;
                break;
            }

            if (!($time >= $sessionStart)) {
                Log::info('Bet is ready for session: ' . $currentSession->lottery_session);
                $isReady = false;
                break;
            }

            // If current time is after the last session, set to the first (morning) session of the next day
            if ($time > $sessions->last()->time) {
                Log::info('Current time is after the last session, setting to first session of the next day.');
                $currentSession = $sessions->first();
                $isReady = false;
                break;
            }
        }

        $sessionTime = strtotime($currentSession->time);
        if ($time == date('H:i:00', $sessionTime)) // Check if current time matches session time in HH:MM:00 format 
        {
            Log::info('Result is ready for session ' . date('Y-m-d') . ' - ' . $currentSession->lottery_session . '(' . $currentSession->time . '). \n ' .
                'Please wait for the next bet.');
        } else {
            $isReady = false;
            // Log::info('Either Betting is on started or Result is on process for session ' . date('Y-m-d') . ' - ' . $currentSession->lottery_session . '(' . $currentSession->time . '). \n ' .
            //     'Please wait for the result.');

            $result = Result::orderBy('created_at', 'desc')->first();
            if ($result) {
                $lastsession = Lottery::find($result->lottery_id);
                Log::info('Result is on process for session ' . date('Y-m-d') . ' - ' . $lastsession->lottery_session . '(' . $lastsession->time . '). \n ' .
                    'Please wait for the result.');
            }
        }

        return response()->json([
            'current_date' => $date,
            'current_time' => $time,
            'isReady' => $isReady,
            'message' => ($isReady && $time == date('H:i:00', $sessionTime) ? 'Result is Ready for ' . $currentSession->lottery_session . ' session ' : 'Either Betting or Result is on process') . '!',
            'session' => $currentSession
        ]);
    }
}
