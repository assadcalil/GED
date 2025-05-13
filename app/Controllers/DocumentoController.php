<?php
// /GED2.0/controllers/documentos_controller.php

// Verificar se as configurações já foram incluídas
if (!defined('ROOT_DIR')) {
    require_once __DIR__ . '/../../...../app/Config/App.php';
    require_once __DIR__ . '/../../...../app/Config/Database.php';
    require_once __DIR__ . '/../../...../app/Config/Auth.php';
    require_once __DIR__ . '/../../...../app/Config/Logger.php';
}

// Verificar autenticação
Auth::requireLogin();

// Definir cabeçalho JSON
header('Content-Type: application/json');

// Verificar método de requisição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Upload de arquivos
    if (isset($_FILES['files'])) {
        $empresaId = $_POST['empresa_id'] ?? 0;
        $empresaCode = $_POST['empresa_code'] ?? '';
        
        if (!$empresaId || !$empresaCode) {
            echo json_encode(['success' => false, 'message' => 'Dados da empresa não fornecidos.']);
            exit;
        }
        
        // Buscar o nome da empresa para compor o nome da pasta
        $empresa = Database::selectOne("SELECT emp_name FROM empresas WHERE emp_code = ?", [$empresaCode]);
        
        if (!$empresa) {
            echo json_encode(['success' => false, 'message' => 'Empresa não encontrada.']);
            exit;
        }
        
        // Definir o caminho de upload com formato emp_code - emp_name
        $pastaEmpresa = $empresaCode . ' - ' . $empresa['emp_name'];
        $uploadPath = "C:\\inetpub\\wwwroot\\GED2.0\\documentos\\empresas\\{$pastaEmpresa}";
        
        // Criar diretório se não existir
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        
        $uploadedFiles = [];
        $errors = [];
        
        foreach ($_FILES['files']['name'] as $key => $fileName) {
            $fileTmpName = $_FILES['files']['tmp_name'][$key];
            $fileSize = $_FILES['files']['size'][$key];
            $fileError = $_FILES['files']['error'][$key];
            
            if ($fileError === UPLOAD_ERR_OK) {
                // Sanitizar nome do arquivo
                $safeFileName = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $fileName);
                $destination = $uploadPath . DIRECTORY_SEPARATOR . $safeFileName;
                
                // Verificar se arquivo já existe
                if (file_exists($destination)) {
                    $safeFileName = time() . '_' . $safeFileName;
                    $destination = $uploadPath . DIRECTORY_SEPARATOR . $safeFileName;
                }
                
                if (move_uploaded_file($fileTmpName, $destination)) {
                    $uploadedFiles[] = $safeFileName;
                    Logger::activity('upload', "Upload do arquivo {$safeFileName} para empresa ID: {$empresaId}");
                } else {
                    $errors[] = "Erro ao mover o arquivo {$fileName}";
                }
            } else {
                $errors[] = "Erro no upload do arquivo {$fileName}";
            }
        }
        
        if (count($uploadedFiles) > 0) {
            echo json_encode(['success' => true, 'files' => $uploadedFiles, 'errors' => $errors]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Nenhum arquivo foi enviado.', 'errors' => $errors]);
        }
    }
    // Exclusão de arquivo
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $empresaId = $_POST['empresa_id'] ?? 0;
        $empresaCode = $_POST['empresa_code'] ?? '';
        $fileName = $_POST['file_name'] ?? '';
        
        if (!$empresaId || !$empresaCode || !$fileName) {
            echo json_encode(['success' => false, 'message' => 'Dados incompletos para exclusão.']);
            exit;
        }
        
        // Buscar o nome da empresa para compor o nome da pasta
        $empresa = Database::selectOne("SELECT emp_name FROM empresas WHERE emp_code = ?", [$empresaCode]);
        
        if (!$empresa) {
            echo json_encode(['success' => false, 'message' => 'Empresa não encontrada.']);
            exit;
        }
        
        $pastaEmpresa = $empresaCode . ' - ' . $empresa['emp_name'];
        $filePath = "C:\\inetpub\\wwwroot\\GED2.0\\documentos\\empresas\\{$pastaEmpresa}\\" . $fileName;
        
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                Logger::activity('delete', "Exclusão do arquivo {$fileName} da empresa ID: {$empresaId}");
                echo json_encode(['success' => true, 'message' => 'Arquivo excluído com sucesso.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao excluir o arquivo.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Arquivo não encontrado.']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
}
?>