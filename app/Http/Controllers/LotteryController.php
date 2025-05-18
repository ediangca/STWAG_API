<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lottery;

class LotteryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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

        try
        {
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
}
