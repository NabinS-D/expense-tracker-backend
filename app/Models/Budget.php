<?php

namespace App\Models;

use App\Casts\Formatted_Date;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Budget extends Model
{
    use HasFactory;

    // Define fillable attributes to protect against mass assignment vulnerability
    protected $fillable = ['user_id', 'category_id', 'amount', 'month', 'year'];

    // Cast to decimal for the amount
    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => Formatted_Date::class, // Apply the custom date formatting cast to created_at
        'updated_at' => Formatted_Date::class, // Optionally apply it to updated_at as well
    ];

    /**
     * Define relationship with the User model
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * You can define the relationship with the Category model if needed
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
