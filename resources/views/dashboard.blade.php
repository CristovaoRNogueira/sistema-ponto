<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Ponto - Prefeitura de Carinhanha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="#">Gestão de Ponto iDClass</a>
        <span class="navbar-text text-white">Status do Servidor: <span class="badge bg-success">Online (Porta 8080)</span></span>
    </div>
</nav>

<div class="container">
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Cadastrar Servidor (Remoto)</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('employee.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" name="name" class="form-control" placeholder="Ex: João da Silva" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">PIS</label>
                            <input type="number" name="pis" class="form-control" placeholder="Apenas números" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Matrícula (Opcional)</label>
                            <input type="text" name="registration_number" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Secretaria / Relógio de Destino</label>
                            <select name="device_id" class="form-select" required>
                                <option value="">Selecione um relógio...</option>
                                @foreach($devices as $device)
                                    <option value="{{ $device->id }}">{{ $device->name }} (SN: {{ $device->serial_number }})</option>
                                @endforeach
                            </select>
                            <small class="text-muted mt-1 d-block">O relógio puxará este cadastro no próximo "ping".</small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Salvar e Enviar Comando</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Últimas Batidas (Tempo Real)</h5>
                    <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary">Atualizar Lista</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 text-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Servidor</th>
                                    <th>PIS</th>
                                    <th>Local (Relógio)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($punches as $punch)
                                <tr>
                                    <td class="align-middle text-nowrap">{{ $punch->punch_time->format('d/m/Y H:i:s') }}</td>
                                    <td class="align-middle fw-bold">{{ $punch->employee->name }}</td>
                                    <td class="align-middle">{{ $punch->employee->pis }}</td>
                                    <td class="align-middle">
                                        <span class="badge bg-secondary">{{ $punch->device->name }}</span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        Nenhuma batida registrada ainda.<br>
                                        Configure um relógio iDClass para apontar para <b>http://19.10.1.4:8080/api/controlid/push</b>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>