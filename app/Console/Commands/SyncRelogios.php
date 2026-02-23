<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncRelogios extends Command
{
    // O comando que você vai digitar no terminal para rodar o robô
    protected $signature = 'ponto:sync';
    protected $description = 'Puxa o arquivo AFD dos relógios Control iD e extrai as batidas';

    public function handle()
    {
        $ip = '10.10.1.39';
        $this->info("Iniciando comunicação com o relógio IP: $ip...");

        // 1. Fazer Login e pegar a Sessão (ignorando verificação SSL)
        $loginResponse = Http::withoutVerifying()->post("https://{$ip}/login.fcgi", [
            'login' => 'admin',
            'password' => 'admin'
        ]);

        if (!$loginResponse->successful() || !isset($loginResponse['session'])) {
            $this->error("Falha ao fazer login no relógio. Verifique a senha ou a rede.");
            return;
        }

        $session = $loginResponse['session'];
        $this->info("Login autorizado! Sessão: $session");

        // 2. Baixar o arquivo AFD bruto
        $this->info("Baixando arquivo AFD (Isso pode levar alguns segundos dependendo do tamanho)...");
        
        $afdResponse = Http::withoutVerifying()->post("https://{$ip}/get_afd.fcgi?session={$session}", [
            'session' => $session
        ]);

        if (!$afdResponse->successful()) {
            $this->error("Falha ao baixar o arquivo AFD.");
            return;
        }

        $afdContent = $afdResponse->body();
        $linhas = explode("\n", $afdContent);
        $totalBatidas = 0;

        $this->info("Arquivo baixado! Analisando registros...");

        // 3. Varrer o arquivo linha por linha procurando as batidas (Tipo 3)
        foreach ($linhas as $linha) {
            $linha = trim($linha);
            
            // Regra do MTE: Linha tem 34/33 caracteres e o 10º caractere é '3'
            if (strlen($linha) >= 33 && substr($linha, 9, 1) === '3') {
                $nsr = substr($linha, 0, 9);
                $data = substr($linha, 10, 8); // DDMMAAAA
                $hora = substr($linha, 18, 4); // HHMM
                $pis = substr($linha, 22, 11);

                // Imprime na tela o que o robô achou
                $this->line("✔️ Batida Encontrada -> PIS: $pis | Data: $data $hora | NSR: $nsr");
                $totalBatidas++;

                // Lógica Futura: Aqui nós colocaremos o código para salvar no banco de dados do seu Dashboard!
            }
        }

        $this->newLine();
        $this->info("Sincronização concluída com sucesso! $totalBatidas batidas de ponto lidas do relógio.");
    }
}