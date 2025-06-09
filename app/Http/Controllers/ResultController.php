<?php

namespace App\Http\Controllers;

use App\Models\Lottery;
use Illuminate\Http\Request;
use App\Models\Result;
use Illuminate\Support\Facades\Log;

class ResultController extends Controller
{
    //

    public function index()
    {
        $result = Result::orderBy('result_id', 'desc')->get();
        if ($result->isEmpty()) {
            return response()->json(['message' => 'No results found'], 404);
        }

        return response()->json($result);
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
    public function showRecentOrByID(Request $request, $result_id = null)
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
            return response()->json(['message' => 'Result not found'. ($result_id !== null? ' for '.$result_id: '').'.'], 404);
        } else {
            $session = Lottery::find($result->lottery_id);
            if (!$session) {
                return response()->json(['message' => 'Session not found for result ' . $result_id], 404);
            }
            return response()->json([
                'session' => $session,
                'result' => $result
            ]);
        }
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
