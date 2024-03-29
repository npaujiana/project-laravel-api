<?php

namespace App\Http\Controllers;

use App\Models\HeaderTransaction;
use App\Models\Product;
use App\Models\ProductsCategories;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = request()->query('search');
        $fillter = request()->query('category');

        if (auth()->user()->role != 'admin') {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You do not have access to this page',
            ], 403);
        }

        $data = Product::select('id', 'name', 'price', 'image', 'stock', 'user_id')->orderBY('id', 'asc')->get();

        if ($query) {
            $data = Product::select('id', 'name', 'price', 'image', 'stock', 'user_id')
                ->where('name', 'ILIKE', '%' . $query . '%')
                ->orderBY('id', 'asc')
                ->get();
        }

        if ($fillter) {
            $data = DB::table('products')
                ->join('products_categories', 'products.id', '=', 'products_categories.product_id')
                ->select('products.id', 'products.name', 'products.price', 'products.image', 'products.stock', 'products.user_id')
                ->orderBY('id', 'asc')
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
        if (auth()->user()->role != 'admin') {
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
            'user_id' => 'required',
            'category' => 'array|required',
            'category.*.id' => 'required',
        ]);

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
        if (auth()->user()->role != 'admin') {
            return response()->json([
                'status' => 'Forbidden',
                'message' => 'not allowed',
            ], 403);
        }

        $product = Product::select('id', 'name', 'price', 'image', 'description', 'user_id', 'stock')->where('id', $id)->first();

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
        if (auth()->user()->role != 'admin') {
            return response()->json([
                'status' => 'Forbidden',
                'message' => 'not allowed',
            ], 403);
        }

        $product = Product::where('id', $id)->first();

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
            'user_id' => 'nullable',
            'category' => 'nullable|array',
            'category.*.id' => 'nullable',
        ]);

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
        if (auth()->user()->role != 'admin') {
            return response()->json([
                'status' => 'Forbidden',
                'message' => 'not allowed',
            ], 403);
        }

        $product = Product::where('id', $id)->first();

        if ($product == null) {
            return response()->json([
                'status' => 'not found',
                'message' => 'product not found',
            ], 404);
        }

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

    public function showUser()
    {
        if (auth()->user()->role != 'admin') {
            return response()->json([
                'status' => 'Forbidden',
                'message' => 'not allowed',
            ], 403);
        }

        $data = User::select('name', 'email', 'role')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ], 200);
    }

    public function history()
    {
        if (auth()->user()->role != 'admin') {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You do not have access to this page',
            ], 403);
        }

        $query = request()->query('id');

        $history = HeaderTransaction::select('id', 'created_at', 'total_product', 'total_quantity', 'total_price')
            ->orderBy('created_at', 'asc')
            ->get();

        if ($query) {
            $history = DB::table('detail_transactions')
                ->join('header_transactions', 'detail_transactions.hdr_trx_id', '=', 'header_transactions.id')
                ->select('detail_transactions.product_id', 'detail_transactions.quantity', 'detail_transactions.total_price')
                ->where('detail_transactions.hdr_trx_id', $query)->get();
        }

        return response()->json([
            'status' => 'success',
            'history' => $history,
        ], 200);
    }
}
