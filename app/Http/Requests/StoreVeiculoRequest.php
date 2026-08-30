<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Valida veículo no cadastro e na edição.
 * Na edição, a placa ignora a do próprio veículo (mesma regra do CPF/email nas pessoas).
 */
class StoreVeiculoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pessoa_id' => ['required', 'exists:pessoas,id'],
            'marca' => ['required', 'string', 'max:100'],
            'modelo' => ['required', 'string', 'max:100'],
            'ano' => ['required', 'integer', 'between:1900,2100'],
            'placa' => [
                'required', 'string', 'max:8',
                Rule::unique('veiculos', 'placa')->ignore($this->route('veiculo')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'pessoa_id.required' => 'Selecione o proprietário do veículo.',
            'pessoa_id.exists' => 'O proprietário selecionado não existe.',
            'marca.required' => 'Informe a marca.',
            'modelo.required' => 'Informe o modelo.',
            'ano.required' => 'Informe o ano.',
            'ano.between' => 'O ano deve estar entre 1900 e 2100.',
            'placa.required' => 'Informe a placa.',
            'placa.unique' => 'Já existe um veículo com esta placa.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Existem dados inválidos no formulário.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
