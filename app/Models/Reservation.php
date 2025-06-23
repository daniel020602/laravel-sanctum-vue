<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'reservation_code',
        'name',
        'email',
        'phone',
        'date',
        'time',
        'table_id'
    ];


    public function table()
    {
        return $this->belongsTo(Table::class);
    }
}
