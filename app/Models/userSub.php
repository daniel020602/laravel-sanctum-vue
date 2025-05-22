<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class userSub extends Model
{
    protected $fillable = ['user_id', 'week','day1','day2','day3','day4','day5'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
