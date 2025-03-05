<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index()
    {
        $userId = Auth::id(); // Get the authenticated user's ID
        $categories = Category::where('user_id', $userId)
            ->with("expenses", "budget")
            ->get();
        return response()->json($categories, 200);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request)
    {
        $userId = Auth::id(); // Get the authenticated user's ID

        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                // Unique per user
                'unique:categories,name,NULL,id,user_id,' . $userId
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create the category for the authenticated user
        $category = Category::create([
            'name' => $request->name,
            'user_id' => $userId, // Set the user ID
        ]);

        return response()->json(['message' => 'Category created successfully', 'category' => $category], 201);
    }

    /**
     * Display the specified category.
     */
    public function show($id)
    {
        $userId = Auth::id(); // Get the authenticated user's ID
        $category = Category::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json($category, 200);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, $id)
    {
        $userId = Auth::id(); // Get the authenticated user's ID
        $category = Category::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                // Unique per user, excluding current category
                'unique:categories,name,' . $id . ',id,user_id,' . $userId
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update the category
        $category->update([
            'name' => $request->name,
        ]);

        return response()->json(['message' => 'Category updated successfully', 'category' => $category], 200);
    }

    /**
     * Remove the specified category.
     */
    public function destroy($id)
    {
        $userId = Auth::id(); // Get the authenticated user's ID
        $category = Category::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully'], 200);
    }
}
