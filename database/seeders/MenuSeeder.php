<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Menu::factory(20)->create([
            'type' => 'main',
        ]);
        Menu::factory(10)->create([
            'type' => 'soup',
        ]);
        Menu::factory(15)->create([
            'type' => 'garnish',
        ]);
        Menu::factory(12)->create([
            'type' => 'dessert',
        ]);
        Menu::factory(8)->create([
            'type' => 'drink',
        ]);
    }
}