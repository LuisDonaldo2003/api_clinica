<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $user->avatar = $user->avatar ? url('storage/' . $user->avatar) : null;
        $roles = $user->roles->pluck('name'); // Obtener los roles del usuario
        return response()->json(['data' => $user, 'roles' => $roles], 200);
    }
}
