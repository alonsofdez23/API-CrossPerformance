<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clase;
use App\Models\Entreno;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Validator;

class ClaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clases = Clase::with('atletas')
        ->get()
        ->sortBy('fecha_hora');

        // $clases = Clase::select('monitor_id', 'entreno_id', 'fecha_hora', 'vacantes')
        //     ->get()
        //     ->sortBy('fecha_hora');

        return response()->json($clases, 200);


    }

    public function indexDate($date)
    {
        $dateFormated = Carbon::parse($date);

        $clases = Clase::whereDate('fecha_hora', '=', $dateFormated)
            ->with('atletas')
            ->get()
            ->sortBy('fecha_hora');

        $responseData = [];

        foreach ($clases as $clase) {
            $responseData[] = [
                'id' => $clase->id,
                'monitor' => User::find($clase->monitor_id),
                'entreno' => Entreno::find($clase->entreno_id),
                'fecha_hora' => $clase->fecha_hora,
                'vacantes' => $clase->vacantes,
                'atletas' => $clase->atletas
            ];
        }

        return response()->json($responseData, 200);
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

    // Atletas
    public function join(Clase $clase)
    {
        $clase->atletas()->attach(Auth::id());

        $clase->vacantes = $clase->vacantes - 1;
        $clase->save();

        return response()->json([
            'status' => true,
            'message' => "Inscrito correctamente a la clase"
        ], 200);
    }

    public function leave(Clase $clase)
    {
        $clase->atletas()->detach(Auth::id());

        $clase->vacantes = $clase->vacantes + 1;
        $clase->save();

        return response()->json([
            'status' => true,
            'message' => "Borrado correctamente de la clase"
        ], 200);
    }

    // Entrenos en clases
    public function addEntrenoUpdate(Request $request, Clase $clase)
    {
        $clase->entreno_id = $request->entreno_id;
        $clase->save();

        $entreno = Entreno::find($request->entreno_id);

        return response()->json([
            'status' => true,
            'message' => "Entreno $entreno->denominacion asignado a la clase"
        ], 200);
    }

    public function deleteEntrenoUpdate(Clase $clase)
    {
        if (!$clase->entreno_id) {
            return response()->json([
                'status' => false,
                'message' => "Clase sin entreno asignado",
            ], 401);
        } elseif ($clase === null) {
            return response()->json([
                'status' => false,
                'message' => "La clase no existe",
            ], 401);

        }

        $clase->entreno_id = null;
        $clase->save();

        return response()->json([
            'status' => true,
            'message' => "Entreno borrado de la clase"
        ], 200);
    }
}
