<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Agenda;
use App\Http\Requests\AgendaRequest;
use Illuminate\Support\Facades\Log;

/**
 * Controller para gerenciar agenda de contatos
 * 
 * NOTA: Este controller não faz parte do fluxo principal de agentes financeiros
 * (Júlia → Pedro → Key). É um módulo separado para gerenciamento de contatos.
 * 
 * O fluxo principal de análise financeira é gerenciado por:
 * - OrchestrationController: Orquestração completa (Júlia → Pedro → Key)
 * - AgentController: Execução individual de agentes
 * - AnalysisController: Análises via Jobs (fluxo alternativo)
 */
class AgendaController extends Controller
{

    /**
     * Lista contatos da agenda
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $limit = min((int)$request->get('limit', 10), 100); // Máximo 100 itens
            $agenda = Agenda::orderBy('created_at', 'desc')->limit($limit)->get();
            
            return response()->json([
                'success' => true,
                'data' => $agenda,
                'count' => $agenda->count(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('AgendaController: Erro ao listar contatos', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar contatos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cria um novo contato na agenda
     * 
     * Usa AgendaRequest para validação automática.
     * 
     * @param AgendaRequest $request
     * @return JsonResponse
     */
    public function criar(AgendaRequest $request): JsonResponse
    {
        try {
            $agenda = Agenda::create([
                'nome' => $request->input('nome'),
                'telefone' => $request->input('telefone'),
                'email' => $request->input('email'),
            ]);

            Log::info('AgendaController: Contato criado', [
                'id' => $agenda->id,
                'nome' => $agenda->nome,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contato criado com sucesso',
                'data' => $agenda,
            ], 201);
        } catch (\Exception $e) {
            Log::error('AgendaController: Erro ao criar contato', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar contato',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Visualiza um contato específico
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function visualizar(int $id): JsonResponse
    {
        try {
            $registro = Agenda::find($id);

            if (!$registro) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registro não encontrado',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $registro,
            ], 200);
        } catch (\Exception $e) {
            Log::error('AgendaController: Erro ao visualizar contato', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao visualizar contato',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Atualiza um contato existente
     * 
     * @param AgendaRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function atualizar(AgendaRequest $request, int $id): JsonResponse
    {
        try {
            $agenda = Agenda::find($id);

            if (!$agenda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registro não encontrado',
                ], 404);
            }

            $agenda->update([
                'nome' => $request->input('nome'),
                'telefone' => $request->input('telefone'),
                'email' => $request->input('email'),
            ]);

            Log::info('AgendaController: Contato atualizado', [
                'id' => $agenda->id,
                'nome' => $agenda->nome,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contato atualizado com sucesso',
                'data' => $agenda,
            ], 200);
        } catch (\Exception $e) {
            Log::error('AgendaController: Erro ao atualizar contato', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar contato',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deleta um contato da agenda
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function deletar(int $id): JsonResponse
    {
        try {
            $registro = Agenda::find($id);

            if (!$registro) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registro não encontrado',
                ], 404);
            }

            $registro->delete();

            Log::info('AgendaController: Contato deletado', [
                'id' => $id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registro deletado com sucesso',
            ], 200);
        } catch (\Exception $e) {
            Log::error('AgendaController: Erro ao deletar contato', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar contato',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



}
