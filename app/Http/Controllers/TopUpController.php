<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TopUp;

class TopUpController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $topups = TopUp::all();
        if ($topups->isEmpty()) {
            return response()->json(['message' => 'No top-ups found'], 404);
        }
        return response()->json($topups);
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
        //Validate the request data
        if (!$request->has('user_id')) {
            return response()->json(['message' => 'User ID is required'], 400);
        }
        if (!$request->has('points')) {
            return response()->json(['message' => 'Points is required'], 400);
        }
        // Validate the request data
        try {
            $request->validate([
                'user_id' => 'required|string|exists:users,user_id',
                'points' => 'required|numeric',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }
        // Create a new top-up record
        $topup_id = now()->format('yMdHis').$request->input('user_id');
        $topup = TopUp::create([
            'user_id' => $request->input('user_id'),
            'points' => $request->input('points'),
        ]);

        
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
