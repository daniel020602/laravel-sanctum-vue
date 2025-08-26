<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\WeekMenu;
use App\Models\Subscription;

class SubscriptionChoice extends Model
{
    protected $fillable = ['subscription_id', 'week_menu_id'];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function weekMenu()
    {
        return $this->belongsTo(WeekMenu::class);
    }
}
