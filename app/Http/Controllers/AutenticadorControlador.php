<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Auth;
use App\Events\EventNovoRegistro;

class AutenticadorControlador extends Controller
{
    public function registro(Request $request){
         $request->validate([
            'name'      => 'required|string',
            'email'     => 'required|string|email|unique:users',
            'password'  => 'required|string|confirmed'
         ]);

         $user = new User([
             'name'     => $request->name,
             'email'    => $request->email,
             'password' => bcrypt($request->password),
             'token'    => str_random(60)
         ]);

         $user->save();

         event(new eventNovoRegistro($user));

         return response()->json([
             'res' => 'Usuario criado com sucesso'
         ], 201);
    }

    public function login(Request $request){
        $request->validate([
            'email'     => 'required|string|email',
            'password'  => 'required|string'
        ]);

        $credenciais = [
            'email' => $request->email,
            'password' => $request->password,
            'acrive' => '1'
        ];

        if(!Auth::attempt($credenciais)){
            return response()->json(['res' => 'Acesso Negado'], 401);
        }

        $user = $request->user();
        $token = $user->createToken('Token de Acesso')->accessToken;

        return response()->json([
            'token' => $token
        ], 200);
    }

    public function ativarRegistro($id, $token){
        $user = User::find($id);

        if($user){
            if($user->token == $token){
                $user->active = true;
                $user->token = '';
                $user->save();
                return view('emails.registroAtivo');
            }
        }

        return view('emails.registroErro');
    }

    public function logout(Request $request){
        $request->user()->token()->revoke();
        return response()->json([
            'res' => 'Deslogado com sucesso'
        ]);
    }
}
