<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OpenAIController;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Sanctum login
Route::post('/login', function (Request $request) {
    try {
        $request->validateWithBag('token_creation', [
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);
    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $e->errors(),
        ], 422);
    }

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'The provided credentials are incorrect.',
        ], 401);
    }

    return response()->json([
        'token' => $user->createToken($request->device_name)->plainTextToken
    ]);
});

// Sanctum register
Route::post('/register', function (Request $request) {
    try {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required',
            'device_name' => 'required',
        ]);
    } catch (ValidationException $e) {
        return response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $e->errors(),
        ], 422);
    }

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    return response()->json([
        'message' => 'User successfully registered',
        'token' => $user->createToken($request->device_name)->plainTextToken
    ], 201);
});


Route::middleware(['auth:sanctum'])->post('/user', function (Request $request) {
    \Log::info('Attempting to access /user route', [
        'token' => $request->bearerToken(),
        'headers' => $request->headers->all(),
    ]);

    try {
        $user = $request->user();
        if (!$user) {
            \Log::warning('User not authenticated in /user route');
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        \Log::info('User authenticated successfully', ['user_id' => $user->id]);
        return response()->json($user);
    } catch (\Exception $e) {
        \Log::error('Error in /user route: ' . $e->getMessage());
        return response()->json(['error' => 'Server error'], 500);
    }
});


Route::middleware('auth:sanctum')->post('/generate-text', [OpenAIController::class, 'generateText']);

Route::middleware('auth:sanctum')->post('/prompt/{identifier}', [OpenAIController::class, 'execute']);


