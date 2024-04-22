<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clases = Clase::select('monitor_id', 'entreno_id', 'fecha_hora', 'vacantes')
            ->get()
            ->sortBy('fecha_hora');

        return response()->json($clases, 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validated
        $validateClase = Validator::make($request->all(),
        [
            'monitor_id' => 'required|exists:users,id',
            'entreno_id' => 'nullable|exists:entrenos,id',
            'fecha_hora' => 'required|date|date_format:Y-m-d H:i:s',
            'vacantes' => 'required|integer|between:1,99'
        ]);

        if ($validateClase->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validateClase->errors()
            ], 401);
        }

        Clase::create($request->all());

        return response()->json([
            'status' => true,
            'message' => "Clase creada correctamente"
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Clase $clase)
    {
        return response()->json($clase->only(['monitor_id', 'entreno_id', 'fecha_hora', 'vacantes']), 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Clase $clase)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Clase $clase)
    {
        // Validated
        $validateClase = Validator::make($request->all(),
        [
            'monitor_id' => 'required|exists:users,id',
            'entreno_id' => 'nullable|exists:entrenos,id',
            'fecha_hora' => 'required|date|date_format:Y-m-d H:i:s',
            'vacantes' => 'required|integer|between:1,99'
        ]);

        if ($validateClase->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validateClase->errors()
            ], 401);
        }

        $clase->fill($request->all());
        $clase->save();

        return response()->json([
            'status' => true,
            'message' => "Clase editada correctamente"
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Clase $clase)
    {
        if ($clase->atletas->isNotEmpty()) {
            return response()->json([
                'status' => false,
                'message' => "Clase con atletas apuntados. No puede borrarse",
                'atletas' => $clase->atletas
            ], 401);
        }

        $clase->delete();

        return response()->json([
            'status' => false,
            'message' => "Clase borrada correctamente"
        ], 200);
    }
}
