<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OldReservation extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'date',
        'time',
        'table_id'
    ];
}
