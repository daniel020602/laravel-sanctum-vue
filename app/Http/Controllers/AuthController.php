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
    public function changeData(Request $request)
    {
        $user = $request->user();
        $fields = $request->validate([
            'phone' => 'sometimes|string|max:15|regex:/^\+?[0-9]{1,15}$/',
            'address' => 'sometimes|string|max:255',
        ]);

        $user->fill($fields)->save();

        return response()->json(['message' => 'User data updated successfully'], 200);
    }


}
