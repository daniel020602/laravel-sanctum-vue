<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\Sub;

class SubsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function store(Request $request)
    {
        // Authorize via the SubPolicy's store() method
        Gate::authorize('store', Sub::class);

        return "teszt";
    }
}
