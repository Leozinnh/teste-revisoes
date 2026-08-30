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
                'cpf' => $faker->cpf,
                'sexo' => $sexo,
                'data_nascimento' => $faker->dateTimeBetween('-65 years', '-18 years')->format('Y-m-d'),
                'telefone' => $faker->phoneNumber,
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

                // A placa precisa ser única no banco; gera até achar uma nova
                do {
                    $placa = $faker->lexify('???-####');
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

        // Revisões: 1 a 6 por veículo (60+ no total).
        // A primeira fica entre 2 anos e 1 mês atrás; as anteriores
        // ficam 60 a 240 dias antes, com km sempre menor
        $veiculos->each(function (Veiculo $veiculo) use ($faker) {
            $quantidade = rand(1, 6);
            $km = rand(8000, 60000);
            $data = $faker->dateTimeBetween('-2 years', '-1 month');

            for ($i = 0; $i < $quantidade; $i++) {
                Revisao::create([
                    'veiculo_id' => $veiculo->id,
                    'data_revisao' => $data->format('Y-m-d'),
                    'quilometragem' => $km,
                    'descricao' => $faker->randomElement(self::DESCRICOES_REVISAO),
                    'valor' => rand(15000, 250000) / 100, // ex.: 185.50
                    'observacoes' => $faker->boolean(30) ? $faker->sentence(6) : null,
                ]);

                // A revisão anterior fica meses antes e com km menor
                $km += rand(5000, 15000);
                $data = (clone $data)->modify('-' . rand(60, 240) . ' days');
            }
        });
    }
}
