<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Valida pessoa no cadastro e na edição.
 * O ignore() impede que o CPF/email únicos bloqueiem a edição da própria pessoa.
 */
class StorePessoaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'cpf' => [
                'required', 'string', 'max:14',
                Rule::unique('pessoas', 'cpf')->ignore($this->route('pessoa')),
            ],
            'sexo' => ['required', 'in:M,F'],
            'data_nascimento' => ['required', 'date', 'before:today'],
            'telefone' => ['required', 'string', 'max:20'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('pessoas', 'email')->ignore($this->route('pessoa')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Informe o nome.',
            'cpf.required' => 'Informe o CPF.',
            'cpf.unique' => 'Já existe uma pessoa com este CPF.',
            'sexo.in' => 'O sexo deve ser M (masculino) ou F (feminino).',
            'data_nascimento.required' => 'Informe a data de nascimento.',
            'data_nascimento.before' => 'A data de nascimento deve ser anterior a hoje.',
            'telefone.required' => 'Informe o telefone.',
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Já existe uma pessoa com este e-mail.',
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
