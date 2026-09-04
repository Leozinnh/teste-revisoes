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

    // CPF e telefone entram mascarados e saem só com dígitos; e-mail em
    // minúsculo e tudo sem espaços nas bordas. Se não normalizar aqui, o
    // unique deixa gravar o mesmo CPF com e sem máscara.
    protected function prepareForValidation(): void
    {
        $dados = [];

        if ($this->has('nome')) {
            $dados['nome'] = trim((string) $this->input('nome'));
        }
        if ($this->has('cpf')) {
            $dados['cpf'] = preg_replace('/\D/', '', (string) $this->input('cpf'));
        }
        if ($this->has('telefone')) {
            $dados['telefone'] = preg_replace('/\D/', '', (string) $this->input('telefone'));
        }
        if ($this->has('email')) {
            $dados['email'] = strtolower(trim((string) $this->input('email')));
        }

        if ($dados !== []) {
            $this->merge($dados);
        }
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'cpf' => [
                'required', 'string', 'digits:11',
                Rule::unique('pessoas', 'cpf')->ignore($this->route('pessoa')),
                function ($attribute, $value, $fail) {
                    if (!$this->cpfValido($value)) {
                        $fail('O CPF informado é inválido.');
                    }
                },
            ],
            'sexo' => ['required', 'in:M,F'],
            'data_nascimento' => ['required', 'date', 'before:today', 'after:1900-01-01'],
            'telefone' => ['required', 'regex:/^\d{10,11}$/'],
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
            'nome.max' => 'O nome deve ter no máximo 255 caracteres.',
            'cpf.required' => 'Informe o CPF.',
            'cpf.digits' => 'O CPF deve ter exatamente 11 dígitos.',
            'cpf.unique' => 'Já existe uma pessoa com este CPF.',
            'sexo.required' => 'Informe o sexo.',
            'sexo.in' => 'O sexo deve ser M (masculino) ou F (feminino).',
            'data_nascimento.required' => 'Informe a data de nascimento.',
            'data_nascimento.date' => 'Informe uma data de nascimento válida.',
            'data_nascimento.before' => 'A data de nascimento deve ser anterior a hoje.',
            'data_nascimento.after' => 'A data de nascimento deve ser posterior a 01/01/1900.',
            'telefone.required' => 'Informe o telefone.',
            'telefone.regex' => 'Informe um telefone válido com DDD (10 ou 11 dígitos).',
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.max' => 'O e-mail deve ter no máximo 255 caracteres.',
            'email.unique' => 'Já existe uma pessoa com este e-mail.',
        ];
    }

    // Valida os dígitos verificadores do CPF
    private function cpfValido(string $cpf): bool
    {
        // Todos os dígitos iguais passam no módulo 11, então são rejeitados à parte
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($posicao = 9; $posicao < 11; $posicao++) {
            $soma = 0;

            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cpf[$i] * (($posicao + 1) - $i);
            }

            $digitoEsperado = (($soma * 10) % 11) % 10;

            if ((int) $cpf[$posicao] !== $digitoEsperado) {
                return false;
            }
        }

        return true;
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
