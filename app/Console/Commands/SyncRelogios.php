<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Device;
use App\Models\Employee;
use App\Models\PunchLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncRelogios extends Command
{
    protected $signature = 'ponto:sync';
    protected $description = 'Puxa o arquivo AFD de todos os relógios (Incrementalmente) e salva as batidas';

    public function handle()
    {
        $devices = Device::whereNotNull('ip_address')->get();

        if ($devices->isEmpty()) {
            $this->warn("Nenhum relógio configurado encontrado.");
            return;
        }

        // Faz cache dos CPFs para não bater no banco milhares de vezes
        $employeesCache = Employee::select('id', 'cpf', 'pis', 'company_id')->get()->groupBy('company_id');

        foreach ($devices as $device) {
            $this->info("Iniciando relógio: {$device->name} (IP: {$device->ip_address})...");

            // Pega o último NSR lido (Memória do Robô)
            $lastProcessedNsr = (int) ($device->last_nsr ?? 0);
            $highestNsrInThisRun = $lastProcessedNsr;

            $loginResponse = Http::withOptions(['verify' => false])->asJson()->timeout(10)
                ->post("https://{$device->ip_address}/login.fcgi", [
                    'login' => $device->username ?? 'admin',
                    'password' => $device->password ?? 'admin'
                ]);

            if (!$loginResponse->successful() || !isset($loginResponse['session'])) {
                $this->error("Falha de login no relógio.");
                continue;
            }

            $session = $loginResponse['session'];
            $this->info("Login OK! Baixando arquivo AFD...");

            $afdResponse = Http::withOptions(['verify' => false])->timeout(60)
                ->post("https://{$device->ip_address}/get_afd.fcgi?session={$session}&mode=671");

            if (!$afdResponse->successful()) {
                $this->error("Falha ao baixar AFD.");
                continue;
            }

            $linhas = explode("\n", trim($afdResponse->body()));
            $novasBatidas = 0;
            $naoEncontrados = 0;

            $this->info("Arquivo possui " . count($linhas) . " linhas. Ignorando NSRs até: {$lastProcessedNsr}");

            $companyEmployees = $employeesCache->get($device->company_id, collect());

            foreach ($linhas as $linha) {
                $linha = trim($linha);

                // Processa apenas linhas do tipo "3" (Batida de Ponto)
                if (strlen($linha) >= 33 && substr($linha, 9, 1) === '3') {

                    // Extrai o NSR rapidamente sem Regex primeiro para ganhar velocidade
                    $nsrString = substr($linha, 0, 9);
                    $currentNsr = (int) $nsrString;

                    // A MÁGICA ESTÁ AQUI: Pula o histórico antigo instantaneamente!
                    if ($currentNsr <= $lastProcessedNsr) {
                        continue;
                    }

                    $matched = false;
                    $punchTime = null;
                    $documento = '';

                    // Regex Portaria 671 (Data ISO)
                    if (preg_match('/^(\d{9})3(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:[-+]\d{2}:?\d{2}|Z)?)(\d{11,12})/', $linha, $matches)) {
                        $punchTime = Carbon::parse($matches[2]);
                        $documento = ltrim($matches[3], '0');
                        $matched = true;
                    }
                    // Regex Antiga
                    elseif (preg_match('/^(\d{9})3(\d{8})(\d{4})(\d{11,12})/', $linha, $matches)) {
                        $punchTime = Carbon::createFromFormat('dmYHi', $matches[2] . $matches[3]);
                        $documento = ltrim($matches[4], '0');
                        $matched = true;
                    }

                    if ($matched) {
                        // Atualiza a memória para o final do loop
                        if ($currentNsr > $highestNsrInThisRun) {
                            $highestNsrInThisRun = $currentNsr;
                        }

                        if (empty($documento)) $documento = '0';

                        $pad11 = str_pad($documento, 11, '0', STR_PAD_LEFT);
                        $pad12 = str_pad($documento, 12, '0', STR_PAD_LEFT);

                        // Busca na memória do Laravel em vez de fazer query no SQL
                        $employee = $companyEmployees->first(function ($emp) use ($documento, $pad11, $pad12) {
                            return in_array($emp->cpf, [$documento, $pad11, $pad12]) ||
                                in_array($emp->pis, [$documento, $pad11, $pad12]);
                        });

                        if ($employee) {
                            // Salva a batida
                            $punch = PunchLog::firstOrCreate([
                                'employee_id' => $employee->id,
                                'punch_time' => $punchTime,
                            ], [
                                'device_id' => $device->id,
                                'nsr' => $nsrString,
                            ]);

                            if ($punch->wasRecentlyCreated) {
                                $novasBatidas++;
                            }
                        } else {
                            $naoEncontrados++;
                        }
                    }
                }
            }

            // SALVA A MEMÓRIA PARA O PRÓXIMO MINUTO
            if ($highestNsrInThisRun > $lastProcessedNsr) {
                $device->update(['last_nsr' => $highestNsrInThisRun]);
                $this->info("Memória do NSR atualizada de {$lastProcessedNsr} para {$highestNsrInThisRun}");
            }

            $this->info("Relógio finalizado! {$novasBatidas} novas batidas.");
            if ($naoEncontrados > 0) {
                $this->warn("{$naoEncontrados} novas batidas ignoradas (Servidor não cadastrado).");
            }
            $this->newLine();
        }
    }
}
