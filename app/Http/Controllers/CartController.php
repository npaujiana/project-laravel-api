<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\DetailTransaction;
use App\Models\HeaderTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index()
    {
        $carts = DB::table('carts')
            ->join('products', 'products.id', '=', 'carts.product_id')
            ->select('carts.id', 'products.name', 'products.image', 'carts.total_quantity', 'carts.total_price')
            ->where('carts.user_id', auth()->user()->id)
            ->get();

        return response()->json([
            'data' => $carts,
        ], 200);
    }

    public function store(Request $request)
    {
        // validasi role user
        if (auth()->user()->role == 'seller' || auth()->user()->role == 'admin') {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'you can not acess this page',
            ], 403);
        }

        $validated = $request->validate([
            'product_id' => 'required',
            'total_quantity' => 'required',
        ]);

        $product = Product::where('id', $validated['product_id'])->first();

        if (empty($product)) {
            return response()->json([
                'status' => 'not found',
                'message' => 'Product not found',
            ], 404);
        }

        if ($validated['total_quantity'] > $product->stock) {
            return response()->json([
                'status' => 'Bad Request',
                'message' => 'the amount you requested exceeds the limit',
            ], 400);
        }

        $validated['total_price'] = $product->price * $validated['total_quantity'];
        $validated['user_id'] = auth()->user()->id;

        // $product->stock -= $validated['total_quantity'];
        // $product->save();

        $checkProduct = Cart::where('product_id', $validated['product_id'])->first();

        $result = false;

        if (empty($checkProduct)) {
            $result = Cart::create($validated);
        } else {
            $validated['total_quantity'] += $checkProduct->total_quantity;
            $validated['total_price'] = $product->price * $validated['total_quantity'];
            $result = $checkProduct->update($validated);
        }

        if (!$result) {
            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully added to cart',
        ], 201);

    }

    public function show(string $id)
    {
        $carts = DB::table('carts')
            ->join('products', 'products.id', '=', 'carts.product_id')
            ->select('carts.id', 'products.name', 'products.image', 'carts.total_quantity', 'carts.total_price')
            ->where('carts.user_id', auth()->user()->id)
            ->where('carts.id', $id)
            ->get();

        if (count($carts) == 0) {
            return response()->json([
                'status' => 'not found',
                'message' => 'cart not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'carts' => $carts,
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'total_quantity' => 'required',
        ]);

        $cart = Cart::where('id', $id)->first();

        if (!$cart) {
            return response()->json([
                'status' => 'not found',
                'message' => 'cart not found',
            ], 404);
        }

        $price_product = Product::where('id', $cart->product_id)->first();

        if (!$price_product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product Undefined',
            ], 500);
        }

        $validated['total_price'] = $price_product->price * $validated['total_quantity'];

        $result = $cart->update($validated);

        if (!$result) {
            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'cart' => $cart,
        ], 200);
    }

    public function destroy(string $id)
    {
        $cart = Cart::find($id);

        if (!$cart) {
            return response()->json([
                'status' => 'not found',
                'message' => 'cart not found',
            ], 404);
        }

        $result = $cart->delete();

        if (!$result) {
            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
            ], 500);
        }

        return response()->noContent();
    }
    public function checkout(Request $request)
    {
        if (auth()->user()->role != 'customer') {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'You do not have access to this page',
            ], 403);
        }

        $validated = $request->validate([
            'cart' => 'required|array',
            'cart.*.id' => 'required',
        ]);

        $isFind = true;

        // melakukan finding data satu satu
        foreach ($validated['cart'] as $cart) {
            $cart = Cart::find($cart['id']);
            if (!$cart) {
                $isFind = false;
            }

        }

        // memberikan pesan request ke user jika salah satu id cart tidak sesuai dengan tabel
        if (!$isFind) {
            return response()->json([
                'status' => 'Bad Request',
                'message' => 'Your input is wrong',
            ], 400);
        }

        // membuat data header
        $headerValue = [
            'user_id' => auth()->user()->id,
            'total_quantity' => 0,
            'total_product' => 0,
            'total_price' => 0,
        ];

        $headerCreate = HeaderTransaction::create($headerValue);

        foreach ($validated['cart'] as $cart) {
            $cart = Cart::find($cart['id']);

            $headerValue['total_quantity'] += $cart['total_quantity'];
            $headerValue['total_product'] += 1;
            $headerValue['total_price'] += $cart['total_price'];

            $headerUpdate = HeaderTransaction::where('id', $headerCreate['id'])->update($headerValue);

            if (!$headerUpdate) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Internal Server Error',
                ], 500);
            }

            $dataTransaksi = [
                'hdr_trx_id' => $headerCreate->id,
                'product_id' => $cart['product_id'],
                'quantity' => $cart['total_quantity'],
                'total_price' => $cart['total_price'],
            ];

            $detailCreate = DetailTransaction::create($dataTransaksi);

            $cart->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'checkout was succesfully',
        ], 200);

    }
}
