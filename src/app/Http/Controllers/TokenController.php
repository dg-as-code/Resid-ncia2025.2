<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgendRequest;
use App\Http\Requests\UpdateAgendRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;


use Firebase\JWT\JWT;

class TokenController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
    //    $senha=  Hash::make("001205");
    //    dd( $senha);

        $segredo='$2y$10$IOf2BnUwcrE8.IxT768QX.UGp/NTMVikAq29i3O3CMVrZiTDOuhtC';
        $user = "Luiz";

        $usuario = $request->input('usuario');
        $senha = $request->input('senha');

        if(!Hash::check($senha, $segredo) || $usuario !== $user){
            return response()->json([
                "message" => "Usuario ou senha invalido"
            ]);

        }

        $key = env('JWT_KEY');
        $dados = array("data" => date("Y-m-d H:i:s"),"Nome" => "Luiz Siqueira", "email" => "lsiqueira@sjcc.com.br");
        $token = JWT::encode($dados, $key,'HS256' );

        return response()->json([
            "token" => $token,
        ]);
    }

}

