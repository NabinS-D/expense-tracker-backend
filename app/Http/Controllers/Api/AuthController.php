<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    // public function register(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|string|email|max:255|unique:users',
    //         'password' => 'required|string|min:8',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     $user = User::create([
    //         'name' => $request->name,
    //         'email' => $request->email,
    //         'password' => Hash::make($request->password),
    //     ]);

    //     return response()->json([
    //         'message' => 'User registered successfully',
    //     ], 201);
    // }

    public function register(Request $request)
    {
        try {
            // Log database connection details
            Log::channel('daily')->info('Database Connection Debug', [
                'default_connection' => config('database.default'),
                'pgsql_host' => config('database.connections.pgsql.host'),
                'pgsql_database' => config('database.connections.pgsql.database'),
                'pgsql_username' => config('database.connections.pgsql.username'),
            ]);

            // Test database connection
            try {
                DB::connection()->getPdo();
                Log::channel('daily')->info('Database connection successful');
            } catch (\Exception $connectionError) {
                Log::channel('daily')->error('Database connection failed', [
                    'error' => $connectionError->getMessage()
                ]);
                return response()->json([
                    'error' => 'Database connection failed',
                    'details' => $connectionError->getMessage()
                ], 500);
            }

            // Existing validation
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                Log::channel('daily')->warning('Validation failed', [
                    'errors' => $validator->errors()
                ]);
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Attempt user creation with detailed logging
            try {
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);

                Log::channel('daily')->info('User registration successful', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);

                return response()->json([
                    'message' => 'User registered successfully',
                    'user_id' => $user->id
                ], 201);

            } catch (\Exception $creationError) {
                Log::channel('daily')->error('User creation failed', [
                    'error' => $creationError->getMessage(),
                    'trace' => $creationError->getTraceAsString()
                ]);

                return response()->json([
                    'error' => 'User creation failed',
                    'details' => $creationError->getMessage()
                ], 500);
            }

        } catch (\Exception $generalError) {
            Log::channel('daily')->critical('Unexpected registration error', [
                'error' => $generalError->getMessage(),
                'trace' => $generalError->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Registration process failed',
                'details' => $generalError->getMessage()
            ], 500);
        }
    }

    /**
     * Login a user.
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        if (!auth()->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = auth()->user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
        ]);
    }

    /**
     * Logout the authenticated user.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Get the authenticated user's details.
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Get all registered user's details.
     */
    public function getAllUsers()
    {
        $users = User::all();
        return response()->json(['data' => $users]);
    }
}
