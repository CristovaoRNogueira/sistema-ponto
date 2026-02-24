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
            $this->warn("Nenhum relógio configurado encontrado.");
            return;
        }

        foreach ($devices as $device) {
            $this->info("Iniciando relógio: {$device->name} (IP: {$device->ip_address})...");

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
            
            $afdResponse = Http::withOptions(['verify' => false])->asJson()->timeout(60) 
                ->post("https://{$device->ip_address}/get_afd.fcgi?session={$session}&mode=671", [
                    'session' => $session
                ]);

            if (!$afdResponse->successful()) {
                $this->error("Falha ao baixar AFD.");
                continue;
            }

            $linhas = explode("\n", $afdResponse->body());
            $novasBatidas = 0;
            $naoEncontrados = 0;

            $this->info("Analisando " . count($linhas) . " linhas...");

            foreach ($linhas as $linha) {
                $linha = trim($linha);
                
                if (strlen($linha) >= 33 && substr($linha, 9, 1) === '3') {
                    $matched = false;
                    $nsr = ''; $documento = ''; $punchTime = null;

                    // Regex Portaria 671 (Data ISO)
                    if (preg_match('/^(\d{9})3(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:[-+]\d{2}:?\d{2}|Z)?)(\d{11,12})/', $linha, $matches)) {
                        $nsr = $matches[1];
                        $punchTime = Carbon::parse($matches[2]); 
                        $documento = $matches[3]; 
                        $matched = true;
                    } 
                    // Regex Antiga
                    elseif (preg_match('/^(\d{9})3(\d{8})(\d{4})(\d{11,12})/', $linha, $matches)) {
                        $nsr = $matches[1];
                        $punchTime = Carbon::createFromFormat('dmYHi', $matches[2] . $matches[3]);
                        $documento = $matches[4];
                        $matched = true;
                    }

                    if ($matched) {
                        $docLimpo = ltrim($documento, '0');
                        if (empty($docLimpo)) $docLimpo = '0';

                        // Prepara variações para encontrar o funcionário de qualquer jeito
                        $pad11 = str_pad($docLimpo, 11, '0', STR_PAD_LEFT);
                        $pad12 = str_pad($docLimpo, 12, '0', STR_PAD_LEFT);
                        $docsTestados = [$documento, $pad11, $pad12];

                        $employee = Employee::where('company_id', $device->company_id)
                            ->where(function($q) use ($docsTestados) {
                                $q->whereIn('cpf', $docsTestados)
                                  ->orWhereIn('pis', $docsTestados);
                            })->first();

                        if ($employee) {
                            $punch = PunchLog::firstOrCreate([
                                'employee_id' => $employee->id,
                                'punch_time' => $punchTime,
                            ], [
                                'device_id' => $device->id,
                                'nsr' => $nsr,
                            ]);

                            if ($punch->wasRecentlyCreated) {
                                $this->line("✔️ Batida Salva -> {$employee->name} | " . $punchTime->format('d/m/Y H:i'));
                                $novasBatidas++;
                            }
                        } else {
                            $naoEncontrados++;
                            
                            // RAIO-X SUPREMO! 
                            if ($naoEncontrados <= 3) {
                                $this->newLine();
                                $this->warn("⚠️ FALHA #" . $naoEncontrados);
                                $this->warn("Linha no ficheiro: " . substr($linha, 0, 55) . "...");
                                $this->warn("O Regex extraiu o documento: '{$documento}'");
                                
                                // Puxa 3 CPFs do banco de dados para a gente comparar
                                $sample = Employee::where('company_id', $device->company_id)
                                                  ->take(3)->pluck('cpf')->toArray();
                                $this->warn("Exemplo de CPFs guardados no seu Banco de Dados: " . implode(', ', $sample));
                            }
                        }
                    }
                }
            }

            $this->info("Relógio finalizado! {$novasBatidas} novas batidas.");
            if ($naoEncontrados > 0) {
                $this->warn("{$naoEncontrados} batidas ignoradas (Sem servidor no banco).");
            }
            $this->newLine();
        }
    }
}