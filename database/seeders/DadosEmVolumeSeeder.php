<?php

namespace Database\Seeders;

use App\Models\Pessoa;
use App\Models\Revisao;
use App\Models\Veiculo;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

/**
 * Gera um volume grande de dados para os relatórios ficarem bem
 * preenchidos, como se o sistema estivesse em uso há algum tempo:
 * 200 pessoas, em torno de 360 veículos e mais de 1.000 revisões.
 *
 * Roda depois do seeder padrão (os dados se somam) ou sozinho:
 *   php artisan db:seed --class=DadosEmVolumeSeeder
 */
class DadosEmVolumeSeeder extends Seeder
{
    // Mesmos modelos e descrições do DatabaseSeeder, repetidos aqui
    // para este seeder ser independente e poder rodar sozinho
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
        $this->command->info('Criando dados em volume...');

        // Pessoas: 200, metade de cada sexo, idades entre 18 e 65
        $pessoas = collect();

        for ($i = 0; $i < 200; $i++) {
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

            if (($i + 1) % 50 === 0) {
                $this->command->info('Pessoas criadas: ' . ($i + 1));
            }
        }

        // Veículos: 1 a 3 por pessoa, com 1 em cada 10 pessoas sem
        // veículo (para os relatórios de contagem terem variação)
        $veiculos = collect();

        // Começa com as placas que já existem no banco, para este
        // seeder poder rodar em cima de dados já cadastrados
        $placasUsadas = Veiculo::pluck('placa')->all();

        $pessoas->each(function (Pessoa $pessoa, int $indice) use ($faker, $veiculos, &$placasUsadas) {
            if ($indice % 10 === 9) {
                return; // esta pessoa fica sem veículo
            }

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
                    'ano' => rand(2005, 2026),
                    'placa' => $placa,
                ]));
            }
        });

        $this->command->info('Veículos criados: ' . $veiculos->count());

        // Revisões: de 0 a 8 por veículo. Gera as datas da mais recente pra
        // trás e cria em ordem cronológica, com a km só aumentando (hodômetro não volta)
        $totalRevisoes = 0;

        $veiculos->each(function (Veiculo $veiculo) use ($faker, &$totalRevisoes) {
            $quantidade = rand(0, 8);

            if ($quantidade === 0) {
                return; // veículo recém-cadastrado, sem revisões ainda
            }

            $datas = [];
            $data = $faker->dateTimeBetween('-6 months', 'now');

            for ($i = 0; $i < $quantidade; $i++) {
                $datas[] = $data->format('Y-m-d');
                $data = (clone $data)->modify('-' . rand(45, 200) . ' days');
            }

            $datas = array_reverse($datas); // da mais antiga para a mais recente
            $km = rand(5000, 30000);

            foreach ($datas as $dataRevisao) {
                Revisao::create([
                    'veiculo_id' => $veiculo->id,
                    'data_revisao' => $dataRevisao,
                    'quilometragem' => $km,
                    'descricao' => $faker->randomElement(self::DESCRICOES_REVISAO),
                    'valor' => rand(15000, 250000) / 100,
                    'observacoes' => $faker->boolean(30) ? $faker->sentence(6) : null,
                ]);

                $totalRevisoes++;

                // A revisão seguinte (mais nova) roda mais do que a anterior
                $km += rand(3000, 12000);
            }

            if ($totalRevisoes % 300 === 0) {
                $this->command->info('Revisões criadas: ' . $totalRevisoes);
            }
        });

        $this->command->info(
            'Concluído: ' . $pessoas->count() . ' pessoas, '
            . $veiculos->count() . ' veículos e '
            . $totalRevisoes . ' revisões.'
        );
    }
}
