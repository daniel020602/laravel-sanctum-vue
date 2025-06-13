<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Week;

class Sub extends Model
{
    protected $fillable = ['user_id', 'week','day1','day2','day3','day4','day5'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function week()
    {
        return $this->belongsTo(Week::class);
    }
}
