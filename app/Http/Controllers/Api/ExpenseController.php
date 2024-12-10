<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Expense;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the expenses.
     */
    public function index(Request $request)
    {
        $request->validate([
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);
        $category = $request->get('category_id');
        $expensesQuery = Expense::with('category', 'category.budget')
        ->where('user_id', auth()->id());
        if ($category) {
            $expensesQuery->where('category_id', $category);
        }
        $expenses = $expensesQuery->orderBy('created_at', 'desc')->get();
        return response()->json($expenses);
    }

    /**
     * Store a newly created expense.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0',
            'category_id' => 'required|integer',
            'date' => 'date',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $expense = Expense::create([
            'user_id' => auth()->id(),
            'category_id' => $request->category_id, // Use category_id from request
            'amount' => $request->amount,
            'date' => $request->date ?? Carbon::now()->toDateString(),
            'description' => $request->description,
        ]);
        return response()->json(['message' => 'Expense created successfully', 'expense' => $expense], 201);
    }

    /**
     * Display the specified expense.
     */
    public function show($id)
    {
        $expense = Expense::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$expense) {
            return response()->json(['message' => 'Expense not found'], 404);
        }

        return response()->json($expense);
    }

    /**
     * Update the specified expense.
     */
    public function update(Request $request, $id)
    {
        $expense = Expense::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$expense) {
            return response()->json(['message' => 'Expense not found'], 404);
        }

        // Only validate the fields that are provided
        $validator = Validator::make($request->all(), [
            'description' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update only the fields that are present in the request
        $expense->update([
            'category_id' => $request->category_id ?? $expense->category_id,
            'description' => $request->description ?? $expense->description,
            'amount'  => $request->amount ?? $expense->amount,
        ]);

        return response()->json(['message' => 'Expense updated successfully', 'expense' => $expense]);
    }

    /**
     * Remove the specified expense.
     */
    public function destroy($id)
    {
        $expense = Expense::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$expense) {
            return response()->json(['message' => 'Expense not found'], 404);
        }

        $expense->delete();

        return response()->json(['message' => 'Expense deleted successfully']);
    }
}
