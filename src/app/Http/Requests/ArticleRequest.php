<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $action = $this->route()->getActionMethod();

        // Regras para reprovação
        if ($action === 'reject') {
            return [
                'motivo_reprovacao' => 'required|string|max:1000',
            ];
        }

        // Regras padrão para criação/atualização
        return [
            'stock_symbol_id' => 'sometimes|exists:stock_symbols,id',
            'symbol' => 'sometimes|string|max:10',
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'status' => 'sometimes|in:pendente_revisao,aprovado,reprovado,publicado',
            'motivo_reprovacao' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'motivo_reprovacao.required' => 'O motivo da reprovação é obrigatório',
            'motivo_reprovacao.max' => 'O motivo da reprovação deve ter no máximo 1000 caracteres',
            'stock_symbol_id.exists' => 'A ação informada não existe',
            'status.in' => 'Status inválido. Valores permitidos: pendente_revisao, aprovado, reprovado, publicado',
        ];
    }
}

