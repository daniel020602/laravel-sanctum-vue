<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Order;
use App\Models\Menu;

class OrderProducts extends Model
{
    use HasFactory;
    protected $fillable = ['order_id', 'menu_id', 'quantity','price'];
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }
}
