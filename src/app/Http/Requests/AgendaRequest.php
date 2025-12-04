<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Request de validação para Agenda de Contatos
 * 
 * NOTA: Este request não faz parte do fluxo principal de agentes financeiros.
 * É usado pelo AgendaController para validar dados de contatos.
 */
class AgendaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }


    /**
     * Mensagens de validação personalizadas
     * 
     * @return array
     */
    public function messages(): array
    {
        return [
            'required' => 'O :attribute é obrigatório.',
            'max' => 'O :attribute ultrapassa a quantidade máxima de caracteres permitida.',
            'min' => 'O :attribute não tem a quantidade mínima de caracteres permitida.',
            'unique' => 'O :attribute já está cadastrado.',
            'email' => 'O :attribute é inválido.',
        ];
    }

    /**
     * Tratamento de falhas de validação
     * 
     * Retorna JSON com primeira mensagem de erro
     * 
     * @param Validator $validator
     * @return void
     * @throws HttpResponseException
     */
    public function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->messages()->first(),
        ], 422));
    }

    /**
     * Regras de validação
     * 
     * @return array
     */
    public function rules(): array
    {
        $id = $this->route('id'); // Para atualização, ignora o próprio registro
        
        return [
            'nome' => 'required|string|max:255',
            'telefone' => 'required|string|min:8|max:15',
            'email' => 'required|email|unique:agendas,email' . ($id ? ',' . $id : ''),
        ];
    }
}
