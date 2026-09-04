<?php

namespace Database\Seeders;

use App\Models\Pessoa;
use App\Models\Revisao;
use App\Models\Veiculo;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

/**
 * Dados fictícios para demonstração.
 * Use com: php artisan migrate:fresh --seed
 */
class DatabaseSeeder extends Seeder
{
    // Modelos por marca, para o par marca/modelo ser coerente
    private const MODELOS = [
        'Fiat' => ['Uno', 'Argo', 'Strada', 'Pulse'],
        'Volkswagen' => ['Gol', 'Polo', 'T-Cross', 'Voyage'],
        'Chevrolet' => ['Onix', 'Celta', 'Cruze', 'S10'],
        'Toyota' => ['Corolla', 'Hilux', 'Yaris', 'Etios'],
        'Honda' => ['Civic', 'Fit', 'HR-V', 'City'],
        'Ford' => ['Ka', 'Fiesta', 'Ranger', 'EcoSport'],
        'Hyundai' => ['HB20', 'Creta', 'Elantra', 'Tucson'],
        'Renault' => ['Sandero', 'Kwid', 'Duster', 'Logan'],
        'Nissan' => ['Kicks', 'Versa', 'Frontier', 'March'],
        'Jeep' => ['Renegade', 'Compass', 'Cherokee', 'Wrangler'],
    ];

    private const DESCRICOES_REVISAO = [
        'Troca de óleo e filtros',
        'Revisão periódica completa',
        'Troca de pneus',
        'Alinhamento e balanceamento',
        'Troca de pastilhas de freio',
        'Revisão preventiva',
        'Troca da bateria',
        'Troca de velas e cabos',
    ];

    public function run(): void
    {
        $faker = Faker::create('pt_BR');

        // Pessoas: 20, alternando sexo e com idades variadas
        $pessoas = collect();

        for ($i = 0; $i < 20; $i++) {
            $sexo = $i % 2 === 0 ? 'M' : 'F';
            $primeiroNome = $sexo === 'M' ? $faker->firstNameMale : $faker->firstNameFemale;

            $pessoas->push(Pessoa::create([
                'nome' => $primeiroNome . ' ' . $faker->lastName,
                // CPF e telefone vêm mascarados do Faker; o banco guarda só os dígitos
                'cpf' => preg_replace('/\D/', '', $faker->cpf),
                'sexo' => $sexo,
                'data_nascimento' => $faker->dateTimeBetween('-65 years', '-18 years')->format('Y-m-d'),
                'telefone' => preg_replace('/\D/', '', $faker->phoneNumber),
                'email' => $faker->unique()->safeEmail,
            ]));
        }

        // Veículos: as 16 primeiras pessoas recebem de 1 a 3 veículos;
        // as 4 últimas ficam sem nenhum, para o relatório "quem possui
        // mais veículos" ter pessoas com 0 na contagem
        $veiculos = collect();
        $placasUsadas = [];

        $pessoas->take(16)->each(function (Pessoa $pessoa) use ($faker, $veiculos, &$placasUsadas) {
            $quantidade = rand(1, 3);

            for ($i = 0; $i < $quantidade; $i++) {
                $marca = array_rand(self::MODELOS);
                $modelo = $faker->randomElement(self::MODELOS[$marca]);

                // Placa única: gera até achar uma livre, sempre sem hífen e em maiúsculas
                do {
                    // bothify troca '?' por letra e '#' por número (o lexify não troca o '#')
                    $placa = strtoupper(str_replace('-', '', $faker->bothify('???-####')));
                } while (in_array($placa, $placasUsadas, true));
                $placasUsadas[] = $placa;

                $veiculos->push(Veiculo::create([
                    'pessoa_id' => $pessoa->id,
                    'marca' => $marca,
                    'modelo' => $modelo,
                    'ano' => rand(2008, 2026),
                    'placa' => $placa,
                ]));
            }
        });

        // Revisões: 1 a 6 por veículo. Gera as datas da mais recente pra trás
        // e cria em ordem cronológica, com a km só aumentando (hodômetro não volta)
        $veiculos->each(function (Veiculo $veiculo) use ($faker) {
            $quantidade = rand(1, 6);

            $datas = [];
            $data = $faker->dateTimeBetween('-2 years', '-1 month');

            for ($i = 0; $i < $quantidade; $i++) {
                $datas[] = $data->format('Y-m-d');
                $data = (clone $data)->modify('-' . rand(60, 240) . ' days');
            }

            $datas = array_reverse($datas); // da mais antiga para a mais recente
            $km = rand(5000, 30000);

            foreach ($datas as $dataRevisao) {
                Revisao::create([
                    'veiculo_id' => $veiculo->id,
                    'data_revisao' => $dataRevisao,
                    'quilometragem' => $km,
                    'descricao' => $faker->randomElement(self::DESCRICOES_REVISAO),
                    'valor' => rand(15000, 250000) / 100, // ex.: 185.50
                    'observacoes' => $faker->boolean(30) ? $faker->sentence(6) : null,
                ]);

                // A revisão seguinte (mais nova) roda mais do que a anterior
                $km += rand(5000, 15000);
            }
        });
    }
}
