<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\WeekMenu;
use App\Models\Subscription;

class Week extends Model
{
    use HasFactory;
    protected $fillable = ['year', 'week_number', 'start_date', 'end_date'];
    
    public function week_menus()
    {
        return $this->hasMany(WeekMenu::class);
    }
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
