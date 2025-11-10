<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockSymbolRequest extends FormRequest
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
        $symbolId = $this->route('id') ?? $this->route('symbol');

        return [
            'symbol' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                'string',
                'max:10',
                'unique:stock_symbols,symbol,' . $symbolId,
            ],
            'company_name' => [
                $this->isMethod('post') ? 'required' : 'sometimes',
                'string',
                'max:255',
            ],
            'is_active' => 'sometimes|boolean',
            'is_default' => 'sometimes|boolean',
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
            'symbol.required' => 'O símbolo da ação é obrigatório',
            'symbol.unique' => 'Este símbolo já está cadastrado',
            'symbol.max' => 'O símbolo deve ter no máximo 10 caracteres',
            'company_name.required' => 'O nome da empresa é obrigatório',
            'company_name.max' => 'O nome da empresa deve ter no máximo 255 caracteres',
        ];
    }
}

