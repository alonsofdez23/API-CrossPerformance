<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clase;
use App\Models\Entreno;
use App\Models\User;
use App\Notifications\DeleteClase;
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
        //dd($request->fecha_hora);

        // Validated
        $validateClase = Validator::make($request->all(),
        [
            'monitor_id' => 'required|exists:users,id',
            'entreno_id' => 'nullable|exists:entrenos,id',
            'fecha_hora' => 'required|date',
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
        $responseData = [
            'id' => $clase->id,
            'monitor_id' => $clase->monitor_id,
            'entreno_id' => $clase->entreno_id,
            'fecha_hora' => $clase->fecha_hora,
            'vacantes' => $clase->vacantes,
        ];

        //dd($clase->fecha_hora);
        return response()->json($responseData, 200);
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
                'message' => "Clase con atletas inscritos. No puede borrarse.",
                'atletas' => $clase->atletas
            ], 401);
        }

        $clase->delete();

        return response()->json([
            'status' => false,
            'message' => "Clase borrada correctamente"
        ], 200);
    }

    public function destroyMail(Clase $clase)
    {
        if ($clase->atletas->isNotEmpty()) {
            try {
                // Envío notifiaciones por email
                foreach($clase->atletas as $atleta) {
                    $atleta->notify(new DeleteClase($clase->fecha_hora, $atleta));
                }

                $clase->atletas()->detach();
                $clase->delete();

                return response()->json([
                    'status' => false,
                    'message' => "Clase con atletas inscritos eliminada, se les notifico por email",
                    'atletas' => $clase->atletas
                ], 200);
            } catch (\Exception $e) {
                // Aunque ocurra un error, asegúrate de eliminar la clase.
                //$clase->delete();

                return response()->json([
                    'status' => false,
                    'message' => "Ocurrió un error al enviar las notificaciones por email"
                ], 500);
            }
        } else if ($clase->atletas->isEmpty()) {
            try {
                $clase->delete();

                return response()->json([
                    'status' => false,
                    'message' => "Clase eliminada correctamente",
                ], 200);
            } catch (\Exception $e) {
                // Aunque ocurra un error, asegúrate de eliminar la clase.
                //$clase->delete();

                return response()->json([
                    'status' => false,
                    'message' => "Ocurrió un error al enviar las notificaciones por email"
                ], 500);
            }
        }
    }

    // Atletas
    public function join(Clase $clase)
    {
        $user = Auth::user();

        if (!$clase->atletas()->where('id', $user->id)->exists()) {
            $clase->atletas()->attach($user->id);

            $clase->vacantes = $clase->vacantes - 1;
            $clase->save();

            return response()->json([
                'status' => true,
                'message' => "Atleta $user->name inscrito en la clase $clase->id a las $clase->fecha_hora"
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => "Atleta $user->name ya está inscrito en la clase $clase->id a las $clase->fecha_hora"
        ], 401);

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
        $user = Auth::user();

        if ($clase->atletas()->where('id', $user->id)->exists()) {
            $clase->atletas()->detach($user->id);

            $clase->vacantes = $clase->vacantes + 1;
            $clase->save();

            return response()->json([
                'status' => true,
                'message' => "Atleta $user->name borrado en la clase $clase->id a las $clase->fecha_hora"
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => "Atleta $user->name no está inscrito en la clase $clase->id a las $clase->fecha_hora"
        ], 401);
    }

    public function joinAtleta(User $atleta, Clase $clase)
    {
        if (!$clase->atletas()->where('id', $atleta->id)->exists()) {
            $clase->atletas()->attach($atleta->id);

            $clase->vacantes = $clase->vacantes - 1;
            $clase->save();

            return response()->json([
                'status' => true,
                'message' => "Atleta $atleta->name inscrito en la clase $clase->id a las $clase->fecha_hora"
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => "Atleta $atleta->name ya está inscrito en la clase $clase->id a las $clase->fecha_hora"
        ], 401);
    }

    public function leaveAtleta(User $atleta, Clase $clase)
    {
        if ($clase->atletas()->where('id', $atleta->id)->exists()) {
            $clase->atletas()->detach($atleta->id);

            $clase->vacantes = $clase->vacantes + 1;
            $clase->save();

            return response()->json([
                'status' => true,
                'message' => "Atleta $atleta->name borrado en la clase $clase->id a las $clase->fecha_hora"
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => "Atleta $atleta->name no está inscrito en la clase $clase->id a las $clase->fecha_hora"
        ], 401);
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
