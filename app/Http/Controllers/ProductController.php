<?php

namespace App\Http\Controllers;

use App\Models\DetailTransaction;
use App\Models\HeaderTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        $query = request()->query('search');
        $fillter = request()->query('category');

        if (auth()->user()->role != 'customer') {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You do not have access to this page',
            ], 403);
        }

        $data = Product::select('id', 'name', 'price', 'image', 'stock')->get();
        if ($query) {
            $data = Product::select('name', 'price', 'image', 'stock')
                ->where('name', 'ILIKE', '%' . $query . '%')
                ->get();
        }

        if ($fillter) {
            $data = DB::table('products')
                ->join('products_categories', 'products.id', '=', 'products_categories.product_id')
                ->where('products_categories.category_id', $fillter)
                ->get();
        }

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

    public function checkout(Request $request)
    {
        if (auth()->user()->role != 'customer') {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'you dont have accest',
            ], 403);
        }

        $headerValue = [
            'user_id' => auth()->user()->id,
            'total_quantity' => 0,
            'total_product' => 0,
            'total_price' => 0,
        ];

        $headerCreate = HeaderTransaction::create($headerValue);

        if (!$headerCreate) {
            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
            ], 500);
        }

        $validated = $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'required|integer',
        ]);

        $product = Product::find($validated['product_id']);

        if ($product == null) {
            return response()->json([
                'status' => 'bad request',
                'message' => 'product not found',
            ], 400);
        }

        if ($product->stock < $validated['quantity']) {
            return response()->json([
                'status' => 'bad request',
                'message' => 'quantity more than stock',
            ], 400);
        }

        $validated['total_price'] = $product->price * $validated['quantity'];

        $validated['hdr_trx_id'] = $headerCreate->id;

        $detailTransaction = DetailTransaction::create($validated);

        if (!$detailTransaction) {
            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
            ], 500);
        }

        $headerCreate->total_quantity = $detailTransaction->quantity;
        $headerCreate->total_price = $detailTransaction->total_price;
        $headerCreate->total_product += 1;
        $headerCreate->save();

        $product->stock -= $validated['quantity'];
        $product->save();

        return response()->json([
            'status' => 'success',
            'message' => 'checkout successfully',
        ], 200);

    }

}
