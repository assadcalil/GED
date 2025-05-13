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
 * Edição de Certificados Digitais
 */

// Verificar se as configurações já foram incluídas
if (!defined('ROOT_DIR')) {
    require_once __DIR__ . '/../../../...../app/Config/App.php';
    require_once __DIR__ . '/../../../...../app/Config/Database.php';
    require_once __DIR__ . '/../../../...../app/Config/Auth.php';
    require_once __DIR__ . '/../../../...../app/Config/Logger.php';
}

// Verificar autenticação
Auth::requireLogin();

// Verificar se ID foi informado
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensagem'] = 'ID do certificado não informado.';
    $_SESSION['tipo'] = 'danger';
    header('Location: /ged2.0/views/certificates/');
    exit;
}

$certificadoId = (int)$_GET['id'];

// Buscar dados do certificado
$sql = "SELECT cd.*, e.id as empresa_id, e.emp_name, e.emp_code
         FROM certificado_digital cd
         INNER JOIN empresas e ON cd.empresa_id = e.id
         WHERE cd.certificado_id = ?";

$certificado = Database::selectOne($sql, [$certificadoId]);

if (!$certificado) {
    $_SESSION['mensagem'] = 'Certificado não encontrado.';
    $_SESSION['tipo'] = 'danger';
    header('Location: /ged2.0/views/certificates/');
    exit;
}

// Registrar acesso
Logger::activity('acesso', 'Acessou o formulário de edição do certificado #' . $certificadoId);

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

