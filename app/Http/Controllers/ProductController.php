<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductsCategories;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        $data = Product::select('name', 'price', 'image')->get();

        if (auth()->user()->role == 'seller') {

            $data = Product::select('id', 'name', 'price', 'image', 'description', 'user_id')->where('user_id', auth()->user()->id)->orderBY('id')->get();
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ], 200);
    }

    public function store(Request $request)
    {
        if (auth()->user()->role == 'customer') {
            return response()->json([
                'status' => 'Forbidden',
                'message' => 'not allowed',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'price' => 'required',
            'image' => 'nullable',
            'description' => 'nullable',
            'category' => 'array|nullable',
        ]);

        $validated['user_id'] = auth()->user()->id;

        $categories = $validated['category'];
        unset($validated['category']);

        DB::beginTransaction();
        try {
            $result = Product::create($validated);

            if (!$result) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'internal server error',
                ], 500);
            }

            foreach ($categories as $category) {
                $dt = [
                    'id_product' => $result->id,
                    'id_category' => $category['id'],
                ];

                ProductsCategories::create($dt);

            };

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => $th->getMessage(),
            ], 500);
        };

        return response()->json([
            'status' => 'success',
            'message' => 'product was posted',
        ], 201);
    }

    public function show(string $id)
    {
        // get product pertama yang ditemukan berdasarkan id
        $product = Product::where('id', $id)->first();

        //kondisi jika user adalah seller
        if (auth()->user()->role == 'seller') {
            //timpa product dengan query khusus seller
            $product = Product::where('id', $id)->where('user_id', auth()->user()->id)->first();
        }

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

    public function update(Request $request, string $id)
    {
        if (auth()->user()->role == 'customer') {
            return response()->json([
                'status' => 'Forbidden',
                'message' => 'not allowed',
            ], 403);
        }

        $product = Product::where('id', $id)->where('user_id', auth()->user()->id)->first();

        if ($product == null) {
            return response()->json([
                'status' => 'not found',
                'message' => 'product not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'nullable',
            'price' => 'nullable',
            'image' => 'nullable',
            'description' => 'nullable',
        ]);

        $result = $product->Update($validated);

        if (!$result) {
            return response()->json([
                'status' => 'Failed',
                'message' => 'internal server error',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
        ], 200);

    }

    public function destroy(string $id)
    {
        if (auth()->user()->role == 'customer') {
            return response()->json([
                'status' => 'Forbidden',
                'message' => 'not allowed',
            ], 403);
        }

        $product = Product::where('id', $id)->where('user_id', auth()->user()->id)->first();

        if ($product == null) {
            return response()->json([
                'status' => 'not found',
                'message' => 'product not found',
            ], 404);
        }

        $isDelete = $product->delete();

        if (!$isDelete) {
            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
            ], 500);
        }

        return response()->noContent();
    }
}
