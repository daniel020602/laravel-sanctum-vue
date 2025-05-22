<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyMenu extends Model
{
    protected $fillable = ['week', 'selection'];

    protected $casts = [
        'selection' => 'array',
    ];
}
