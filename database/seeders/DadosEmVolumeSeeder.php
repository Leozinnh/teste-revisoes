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
                'cpf' => $faker->cpf,
                'sexo' => $sexo,
                'data_nascimento' => $faker->dateTimeBetween('-65 years', '-18 years')->format('Y-m-d'),
                'telefone' => $faker->phoneNumber,
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

                // Placa única no banco: gera até achar uma que não foi usada
                do {
                    $placa = $faker->lexify('???-####');
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

        // Revisões: de 0 a 8 por veículo, espalhadas pelos últimos
        // 3 anos. A mais recente fica entre 6 meses atrás e hoje;
        // as anteriores ficam 45 a 200 dias antes, com km sempre menor
        $totalRevisoes = 0;

        $veiculos->each(function (Veiculo $veiculo) use ($faker, &$totalRevisoes) {
            $quantidade = rand(0, 8);

            if ($quantidade === 0) {
                return; // veículo recém-cadastrado, sem revisões ainda
            }

            $km = rand(10000, 120000);
            $data = $faker->dateTimeBetween('-6 months', 'now');

            for ($i = 0; $i < $quantidade; $i++) {
                Revisao::create([
                    'veiculo_id' => $veiculo->id,
                    'data_revisao' => $data->format('Y-m-d'),
                    'quilometragem' => $km,
                    'descricao' => $faker->randomElement(self::DESCRICOES_REVISAO),
                    'valor' => rand(15000, 250000) / 100,
                    'observacoes' => $faker->boolean(30) ? $faker->sentence(6) : null,
                ]);

                $totalRevisoes++;

                // A revisão anterior fica meses antes e com km menor
                $km += rand(3000, 12000);
                $data = (clone $data)->modify('-' . rand(45, 200) . ' days');
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
