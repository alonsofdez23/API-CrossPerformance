<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function assignRole(User $user, Role $role)
    {
        $user->assignRole($role->name);

        return response()->json([
            'status' => true,
            'message' => "Rol $role->name asignado a $user->name",
        ], 200);
    }

    public function revokeRole(User $user)
    {
        if ($user->roles->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => "$user->name no tiene roles asignados",
            ], 200);
        }

        $user->roles()->detach();

        return response()->json([
            'status' => true,
            'message' => "Roles de $user->name borrados correctamente",
        ], 200);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        //
    }
}
