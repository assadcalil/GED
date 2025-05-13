<?php

// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Sistema Contabilidade Estrela 2.0
 * Visualizador e Gerenciador de Boletos
 */

// Verificar se as configurações já foram incluídas
if (!defined('ROOT_DIR')) {
    require_once __DIR__ . '/../../../...../app/Config/App.php';
    require_once __DIR__ . '/../../../...../app/Config/Database.php';
    require_once __DIR__ . '/../../../...../app/Config/Auth.php';
    require_once __DIR__ . '/../../../...../app/Config/Logger.php';
}

// Verificar autenticação e permissão
Auth::requireLogin();

// Verificar ID do cliente
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: viewListagemImpostos.php?error=ID de cliente inválido');
    exit;
}

// Buscar informações do cliente
$cliente = Database::selectOne("SELECT * FROM impostos WHERE id = ?", [$id]);
if (!$cliente) {
    header('Location: viewListagemImpostos.php?error=Cliente não encontrado');
    exit;
}

// Formata valor para exibição no padrão brasileiro
function formata_valor_real($valor) {
    if (!is_numeric($valor)) {
        return '0,00';
    }
    return number_format((float)$valor, 2, ',', '.');
}

// Processar atualização de dados do cliente
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['atualizar_cliente'])) {
        // Atualizar dados do cliente
        $nome = trim($_POST['nome']);
        $cpf = trim($_POST['cpf']);
        $email = trim($_POST['email']);
        $tel = trim($_POST['tel'] ?? '');

        if (empty($nome) || empty($cpf)) {
            $error = 'Nome e CPF são campos obrigatórios';
        } else {
            try {
                Database::execute(
                    "UPDATE impostos SET nome = ?, cpf = ?, email = ?, tel = ? WHERE id = ?",
                    [$nome, $cpf, $email, $tel, $id]
                );
                
                Logger::activity('cliente', "Atualizou dados do cliente $nome (ID: $id)");
                $success = 'Dados do cliente atualizados com sucesso';
                
                // Recarregar dados do cliente
                $cliente = Database::selectOne("SELECT * FROM impostos WHERE id = ?", [$id]);
            } catch (Exception $e) {
                $error = 'Erro ao atualizar dados: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['gerar_boleto'])) {
        // Gerar novo boleto
        $valor = str_replace(['.', ','], ['', '.'], $_POST['valor']);
        $vencimento = $_POST['vencimento'];
        
        if (!is_numeric($valor) || $valor <= 0) {
            $error = 'Valor do boleto inválido';
        } elseif (empty($vencimento)) {
            $error = 'Data de vencimento é obrigatória';
        } else {
            try {
                // Atualizar dados na tabela impostos
                Database::execute(
                    "UPDATE impostos SET valor2025 = ?, vencimento = ? WHERE id = ?",
                    [$valor, $vencimento, $id]
                );
                
                // Redirecionar para a página de geração de boleto
                header('Location: BoletoCef.php?id=' . $id);
                exit;
            } catch (Exception $e) {
                $error = 'Erro ao preparar boleto: ' . $e->getMessage();
            }
        }
    }
}

// Buscar histórico de boletos
$boletos = Database::select(
    "SELECT * FROM impostos_boletos
     WHERE imposto_id = ?
     ORDER BY data_emissao DESC",
    [$id]
);

// Definir status para exibição
$statusLabels = [
    '0' => '<span class="badge bg-info text-white"><i class="fas fa-clock me-1"></i> BOLETO NÃO EMITIDO</span>',
    '1' => '<span class="badge bg-success text-white"><i class="fas fa-check-circle me-1"></i> BOLETO PAGO</span>',
    '5' => '<span class="badge bg-danger text-white"><i class="fas fa-sync-alt fa-spin me-1"></i> ESPERANDO PAGAMENTO</span>',
    '6' => '<span class="badge bg-success text-white"><i class="fas fa-money-bill me-1"></i> PAGAMENTO EM DINHEIRO</span>',
    '8' => '<span class="badge bg-secondary text-white"><i class="fas fa-gift me-1"></i> CORTESIA</span>'
];

// Registrar acesso
Logger::activity('acesso', 'Visualizou dados do cliente ' . $cliente['nome']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciador de Cliente - <?php echo htmlspecialchars($cliente['nome']); ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Estilo personalizado -->
    <link rel="stylesheet" href="/GED2.0/assets/css/dashboard.css">
    
    <style>
        .client-info-card {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .client-header {
            background: linear-gradient(135deg, #3498db, #2c3e50);
            padding: 20px;
            color: white;
        }
        
        .status-history {
            margin-top: 30px;
        }
        
        .year-box {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .year-box:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }
        
        .year-header {
            font-weight: 600;
            color: #3498db;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }
        
        .form-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .form-section h5 {
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .boleto-history {
            margin-top: 30px;
        }
        
        .boleto-item {
            border-left: 4px solid #3498db;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f8f9fa;
            border-radius: 0 10px 10px 0;
            transition: all 0.3s ease;
        }
        
        .boleto-item:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .nav-tabs .nav-link.active {
            border-color: transparent;
            border-bottom: 3px solid #3498db;
            font-weight: 600;
        }
        
        .nav-tabs .nav-link {
            color: #495057;
        }
    </style>
</head>
<body data-user-type="<?php echo $_SESSION['user_type']; ?>">
    <div class="dashboard-container">
        <!-- Menu Lateral -->
        <?php include_once ROOT_PATH . '/views/partials/sidebar.php'; ?>
        
        <!-- Conteúdo Principal -->
        <div class="main-content">
            <!-- Cabeçalho -->
            <?php include_once ROOT_PATH . '/views/partials/header.php'; ?>
            
            <!-- Conteúdo da Página -->
            <div class="dashboard-content">
                <div class="container-fluid">
                    <!-- Cabeçalho da Página -->
                    <div class="page-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="page-title">Gerenciador de Cliente</h1>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="viewListagemImpostos.php">Imposto de Renda</a></li>
                                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($cliente['nome']); ?></li>
                                    </ol>
                                </nav>
                            </div>
                            <div class="col-auto">
                                <a href="viewListagemImpostos.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Voltar
                                </a>
                                <?php if (!in_array($cliente['status_boleto_2025'], ['1', '6', '8'])): ?>
                                <a href="viewBoletoCef.php?id=<?php echo $id; ?>" class="btn btn-primary ms-2">
                                    <i class="fas fa-barcode me-2"></i> Gerar Boleto
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Informações do Cliente -->
                    <div class="client-info-card mb-4">
                        <div class="client-header">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h3 class="mb-1"><?php echo htmlspecialchars($cliente['nome']); ?></h3>
                                    <p class="mb-0"><i class="fas fa-id-card me-2"></i> <?php echo htmlspecialchars($cliente['cpf']); ?></p>
                                    <?php if (!empty($cliente['email'])): ?>
                                    <p class="mb-0"><i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($cliente['email']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($cliente['telefone'])): ?>
                                    <p class="mb-0"><i class="fas fa-phone me-2"></i> <?php echo htmlspecialchars($cliente['telefone']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <div class="client-code">
                                        <span class="badge bg-light text-dark fs-5 p-2">
                                            <i class="fas fa-hashtag me-2"></i> <?php echo htmlspecialchars($cliente['codigo']); ?>
                                        </span>
                                    </div>
                                    <div class="mt-2">
                                        <span class="badge bg-info text-white">
                                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($cliente['usuario']); ?>
                                        </span>
                                        <?php echo $statusLabels[$cliente['status_boleto_2025']]; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="status-history">
                                <h5 class="mb-4">Histórico de Pagamentos</h5>
                                <div class="row">
                                    <!-- 2025 -->
                                    <div class="col-md-4">
                                        <div class="year-box">
                                            <div class="year-header">
                                                <i class="fas fa-calendar-alt me-2"></i> 2025
                                            </div>
                                            <div class="mb-2">
                                                <strong>Valor:</strong> R$ <?php echo formata_valor_real($cliente['valor2025']); ?>
                                            </div>
                                            <div class="mb-2">
                                                <strong>Status:</strong> <?php echo $statusLabels[$cliente['status_boleto_2025']]; ?>
                                            </div>
                                            <?php if ($cliente['status_boleto_2025'] == 1 || $cliente['status_boleto_2025'] == 6): ?>
                                            <div class="mb-2">
                                                <strong>Data Pagamento:</strong> <?php echo !empty($cliente['data_pagamento_2025']) ? date('d/m/Y', strtotime($cliente['data_pagamento_2025'])) : '-'; ?>
                                            </div>
                                            <?php else: ?>
                                            <div class="mb-2">
                                                <strong>Vencimento:</strong> <?php echo !empty($cliente['vencimento']) ? date('d/m/Y', strtotime($cliente['vencimento'])) : '-'; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- 2024 -->
                                    <div class="col-md-4">
                                        <div class="year-box">
                                            <div class="year-header">
                                                <i class="fas fa-calendar-alt me-2"></i> 2024
                                            </div>
                                            <div class="mb-2">
                                                <strong>Valor:</strong> R$ <?php echo formata_valor_real($cliente['valor2024']); ?>
                                            </div>
                                            <div class="mb-2">
                                                <strong>Status:</strong> <?php echo $statusLabels[$cliente['status_boleto_2024'] ?? '0']; ?>
                                            </div>
                                            <?php if (($cliente['status_boleto_2024'] ?? 0) == 1 || ($cliente['status_boleto_2024'] ?? 0) == 6): ?>
                                            <div class="mb-2">
                                                <strong>Data Pagamento:</strong> <?php echo !empty($cliente['data_pagamento_2024']) ? date('d/m/Y', strtotime($cliente['data_pagamento_2024'])) : '-'; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- 2023 -->
                                    <div class="col-md-4">
                                        <div class="year-box">
                                            <div class="year-header">
                                                <i class="fas fa-calendar-alt me-2"></i> 2023
                                            </div>
                                            <div class="mb-2">
                                                <strong>Valor:</strong> R$ <?php echo formata_valor_real($cliente['valor2023'] ?? 0); ?>
                                            </div>
                                            <div class="mb-2">
                                                <strong>Status:</strong> <?php echo $statusLabels[$cliente['status_boleto_2023'] ?? '0']; ?>
                                            </div>
                                            <?php if (($cliente['status_boleto_2023'] ?? 0) == 1 || ($cliente['status_boleto_2023'] ?? 0) == 6): ?>
                                            <div class="mb-2">
                                                <strong>Data Pagamento:</strong> <?php echo !empty($cliente['data_pagamento_2023']) ? date('d/m/Y', strtotime($cliente['data_pagamento_2023'])) : '-'; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Abas de Seções -->
                    <ul class="nav nav-tabs" id="clientTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="boletos-tab" data-bs-toggle="tab" data-bs-target="#boletos" type="button" role="tab">
                                <i class="fas fa-barcode me-2"></i> Boletos
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="edit-tab" data-bs-toggle="tab" data-bs-target="#edit" type="button" role="tab">
                                <i class="fas fa-edit me-2"></i> Editar Dados
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="new-boleto-tab" data-bs-toggle="tab" data-bs-target="#new-boleto" type="button" role="tab">
                                <i class="fas fa-plus-circle me-2"></i> Novo Boleto
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content p-4 bg-white border border-top-0 rounded-bottom" id="clientTabsContent">
                        <!-- Aba de Boletos -->
                        <div class="tab-pane fade show active" id="boletos" role="tabpanel">
                            <h5 class="mb-4">Histórico de Boletos</h5>
                            
                            <?php if (empty($boletos)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> Nenhum boleto registrado para este cliente.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Data Emissão</th>
                                                <th>Vencimento</th>
                                                <th>Valor</th>
                                                <th>Status</th>
                                                <th>Código Barras</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($boletos as $boleto): ?>
                                                <tr>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($boleto['data_emissao'])); ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($boleto['data_vencimento'])); ?></td>
                                                    <td>R$ <?php echo formata_valor_real($boleto['valor']); ?></td>
                                                    <td>
                                                        <?php 
                                                        if ($boleto['status'] == 1) {
                                                            echo '<span class="badge bg-success">PAGO</span>';
                                                        } elseif ($boleto['status'] == 5) {
                                                            echo '<span class="badge bg-danger">PENDENTE</span>';
                                                        } elseif ($boleto['status'] == 0) {
                                                            echo '<span class="badge bg-secondary">CANCELADO</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <small><?php echo htmlspecialchars(substr($boleto['linha_digitavel'], 0, 20) . '...'); ?></small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="viewBoletoArmazenado.php?id=<?php echo $boleto['id']; ?>" class="btn btn-sm btn-info" target="_blank" data-bs-toggle="tooltip" title="Visualizar">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            
                                                            <a href="emailBoleto.php?id=<?php echo $boleto['id']; ?>" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="Enviar por Email">
                                                                <i class="fas fa-envelope"></i>
                                                            </a>
                                                            
                                                            <!--<?php if ($boleto['status'] == 5): ?>
                                                                <a href="marcarBoletoPago.php?id=<?php echo $boleto['id']; ?>" class="btn btn-sm btn-success" data-bs-toggle="tooltip" title="Marcar como Pago">
                                                                    <i class="fas fa-check"></i>
                                                                </a>-->
                                                                
                                                                <a href="cancelarBoleto.php?id=<?php echo $boleto['id']; ?>" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="Cancelar">
                                                                    <i class="fas fa-ban"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Aba de Edição -->
                        <div class="tab-pane fade" id="edit" role="tabpanel">
                            <h5 class="mb-4">Editar Dados do Cliente</h5>
                            
                            <form action="visualizador_boletos.php?id=<?php echo $id; ?>" method="post">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nome" class="form-label">Nome Completo</label>
                                            <input type="text" class="form-control" id="nome" name="nome" value="<?php echo htmlspecialchars($cliente['nome']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cpf" class="form-label">CPF</label>
                                            <input type="text" class="form-control" id="cpf" name="cpf" value="<?php echo htmlspecialchars($cliente['cpf']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($cliente['email'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="tel" class="form-label">Telefone</label>
                                            <input type="text" class="form-control" id="tel" name="tel" value="<?php echo htmlspecialchars($cliente['tel'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Código</label>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($cliente['codigo']); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Usuário Responsável</label>
                                            <p class="form-control-plaintext"><?php echo htmlspecialchars($cliente['usuario']); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Cadastrado em</label>
                                            <p class="form-control-plaintext"><?php echo date('d/m/Y H:i', strtotime($cliente['data'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> Para alterar os dados do cliente, entre em contato com o administrador do sistema.
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" name="atualizar_cliente" value="1" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> Salvar Alterações
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Aba de Novo Boleto -->
                        <div class="tab-pane fade" id="new-boleto" role="tabpanel">
                            <h5 class="mb-4">Gerar Novo Boleto</h5>
                            
                            <?php if (in_array($cliente['status_boleto_2025'], ['1', '6', '8'])): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i> 
                                    <?php if ($cliente['status_boleto_2025'] == '1'): ?>
                                        Este cliente já possui um boleto pago para 2025.
                                    <?php elseif ($cliente['status_boleto_2025'] == '6'): ?>
                                        Este cliente já registrou pagamento em dinheiro para 2025.
                                    <?php elseif ($cliente['status_boleto_2025'] == '8'): ?>
                                        Este cliente está marcado como cortesia para 2025.
                                    <?php endif; ?>
                                </div>
                                
                                <p>Para gerar um novo boleto, é necessário alterar o status atual do cliente.</p>
                                
                                <form action="alterarStatusImposto.php" method="post" class="mt-3">
                                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="novo_status" class="form-label">Novo Status</label>
                                                <select class="form-select" id="novo_status" name="novo_status" required>
                                                    <option value="">Selecione...</option>
                                                    <option value="0">Boleto Não Emitido</option>
                                                    <option value="5">Esperando Pagamento</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="motivo" class="form-label">Motivo da Alteração</label>
                                                <input type="text" class="form-control" id="motivo" name="motivo" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-sync-alt me-2"></i> Alterar Status
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <form action="visualizador_boletos.php?id=<?php echo $id; ?>" method="post">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="valor" class="form-label">Valor do Boleto (R$)</label>
                                                <input type="text" class="form-control" id="valor" name="valor" value="<?php echo formata_valor_real($cliente['valor2025']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="vencimento" class="form-label">Data de Vencimento</label>
                                                <input type="date" class="form-control" id="vencimento" name="vencimento" value="<?php echo date('Y-m-d', strtotime($cliente['vencimento'] ?? '+7 days')); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> Ao gerar um novo boleto, as informações acima serão salvas e você será redirecionado para a página de emissão do boleto.
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" name="gerar_boleto" value="1" class="btn btn-primary">
                                            <i class="fas fa-barcode me-2"></i> Gerar Boleto
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Rodapé -->
            <footer class="dashboard-footer">
                <div class="container-fluid">
                    <div class="copyright">
                        GED Contabilidade Estrela &copy; <?php echo date('Y'); ?>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap Bundle com Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery Mask -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    
    <!-- Script personalizado -->
    <script src="/GED2.0/assets/js/dashboard.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Máscaras para campos
            $('#cpf').mask('000.000.000-00');
            $('#tel').mask('(00) 00000-0000');
            $('#valor').mask('#.##0,00', {reverse: true});
            
            // Atualizar relógio de Brasília
            function updateBrasiliaTime() {
                const now = new Date();
                const brasiliaTime = new Date(now.getTime() - (now.getTimezoneOffset() * 60000));
                const formattedTime = brasiliaTime.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                document.getElementById('brasilia-clock').textContent = formattedTime;
            }
            
            updateBrasiliaTime();
            setInterval(updateBrasiliaTime, 1000);
        });
    </script>
</body>
</html>