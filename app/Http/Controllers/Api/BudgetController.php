<?php

namespace App\Http\Controllers\Api;

use App\Models\Budget;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class BudgetController extends Controller
{
    /**
     * Display a listing of the user's budgets.
     */
    public function index()
    {
        $budgets = Budget::with('category')->where('user_id', auth()->id()) // Get only the authenticated user's budgets
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return response()->json($budgets);
    }

    /**
     * Store a newly created budget.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id|unique:budgets,category_id', // Ensure category is unique
            'amount' => 'required|numeric|min:0',
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $budget = Budget::create([
            'user_id' => auth()->id(),
            'category_id' => $request->category_id, // Store category_id
            'amount' => $request->amount,
            'month' => $request->month ?? now()->month, // Default to current month
            'year' => $request->year ?? now()->year,   // Default to current year
        ]);

        return response()->json(['message' => 'Budget created successfully', 'budget' => $budget], 201);
    }

    /**
     * Display the specified budget.
     */
    public function show($id)
    {
        $budget = Budget::where('id', $id)
            ->where('user_id', auth()->id()) // Ensure only the authenticated user can access their budget
            ->first();

        if (!$budget) {
            return response()->json(['message' => 'Budget not found'], 404);
        }

        return response()->json($budget);
    }

    /**
     * Update the specified budget.
     */
    public function update(Request $request, $id)
    {
        $budget = Budget::where('id', $id)
            ->where('user_id', auth()->id()) // Ensure only the authenticated user can update their budget
            ->first();

        if (!$budget) {
            return response()->json(['message' => 'Budget not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'exists:categories,id', // Ensure valid category_id
            'amount' => 'numeric|min:0',
            'month' => 'integer|min:1|max:12',
            'year' => 'integer|min:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $budget->update($request->all());

        return response()->json(['message' => 'Budget updated successfully', 'budget' => $budget]);
    }

    /**
     * Remove the specified budget.
     */
    public function destroy($id)
    {
        $budget = Budget::where('id', $id)
            ->where('user_id', auth()->id()) // Ensure only the authenticated user can delete their budget
            ->first();

        if (!$budget) {
            return response()->json(['message' => 'Budget not found'], 404);
        }

        $budget->delete();

        return response()->json(['message' => 'Budget deleted successfully']);
    }
}
