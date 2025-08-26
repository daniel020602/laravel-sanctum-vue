<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Sub;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Menu;

class Week extends Model
{
    use HasFactory;
    protected $fillable= ['week','soup','day1a','day1b','day1c','day2a','day2b','day2c','day3a','day3b','day3c','day4a','day4b','day4c','day5a','day5b','day5c'];
    public function subs()
    {
        return $this->hasMany(Sub::class);
    }

    // Individual menu item relationships
    public function soupMenu()
    {
        return $this->belongsTo(Menu::class, 'soup');
    }

    public function day1aMenu()
    {
        return $this->belongsTo(Menu::class, 'day1a');
    }

    public function day1bMenu()
    {
        return $this->belongsTo(Menu::class, 'day1b');
    }

    public function day1cMenu()
    {
        return $this->belongsTo(Menu::class, 'day1c');
    }

    public function day2aMenu()
    {
        return $this->belongsTo(Menu::class, 'day2a');
    }

    public function day2bMenu()
    {
        return $this->belongsTo(Menu::class, 'day2b');
    }

    public function day2cMenu()
    {
        return $this->belongsTo(Menu::class, 'day2c');
    }

    public function day3aMenu()
    {
        return $this->belongsTo(Menu::class, 'day3a');
    }

    public function day3bMenu()
    {
        return $this->belongsTo(Menu::class, 'day3b');
    }

    public function day3cMenu()
    {
        return $this->belongsTo(Menu::class, 'day3c');
    }

    public function day4aMenu()
    {
        return $this->belongsTo(Menu::class, 'day4a');
    }

    public function day4bMenu()
    {
        return $this->belongsTo(Menu::class, 'day4b');
    }

    public function day4cMenu()
    {
        return $this->belongsTo(Menu::class, 'day4c');
    }

    public function day5aMenu()
    {
        return $this->belongsTo(Menu::class, 'day5a');
    }

    public function day5bMenu()
    {
        return $this->belongsTo(Menu::class, 'day5b');
    }

    public function day5cMenu()
    {
        return $this->belongsTo(Menu::class, 'day5c');
    }

}

