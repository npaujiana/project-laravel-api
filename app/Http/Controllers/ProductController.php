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
        $data = Product::select('name', 'price', 'image', 'stock')->get();

        if (auth()->user()->role == 'seller') {
            $data = Product::select('id', 'name', 'price', 'image', 'description', 'user_id', 'stock')->where('user_id', auth()->user()->id)->orderBY('id')->get();
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
                ret urn response()->json([
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
        // get product pertama yang ditemukan berdasarkan id
        $product = Product::select('id', 'name', 'price', 'image', 'description', 'user_id', 'stock')->where('id', $id)->first();

        //kondisi jika user adalah seller
        if (auth()->user()->role == 'seller') {
            //timpa product dengan query khusus seller
            $product = Product::select('id', 'name', 'price', 'image', 'description', 'user_id', 'stock')->where('id', $id)->where('user_id', auth()->user()->id)->first();
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

        $product = Product::where('id', $id)->first();

        if (auth()->user()->role == 'seller') {
            $product = Product::where('id', $id)->where('user_id', auth()->user()->id)->first();
        }

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

}

//     public function addToCart(Request $request, string $id)
//     {
//         if (auth()->user()->role == 'seller' || auth()->user()->role == 'admin') {
//             return response()->json([
//                 'status' => 'forbidden',
//                 'message' => 'You dont have access ',
//             ], 403);
//         }

//         $product = Product::find($id);

//         if (!$product) {
//             return response()->json([
//                 'status' => 'not found',
//                 'message' => 'Product Not Found',
//             ], 404);
//         }

//         $cartId = CartsHeader::select('id')->where('user_id', auth()->user()->id)->first();

//         $validated = $request->validate([
//             'total_quantity' => 'required',
//         ]);

//         if ($validated > $product->stock) {
//             return response()->json([
//                 'status' => 'conflict',
//                 'message' => 'the amount you requested exceeds the limit',
//             ], 409);
//         }

//         $product->stock -= $validated['total_quantity'];
//         $product->save();

//         $cartValue = [
//             'cart_id' => $cartId,
//             'product_id' => $id,
//             'total_price' => $product->price,
//             'total_quantity' => $validated['total_quantity'],
//         ];

//         $result = CartsHeader::create($cartValue);

//         if (!$result) {
//             return response()->json([
//                 'status' => 'error',
//                 'message' => 'internal server error',
//             ], 500);
//         }

//         return response()->json([
//             'status' => 'success',
//             'message' => 'Successfully added to cart',
//         ], 200);
//     }
// }
