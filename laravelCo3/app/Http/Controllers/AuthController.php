<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->error(), 422);
        };
        if (!$token = auth()->attempt($validator->validated())) {

            return response()->json(['error' => 'Unauthorized'], 401);
        };
        $credentials = $request->only('email', 'password');
        $token = Auth::attempt($credentials);

        return $this->createNewToken($token);
    }
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {

            return response()->json($validator->errors()->toJson(), 400);
        }
        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        ));
        return response()->json([
            'message' => 'User successfully registered', 'user' => $user,
        ], 201);
    }
    public function logout()
    {
        auth()->logout();
        return reponse()->json(['message' => 'User successfully signed out'], 201);

    }
    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }

    public function userProfile()
    {
        return response()->json(auth()->user());
    }
    public function createNewToken($token)
    {

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => auth()->user(),
        ]);
    }
    public function changePassWord(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pld_password' => 'required|string|min:6',
            'new_password' => 'required|string|confirmed|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->error()->toJson(), 404);
        }
        $userId = auth()->user()->id;

        $user = User::where('id', $userId)->update(['password' => bcrypt($request->new_password)]);
        return response()->json(['message' => 'User successfully changed password',
            'user' => $user], 201);
    }

}
