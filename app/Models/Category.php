<?php

namespace App\Models;

use App\Models\Budget;
use App\Models\Expense;
use App\Casts\Formatted_Date;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'created_at' => Formatted_Date::class, // Apply the custom date formatting cast to created_at
        'updated_at' => Formatted_Date::class, // Optionally apply it to updated_at as well
    ];


    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function budget()
    {
        return $this->hasOne(Budget::class);
    }
}
