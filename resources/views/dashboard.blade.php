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

    <div class="mb-4">
        <button type="button" class="btn btn-warning me-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAtestado">
            Lançar Atestado / Justificativa
        </button>
        <button type="button" class="btn btn-info text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#modalExcecao">
            Configurar Troca de Plantão / Exceção
        </button>
    </div>

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
                            <label class="form-label">CPF (Opcional)</label>
                            <input type="text" name="cpf" class="form-control" placeholder="Apenas números">
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
                            <label class="form-label">Secretaria / Departamento</label>
                            <select name="department_id" class="form-select">
                                <option value="">Selecione...</option>
                                @if(isset($departments))
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jornada de Trabalho Padrão</label>
                            <select name="shift_id" class="form-select">
                                <option value="">Selecione a jornada...</option>
                                @if(isset($shifts))
                                    @foreach($shifts as $shift)
                                        <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-primary fw-bold">Relógio de Destino (Push)</label>
                            <select name="device_id" class="form-select border-primary" required>
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

<div class="modal fade" id="modalAtestado" tabindex="-1" aria-labelledby="modalAtestadoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="modalAtestadoLabel">Registrar Atestado / Ausência</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('admin.absences.store') ?? '#' }}" method="POST">
          @csrf
          <div class="modal-body">
              <div class="mb-3">
                  <label class="form-label fw-bold">Servidor</label>
                  <select name="employee_id" required class="form-select">
                      <option value="">Selecione um servidor...</option>
                      @if(isset($employees))
                          @foreach($employees as $emp)
                              <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                          @endforeach
                      @endif
                  </select>
              </div>

              <div class="mb-3">
                  <label class="form-label fw-bold">Tipo de Justificativa</label>
                  <select name="type" required class="form-select">
                      <option value="Atestado Médico">Atestado Médico</option>
                      <option value="Licença Maternidade/Paternidade">Licença Maternidade/Paternidade</option>
                      <option value="Serviço Externo">Serviço Externo</option>
                      <option value="Luto">Licença Nojo (Luto)</option>
                      <option value="Outros">Outros (Abonado)</option>
                  </select>
              </div>

              <div class="row mb-3">
                  <div class="col-md-6">
                      <label class="form-label fw-bold">Data/Hora Início</label>
                      <input type="datetime-local" name="start_date" required class="form-control">
                  </div>
                  <div class="col-md-6">
                      <label class="form-label fw-bold">Data/Hora Fim</label>
                      <input type="datetime-local" name="end_date" required class="form-control">
                  </div>
              </div>

              <div class="mb-3">
                  <label class="form-label fw-bold">Motivo / CID (Obrigatório)</label>
                  <textarea name="reason" rows="3" required class="form-control" placeholder="Descreva o motivo ou informe o CID..."></textarea>
              </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-warning">Salvar Atestado</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalExcecao" tabindex="-1" aria-labelledby="modalExcecaoLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="modalExcecaoLabel">Configurar Troca de Plantão / Exceção</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('admin.shift_exceptions.store') ?? '#' }}" method="POST">
          @csrf
          <div class="modal-body">
              <div class="mb-3">
                  <label class="form-label fw-bold">Servidor</label>
                  <select name="employee_id" required class="form-select">
                      <option value="">Selecione o servidor...</option>
                      @if(isset($employees))
                          @foreach($employees as $emp)
                              <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                          @endforeach
                      @endif
                  </select>
              </div>

              <div class="row mb-3">
                  <div class="col-md-6">
                      <label class="form-label fw-bold">Data da Ocorrência</label>
                      <input type="date" name="exception_date" required class="form-control">
                  </div>
                  <div class="col-md-6">
                      <label class="form-label fw-bold">Tipo de Exceção</label>
                      <select name="type" id="exception_type" onchange="handleExceptionTypeChange()" required class="form-select">
                          <option value="swap">Troca de Plantão (Vai trabalhar)</option>
                          <option value="extra">Plantão Extra (Convocação)</option>
                          <option value="day_off">Folga / Descanso Compensatório</option>
                      </select>
                  </div>
              </div>

              <div id="time_fields_container" class="bg-light p-3 border rounded mb-3">
                  <h6 class="text-primary fw-bold mb-3">Horários Exigidos neste dia</h6>
                  <div class="row mb-2">
                      <div class="col-md-3">
                          <label class="form-label small">Entrada 1</label>
                          <input type="time" name="in_1" class="form-control form-control-sm">
                      </div>
                      <div class="col-md-3">
                          <label class="form-label small">Saída 1</label>
                          <input type="time" name="out_1" class="form-control form-control-sm">
                      </div>
                      <div class="col-md-3">
                          <label class="form-label small">Entrada 2</label>
                          <input type="time" name="in_2" class="form-control form-control-sm">
                      </div>
                      <div class="col-md-3">
                          <label class="form-label small">Saída 2</label>
                          <input type="time" name="out_2" class="form-control form-control-sm">
                      </div>
                  </div>
                  <div>
                      <label class="form-label small fw-bold mt-2">Carga Horária Total a Cumprir (em Minutos)</label>
                      <input type="number" name="daily_work_minutes" class="form-control form-control-sm" placeholder="Ex: 720 (para 12h) ou 480 (para 8h)">
                  </div>
              </div>

              <div class="mb-3">
                  <label class="form-label fw-bold">Observação de RH</label>
                  <input type="text" name="observation" class="form-control" placeholder="Ex: Trocando plantão com o servidor Carlos...">
              </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-info text-white">Salvar Exceção</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function handleExceptionTypeChange() {
        const type = document.getElementById('exception_type').value;
        const timeFields = document.getElementById('time_fields_container');
        const minutesInput = document.querySelector('input[name="daily_work_minutes"]');
        
        if (type === 'day_off') {
            timeFields.style.display = 'none';
            minutesInput.removeAttribute('required');
            minutesInput.value = ''; 
        } else {
            timeFields.style.display = 'block';
            minutesInput.setAttribute('required', 'required');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        handleExceptionTypeChange();
    });
</script>
</body>
</html>