<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreRevisaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'veiculo_id' => ['required', 'exists:veiculos,id'],
            'data_revisao' => ['required', 'date'],
            'quilometragem' => ['required', 'integer', 'min:0'],
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'numeric', 'min:0'],
            'observacoes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'veiculo_id.required' => 'Selecione o veículo.',
            'veiculo_id.exists' => 'O veículo selecionado não existe.',
            'data_revisao.required' => 'Informe a data da revisão.',
            'quilometragem.required' => 'Informe a quilometragem.',
            'quilometragem.min' => 'A quilometragem não pode ser negativa.',
            'descricao.required' => 'Informe a descrição da revisão.',
            'valor.required' => 'Informe o valor.',
            'valor.min' => 'O valor não pode ser negativo.',
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
