<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
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

            $responseData[] = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $userRole,
                'profile_photo_url' => $user->profile_photo_url
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
                'token' => $user->createToken("API TOKEN")->plainTextToken,
                'user' => $user
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
        ]);

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Error de validación',
                'errors' => $validateUser->errors()
            ], 401);
        }

        if (!empty(request()->input('profile_photo_url'))) {
            $base64Image = request()->input('profile_photo_url');

            if (!$tmpFileObject = $this->validateBase64($base64Image, ['png', 'jpg', 'jpeg', 'gif'])) {
                return response()->json([
                    'error' => 'Formato de imagen invalido.'
                ], 415);
            }

            $storedFilePath = $this->storeFile($tmpFileObject);

            if(!$storedFilePath) {
                return response()->json([
                    'error' => 'Algo salió mal, el archivo no ha sido guardado.'
                ], 500);
            }

            if ($user->profile_photo_url) {
                $path = parse_url($user->profile_photo_url);
                $filename = basename($path['path']);

                Storage::disk('s3')->delete('users/' . $filename);
            }

            $urlImage = url(Storage::url($storedFilePath));

            $user->fill([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'profile_photo_url' => $urlImage,
            ]);

            $user->save();

            return response()->json([
                'status' => true,
                'message' => "Usuario $user->name editado correctamente"
            ], 200);
        }

        $user->fill([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
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

    /**
     * Validate base64 content.
     *
     * @see https://stackoverflow.com/a/52914093
     */
    private function validateBase64(string $base64data, array $allowedMimeTypes)
    {
        // strip out data URI scheme information (see RFC 2397)
        if (str_contains($base64data, ';base64')) {
            list(, $base64data) = explode(';', $base64data);
            list(, $base64data) = explode(',', $base64data);
        }

        // strict mode filters for non-base64 alphabet characters
        if (base64_decode($base64data, true) === false) {
            return false;
        }

        // decoding and then re-encoding should not change the data
        if (base64_encode(base64_decode($base64data)) !== $base64data) {
            return false;
        }

        $fileBinaryData = base64_decode($base64data);

        // temporarily store the decoded data on the filesystem to be able to use it later on
        $tmpFileName = tempnam(sys_get_temp_dir(), 'medialibrary');
        file_put_contents($tmpFileName, $fileBinaryData);

        $tmpFileObject = new File($tmpFileName);

        // guard against invalid mime types
        $allowedMimeTypes = Arr::flatten($allowedMimeTypes);

        // if there are no allowed mime types, then any type should be ok
        if (empty($allowedMimeTypes)) {
            return $tmpFileObject;
        }

        // Check the mime types
        $validation = Validator::make(
            ['file' => $tmpFileObject],
            ['file' => 'mimes:' . implode(',', $allowedMimeTypes)]
        );

        if($validation->fails()) {
            return false;
        }

        return $tmpFileObject;
    }

    /**
     * Store the temporary file object
     */
    private function storeFile(File $tmpFileObject)
    {
        $tmpFileObjectPathName = $tmpFileObject->getPathname();

        $file = new UploadedFile(
            $tmpFileObjectPathName,
            $tmpFileObject->getFilename(),
            $tmpFileObject->getMimeType(),
            0,
            true
        );

        $storedFile = $file->store('users', ['disk' => 's3']);

        unlink($tmpFileObjectPathName); // delete temp file

        return $storedFile;
    }

    public function testBase64()
    {
        if (request()->isJson() && !empty(request()->input('image'))) {
            $base64Image = request()->input('image');

            if (!$tmpFileObject = $this->validateBase64($base64Image, ['png', 'jpg', 'jpeg', 'gif'])) {
                return response()->json([
                    'error' => 'Formato de imagen invalido.'
                ], 415);
            }

            $storedFilePath = $this->storeFile($tmpFileObject);

            if(!$storedFilePath) {
                return response()->json([
                    'error' => 'Algo salió mal, el archivo no ha sido guardado.'
                ], 500);
            }

            return response()->json([
                'image_url' => url(Storage::url($storedFilePath)),
            ]);
        }

        return response()->json([
            'error' => 'Petición inválida.'
        ], 400);
    }
}
