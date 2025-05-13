<?php

// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Formulário de envio de certificados digitais
 * Permite selecionar a empresa, anexar certificado e configurar envio de email
 */
date_default_timezone_set('America/Sao_Paulo');
header("Content-type: text/html; charset=utf-8");

// Verificar se as configurações já foram incluídas
if (!defined('ROOT_DIR')) {
    require_once __DIR__ . '/../../../...../app/Config/App.php';
    require_once __DIR__ . '/../../../...../app/Config/Database.php';
    require_once __DIR__ . '/../../../...../app/Config/Auth.php';
    require_once __DIR__ . '/../../../...../app/Config/Logger.php';
}

// Incluir o controller
require_once(__DIR__ . '/../../../...../app/Controllers/CertificadoEmailController.php');

// Inicializar variáveis
$mensagemSucesso = "Enviado com Sucesso";
$mensagemErro = "Não enviado";
$showPopup = false;
$showErrorPopup = false;

$dadosFormulario = array(
    'emp_code' => '',
    'emp_name' => '',
    'emp_cnpj' => '',
    'tipo_certificado' => '',
    'data_renovacao' => '',
    'certificado_vencimento' => '',
    'emails_destinatario' => '',
    'senha_certificado' => ''
);

// Lista de emails disponíveis
$emailsDisponiveis = array(
    'cestrela@terra.com.br',
    'cestrela.diretoria@terra.com.br', 
    'cestrela.financeiro@terra.com.br', 
    'cestrela.tesouraria@terra.com.br', 
    'cestrela.visitadores@terra.com.br', 
    'cestrela.expediente@terra.com.br', 
    'cestrela.fiscal@terra.com.br', 
    'cestrela.iss@terra.com.br', 
    'cestrela.dp@terra.com.br', 
    'cestrela.dp2@terra.com.br', 
    'cestrela.dp4@terra.com.br', 
    'cestrela.contabil@terra.com.br', 
    'meucontador@terra.com.br'
);

// Instanciar o controller
$controller = new ControllerEnviaEmailCertificado();

// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Registrar a ação no log
    Logger::activity('form_submit', 'Formulário de certificado digital submetido');
    
    // Capturar dados do formulário
    $dadosFormulario = array(
        'emp_code' => isset($_POST['emp_code']) ? $_POST['emp_code'] : '',
        'tipo_certificado' => isset($_POST['tipo_certificado']) ? $_POST['tipo_certificado'] : '',
        'data_renovacao' => isset($_POST['data_renovacao']) ? $_POST['data_renovacao'] : '',
        'certificado_vencimento' => isset($_POST['certificado_vencimento']) ? $_POST['certificado_vencimento'] : '',
        'senha_certificado' => isset($_POST['senha_certificado']) ? $_POST['senha_certificado'] : ''
    );

    // Processar emails selecionados
    $emailsSelecionados = isset($_POST['emails_selecionados']) ? $_POST['emails_selecionados'] : array();
    $dadosFormulario['emails_destinatario'] = implode(', ', $emailsSelecionados);
    
    // Se o número da empresa foi informado, buscar dados
    if (!empty($dadosFormulario['emp_code'])) {
        Logger::activity('buscar_empresa', 'Buscando dados da empresa: ' . $dadosFormulario['emp_code']);
        $empresa = $controller->buscarEmpresa($dadosFormulario['emp_code']);
        if ($empresa) {
            $dadosFormulario['emp_name'] = $empresa['emp_name'];
            $dadosFormulario['emp_cnpj'] = $empresa['emp_cnpj'];
            
            // Registrar sucesso na busca
            Logger::info("Empresa encontrada: {$empresa['emp_name']} ({$dadosFormulario['emp_code']})");
            
            // Se o email não foi preenchido, usar o da empresa
            if (empty($dadosFormulario['emails_destinatario']) && !empty($empresa['email_empresa'])) {
                $dadosFormulario['emails_destinatario'] = $empresa['email_empresa'];
                Logger::info("Email da empresa usado como destinatário: {$empresa['email_empresa']}");
            }
        } else {
            $mensagemErro = "Empresa não encontrada com o código informado.";
            $showErrorPopup = true;
            Logger::warning("Empresa não encontrada: {$dadosFormulario['emp_code']}");
        }
    }
    
    // Verificar se é para enviar o email
    if (isset($_POST['acao']) && $_POST['acao'] === 'enviar_email') {
        Logger::activity('iniciar_envio', 'Tentativa de envio de certificado');
        
        // Verificar se os campos obrigatórios estão preenchidos
        if (empty($dadosFormulario['emp_code']) || empty($dadosFormulario['emp_name'])) {
            $mensagemErro = "Preencha o número da empresa e clique em 'Buscar' antes de enviar o email.";
            $showErrorPopup = true;
            Logger::warning("Tentativa de envio sem dados da empresa");
        }
        elseif (empty($dadosFormulario['tipo_certificado'])) {
            $mensagemErro = "Selecione o tipo de certificado.";
            $showErrorPopup = true;
            Logger::warning("Tentativa de envio sem tipo de certificado");
        }
        elseif (empty($dadosFormulario['data_renovacao']) || empty($dadosFormulario['certificado_vencimento'])) {
            $mensagemErro = "Preencha as datas de renovação e vencimento.";
            $showErrorPopup = true;
            Logger::warning("Tentativa de envio sem datas completas");
        }
        elseif (empty($dadosFormulario['emails_destinatario'])) {
            $mensagemErro = "Selecione pelo menos um email de destinatário.";
            $showErrorPopup = true;
            Logger::warning("Tentativa de envio sem destinatários");
        }
        else {
            $arquivoCertificado = isset($_FILES['arquivo_certificado']) ? $_FILES['arquivo_certificado'] : null;
            
            // Verificar se foi enviado um arquivo
            if (!isset($arquivoCertificado['tmp_name']) || empty($arquivoCertificado['tmp_name'])) {
                $mensagemErro = "É necessário selecionar um arquivo de certificado.";
                $showErrorPopup = true;
                Logger::warning("Tentativa de envio sem arquivo de certificado");
            } else {
                // Registrar preparação para o envio
                Logger::info("Preparando envio de certificado para {$dadosFormulario['emp_name']}", [
                    'tipo' => $dadosFormulario['tipo_certificado'],
                    'arquivo' => $arquivoCertificado['name'],
                    'destinatarios' => $dadosFormulario['emails_destinatario']
                ]);
                
                // Processar o envio do email
                $resultado = $controller->processarEnvio($dadosFormulario, $arquivoCertificado);
                
                if ($resultado['sucesso']) {
                    $mensagemSucesso = $resultado['mensagem'];
                    $showPopup = true;
                    
                    // Registrar sucesso
                    Logger::activity('envio_sucesso', "Certificado enviado com sucesso para {$dadosFormulario['emp_name']}");
                    
                    // Limpar o formulário após envio com sucesso
                    $dadosFormulario = array(
                        'emp_code' => '',
                        'emp_name' => '',
                        'emp_cnpj' => '',
                        'tipo_certificado' => '',
                        'data_renovacao' => '',
                        'certificado_vencimento' => '',
                        'emails_destinatario' => '',
                        'senha_certificado' => ''
                    );
                } else {
                    $mensagemErro = $resultado['mensagem'];
                    $showErrorPopup = true;
                    
                    // Registrar erro
                    Logger::error("Falha no envio de certificado: {$resultado['mensagem']}", [
                        'empresa' => $dadosFormulario['emp_name'],
                        'tipo' => $dadosFormulario['tipo_certificado']
                    ]);
                }
            }
        }
    }
}

// Obter lista de tipos de certificados
$tiposCertificado = $controller->getTiposCertificado();

