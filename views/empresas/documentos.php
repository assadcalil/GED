<?php

// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}

// /GED2.0/views/empresas/documentos.php

// Verificar se as configurações já foram incluídas
if (!defined('ROOT_DIR')) {
    require_once __DIR__ . '/../../../...../app/Config/App.php';
    require_once __DIR__ . '/../../../...../app/Config/Database.php';
    require_once __DIR__ . '/../../../...../app/Config/Auth.php';
    require_once __DIR__ . '/../../../...../app/Config/Logger.php';
}

// Verificar autenticação
Auth::requireLogin();

// Obter ID da empresa
$empresaId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($empresaId <= 0) {
    header('Location: /GED2.0/views/empresas/list.php?error=invalid_id');
    exit;
}

// Buscar dados da empresa
$empresa = Database::selectOne("SELECT * FROM empresas WHERE id = ?", [$empresaId]);

if (!$empresa) {
    header('Location: /GED2.0/views/empresas/list.php?error=empresa_not_found');
    exit;
}

// Registrar acesso
Logger::activity('acesso', "Acessou documentos da empresa ID: {$empresaId}");

// Definir o caminho dos documentos
$documentosPath = "C:\\inetpub\\wwwroot\\GED2.0\\documentos\\empresas\\" . $empresa['emp_code'] . ' - ' . $empresa['emp_name'];
$webPath = "/GED2.0/documentos/empresas/" . $empresa['emp_code'] . ' - ' . $empresa['emp_name'];

// Verificar se a pasta existe, se não existir, criar
if (!file_exists($documentosPath)) {
    mkdir($documentosPath, 0777, true);
}

// Listar arquivos da pasta
// Listar arquivos da pasta
$arquivos = [];
if (is_dir($documentosPath)) {
    $files = scandir($documentosPath);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            // Use encapsulation and proper escaping for file paths
            $filePath = $documentosPath . DIRECTORY_SEPARATOR . $file;
            
            // Properly encode the file path for Windows
            $encodedFilePath = iconv('UTF-8', 'Windows-1252', $filePath);
            
            // Check if file exists and is readable before attempting to get info
            if (file_exists($encodedFilePath) && is_readable($encodedFilePath)) {
                $extension = strtolower(pathinfo($encodedFilePath, PATHINFO_EXTENSION));
                
                try {
                    $fileSize = filesize($encodedFilePath);
                    $fileModTime = filemtime($encodedFilePath);
                    
                    $arquivos[] = [
                        'nome' => $file,
                        'tamanho' => $fileSize,
                        'data_modificacao' => $fileModTime,
                        'tipo' => getFileType($extension),
                        'caminho_web' => $webPath . '/' . rawurlencode($file)
                    ];
                } catch (Exception $e) {
                    // Optionally log the error or skip problematic files
                    error_log("Error processing file: " . $encodedFilePath . " - " . $e->getMessage());
                }
            }
        }
    }
}

function getFileType($extension) {
    $fileTypes = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed'
    ];
    
    return $fileTypes[$extension] ?? 'application/octet-stream';
}

