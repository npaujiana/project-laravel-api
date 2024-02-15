<?php

namespace App\Http\Controllers;

use App\Models\CartsHeader;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        $token = Auth::attempt($credentials);

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'username/password wrong',
            ], 401);
        }

        return response()->json([
            'status' => 'success',
            'token' => $token,
        ], 200);

    }

    public function registrasi(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'customer',
        ];

        $user = User::create($data);
        

        $cart = [
            'user_id' => $user->id,
            'total_produk' => 0,
            'total_quantity' => 0,
            'total_price' => 0,
        ];

        $carts = CartsHeader::create($cart);

        if (!$user || !$carts) {
            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
            ], 500);
        }

        return response()->json([
            'status' => 'Register Succesfully',
        ], 200);
    }

    public function logout()
    {
        Auth::logout();
        return response()->json([
            'status' => 'succes',
            'message' => 'Log out Succesfully',
        ]);

    }

    public function addSeller(Request $request)
    {

        if (auth()->user()->role == 'customer' || auth()->user()->role == 'seller') {
            return response()->json([
                'status' => 'Unauthorized',
                'message' => 'not allowed',
            ], 401);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'seller',
        ];

        $user = User::create($data);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'add seller was completed',
        ], 201);

    }
}
