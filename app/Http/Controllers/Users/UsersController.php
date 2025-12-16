<?php

namespace App\Http\Controllers\Users;

use App\Models\Users;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;

class UsersController extends Controller
{

    /**
     * Show the form for creating a new resource.
     */
    public function create(UserSaveRequest $request)
    {
        $data = $request->all();
        $data['email'] = strtolower($data['email']);
        $data['password'] = Hash::make($data['password']);
        $user = new User();
        $user->fill($data);

        $userService = new UserService();
        $userService->generateExtId($user);
        $user->last_activity = null;
        $user->terms_agreement_date = null;

        $user->save();

        $role = Role::where('name', 'Aluno')->first();

        $user->roles()->attach($role->id);
    }

    public function login(UserLoginRequest $request)
    {
        $credentials = [
            'email' => strtolower($request->email),
            'password' => $request->password,
        ];

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Credenciais inválidas'
            ], 401);
        }

        $user = Auth::user();
        $token = $this->generateJwtToken($user);
        return response()->json(['token' => $token], 200);
    }

    /**
     * Generate a JWT Token with custom claims to Login, impersonificate and depersonificate
     *
     * @param array $payload
     * @return string $token
     */
    public static function generateJwtToken($user = null)
    {
        $payload = [
            'name' => $user->name,
            'email' => $user->email,
            'external_id' => $user->external_id,
        ];

        return JWTAuth::claims($payload)->fromUser($user);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store()
    {
        dd('hey');

    }

    /**
     * Display the specified resource.
     */
    public function show(Users $users)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Users $users)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Users $users)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Users $users)
    {
        //
    }
}