// Função para formatar tamanho de arquivo
function formatarTamanhoArquivo($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Função para obter ícone do arquivo
function getIconeArquivo($tipo) {
    if (strpos($tipo, 'pdf') !== false) {
        return 'fas fa-file-pdf text-danger';
    } elseif (strpos($tipo, 'word') !== false || strpos($tipo, 'document') !== false) {
        return 'fas fa-file-word text-primary';
    } elseif (strpos($tipo, 'excel') !== false || strpos($tipo, 'spreadsheet') !== false) {
        return 'fas fa-file-excel text-success';
    } elseif (strpos($tipo, 'image') !== false) {
        return 'fas fa-file-image text-info';
    } elseif (strpos($tipo, 'zip') !== false || strpos($tipo, 'rar') !== false) {
        return 'fas fa-file-archive text-warning';
    } else {
        return 'fas fa-file text-secondary';
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos - <?php echo htmlspecialchars($empresa['emp_name']); ?> - <?php echo SITE_NAME; ?></title>
    
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
        .document-card {
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0;
        }
        
        .document-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .file-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .upload-area {
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .upload-area:hover {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }
        
        .upload-area.dragover {
            border-color: #0d6efd;
            background-color: #e7f1ff;
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
            
            <!-- Conteúdo da Página -->
            <div class="dashboard-content">
                <div class="container-fluid">
                    <!-- Cabeçalho da Página -->
                    <div class="page-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="page-title">Documentos</h1>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="/GED2.0/views/empresas/list.php">Empresas</a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Documentos</li>
                                    </ol>
                                </nav>
                            </div>
                            <div class="col-auto">
                                <a href="/GED2.0/views/empresas/list.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Voltar
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informações da Empresa -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5><?php echo htmlspecialchars($empresa['emp_name']); ?></h5>
                                    <p class="mb-1"><strong>Código:</strong> <?php echo htmlspecialchars($empresa['emp_code']); ?></p>
                                    <p class="mb-1"><strong>CNPJ:</strong> <?php echo htmlspecialchars($empresa['emp_cnpj']); ?></p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                        <i class="fas fa-upload me-2"></i> Upload de Documento
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Listagem de Documentos -->
                    <?php if (empty($arquivos)): ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                                <h5>Nenhum documento encontrado</h5>
                                <p class="text-muted">Clique no botão "Upload de Documento" para adicionar arquivos</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($arquivos as $arquivo): ?>
                                <div class="col-md-4 col-lg-3 mb-4">
                                    <div class="card document-card">
                                        <div class="card-body text-center">
                                            <i class="<?php echo getIconeArquivo($arquivo['tipo']); ?> file-icon"></i>
                                            <h6 class="card-title text-truncate" title="<?php echo htmlspecialchars($arquivo['nome']); ?>">
                                                <?php echo htmlspecialchars($arquivo['nome']); ?>
                                            </h6>
                                            <p class="card-text small text-muted">
                                                Tamanho: <?php echo formatarTamanhoArquivo($arquivo['tamanho']); ?><br>
                                                Modificado: <?php echo date('d/m/Y H:i', $arquivo['data_modificacao']); ?>
                                            </p>
                                            <div class="btn-group">
                                                <a href="<?php echo $arquivo['caminho_web']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                    <i class="fas fa-eye"></i> Visualizar
                                                </a>
                                                <a href="<?php echo $arquivo['caminho_web']; ?>" class="btn btn-sm btn-success" download>
                                                    <i class="fas fa-download"></i> Baixar
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmarExclusao('<?php echo htmlspecialchars($arquivo['nome']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
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
    
    <!-- Modal de Upload -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Upload de Documento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="upload-area" id="uploadArea">
                        <i class="fas fa-cloud-upload-alt fa-3x mb-3 text-primary"></i>
                        <h5>Arraste e solte arquivos aqui</h5>
                        <p class="text-muted">ou</p>
                        <button type="button" class="btn btn-primary" id="selectFiles">
                            <i class="fas fa-folder-open me-2"></i> Selecionar Arquivos
                        </button>
                        <input type="file" id="fileInput" multiple hidden>
                    </div>
                    <div id="fileList" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="uploadButton" disabled>
                        <i class="fas fa-upload me-2"></i> Fazer Upload
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o arquivo <strong id="deleteFileName"></strong>?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i> Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteButton">Excluir</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap Bundle com Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script personalizado para documentos -->
    <script>
        // Variáveis globais
        const empresaId = <?php echo $empresaId; ?>;
        const empresaCode = '<?php echo $empresa['emp_code']; ?>';
        const empresaName = '<?php echo addslashes($empresa['emp_name']); ?>';
        let filesToUpload = [];
        
        // Inicialização
        $(document).ready(function() {
            // Configurar área de upload
            setupUploadArea();
            
            // Evento do botão de seleção de arquivos
            $('#selectFiles').click(function() {
                $('#fileInput').click();
            });
            
            // Evento de seleção de arquivos
            $('#fileInput').change(function() {
                handleFileSelect(this.files);
            });
            
            // Evento do botão de upload
            $('#uploadButton').click(function() {
                uploadFiles();
            });
        });
        
        // Configurar área de upload com drag and drop
        function setupUploadArea() {
            const uploadArea = document.getElementById('uploadArea');
            
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('dragover');
            });
            
            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('dragover');
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('dragover');
                handleFileSelect(e.dataTransfer.files);
            });
        }
        
        // Manipular seleção de arquivos
        function handleFileSelect(files) {
            filesToUpload = Array.from(files);
            updateFileList();
            $('#uploadButton').prop('disabled', filesToUpload.length === 0);
        }
        
        // Atualizar lista de arquivos
        function updateFileList() {
            const fileList = $('#fileList');
            fileList.empty();
            
            if (filesToUpload.length > 0) {
                const table = $('<table class="table table-sm">');
                const thead = $('<thead><tr><th>Arquivo</th><th>Tamanho</th><th>Ação</th></tr></thead>');
                const tbody = $('<tbody>');
                
                filesToUpload.forEach((file, index) => {
                    const row = $(`
                        <tr>
                            <td>${file.name}</td>
                            <td>${formatarTamanhoArquivo(file.size)}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger" onclick="removeFile(${index})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    `);
                    tbody.append(row);
                });
                
                table.append(thead).append(tbody);
                fileList.append(table);
            }
        }
        
        // Remover arquivo da lista
        function removeFile(index) {
            filesToUpload.splice(index, 1);
            updateFileList();
            $('#uploadButton').prop('disabled', filesToUpload.length === 0);
        }
        
        // Fazer upload dos arquivos
        function uploadFiles() {
            if (filesToUpload.length === 0) return;
            
            const formData = new FormData();
            formData.append('empresa_id', empresaId);
            formData.append('empresa_code', empresaCode);
            
            filesToUpload.forEach((file, index) => {
                formData.append('files[]', file);
            });
            
            $('#uploadButton').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i> Enviando...');
            
            $.ajax({
                url: '/GED2.0/controllers/documentos_controller.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Erro ao fazer upload: ' + response.message);
                        $('#uploadButton').prop('disabled', false).html('<i class="fas fa-upload me-2"></i> Fazer Upload');
                    }
                },
                error: function() {
                    alert('Erro ao fazer upload dos arquivos.');
                    $('#uploadButton').prop('disabled', false).html('<i class="fas fa-upload me-2"></i> Fazer Upload');
                }
            });
        }
        
        // Confirmar exclusão de arquivo
        function confirmarExclusao(fileName) {
            $('#deleteFileName').text(fileName);
            $('#deleteModal').modal('show');
            
            $('#confirmDeleteButton').off('click').on('click', function() {
                excluirArquivo(fileName);
            });
        }
        
        // Excluir arquivo
        function excluirArquivo(fileName) {
            $.ajax({
                url: '/GED2.0/controllers/documentos_controller.php',
                type: 'POST',
                data: {
                    action: 'delete',
                    empresa_id: empresaId,
                    empresa_code: empresaCode,
                    file_name: fileName
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Erro ao excluir arquivo: ' + response.message);
                    }
                },
                error: function() {
                    alert('Erro ao excluir arquivo.');
                }
            });
        }
        
        // Função para formatar tamanho de arquivo
        function formatarTamanhoArquivo(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>