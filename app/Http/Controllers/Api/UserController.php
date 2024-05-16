<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Throwable;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->get();

        $responseData = [];

        foreach ($users as $user) {
            $userRole = $user->roles->pluck('name')->toArray();

            if (empty($userRole)) {
                $userRole = null;
            } else {
                $userRole = $userRole[0];
            }

            if (!$user->profile_photo_url) {
                $name = trim(collect(explode(' ', $user->name))->map(function ($segment) {
                    return mb_substr($segment, 0, 1);
                })->join(' '));

                $photoApi = 'https://ui-avatars.com/api/?name='.urlencode($name);
            }

            $responseData[] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $userRole,
                'profile_photo_url' => $user->profile_photo_url ?? $photoApi
            ];
        }

        return response()->json($responseData, 200);
    }

    public function usersForRole(Role $role)
    {

        $users = User::role($role)->get();

        return response()->json($users, 200);
    }

    public function show(User $user)
    {
        $responseData = [];

        $userRole = $user->roles->pluck('name')->toArray();

            if (empty($userRole)) {
                $userRole = null;
            } else {
                $userRole = $userRole[0];
            }

        $responseData[] = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $userRole,
            'profile_photo_url' => $user->profile_photo_url
        ];

        return response()->json($responseData, 200);
    }

    public function createUser(Request $request)
    {
        try {
            // Validated
            $validateUser = Validator::make($request->all(),
            [
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
                'password' => 'required',
                'role' => 'exists:roles,name',
                'profile_photo_url' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Error de validación',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            if ($request->hasFile('profile_photo_url')) {
                $file = $request->file('profile_photo_url');

                $routeImage = Storage::disk('s3')->put('users', $file);

                $urlImage = Storage::disk('s3')->url($routeImage);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'profile_photo_url' => $urlImage ?? null,
            ]);

            if ($request->role) {
                $user->assignRole($request->role);
            }

            return response()->json([
                'status' => true,
                'message' => 'Usuario creado correctamente',
                'token' => $user->createToken("API TOKEN")->plainTextToken
            ], 200);

        } catch (Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function loginUser(Request $request)
    {
        try {
            // Validated
            $validateUser = Validator::make($request->all(),
            [
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Error de validación',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            if (!Auth::attempt($request->only(['email', 'password']))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email y contraseña incorrecto.'
                ], 401);
            }

            $user = User::where('email', $request->email)->first();

            return response()->json([
                'status' => true,
                'message' => 'Usuario logueado correctamente',
                'token' => $user->createToken("API TOKEN")->plainTextToken
            ], 200);

        } catch (Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function logout()
    {
        $user = Auth::user();
        $user->tokens()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Tokens de ' . $user->name . ' revocados correctamente'
        ], 200);
    }

    public function testToken()
    {
        $user = Auth::user();

        return response()->json([
            'status' => true,
            'message' => 'Token de ' . $user->name,
            'token' => 'token OK'
        ]);
    }

    public function user()
    {
        $user = Auth::user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roles->first()->name ?? null,
            'profile_photo_url' => $user->profile_photo_url,
        ]);
    }

    public function update(Request $request, User $user)
    {
        // Validated
        $validateUser = Validator::make($request->all(),
        [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'required',
            'role' => 'exists:roles,name',
            'profile_photo_url' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validateUser->errors()
            ], 401);
        }

        if ($request->hasFile('profile_photo_url')) {
            $file = $request->file('profile_photo_url');

            $routeImage = Storage::disk('s3')->put('users', $file);

            $urlImage = Storage::disk('s3')->url($routeImage);

            if ($user->profile_photo_url) {
                $path = parse_url($user->profile_photo_url);
                $filename = basename($path['path']);

                Storage::disk('s3')->delete('users/' . $filename);
            }
        }

        $user->fill([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'profile_photo_url' => $urlImage ?? null,
        ]);

        $user->save();

        return response()->json([
            'status' => true,
            'message' => "Usuario $user->name editado correctamente"
        ], 200);
    }

    public function destroy(User $user)
    {
        if ($user->profile_photo_url) {
            $path = parse_url($user->profile_photo_url);
            $filename = basename($path['path']);

            Storage::disk('s3')->delete('users/' . $filename);
        }

        $user->delete();

        return response()->json([
            'status' => false,
            'message' => "Usuario $user->name borrado correctamente"
        ], 200);
    }
}
