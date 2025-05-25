<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    //


    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $users = User::all();


        if ($users->isEmpty()) {
            return response()->json(['message' => 'No users found'], 404);
        }
        return response()->json($users);
    }

    public function register(Request $request)
    {

        Log::info('Register request received', $request->all());

        if (
            !$request->has('firstname') ||
            !$request->has('lastname') ||
            !$request->has('birthdate') ||
            !$request->has('email') ||
            !$request->has('password') ||
            !$request->has('type') ||
            !$request->has('uplinecode') ||
            !$request->has('avatar') ||
            !$request->has('uuid') ||
            !$request->has('devicemodel')
        ) {
            return response()->json(['message' => 'Firstname, Lastname, Birthdate, Email, Password, Type, Uplinecode, UUID, Devicename are required.
            '], 400);
        }

        if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'Invalid email format'], 400);
        }

        try {
            $request->validate([
                'firstname' => 'required|string|max:255',
                'lastname' => 'required|string|max:255',
                'birthdate' => 'required|date',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'type' => 'required|string|max:255|nullable', //default user
                'referencecode' => 'required|string|max:255', //generated
                'uplinecode' => 'string|max:255',
                'avatar' => 'required|integer|nullable', //default 0
                'level' => 'required|integer|nullable',
                'uuid' => 'required|string|max:255|unique:users',
                'devicemodel' => 'required|string|max:255',
            ]);
            Log::info('Validation passed');
        } catch (ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return response()->json(['errors' => $e->errors()], 422);
        }

        $user_id = DB::selectOne('SELECT GenerateUserAccID() AS user_id')->user_id;
        Log::info('Generated user_id', ['user_id' => $user_id]);

        // Generate referencecode
        $referencecode = $this->generateReferenceCode();
        Log::info('Generated referencecode', ['referencecode' => $referencecode]);

        if ($request->type == "user") {
            $isUplineReferenceExist = User::where('uplinecode', $request->uplinecode)->first();
            if (!$isUplineReferenceExist) {
                return response()->json(['message' => 'Reference Code not found.'], 404);
            }
        }
        Log::info('Upline code exists', ['uplinecode' => $request->uplinecode]);

        $level = DB::selectOne('SELECT count(*) as noOfDownline from users where referencecode = ?', [$request->uplinecode])->noOfDownline;
        if ($level == 0) {
            $level = 1;
        } else {
            $level = $level + 1;
        }

        Log::info('Upline level', ['level' => $level]);

        if ($level >= 11) {
            return response()->json(['message' => 'Level exceeded.'], 404);
        }

        $user = User::create([
            'user_id' => $user_id, // Pass the generated user_id
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'birthdate' => $request->birthdate,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'type' => $request->type,
            'referencecode' => $referencecode, //generated
            // 'referencecode' => $request->referencecode,
            'uplinecode' => in_array($request->type, ["root", "admin", "member"]) ? $request->type : $request->referencecode, 
            'avatar' => $request->avatar,
            'level' => $level,
            'uuid' => $request->uuid,
            'devicemodel' => $request->devicemodel,
        ]);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user
        ], 201);
    }

    private function generateReferenceCode(): string
    {
        $prefix = 'REF';
        $date = date('YHmMds'); // Current date in YYYYMMDD format
        $randomString = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6)); // Random alphanumeric string
        return "{$prefix}-{$date}-{$randomString}";
    }

    public function login(Request $request)
    {

        Log::info('Login request received', $request->all());

        if (!$request->has('email') || !$request->has('password')
        //  || !$request->has('uuid')
        ) {
            return response()->json(['message' => 'Email and password are required'], 400);
        }

        if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'Invalid email format'], 400);
        }

        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
                // 'uuid' => 'required',
            ]);

            Log::info('Validation passed');
        } catch (ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return response()->json(['errors' => $e->errors()], 422);
        }


        $user = User::where('email', $request->email)->first();


        if (!$user) {
            return response()->json(['message' => 'Email not yet Registered'], 401);
        }
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid Password'], 401);
        }
        // if ($user->uuid != $request->uuid) {
        //     return response()->json(['message' => 'Device is not registered'], 401);
        // }

        Log::info('User check', ['user_id' => optional($user)->user_id, 'email' => $request->email]);


        // Create the API token for the user
        // $token = $user->createToken('API Token')->plainTextToken;

        // return response()->json([
        //     'token' => $token
        // ]);

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('API Token')->plainTextToken,
            'message' => 'Login successful'
        ]);
    }

    public function logout(Request $request)
    {
        try {
            if ($request->user() == null) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }
            // Attempt to delete the current access token
            $request->user()->currentAccessToken()->delete();

            return response()->json(['message' => 'Logged out successfully']);
        } catch (\Exception $e) {
            // Log the exception for debugging
            Log::error('Logout failed', ['error' => $e->getMessage()]);

            // Return a generic error response
            return response()->json(['message' => 'Failed to log out. Please try again later.'], 500);
        }
    }

    public function userInfo($user_id)
    {
        // Find the user by user_id
        $user = User::where('user_id', $user_id)->first();

        // Check if the user exists
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Return the user's information
        return response()->json($user);
    }
    public function user(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();

        // Check if the user is authenticated
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Return the user's information
        return response()->json($user);
    }
    
    public function refresh(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();

        return response()->json([
            'token' => $user->createToken('API Token')->plainTextToken
        ]);
    }
    public function delete(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $user->update($request->all());

        return response()->json(['message' => 'User updated successfully']);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 401);
        }

        $user->password = bcrypt($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully']);
    }
    public function updateEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email,' . $request->user()->id,
        ]);

        $user = $request->user();
        $user->email = $request->email;
        $user->save();

        return response()->json(['message' => 'Email updated successfully']);
    }

    public function getUserByType(Request $request, $type)
    {

        // $users = User::where('type', $request->type)->get();
        $users = User::where('type', $type)->get();

        // return response()->json($types);
        if ($users->isEmpty()) {
            return response()->json(['message' => 'No Users found for ' . $request->type], 404);
        }

        return response()->json($users);
    }


    public function getUserById(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    public function getUserByIdWithToken(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Get the user's tokens
        $tokens = $user->tokens;

        return response()->json([
            'user' => $user,
            'tokens' => $tokens
        ]);
    }

    public function updateUserById(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->update($request->all());

        return response()->json(['message' => 'User updated successfully']);
    }
    public function updateUserPasswordById(Request $request, $id)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 401);
        }

        $user->password = bcrypt($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully']);
    }

    public function updateUserEmailById(Request $request, $id)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email,' . $id,
        ]);

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->email = $request->email;
        $user->save();

        return response()->json(['message' => 'Email updated successfully']);
    }


    public function updateAvatarById(Request $request, $id)
    {
        if (!$request->has('avatar')) {
            return response()->json(['message' => 'Avatar is required'], 400);
        }

        $user = User::where('user_id', $id)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'avatar' => 'required|integer',
        ]);

        $user->avatar = $request->avatar;
        $user->save();

        return response()->json(['message' => 'Avatar updated successfully']);
    }


    public function deleteUserById(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function getUserByEmail(Request $request, $email)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    public function getDownlines(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $downlines = User::where('uplinecode', $user->referencecode)->get();

        if ($downlines->isEmpty()) {
            return response()->json(['message' => 'No downlines found'], 404);
        }

        return response()->json($downlines);
    }

    public function getUpline(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $upline = User::where('referencecode', $user->uplinecode)->first();

        if ($user->level == 0) {
            return response()->json(['message' => 'Root user has no upline'], 404);
        }

        if (!$upline) {
            return response()->json(['message' => 'No upline found'], 404);
        }

        return response()->json($upline);
    }
}
