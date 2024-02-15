<?php

namespace App\Http\Controllers;

use App\Models\CartsDetail;
use App\Models\CartsHeader;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index()
    {
        $carts = DB::table('carts_detail')
            ->join('carts_header', 'carts_header.id', '=', 'carts_detail.cart_id')
            ->join('products', 'products.id', '=', 'carts_detail.product_id')
            ->select('carts_detail.id', 'products.name', 'products.image', 'carts_detail.total_quantity', 'carts_detail.total_price')
            ->where('carts_header.user_id', auth()->user()->id)
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

        $customer_cart_id = CartsHeader::select('id')->where('user_id', auth()->user()->id)->first();

        if (empty($customer_cart_id)) {
            return response()->json([
                'status' => 'forbidden',
                'message' => 'something wrong',
            ], 403);
        }

        $cart_header = CartsHeader::where('id', $customer_cart_id['id'])->first();

        if (empty($cart_header)) {
            return response()->json([
                'status' => 'not found',
                'message' => 'cart not found',
            ], 404);
        }

        if ($validated['total_quantity'] > $product->stock) {
            return response()->json([
                'status' => 'Bad Request',
                'message' => 'the amount you requested exceeds the limit',
            ], 400);
        }

        $validated['total_price'] = $product->price * $validated['total_quantity'];
        $validated['cart_id'] = $customer_cart_id['id'];

        $product->stock -= $validated['total_quantity'];
        $product->save();

        $checkProduct = CartsDetail::where('product_id', $validated['product_id'])->first();

        $result = false;

        if (empty($checkProduct->product_id)) {
            $cart_header->total_produk += 1;
            $cart_header->total_price += $validated['total_price'];
            $cart_header->total_quantity += $validated['total_quantity'];
            $cart_header->save();
            $result = CartsDetail::create($validated);

        } else {
            $cart_header->total_price += $validated['total_price'];
            $cart_header->total_quantity += $validated['total_quantity'];
            $validated['total_quantity'] += $checkProduct['total_quantity'];
            $cart_header->save();
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
        $carts = DB::table('carts_detail')
            ->join('carts_header', 'carts_header.id', '=', 'carts_detail.cart_id')
            ->join('products', 'products.id', '=', 'carts_detail.product_id')
            ->select('cart_detail.id', 'products.name', 'products.image', 'carts_detail.total_quantity', 'carts_detail.total_price')
            ->where('carts_header.user_id', auth()->user()->id)
            ->where('carts_detail.id', $id)
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

        $cart = CartsDetail::find($id);

        if (!$cart) {
            return response()->json([
                'status' => 'not found',
                'message' => 'cart not found',
            ], 404);
        }

        $validated['total_price'] = $cart->total_price * $validated['total_quantity'];

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
        $cart = CartsDetail::find($id);

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
}
