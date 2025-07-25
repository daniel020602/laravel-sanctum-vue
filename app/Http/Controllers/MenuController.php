<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\MenuRequest;
use App\Mail\ReservationCode;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class MenuController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }
    public function index()
    {
        //Mail::to('asd@asd.com')->send(new ReservationCode());
        return Menu::all();
        
    }
    public function store(MenuRequest $request)
    {
        
        $this->authorize('admin', Menu::class);
        $validated = $request->validated();
        $menu = Menu::create($validated);
        return response()->json([
            'message' => 'Menu created successfully',
            'data' => $menu
        ], 201);
    }
    public function show(Menu $menu)
    {
        return response()->json([
            'message' => 'Menu retrieved successfully',
            'data' => $menu
        ]);
    }
    public function update(MenuRequest $request, Menu $menu)
    {
        // Authorize via the MenuPolicy's update() method
        Gate::authorize('admin', Auth::user());
        $validated = $request->validated();
        $menu->update($validated);
        return response()->json([
            'message' => 'Menu updated successfully',
            'data' => $menu
        ]);
    }
    public function destroy(Menu $menu)
    {
        // Authorize via the MenuPolicy's delete() method
        Gate::authorize('admin', Auth::user());
        $menu->delete();
        return response()->json([
            'message' => 'Menu deleted successfully'
        ]);
    }

}
