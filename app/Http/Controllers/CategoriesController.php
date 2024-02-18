<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    public function index()
    {
        $query = request()->query('search');

        $categories = Category::select('id', 'category', 'description')->get();

        if ($query) {
            $categories = Category::select('id', 'category', 'description')
                ->where('category', 'ILIKE', '%' . $query . '%')
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'categories' => $categories,
        ], 200);
    }

    public function store(Request $request)
    {
        if (auth()->user()->role == 'customer' || auth()->user()->role == 'seller') {
            return response()->json([
                'status' => 'Forbidden',
                'message' => 'not allowed',
            ], 403);
        }

        $validated = $request->validate([
            'category' => 'required',
            'description' => 'required',
        ]);

        $isCreate = Category::create($validated);

        if (!$isCreate) {
            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Category was Created',
        ], 201);

    }

    public function show(string $id)
    {
        $category = Category::select('id', 'category', 'description')->find($id);

        if (!$category) {
            return response()->json([
                'status' => 'not found',
                'message' => 'Category Not Found',
            ], 404);
        }

        return response()->json($category);
    }

    public function update(Request $request, string $id)
    {
        if (auth()->user()->role == 'customer' || auth()->user()->role == 'seller') {
            return response()->json([
                'status' => 'Forbidden',
                'message' => 'not allowed',
            ], 403);
        }

        $validated = $request->validate([
            'category' => 'nullable',
            'description' => 'nullable',
        ]);

        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status' => 'not found',
                'message' => 'category not found',
            ], 404);
        }

        $isUpdate = $category->update($validated);

        if (!$isUpdate) {
            return response()->json([
                'status' => 'error',
                'message' => 'Internal Server Error',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Update category was completed',
        ], 200);

    }

    public function destroy(string $id)
    {
        if (auth()->user()->role == 'customer' || auth()->user()->role == 'seller') {
            return response()->json([
                'status' => 'Forbidden',
                'message' => 'not allowed',
            ], 403);
        }

        $category = Category::find($id);

        if ($category == null) {
            return response()->json([
                'status' => 'not found',
                'message' => 'category not found',
            ], 404);
        }

        $isDelete = $category->delete();

        if (!$isDelete) {
            return response()->json([
                'status' => 'error',
                'message' => 'internal server error',
            ], 500);
        }

        return response()->noContent();
    }
}
