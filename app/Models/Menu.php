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
    // There is no direct relationship to Week, but you can use a helper scope if needed
    // Example usage: Menu::find($id)->referencedWeeks()->get();
    public function referencedWeeks()
    {
        return Week::where('soup', $this->id)
            ->orWhere('day1a', $this->id)
            ->orWhere('day1b', $this->id)
            ->orWhere('day1c', $this->id)
            ->orWhere('day2a', $this->id)
            ->orWhere('day2b', $this->id)
            ->orWhere('day2c', $this->id)
            ->orWhere('day3a', $this->id)
            ->orWhere('day3b', $this->id)
            ->orWhere('day3c', $this->id)
            ->orWhere('day4a', $this->id)
            ->orWhere('day4b', $this->id)
            ->orWhere('day4c', $this->id)
            ->orWhere('day5a', $this->id)
            ->orWhere('day5b', $this->id)
            ->orWhere('day5c', $this->id);
    }

}
