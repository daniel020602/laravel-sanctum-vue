<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Sub;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Week extends Model
{
    use HasFactory;
    protected $fillable= ['week','soup','day1a','day1b','day1c','day2a','day2b','day2c','day3a','day3b','day3c','day4a','day4b','day4c','day5a','day5b','day5c'];
    public function subs()
    {
        return $this->hasMany(Sub::class);
    }
}

