<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Week;
use App\Models\SubscriptionChoice;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Subscription extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'week_id', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function week()
    {
        return $this->belongsTo(Week::class);
    }

    public function subscription_choices()
    {
        return $this->hasMany(SubscriptionChoice::class);
    }
}
