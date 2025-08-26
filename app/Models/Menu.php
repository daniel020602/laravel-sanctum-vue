<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OrderProducts;
use App\Models\WeekMenu;



class Menu extends Model
{
    use HasFactory;
    protected $fillable = [ 'name', 'type', 'price'];
    public function order_products()
    {
        return $this->hasMany(OrderProducts::class);
    }
    // There is no direct relationship to Week, but you can use a helper scope if needed
    // Example usage: Menu::find($id)->referencedWeeks()->get();
    public function week_menus()
    {
        return $this->hasMany(WeekMenu::class);
    }

}
