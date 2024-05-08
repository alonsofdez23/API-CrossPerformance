<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entreno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EntrenoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $entrenos = Entreno::select('id', 'denominacion', 'entreno')->get()->sortBy('id');

        return response()->json($entrenos, 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validated
        $validateEntreno = Validator::make($request->all(),
        [
            'denominacion' => 'required|unique:entrenos,denominacion',
            'entreno' => 'required'
        ]);

        if ($validateEntreno->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validateEntreno->errors()
            ], 401);
        }

        Entreno::create($request->all());

        return response()->json([
            'status' => true,
            'message' => "Entreno $request->denominacion creado correctamente"
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Entreno $entreno)
    {
        return response()->json($entreno->only(['denominacion', 'entreno']), 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Entreno $entreno)
    {

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Entreno $entreno)
    {
        // Validated
        $validateEntreno = Validator::make($request->all(),
        [
            'denominacion' => 'required|unique:entrenos,denominacion,' . $entreno->id,
            'entreno' => 'required'
        ]);

        if ($validateEntreno->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validateEntreno->errors()
            ], 401);
        }

        $entreno->fill($request->all());
        $entreno->save();

        return response()->json([
            'status' => true,
            'message' => "Entreno $entreno->denominacion editado correctamente"
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Entreno $entreno)
    {
        if ($entreno->clases->isNotEmpty()) {
            return response()->json([
                'status' => false,
                'message' => "Entreno $entreno->denominacion asignado a clase. No puede borrarse"
            ], 401);
        }

        $entreno->delete();

        return response()->json([
            'status' => false,
            'message' => "Entreno $entreno->denominacion borrado correctamente"
        ], 200);
    }
}
