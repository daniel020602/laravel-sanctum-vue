<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Week;
use App\Models\Menu;
use App\Models\SubscriptionChoice;

class WeekMenu extends Model
{
    use HasFactory;
    protected $fillable = ['week_id', 'menu_id', 'day_of_week'];
    
    public function week()
    {
        return $this->belongsTo(Week::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function subscription_choices()
    {
        return $this->hasMany(SubscriptionChoice::class);
    }
}
