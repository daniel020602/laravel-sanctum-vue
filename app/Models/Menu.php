<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OrderProducts;



class Menu extends Model
{
    use HasFactory;
    protected $fillable = [ 'name', 'type', 'price'];
    public function order_products()
    {
        return $this->hasMany(OrderProducts::class);
    }

}
