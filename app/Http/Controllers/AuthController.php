<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request){
        $fields=$request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed'
        ]);

        $user=User::create($fields);
        $token= $user->createToken($request->name)->plainTextToken;
        return [
            'user' => $user,
            'token' => $token
        ];
    }
    public function login(Request $request){
        $fields=$request->validate([
            'email' => 'required|exists:users,email',
            'password' => 'required'
        ]);
        $user =User::where('email', $request->email)->first();
        if(!$user||!Hash::check($request->password, $user->password)){
            return response()->json(['errors' => ['email' => ['Invalid credentials']]], 401);

        }
        $token= $user->createToken($user->name)->plainTextToken;
        return [
            'user' => $user,
            'token' => $token
        ];
    }
    public function logout(Request $request){
        $request->user()->tokens()->delete();
        return ['message' => 'Logged out'];
    }
    public function listUsers(Request $request)
    {
        if (!($request->user() && $request->user()->is_admin)) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $users = User::select('id', 'name', 'email', 'phone', 'address')->get();

        return response()->json([
            'users' => $users
        ]);
    }
    public function showUser($id, Request $request)
    {
        if (!($request->user() && $request->user()->is_admin)) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $user = User::select('id', 'name', 'email', 'phone', 'address')->find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        return response()->json([
            'user' => $user
        ]);
    }
    public function deleteUser($id, Request $request)
    {
        if (!($request->user() && $request->user()->is_admin)) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->delete();
        return response()->json(['message' => 'User deleted successfully'], 200);
    }   
    public function promoteUser($id, Request $request)
    {
        if (!($request->user() && $request->user()->is_admin)) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->is_admin = true;
        $user->save();
        return response()->json([
            'message' => 'User promoted to admin successfully',
            'user' => $user
        ], 200);
    }
    public function demoteUser($id, Request $request)
    {
        if (!($request->user() && $request->user()->is_admin)) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        $user->is_admin = false;
        $user->save();
        return response()->json([
            'message' => 'User demoted to regular user successfully',
            'user' => $user
        ], 200);
    }
    public function changeData($id = null, Request $request)
    {
        // If an id is provided, only admins may change another user's data.
        if ($id) {
            if (!($request->user() && $request->user()->is_admin)) {
                return response()->json(['message' => 'Access denied'], 403);
            }
            $user = User::find($id);
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }
        } else {
            // no id: update own data
            $user = $request->user();
            if (! $user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        }

        $fields = $request->validate([
            'phone' => 'sometimes|string|max:15|regex:/^\+?[0-9]{1,15}$/',
            'address' => 'sometimes|string|max:255',
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->fill($fields)->save();

        return response()->json(['message' => 'User data updated successfully', 'user' => $user], 200);
    }
}
