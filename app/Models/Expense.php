<?php

namespace App\Models;

use App\Models\Category;
use App\Casts\Formatted_Date;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'amount',
        'date',
        'description',
    ];

    protected $casts = [
       'created_at' => Formatted_Date::class, // Apply the custom date formatting cast to created_at
        'updated_at' => Formatted_Date::class, // Optionally apply it to updated_at as well
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
