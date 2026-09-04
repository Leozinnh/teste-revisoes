<?php

namespace App\Http\Requests;

use App\Models\Revisao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Valida revisão no cadastro e na edição (mesmo request atende store e update).
 */
class StoreRevisaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Descrição e observações sem espaços nas bordas; observação vazia vira null
    protected function prepareForValidation(): void
    {
        $dados = [];

        if ($this->has('descricao')) {
            $dados['descricao'] = trim((string) $this->input('descricao'));
        }
        if ($this->has('observacoes')) {
            $observacoes = trim((string) $this->input('observacoes'));
            $dados['observacoes'] = $observacoes === '' ? null : $observacoes;
        }

        if ($dados !== []) {
            $this->merge($dados);
        }
    }

    public function rules(): array
    {
        return [
            'veiculo_id' => ['required', 'exists:veiculos,id'],
            'data_revisao' => ['required', 'date', 'after:1900-01-01', 'before_or_equal:today'],
            'quilometragem' => ['required', 'integer', 'min:0', 'max:2000000'],
            'descricao' => ['required', 'string', 'max:255'],
            // decimal(10,2) na coluna: teto de R$ 99.999.999,99 e até 2 casas decimais
            'valor' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'observacoes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        // O hodômetro não volta: a km não pode ser menor que a maior já
        // registrada do veículo. Na edição, a própria revisão fica fora da
        // conta — senão não dava pra corrigir um erro de digitação pra baixo.
        $validator->after(function (Validator $validator) {
            // Só avalia quando os campos da regra passaram na validação básica
            if ($validator->errors()->has('veiculo_id') || $validator->errors()->has('quilometragem')) {
                return;
            }

            $consulta = Revisao::where('veiculo_id', (int) $this->input('veiculo_id'));

            if ($this->route('revisao')) {
                $consulta->whereKeyNot((int) $this->route('revisao'));
            }

            $maiorKm = $consulta->max('quilometragem');

            if ($maiorKm !== null && (int) $this->input('quilometragem') < (int) $maiorKm) {
                $validator->errors()->add(
                    'quilometragem',
                    'A quilometragem não pode ser menor que a última registrada para este veículo ('
                    . number_format((int) $maiorKm, 0, ',', '.') . ' km).'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'veiculo_id.required' => 'Selecione o veículo.',
            'veiculo_id.exists' => 'O veículo selecionado não existe.',
            'data_revisao.required' => 'Informe a data da revisão.',
            'data_revisao.date' => 'Informe uma data de revisão válida.',
            'data_revisao.after' => 'A data da revisão deve ser posterior a 01/01/1900.',
            'data_revisao.before_or_equal' => 'A data da revisão não pode ser no futuro.',
            'quilometragem.required' => 'Informe a quilometragem.',
            'quilometragem.integer' => 'A quilometragem deve ser um número inteiro.',
            'quilometragem.min' => 'A quilometragem não pode ser negativa.',
            'quilometragem.max' => 'A quilometragem não pode ultrapassar 2.000.000 km.',
            'descricao.required' => 'Informe a descrição da revisão.',
            'descricao.max' => 'A descrição deve ter no máximo 255 caracteres.',
            'valor.required' => 'Informe o valor.',
            'valor.numeric' => 'O valor deve ser um número.',
            'valor.min' => 'O valor não pode ser negativo.',
            'valor.max' => 'O valor não pode ultrapassar R$ 99.999.999,99.',
            'valor.decimal' => 'O valor deve ter no máximo 2 casas decimais.',
            'observacoes.string' => 'As observações devem ser um texto.',
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
