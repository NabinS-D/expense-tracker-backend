<?php

namespace App\Casts;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class Formatted_Date implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return Carbon::now('Asia/Kathmandu')->format('F j, Y h:i A'); // Example format: "November 29, 2024 10:47 AM"

    }

    /**
 * Prepare the given value for storage.
 *
 * @param  array<string, mixed>  $attributes
 */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        // Ensure the value is a valid Carbon instance
        $carbonDate = $value instanceof Carbon ? $value : Carbon::parse($value, 'Asia/Kathmandu');

        // Return the value in the default DATETIME format for storage
        return $carbonDate->setTimezone('UTC')->toDateTimeString(); // Default format: YYYY-MM-DD HH:MM:SS
    }

}
