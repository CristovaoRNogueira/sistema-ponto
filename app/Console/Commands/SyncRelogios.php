<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Device;
use App\Models\Employee;
use App\Models\PunchLog;
use Carbon\Carbon;

class SyncRelogios extends Command
{
    protected $signature = 'ponto:sync';
    protected $description = 'Puxa o arquivo AFD de todos os relógios e salva as batidas no banco de dados';

    public function handle()
    {
        $devices = Device::whereNotNull('ip_address')->get();

        if ($devices->isEmpty()) {
            $this->warn("Nenhum relógio com IP configurado encontrado no banco.");
            return;
        }

        foreach ($devices as $device) {
            $this->info("Iniciando comunicação com o relógio: {$device->name} (IP: {$device->ip_address})...");

            // 1. Fazer Login forçando JSON
            $loginResponse = Http::withoutVerifying()
                ->asJson()
                ->timeout(10)
                ->post("https://{$device->ip_address}/login.fcgi", [
                    'login' => $device->username ?? 'admin',
                    'password' => $device->password ?? 'admin'
                ]);

            if (!$loginResponse->successful() || !isset($loginResponse['session'])) {
                $erro = $loginResponse->body() ?: 'Timeout / Sem Resposta';
                $this->error("Falha de login no relógio {$device->name}. Retorno: {$erro}");
                continue;
            }

            $session = $loginResponse['session'];
            $this->info("Login OK! Baixando arquivo AFD do relógio {$device->name}...");
            
            // 2. Baixar AFD (Agora com o array e asJson para forçar o formato correto)
            $afdResponse = Http::withoutVerifying()
                ->asJson()
                ->timeout(60) // Timeout maior pois o arquivo pode ser pesado
                ->post("https://{$device->ip_address}/get_afd.fcgi?session={$session}", [
                    'session' => $session
                ]);

            if (!$afdResponse->successful()) {
                $this->error("Falha ao baixar AFD do relógio {$device->name}. Erro: " . $afdResponse->body());
                continue;
            }

            $linhas = explode("\n", $afdResponse->body());
            $novasBatidas = 0;

            $this->info("Arquivo lido com " . count($linhas) . " linhas. Analisando batidas...");

            // 3. Processar cada linha (Tipo 3 = Batida do Trabalhador)
            foreach ($linhas as $linha) {
                $linha = trim($linha);
                
                // Regra do MTE para o Tipo 3 (A partir da Portaria 671, os últimos 11 dígitos são o CPF)
                if (strlen($linha) >= 33 && substr($linha, 9, 1) === '3') {
                    $nsr = substr($linha, 0, 9);
                    $data = substr($linha, 10, 8); // DDMMAAAA
                    $hora = substr($linha, 18, 4); // HHMM
                    $cpf = substr($linha, 22, 11); 

                    // Busca o funcionário pelo CPF
                    $employee = Employee::where('company_id', $device->company_id)
                                        ->where('cpf', $cpf)
                                        ->first();

                    if ($employee) {
                        // Formata a data/hora para o banco de dados
                        $punchTime = Carbon::createFromFormat('dmYHi', $data . $hora);

                        // firstOrCreate garante que não vamos duplicar batidas se rodarmos o comando 2 vezes
                        $punch = PunchLog::firstOrCreate([
                            'employee_id' => $employee->id,
                            'punch_time' => $punchTime,
                        ], [
                            'device_id' => $device->id,
                            'punch_type' => 'normal', 
                        ]);

                        if ($punch->wasRecentlyCreated) {
                            $this->line("✔️ Batida Salva -> Servidor: {$employee->name} | Data: {$data} {$hora} | NSR: {$nsr}");
                            $novasBatidas++;
                        }
                    }
                }
            }

            $this->info("Relógio {$device->name} finalizado! {$novasBatidas} novas batidas registradas no sistema.");
            $this->newLine();
        }

        $this->info("Sincronização global concluída!");
    }
}