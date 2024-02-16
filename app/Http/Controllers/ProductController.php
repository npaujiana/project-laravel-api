<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;

class ProductController extends Controller
{
    public function index()
    {
        if (auth()->user()->role != 'customer') {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You do not have access to this page',
            ], 403);
        }
    

        $data = Product::select('name', 'price', 'image', 'stock')->get();

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ], 200);
    }
    public function show(string $id)
    {
        if (auth()->user()->role != 'customer') {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You do not have access to this page',
            ], 403);
        }

        // get product pertama yang ditemukan berdasarkan id
        $product = Product::select('id', 'name', 'price', 'image', 'description', 'user_id', 'stock')->where('id', $id)->first();

        // validasi jika data tidak ditemukan
        if ($product == null) {
            return response()->json([
                'status' => 'not found',
                'message' => 'Product Not Found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $product,
        ], 200);

    }

}
