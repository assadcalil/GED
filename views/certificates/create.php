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
 * Cadastro de Certificados Digitais
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

// Apenas administradores e editores podem cadastrar usuários
if (!Auth::isAdmin() && !Auth::isUserType(Auth::EDITOR)) {
    header('Location: /access-denied.php');
    exit;
}

// Registrar acesso
Logger::activity('acesso', 'Acessou o formulário de cadastro de certificado digital');

// Inicializar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_type'] = $_SESSION['user_type'] ?? 1; // Admin por padrão
$_SESSION['user_name'] = $_SESSION['user_name'] ?? 'Usuário do Sistema';

// Obter lista de empresas para o formulário
$sqlEmpresas = "SELECT id, emp_code, emp_name FROM empresas ORDER BY emp_name ASC";
$empresas = Database::select($sqlEmpresas);

// Arrays para campos de seleção
$tiposCertificados = [
    'e-CNPJ' => 'e-CNPJ',
    'e-CPF' => 'e-CPF',
    'NF-e' => 'NF-e',
    'CT-e' => 'CT-e',
    'MDF-e' => 'MDF-e',
    'OUTRO' => 'Outro'
];

$situacoesCertificados = [
    'VIGENTE' => 'Vigente',
    'VENCIDO' => 'Vencido',
    'PRESTES_A_VENCER' => 'Prestes a Vencer',
    'RENOVACAO_PENDENTE' => 'Renovação Pendente'
];

$mensagem = '';
$tipo = '';

