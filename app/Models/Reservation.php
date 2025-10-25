<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reservation extends Model
{
    use HasFactory;
    protected $fillable = [
        'reservation_code',
        'name',
        'email',
        'phone',
        'date',
        'time',
        'table_id',
        'is_confirmed',
    ];

    /**
     * Attribute casting
     *
     * @var array
     */
    protected $casts = [
        'is_confirmed' => 'boolean',
    ];


    public function table()
    {
        return $this->belongsTo(Table::class);
    }
}
