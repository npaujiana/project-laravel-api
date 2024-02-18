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
            $data = Product::select('id', 'name', 'price', 'image', 'stock')
                ->where('name', 'ILIKE', '%' . $query . '%')
                ->get();
        }

        if ($fillter) {
            $data = DB::table('products')
                ->join('products_categories', 'products.id', '=', 'products_categories.product_id')
                ->select('products.id', 'products.name', 'products.price', 'products.image', 'products.stock')
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

    public function history()
    {
        if (auth()->user()->role != 'customer') {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You do not have access to this page',
            ], 403);
        }

        $query = request()->query('id');

        $history = HeaderTransaction::select('id', 'created_at', 'total_product', 'total_quantity', 'total_price')
            ->where('user_id', auth()->user()->id)
            ->orderBy('created_at', 'asc')
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