// Verificar se há uma mensagem de retorno do processamento do formulário
if (isset($_SESSION['mensagem'])) {
    $mensagem = $_SESSION['mensagem'];
    $tipo = $_SESSION['tipo'];
    
    // Limpar as variáveis de sessão
    unset($_SESSION['mensagem']);
    unset($_SESSION['tipo']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Certificado Digital - <?php echo SITE_NAME; ?></title>
    
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
    
    <!-- Datepicker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <style>
        .form-label {
            font-weight: 500;
        }
        
        .required:after {
            content: ' *';
            color: red;
        }
        
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .upload-area:hover {
            border-color: #aaa;
            background-color: #f9f9f9;
        }
        
        .upload-icon {
            font-size: 40px;
            color: #6c757d;
            margin-bottom: 10px;
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
                                <h1 class="page-title">Cadastro de Certificado Digital</h1>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="/ged2.0/views/certificates/">Certificados Digitais</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Cadastrar</li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($mensagem)): ?>
                        <div class="alert alert-<?php echo $tipo; ?> alert-dismissible fade show" role="alert">
                            <?php echo $mensagem; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Card do formulário -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Informações do Certificado</h5>
                        </div>
                        <div class="card-body">
                            <form action="/ged2.0/controllers/certificados_controller.php" method="post" enctype="multipart/form-data" id="formCertificado">
                                <input type="hidden" name="acao" value="cadastrar">
                                
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5><i class="fas fa-building me-2"></i> Dados da Empresa</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="emp_code" class="form-label">Número da Empresa</label>
                                            <div class="input-group">
                                                <input type="text" name="emp_code" id="emp_code" class="form-control" placeholder="Digite o número da empresa" required>
                                                <button type="button" class="btn btn-primary" id="buscar-empresa">
                                                    <i class="fas fa-search"></i> Buscar
                                                </button>
                                            </div>
                                            <div class="form-text">O número será usado para buscar os dados da empresa</div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="emp_cnpj" class="form-label">CNPJ</label>
                                                <input type="text" name="emp_cnpj" id="emp_cnpj" class="form-control" readonly>
                                                <input type="hidden" name="empresa_id" id="empresa_id">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="emp_name" class="form-label">Razão Social</label>
                                                <input type="text" name="emp_name" id="emp_name" class="form-control" readonly>
                                            </div>
                                        </div>
                                        <div id="mensagem-empresa" class="mt-2"></div>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="tipo_certificado" class="form-label required">Tipo de Certificado</label>
                                            <select class="form-select" id="tipo_certificado" name="tipo_certificado" required>
                                                <option value="">Selecione um tipo</option>
                                                <?php foreach ($tiposCertificados as $key => $value): ?>
                                                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="certificado_emissao" class="form-label required">Data de Emissão</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                                <input type="text" class="form-control datepicker" id="certificado_emissao" name="certificado_emissao" placeholder="DD/MM/AAAA" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="certificado_validade" class="form-label required">Data de Validade</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                                <input type="text" class="form-control datepicker" id="certificado_validade" name="certificado_validade" placeholder="DD/MM/AAAA" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="certificado_responsavel" class="form-label">Responsável</label>
                                            <input type="text" class="form-control" id="certificado_responsavel" name="certificado_responsavel" placeholder="Nome do responsável pelo certificado">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="certificado_situacao" class="form-label required">Situação</label>
                                            <select class="form-select" id="certificado_situacao" name="certificado_situacao" required>
                                                <?php foreach ($situacoesCertificados as $key => $value): ?>
                                                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="d-flex justify-content-between">
                                            <a href="/ged2.0/views/certificates/list.php" class="btn btn-secondary">
                                                <i class="fas fa-arrow-left me-2"></i> Voltar
                                            </a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i> Salvar Certificado
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
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
    
    <!-- Flatpickr (Datepicker) -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    
    <!-- Script personalizado -->
    <script src="/GED2.0/assets/js/dashboard.js"></script>
    
    <script>
    $(document).ready(function() {
    // Configuração do datepicker
    $(".datepicker").flatpickr({
        locale: "pt",
        dateFormat: "d/m/Y",
        allowInput: true
    });
    
    // Habilitar/desabilitar campo "Outro Tipo"
    $('#tipo_certificado').change(function() {
        if ($(this).val() === 'OUTRO') {
            $('#outro_tipo').prop('disabled', false).prop('required', true);
        } else {
            $('#outro_tipo').prop('disabled', true).prop('required', false).val('');
        }
    });
    
    // Mostrar/ocultar senha
    $('.toggle-password').click(function() {
        const targetId = $(this).data('target');
        const inputField = $('#' + targetId);
        const icon = $(this).find('i');
        
        if (inputField.attr('type') === 'password') {
            inputField.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            inputField.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Verificar se os elementos existem antes de adicionar eventos
    if ($('#uploadArea').length) {
        // Upload de arquivo do certificado
        $('#uploadArea').click(function() {
            $('#arquivo_certificado').click();
        });
        
        $('#arquivo_certificado').change(function() {
            const file = this.files[0];
            if (file) {
                $('#fileName').text(file.name);
                $('#filePreview').removeClass('d-none');
            } else {
                $('#filePreview').addClass('d-none');
            }
        });
    }
    
    if ($('#uploadAreaDocs').length) {
        // Upload de documentos adicionais
        $('#uploadAreaDocs').click(function() {
            $('#documentacao_adicional').click();
        });
        
        $('#documentacao_adicional').change(function() {
            const files = this.files;
            if (files.length > 0) {
                $('#docsCount').text(files.length);
                $('#docsPreview').removeClass('d-none');
            } else {
                $('#docsPreview').addClass('d-none');
            }
        });
    }
    
    // Corrigir o código para Drag and Drop - verificar se os elementos existem
    const dragDropElements = ['uploadArea', 'uploadAreaDocs'].filter(id => document.getElementById(id));
    
    dragDropElements.forEach(function(id) {
        const element = document.getElementById(id);
        
        element.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('bg-light');
        });
        
        element.addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('bg-light');
        });
        
        element.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('bg-light');
            
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (id === 'uploadArea') {
                if (files.length > 0) {
                    document.getElementById('arquivo_certificado').files = files;
                    $('#fileName').text(files[0].name);
                    $('#filePreview').removeClass('d-none');
                }
            } else {
                if (files.length > 0) {
                    document.getElementById('documentacao_adicional').files = files;
                    $('#docsCount').text(files.length);
                    $('#docsPreview').removeClass('d-none');
                }
            }
        });
    });
    
    // Validação do formulário antes de enviar
    $('#formCertificado').submit(function(e) {
        const emissao = $('#certificado_emissao').val();
        const validade = $('#certificado_validade').val();
        
        if (emissao && validade) {
            // Converter para formato de data para comparação
            const partsEmissao = emissao.split('/');
            const partsValidade = validade.split('/');
            
            const dataEmissao = new Date(partsEmissao[2], partsEmissao[1] - 1, partsEmissao[0]);
            const dataValidade = new Date(partsValidade[2], partsValidade[1] - 1, partsValidade[0]);
            
            if (dataValidade < dataEmissao) {
                e.preventDefault();
                alert('A data de validade não pode ser anterior à data de emissão.');
                return false;
            }
        }
        
        return true;
    });
    
    // Busca de empresa por código - corrigido para evitar erro de variável
    $('#buscar-empresa').click(function() {
        const codigo = $('#emp_code').val().trim(); // Corrigido o nome da variável
        
        if (codigo === '') {
            $('#mensagem-empresa').html(
                '<div class="alert alert-warning">' +
                '<i class="fas fa-exclamation-triangle me-2"></i> Por favor, digite o número da empresa.' +
                '</div>'
            );
            return;
        }
        
        // Mostrar indicador de carregamento
        $('#mensagem-empresa').html(
            '<div class="d-flex align-items-center">' +
            '<div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>' +
            '<span>Buscando empresa...</span>' +
            '</div>'
        );
        
        // Limpar campos
        $('#emp_name').val('');
        $('#emp_cnpj').val('');
        $('#empresa_id').val('');
        
        // Fazer a consulta AJAX - caminho corrigido
        $.ajax({
            url: '/ged2.0/controllers/buscar_empresa.php',
            method: 'GET',
            data: {
                codigo: codigo // Usar a variável correta
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const empresa = response.data;
                    
                    // Preencher os campos com os dados da empresa
                    $('#empresa_id').val(empresa.id);
                    $('#emp_name').val(empresa.emp_name);
                    $('#emp_cnpj').val(empresa.emp_cnpj || '');
                    
                    $('#mensagem-empresa').html(
                        '<div class="alert alert-success">' +
                        '<i class="fas fa-check-circle me-2"></i> Empresa encontrada com sucesso!' +
                        '</div>'
                    );
                } else {
                    // Limpar campos e mostrar mensagem de erro
                    $('#emp_name').val('');
                    $('#emp_cnpj').val('');
                    $('#empresa_id').val('');
                    
                    $('#mensagem-empresa').html(
                        '<div class="alert alert-danger">' +
                        '<i class="fas fa-times-circle me-2"></i> ' + response.message +
                        '</div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                // Mensagem em caso de erro de comunicação
                $('#emp_name').val('');
                $('#emp_cnpj').val('');
                $('#empresa_id').val('');
                
                console.log('Erro AJAX:', xhr.responseText, status, error);
                
                $('#mensagem-empresa').html(
                    '<div class="alert alert-danger">' +
                    '<i class="fas fa-exclamation-circle me-2"></i> Falha na comunicação com o servidor. ' +
                    'Código: ' + (xhr.status || 'desconhecido') + '. Tente novamente.' +
                    '</div>'
                );
            }
        });
    });

    // Limpar mensagem quando o usuário digita novamente
    $('#emp_code').on('input', function() {
        $('#mensagem-empresa').html('');
    });
});
    </script>
</body>
</html>