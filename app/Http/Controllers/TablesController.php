<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Table;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\TableRequest;

class TablesController extends Controller
{
    function __construct()
    {
        $this->middleware('auth:sanctum')->except(['show', 'index']);
    }
    public function index()
    {
        return Table::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TableRequest $request)
    {
        $this->authorize('admin', Table::class);

        $table = Table::create($request->validated());

        return response()->json($table, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Table::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TableRequest $request, string $id)
    {
        $this->authorize('admin', Table::class);

        $table = Table::findOrFail($id);
        $table->update($request->validated());

        return response()->json($table, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->authorize('admin', Table::class);

        $table = Table::findOrFail($id);
        $table->delete();

        return response()->json(['message' => 'Table deleted successfully'], 200);
    }
}
