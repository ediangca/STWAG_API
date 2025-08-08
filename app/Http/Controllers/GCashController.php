<?php

namespace App\Http\Controllers;

use App\Models\GCash;
use Illuminate\Http\Request;

class GCashController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $gcash = GCash::all();

        if ($gcash->isEmpty()) {
            return response()->json(['message' => 'No GCash numbers found'], 404);
        }
        return response()->json($gcash);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!$request->has('gcashno')) {
            return response()->json(['message' => 'GCash number is required'], 400);
        }
        //

        try {
            $data = $request->validate([
                'gcashno' => 'required|string|max:45|unique:gcash,gcashno',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }

        $gcash = GCash::create($data);
        if (!$gcash) {
            return response()->json(['message' => 'Failed to create GCash number'], 500);
        }

        return response()->json([
            'message' => 'GCash number created successfully',
            'gcash' => $gcash
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $gcash = GCash::find($id);
        if (!$gcash) {
            return response()->json(['message' => 'GCash number not found'], 404);
        }
        return response()->json($gcash);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $gcash = GCash::find($id);
        if (!$gcash) {
            return response()->json(['message' => 'GCash number not found'], 404);
        }
        return response()->json($gcash);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if (!$request->has('gcashno')) {
            return response()->json(['message' => 'GCash number is required'], 400);
        }
        $gcash = GCash::find($id);
        if (!$gcash) {
            return response()->json(['message' => 'GCash number not found'], 404);
        }
        $data = $request->validate([
            'gcashno' => 'required|string|max:45',
        ]);
        $gcash->update($data);
        return response()->json([
            'message' => 'GCash number updated successfully',
            'gcash' => $gcash
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GCash $gCash)
    {
        //
    }
}
