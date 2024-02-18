<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        if (auth()->user()->role != 'admin') {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You do not have access to this page',
            ], 403);
        }

        $query = request()->query('search');

        $users = User::select('id', 'name', 'email', 'role')
            ->where('role', '!=', 'admin')
            ->get();

        if ($query) {
            $users = User::select('name', 'email', 'role')
                ->where('name', 'ILIKE', '%' . $query . '%')
                ->where('role', '!=', 'admin')
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ], 200);

    }

    public function store(Request $request)
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

    public function show(string $id)
    {
        if (auth()->user()->role != 'admin') {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You do not have access to this page',
            ], 403);
        }

        $users = User::select('id', 'name', 'email', 'role')
            ->where('role', '!=', 'admin')
            ->where('id', $id)
            ->first();

        if ($users == null) {
            return response()->json([
                'status' => 'not found',
                'message' => 'Users Not Found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ], 200);
    }
}
