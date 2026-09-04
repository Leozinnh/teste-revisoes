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

    // Placa em maiúscula e sem hífen antes de validar — o unique do banco
    // diferencia maiúsculas.
    protected function prepareForValidation(): void
    {
        $dados = [];

        if ($this->has('marca')) {
            $dados['marca'] = trim((string) $this->input('marca'));
        }
        if ($this->has('modelo')) {
            $dados['modelo'] = trim((string) $this->input('modelo'));
        }
        if ($this->has('placa')) {
            $dados['placa'] = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $this->input('placa')));
        }

        if ($dados !== []) {
            $this->merge($dados);
        }
    }

    public function rules(): array
    {
        return [
            'pessoa_id' => ['required', 'exists:pessoas,id'],
            'marca' => ['required', 'string', 'max:100'],
            'modelo' => ['required', 'string', 'max:100'],
            'ano' => ['required', 'integer', 'between:1900,2100'],
            // Padrão antigo (ABC1234) ou Mercosul (ABC1D23), sem hífen
            'placa' => [
                'required', 'string',
                'regex:/^([A-Z]{3}\d{4}|[A-Z]{3}\d[A-Z]\d{2})$/',
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
            'marca.max' => 'A marca deve ter no máximo 100 caracteres.',
            'modelo.required' => 'Informe o modelo.',
            'modelo.max' => 'O modelo deve ter no máximo 100 caracteres.',
            'ano.required' => 'Informe o ano.',
            'ano.integer' => 'O ano deve ser um número inteiro.',
            'ano.between' => 'O ano deve estar entre 1900 e 2100.',
            'placa.required' => 'Informe a placa.',
            'placa.regex' => 'Formato de placa inválido. Use o padrão antigo (ABC1234) ou o Mercosul (ABC1D23).',
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
