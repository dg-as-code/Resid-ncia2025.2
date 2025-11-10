<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Agenda;

use App\Http\Requests\AgendaRequest;

class AgendaController extends Controller
{

    //listar
    public function index(){
        // $agenda = Agenda::all();   //select * from agendas.
        $agenda = Agenda::limit(10)->get();   //select * from agendas
        return response()->json($agenda, 200);
    }

    //criar
    public function criar(Request $request){
        $nome_recebido = $request->input('nome');
        $telefone_recebido = $request->input('telefone');


        //INICIO DAS VALIDAÇÕES MANUAIS
            //validar nome e obrigatorio
            if(!$nome_recebido){
                return response()->json(['erro' => 'Nome e obrigatorio'], 400);
            }

            //validar telefone e obrigatorio
            if(!$telefone_recebido){
                return response()->json(['erro' => 'Telefone e obrigatorio'], 400);
            }

            //validar telefone minimo 8 caracteres
            if(strlen($telefone_recebido) < 8){
                return response()->json(['erro' => 'Telefone invalido'], 400);
            }


            //validar nome unico
            $valida_nome = Agenda::where('nome', $nome_recebido)->first();

            if($valida_nome){
                return response()->json(['erro' => 'Nome ja cadastrado'], 400);
            }

            //validar telefone unico
            $valida_telefone = Agenda::where('telefone', $telefone_recebido)->first();
            if($valida_telefone){
                return response()->json(['erro' => 'Telefone ja cadastrado'], 400);
            }
        //FIM DAS VALIDAÇÕES MANUAIS


        $agenda = new Agenda();
        $agenda->nome = $nome_recebido;
        $agenda->telefone = $telefone_recebido;
        $agenda->save();

        return response()->json($agenda, 201);

    }

    //visualizar
    public function visualizar($id){
        $registro = Agenda::find($id);

        if(!$registro){
            return response()->json(['erro' => 'Registro nao encontrado'], 404);
        }

        return response()->json($registro, 200);

    }


    //atualizar
    public function atualizar(Request $request, $id){
        $nome_recebido = $request->input('nome');
        $telefone_recebido = $request->input('telefone');

        $agenda = Agenda::find($id);

        if(!$agenda){
            return response()->json(['erro' => 'Registro nao encontrado'], 404);
        }



        //INICIO DAS VALIDAÇÕES MANUAIS
            //validar nome e obrigatorio
            if(!$nome_recebido){
                return response()->json(['erro' => 'Nome e obrigatorio'], 400);
            }

            //validar telefone e obrigatorio
            if(!$telefone_recebido){
                return response()->json(['erro' => 'Telefone e obrigatorio'], 400);
            }

            //validar telefone minimo 8 caracteres
            if(strlen($telefone_recebido) < 8){
                return response()->json(['erro' => 'Telefone invalido'], 400);
            }


            //validar nome unico
            // $valida_nome = Agenda::where('nome', $nome_recebido)->first();

            // if($valida_nome){
            //     return response()->json(['erro' => 'Nome ja cadastrado'], 400);
            // }

            //validar telefone unico
            // $valida_telefone = Agenda::where('telefone', $telefone_recebido)->first();
            // if($valida_telefone){
            //     return response()->json(['erro' => 'Telefone ja cadastrado'], 400);
            // }
        //FIM DAS VALIDAÇÕES MANUAIS


        $agenda->nome = $nome_recebido;
        $agenda->telefone = $telefone_recebido;
        $agenda->save();

        return response()->json($agenda, 200);

    }

    //deletar
    public function deletar($id){
        $registro = Agenda::find($id);

        if(!$registro){
            return response()->json(['erro' => 'Registro nao encontrado'], 404);
        }

        $registro->delete();

        return response()->json(['msg' => 'Registro deletado com sucesso'], 200);
    }



}
