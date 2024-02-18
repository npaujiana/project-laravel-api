<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductsCategories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductSellerController extends Controller
{
    public function index()
    {
        $query = request()->query('search');
        $fillter = request()->query('category');

        if (auth()->user()->role != 'seller') {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You do not have access to this page',
            ], 403);
        }

        $data = Product::select('id', 'name', 'price', 'image', 'description', 'user_id', 'stock')->where('user_id', auth()->user()->id)->orderBY('id', 'asc')->get();

        if ($query) {
            $data = Product::select('name', 'price', 'image', 'stock')
                ->where('name', 'ILIKE', '%' . $query . '%')
                ->where('user_id', auth()->user()->id)
                ->orderBY('id', 'asc')
                ->get();
        }

        if ($fillter) {
            $data = DB::table('products')
                ->join('products_categories', 'products.id', '=', 'products_categories.product_id')
                ->select('products.id', 'products.name', 'products.price', 'products.image', 'products.stock')
                ->orderBY('id', 'asc')
                ->where('user_id', auth()->user()->id)
                ->where('products_categories.category_id', $fillter)
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ], 200);
    }

    public function store(Request $request)
    {
        if (auth()->user()->role != 'seller') {
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
            'stock' => 'required',
            'category' => 'array|required',
            'category.*.id' => 'required',
        ]);

        $validated['user_id'] = auth()->user()->id;

        $categories = $validated['category'];
        unset($validated['category']);

        DB::beginTransaction();
        try {
            $result = Product::create($validated);
            $div = [];

            if (!$result) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'internal server error',
                ], 500);
            }

            foreach ($categories as $category) {
                $dt = [
                    'product_id' => $result->id,
                    'category_id' => $category['id'],
                ];

                $div[] = ProductsCategories::create($dt);

            };

            $result->category = $div;
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
        if (auth()->user()->role != 'seller') {
            return response()->json([
                'status' => 'Forbidden',
                'message' => 'not allowed',
            ], 403);
        }

        // get product pertama yang ditemukan berdasarkan id
        $product = Product::select('id', 'name', 'price', 'image', 'description', 'user_id', 'stock')->where('user_id', auth()->user()->id)->where('id', $id)->first();

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
        if (auth()->user()->role != 'seller') {
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
            'stock' => 'nullable',
            'category' => 'nullable|array',
            'category.*.id' => 'nullable',
        ]);

        $validated['user_id'] = auth()->user()->id;

        if (!isset($validated['category'])) {
            $result = $product->Update($validated);

            if (!$result) {
                return response()->json([
                    'status' => 'Failed',
                    'message' => 'internal server error',
                ], 500);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Update Was Completed',
            ], 200);
        }

        $categories = $validated['category'];
        unset($validated['category']);

        DB::beginTransaction();
        try {
            $result = $product->Update($validated);

            if (!$result) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'internal server error',
                ], 500);
            }

            ProductsCategories::where('product_id', $id)->delete();

            foreach ($categories as $category) {
                $dt = [
                    'product_id' => $id,
                    'category_id' => $category['id'],
                ];

                ProductsCategories::where('product_id', $dt['product_id'])->create($dt);

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
            'message' => 'update was completed',
        ], 200);

    }

    public function destroy(string $id)
    {
        if (auth()->user()->role != 'seller') {
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

        DB::table('carts')->where('product_id', $id)->delete();
        DB::table('products_categories')->where('product_id', $id)->delete();
        $isDelete = $product->delete();

        if (!$isDelete) {
            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
            ], 500);
        }

        return response()->noContent();
    }

    public function history()
    {
        if (auth()->user()->role != 'seller') {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You do not have access to this page',
            ], 403);
        }

        $query = request()->query('id');

        $history = DB::table('detail_transactions')
            ->join('header_transactions', 'detail_transactions.hdr_trx_id', '=', 'header_transactions.id')
            ->join('products', 'detail_transactions.product_id', '=', 'products.id')
            ->select('detail_transactions.product_id', 'detail_transactions.quantity', 'detail_transactions.total_price', 'header_transactions.user_id')
            ->where('products.user_id', auth()->user()->id)
            ->get();

        if ($query) {
            $history = DB::table('detail_transactions')
                ->join('header_transactions', 'detail_transactions.hdr_trx_id', '=', 'header_transactions.id')
                ->select('detail_transactions.product_id', 'detail_transactions.quantity', 'detail_transactions.total_price')
                ->where('header_transactions.user_id', auth()->user()->id)
                ->where('detail_transactions.hdr_trx_id', $query)->get();
        }

        return response()->json([
            'status' => 'success',
            'history' => $history,
        ], 200);
    }
}