// Função para formatar data para o formato brasileiro
function formatarData($data) {
    if (empty($data)) return '';
    return date('d/m/Y', strtotime($data));
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listagem de Usuários - <?php echo SITE_NAME; ?></title>
    
    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- Estilo personalizado -->
    <link rel="stylesheet" href="/GED2.0/assets/css/dashboard.css">
    <style>
        /* Estilos específicos da página */
        :root {
            --primary-color: #0078D4;
            --secondary-color: #106EBE;
            --light-color: #f5f5f5;
            --dark-color: #333333;
            --success-color: #28a745;
            --danger-color: #dc3545;
        }
        
        body {
            background-color: var(--light-color);
            font-family: 'Segoe UI', Arial, sans-serif;
            padding-top: 20px;
            padding-bottom: 20px;
        }
        
        .content-wrapper {
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: calc(100vh - 60px);
        }
        
        .page-container {
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
        }
        
        .page-title {
            margin-bottom: 20px;
            color: var(--primary-color);
            text-align: center;
            font-size: 1.8rem;
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            margin-bottom: 16px;
        }
        
        .header-card {
            background-color: var(--primary-color);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
            margin-bottom: 0;
        }
        
        .header-card h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .header-card p {
            margin: 5px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding: 12px 16px;
        }
        
        .card-header h5 {
            margin: 0;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 16px;
        }
        
        .card-body {
            padding: 16px;
        }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 6px;
            color: #555;
            font-size: 14px;
        }
        
        .form-control, .form-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            height: auto;
        }
        
        .mb-3 {
            margin-bottom: 12px !important;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 120, 212, 0.25);
        }
        
        .form-control[readonly] {
            background-color: #f5f5f5;
        }
        
        .form-text {
            color: #6c757d;
            font-size: 12px;
            margin-top: 4px;
        }
        
        .emails-display {
            min-height: 38px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f8f9fa;
            font-size: 14px;
        }
        
        .email-selector {
            max-height: 150px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px;
            margin-top: 8px;
            background-color: #f9f9f9;
        }
        
        .form-check {
            margin-bottom: 4px;
        }
        
        .form-check-label {
            font-size: 13px;
        }
        
        .btn-group-sm > .btn, .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .btn-send {
            padding: 10px 20px;
            font-size: 16px;
            font-weight: 600;
        }
        
        .modal-success .modal-header {
            background-color: var(--success-color);
            color: white;
        }
        
        .modal-error .modal-header {
            background-color: var(--danger-color);
            color: white;
        }
        
        .footer {
            text-align: center;
            padding: 15px;
            color: #666;
            font-size: 14px;
            margin-top: 20px;
            background-color: white;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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
            <header class="dashboard-header">
                <div class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </div>
                
                <div class="brasilia-time">
                    <i class="far fa-clock"></i> Horário de Brasília: <span id="brasilia-clock"><?php echo Config::getCurrentBrasiliaHour(); ?></span>
                </div>
                
                <div class="header-right">
                    <div class="notifications dropdown">
                        <button class="btn dropdown-toggle" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="far fa-bell"></i>
                            <span class="notification-badge">3</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                            <li><h6 class="dropdown-header">Notificações</h6></li>
                            <li><a class="dropdown-item" href="#">Novo documento adicionado</a></li>
                            <li><a class="dropdown-item" href="#">Certificado expirando em 10 dias</a></li>
                            <li><a class="dropdown-item" href="#">Solicitação de acesso pendente</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="#">Ver todas</a></li>
                        </ul>
                    </div>
                    
                    <div class="user-profile dropdown">
                        <button class="btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar">
                                <img src="/GED2.0/assets/img/avatar.png" alt="Avatar do Usuário">
                            </div>
                            <div class="user-info">
                                <span class="user-name"><?php echo $_SESSION['user_name']; ?></span>
                                <span class="user-role"><?php echo Auth::getUserTypeName(); ?></span>
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="/profile"><i class="fas fa-user-circle me-2"></i> Meu Perfil</a></li>
                            <li><a class="dropdown-item" href="/settings"><i class="fas fa-cog me-2"></i> Configurações</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/?logout=1"><i class="fas fa-sign-out-alt me-2"></i> Sair</a></li>
                        </ul>
                    </div>
                </div>
            </header>
    <div class="content-wrapper">
        <div class="page-container">
            <div class="card header-card">
                <h1>Gerenciador de Certificados Digitais</h1>
                <p>Contabilidade Estrela</p>
            </div>
            
            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data" id="form-certificado">
                <div class="card mb-3">
                    <div class="card-header">
                        <h5><i class="fas fa-upload me-2"></i> Upload do Certificado Digital</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="arquivo_certificado" class="form-label">Selecione o arquivo do certificado</label>
                            <input type="file" name="arquivo_certificado" id="arquivo_certificado" class="form-control" accept=".pfx,.p12,.cer,.pem">
                            <div class="form-text">Formatos aceitos: .pfx, .p12, .cer, .pem</div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">
                        <h5><i class="fas fa-building me-2"></i> Dados da Empresa</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="emp_code" class="form-label">Número da Empresa</label>
                            <div class="input-group">
                                <input type="text" name="emp_code" id="emp_code" class="form-control" placeholder="Digite o número da empresa" value="<?php echo $dadosFormulario['emp_code']; ?>" required>
                                <button type="button" class="btn btn-primary" id="buscar-empresa">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                            <div class="form-text">O número será usado para buscar os dados da empresa</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="emp_cnpj" class="form-label">CNPJ</label>
                                <input type="text" name="emp_cnpj" id="emp_cnpj" class="form-control" value="<?php echo $dadosFormulario['emp_cnpj']; ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="emp_name" class="form-label">Razão Social</label>
                                <input type="text" name="emp_name" id="emp_name" class="form-control" value="<?php echo $dadosFormulario['emp_name']; ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header">
                        <h5><i class="fas fa-certificate me-2"></i> Informações do Certificado</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tipo_certificado" class="form-label">Tipo de Certificado</label>
                                <select name="tipo_certificado" id="tipo_certificado" class="form-select" required>
                                    <option value="">Selecione o tipo...</option>
                                    <?php foreach ($tiposCertificado as $valor => $nome): ?>
                                    <option value="<?php echo $valor; ?>" <?php echo ($dadosFormulario['tipo_certificado'] === $valor) ? 'selected' : ''; ?>>
                                        <?php echo $nome; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="senha_certificado" class="form-label">Senha do Certificado</label>
                                <input type="text" name="senha_certificado" id="senha_certificado" class="form-control" value="<?php echo $dadosFormulario['senha_certificado']; ?>" placeholder="Digite a senha">
                                <div class="form-text">Senha que será incluída no email</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="data_renovacao" class="form-label">Data de Renovação</label>
                                <input type="date" name="data_renovacao" id="data_renovacao" class="form-control" value="<?php echo $dadosFormulario['data_renovacao']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="certificado_vencimento" class="form-label">Data de Vencimento</label>
                                <input type="date" name="certificado_vencimento" id="certificado_vencimento" class="form-control" value="<?php echo $dadosFormulario['certificado_vencimento']; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email do(s) Destinatário(s)</label>
                            <div id="emails-display" class="emails-display mb-1" contenteditable="true" style="background-color: white; border: 1px solid #ccc; padding: 8px;">
                                <?php echo $dadosFormulario['emails_destinatario']; ?>
                            </div>
                            
                            <!-- Seletor de emails -->
                            <div class="email-selector">
                                <?php foreach ($emailsDisponiveis as $email): ?>
                                <div class="form-check">
                                    <input class="form-check-input email-checkbox" type="checkbox" name="emails_selecionados[]" value="<?php echo $email; ?>" id="email_<?php echo md5($email); ?>">
                                    <label class="form-check-label" for="email_<?php echo md5($email); ?>">
                                        <?php echo $email; ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Botões de ação para o seletor de emails -->
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary me-1" id="select-all-emails">Selecionar Todos</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="deselect-all-emails">Limpar Seleção</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                
                
                <div class="d-flex justify-content-center mt-3 mb-4">
                    <button type="submit" name="acao" value="enviar_email" class="btn btn-primary btn-send">
                        <i class="fas fa-paper-plane me-2"></i> Enviar Certificado por Email
                    </button>
                </div>
            </form>
            
            <div class="footer">
                &copy; <?php echo date('Y'); ?> Contabilidade Estrela - Todos os direitos reservados
            </div>
        </div>
    </div>
    
    <!-- Modal de Sucesso -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-success">
                <div class="modal-header">
                    <h5 class="modal-title">Sucesso!</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-check-circle fa-4x text-success"></i>
                    </div>
                    <p class="text-center"><?php echo $mensagemSucesso; ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Erro -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-error">
                <div class="modal-header">
                    <h5 class="modal-title">Erro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-exclamation-circle fa-4x text-danger"></i>
                    </div>
                    <p class="text-center"><?php echo $mensagemErro; ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar modal de sucesso ou erro
            <?php if ($showPopup): ?>
            var successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
            <?php endif; ?>
            
            <?php if ($showErrorPopup): ?>
            var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            errorModal.show();
            <?php endif; ?>
            
            // Script para buscar os dados da empresa ao clicar no botão
            const numeroEmpresaInput = document.querySelector('#emp_code');
            const buscarEmpresaBtn = document.querySelector('#buscar-empresa');
            
            if (buscarEmpresaBtn) {
                buscarEmpresaBtn.addEventListener('click', function() {
                    // Verificar se o número da empresa foi preenchido
                    if (numeroEmpresaInput.value.trim() !== '') {
                        // Criar um elemento para indicar carregamento
                        const loadingIcon = document.createElement('span');
                        loadingIcon.classList.add('spinner-border', 'spinner-border-sm', 'ms-1');
                        loadingIcon.setAttribute('role', 'status');
                        loadingIcon.setAttribute('aria-hidden', 'true');
                        
                        // Adicionar ícone de carregamento e desabilitar botão
                        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Buscando...';
                        this.disabled = true;
                        
                        // Submit do formulário para buscar os dados
                        numeroEmpresaInput.form.submit();
                    } else {
                        // Mostrar mensagem de erro se o campo estiver vazio
                        var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                        document.querySelector('#errorModal .modal-body p').textContent = 'Por favor, digite o número da empresa antes de buscar.';
                        errorModal.show();
                    }
                });
            }
            
            // Permitir que o usuário pressione Enter no campo para buscar
            if (numeroEmpresaInput) {
                numeroEmpresaInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (buscarEmpresaBtn) {
                            buscarEmpresaBtn.click();
                        }
                    }
                });
            }
            
            // Validar datas ao mudar
            const dataRenovacao = document.querySelector('#data_renovacao');
            const dataVencimento = document.querySelector('#certificado_vencimento');
            
            if (dataRenovacao && dataVencimento) {
                dataVencimento.addEventListener('change', function() {
                    if (dataRenovacao.value && this.value) {
                        const renovacao = new Date(dataRenovacao.value);
                        const vencimento = new Date(this.value);
                        
                        if (vencimento < renovacao) {
                            var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                            document.querySelector('#errorModal .modal-body p').textContent = 'A data de vencimento não pode ser anterior à data de renovação.';
                            errorModal.show();
                            this.value = '';
                        }
                    }
                });
                
                dataRenovacao.addEventListener('change', function() {
                    if (dataVencimento.value && this.value) {
                        const renovacao = new Date(this.value);
                        const vencimento = new Date(dataVencimento.value);
                        
                        if (vencimento < renovacao) {
                            var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                            document.querySelector('#errorModal .modal-body p').textContent = 'A data de renovação não pode ser posterior à data de vencimento.';
                            errorModal.show();
                            this.value = '';
                        }
                    }
                });
            }
            
            // Manipulação do seletor de emails
            const emailCheckboxes = document.querySelectorAll('.email-checkbox');
            const emailsDisplay = document.getElementById('emails-display');
            
            emailCheckboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    updateSelectedEmails();
                });
            });
            
            // Botão selecionar todos emails
            document.getElementById('select-all-emails').addEventListener('click', function() {
                emailCheckboxes.forEach(function(checkbox) {
                    checkbox.checked = true;
                });
                updateSelectedEmails();
            });
            
            // Botão limpar seleção de emails
            document.getElementById('deselect-all-emails').addEventListener('click', function() {
                emailCheckboxes.forEach(function(checkbox) {
                    checkbox.checked = false;
                });
                updateSelectedEmails();
            });
            
            // Função para atualizar o display dos emails selecionados
            function updateSelectedEmails() {
                const selectedEmails = [];
                emailCheckboxes.forEach(function(checkbox) {
                    if (checkbox.checked) {
                        selectedEmails.push(checkbox.value);
                    }
                });
                
                emailsDisplay.textContent = selectedEmails.join(', ');
            }

            // Pré-selecionar emails se já houver emails preenchidos
            const preSelectedEmails = '<?php echo $dadosFormulario["emails_destinatario"]; ?>';
            if (preSelectedEmails) {
                const emailsArray = preSelectedEmails.split(',').map(e => e.trim());
                emailCheckboxes.forEach(function(checkbox) {
                    if (emailsArray.includes(checkbox.value)) {
                        checkbox.checked = true;
                    }
                });
            }
            
            // Validar formato de email
            const emailInput = document.querySelector('#emails-display');
            if (emailInput) {
                emailInput.addEventListener('blur', function() {
                    if (this.textContent) {
                        const emails = this.textContent.split(',').map(email => email.trim());
                        const invalidEmails = [];
                        
                        emails.forEach(email => {
                            if (email && !isValidEmail(email)) {
                                invalidEmails.push(email);
                            }
                        });
                        
                        if (invalidEmails.length > 0) {
                            var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                            document.querySelector('#errorModal .modal-body p').textContent = 'Os seguintes emails possuem formato inválido: ' + invalidEmails.join(', ');
                            errorModal.show();
                        }
                    }
                });
            }
            
            // Função para validar formato de email
            function isValidEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }
            
            // Limpar formulário após fechamento do modal de sucesso
            const successModal = document.getElementById('successModal');
            if (successModal) {
                successModal.addEventListener('hidden.bs.modal', function () {
                    document.getElementById('form-certificado').reset();
                    document.querySelector('#emp_cnpj').value = '';
                    document.querySelector('#emp_name').value = '';
                    document.querySelector('#emails-display').textContent = '';
                    
                    // Limpar seleção de emails
                    emailCheckboxes.forEach(function(checkbox) {
                        checkbox.checked = false;
                    });
                });
            }
        });
    </script>
</body>
</html>