// Função para formatar data
function formatarData($data) {
    if (empty($data)) return '';
    
    $timestamp = strtotime($data);
    return date('d/m/Y', $timestamp);
}

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
    <title>Editar Certificado Digital - <?php echo SITE_NAME; ?></title>
    
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
        
        .arquivo-item {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 10px;
            background-color: #f8f9fa;
            transition: all 0.2s ease;
        }
        
        .arquivo-item:hover {
            background-color: #e9ecef;
        }
        
        .arquivo-icon {
            font-size: 24px;
            margin-right: 10px;
        }
        
        .file-pdf {
            color: #dc3545;
        }
        
        .file-image {
            color: #198754;
        }
        
        .file-archive {
            color: #fd7e14;
        }
        
        .file-certificate {
            color: #0d6efd;
        }
        
        .file-default {
            color: #6c757d;
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
                                <h1 class="page-title">Editar Certificado Digital</h1>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="/ged2.0/views/certificates/">Certificados Digitais</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Editar</li>
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
                            <h5 class="card-title mb-0">Informações do Certificado #<?php echo $certificadoId; ?></h5>
                        </div>
                        <div class="card-body">
                            <form action="/../ged2.0/../...../app/Controllers/CertificadoController.php" method="post" enctype="multipart/form-data" id="formCertificado">
                                <input type="hidden" name="acao" value="atualizar">
                                <input type="hidden" name="certificado_id" value="<?php echo $certificadoId; ?>">
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <div class="form-group mb-3">
                                            <label for="empresa_id" class="form-label required">Empresa</label>
                                            <select class="form-select" id="empresa_id" name="empresa_id" required>
                                                <option value="">Selecione uma empresa</option>
                                                <?php foreach ($empresas as $empresa): ?>
                                                    <option value="<?php echo $empresa['id']; ?>" <?php echo ($certificado['empresa_id'] == $empresa['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($empresa['emp_code'] . ' - ' . $empresa['emp_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Empresa associada ao certificado digital.</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="tipo_certificado" class="form-label required">Tipo de Certificado</label>
                                            <select class="form-select" id="tipo_certificado" name="tipo_certificado" required>
                                                <option value="">Selecione um tipo</option>
                                                <?php foreach ($tiposCertificados as $key => $value): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo ($certificado['certificado_tipo'] == $key) ? 'selected' : ''; ?>>
                                                        <?php echo $value; ?>
                                                    </option>
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
                                                <input type="text" class="form-control datepicker" id="certificado_emissao" 
                                                    name="certificado_emissao" placeholder="DD/MM/AAAA" required
                                                    value="<?php echo formatarData($certificado['certificado_emissao']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="certificado_validade" class="form-label required">Data de Validade</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                                <input type="text" class="form-control datepicker" id="certificado_validade" 
                                                    name="certificado_validade" placeholder="DD/MM/AAAA" required
                                                    value="<?php echo formatarData($certificado['certificado_validade']); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="certificado_titular" class="form-label">Responsável</label>
                                            <input type="text" class="form-control" id="certificado_titular" 
                                                name="certificado_titular" placeholder="Nome do responsável pelo certificado"
                                                value="<?php echo htmlspecialchars($certificado['certificado_titular'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="certificado_situacao" class="form-label required">Situação</label>
                                            <select class="form-select" id="certificado_situacao" name="certificado_situacao" required>
                                                <?php foreach ($situacoesCertificados as $key => $value): ?>
                                                    <option value="<?php echo $key; ?>" <?php echo ($certificado['certificado_situacao'] == $key) ? 'selected' : ''; ?>>
                                                        <?php echo $value; ?>
                                                    </option>
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
                                            <div>
                                                <a href="/../ged2.0/../...../app/Controllers/CertificadoController.php?acao=gerar_pdf&id=<?php echo $certificadoId; ?>" 
                                                   class="btn btn-info text-white me-2" target="_blank">
                                                    <i class="fas fa-file-pdf me-2"></i> Gerar PDF
                                                </a>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i> Salvar Alterações
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Histórico de Alterações
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history me-2"></i> Histórico de Alterações
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php

// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}

                            // Buscar histórico de alterações
                            $sqlHistorico = "SELECT h.*, u.nome as usuario_nome 
                                            FROM certificado_historico h
                                            LEFT JOIN usuarios u ON h.usuario_id = u.id
                                            WHERE h.certificado_id = ?
                                            ORDER BY h.data_alteracao DESC
                                            LIMIT 10";
                            $historico = Database::select($sqlHistorico, [$certificadoId]);
                            ?>
                            
                            <?php if (empty($historico)): ?>
                                <div class="alert alert-info">
                                    Nenhum registro de alteração encontrado para este certificado.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Usuário</th>
                                                <th>Ação</th>
                                                <th>Detalhes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($historico as $item): ?>
                                                <tr>
                                                    <td><?php echo date('d/m/Y H:i:s', strtotime($item['data_alteracao'])); ?></td>
                                                    <td><?php echo htmlspecialchars($item['usuario_nome']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['tipo_acao']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['descricao']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>-->
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
    
    <!-- Modal de Exclusão de Arquivo -->
    <div class="modal fade" id="deleteArquivoModal" tabindex="-1" aria-labelledby="deleteArquivoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteArquivoModalLabel">Confirmar Exclusão de Arquivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o arquivo <strong id="arquivoNome"></strong>?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i> Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="/../ged2.0/../...../app/Controllers/CertificadoController.php" method="post" id="deleteArquivoForm">
                        <input type="hidden" name="acao" value="remover_arquivo">
                        <input type="hidden" name="arquivo_id" id="arquivo_id">
                        <input type="hidden" name="certificado_id" value="<?php echo $certificadoId; ?>">
                        <button type="submit" class="btn btn-danger">Confirmar Exclusão</button>
                    </form>
                </div>
            </div>
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
        
        // Drag and drop para arquivos
        ['uploadArea', 'uploadAreaDocs'].forEach(function(id) {
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
        
        // Modal de exclusão de arquivo
        $('.delete-arquivo').click(function() {
            const id = $(this).data('id');
            const nome = $(this).data('nome');
            
            $('#arquivo_id').val(id);
            $('#arquivoNome').text(nome);
            
            $('#deleteArquivoModal').modal('show');
        });
    });
    </script>
</body>
</html>