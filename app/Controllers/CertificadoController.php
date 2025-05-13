<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Sistema Contabilidade Estrela 2.0
 * Controller para gerenciamento de Certificados Digitais
 */

// Verificar se as configurações já foram incluídas
if (!defined('ROOT_DIR')) {
    require_once __DIR__ . '/../../...../app/Config/App.php';
    require_once __DIR__ . '/../../...../app/Config/Database.php';
    require_once __DIR__ . '/../../...../app/Config/Auth.php';
    require_once __DIR__ . '/../../...../app/Config/Logger.php';
}

// Inicializar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação
Auth::requireLogin();

// Processar a ação solicitada
$acao = isset($_POST['acao']) ? $_POST['acao'] : (isset($_GET['acao']) ? $_GET['acao'] : '');

switch ($acao) {
    case 'cadastrar':
        cadastrarCertificado();
        break;
    case 'editar':
        editarCertificado();
        break;
    case 'excluir':
        excluirCertificado();
        break;
    case 'visualizar':
        visualizarCertificado();
        break;
    default:
        // Ação inválida, redirecionar para a lista
        header('Location: /ged2.0/views/certificates/list.php');
        exit;
}

/**
 * Função para cadastrar um novo certificado digital
 */
function cadastrarCertificado() {
    // Verificar permissões
    if (!Auth::isAdmin() && !Auth::isUserType(Auth::EDITOR)) {
        setMensagem('Você não tem permissão para cadastrar certificados digitais.', 'danger');
        header('Location: /ged2.0/views/certificates/list.php');
        exit;
    }

    // Validar dados do formulário
    $empresa_id = filter_input(INPUT_POST, 'empresa_id', FILTER_VALIDATE_INT);
    $emp_code = filter_input(INPUT_POST, 'emp_code', FILTER_SANITIZE_STRING);
    $emp_name = filter_input(INPUT_POST, 'emp_name', FILTER_SANITIZE_STRING);
    $emp_cnpj = filter_input(INPUT_POST, 'emp_cnpj', FILTER_SANITIZE_STRING);
    $tipo_certificado = filter_input(INPUT_POST, 'tipo_certificado', FILTER_SANITIZE_STRING);
    $certificado_emissao = filter_input(INPUT_POST, 'certificado_emissao', FILTER_SANITIZE_STRING);
    $certificado_validade = filter_input(INPUT_POST, 'certificado_validade', FILTER_SANITIZE_STRING);
    $certificado_responsavel = filter_input(INPUT_POST, 'certificado_responsavel', FILTER_SANITIZE_STRING);
    $certificado_situacao = filter_input(INPUT_POST, 'certificado_situacao', FILTER_SANITIZE_STRING);
    
    // Validação básica
    if (!$empresa_id || !$emp_code || !$emp_name || !$tipo_certificado || !$certificado_emissao || !$certificado_validade || !$certificado_situacao) {
        setMensagem('Preencha todos os campos obrigatórios.', 'danger');
        header('Location: /ged2.0/views/certificates/create.php');
        exit;
    }
    
    // Converter datas do formato brasileiro para formato do banco de dados (Y-m-d)
    $emissao_db = converterDataParaBanco($certificado_emissao);
    $validade_db = converterDataParaBanco($certificado_validade);
    
    // Verificar se a data de validade é posterior à data de emissão
    if (strtotime($validade_db) <= strtotime($emissao_db)) {
        setMensagem('A data de validade deve ser posterior à data de emissão.', 'danger');
        header('Location: /ged2.0/views/certificates/create.php');
        exit;
    }
    
    // Criar o titular do certificado
    $certificado_titular = $emp_name;
    
    // Adicionar o responsável ao titular, se existir
    if (!empty($certificado_responsavel)) {
        $certificado_titular .= ' - ' . $certificado_responsavel;
    }
    
    // Mapear tipo de certificado para campos corretos da tabela
    $certificado_tipo = 'A1'; // Valor padrão
    $certificado_categoria = 'E-CNPJ'; // Valor padrão
    
    // Determinar tipo e categoria com base no valor do campo tipo_certificado
    switch($tipo_certificado) {
        case 'e-CNPJ':
            $certificado_tipo = 'A3';
            $certificado_categoria = 'E-CNPJ';
            break;
        case 'e-CPF':
            $certificado_tipo = 'A3';
            $certificado_categoria = 'E-CPF';
            break;
        case 'NF-e':
        case 'CT-e':
        case 'MDF-e':
            $certificado_tipo = 'A1';
            $certificado_categoria = 'E-CNPJ';
            break;
        default:
            // Valor padrão já definido
            break;
    }
    
    // Inserir no banco de dados
    try {
        $sql = "INSERT INTO certificado_digital 
                (empresa_id, emp_name, emp_code, certificado_tipo, certificado_categoria, 
                certificado_emissao, certificado_validade, certificado_situacao, certificado_titular) 
                VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $empresa_id,
            $emp_name,
            $emp_code,
            $certificado_tipo,
            $certificado_categoria,
            $emissao_db,
            $validade_db,
            $certificado_situacao,
            $certificado_titular
        ];
        
        $result = Database::execute($sql, $params);
        
        if ($result) {
            // Tentar obter o último ID inserido de várias formas
            try {
                // Tentar método lastInsertId se existir
                if (method_exists('Database', 'lastInsertId')) {
                    $certificado_id = Database::lastInsertId();
                } else {
                    // Usar consulta SQL como fallback
                    $sql_last_id = "SELECT MAX(certificado_id) AS last_id FROM certificado_digital";
                    $lastIdResult = Database::select($sql_last_id);
                    $certificado_id = $lastIdResult[0]['last_id'];
                }
            } catch (Exception $e) {
                Logger::error('certificado', "Erro ao obter último ID inserido: " . $e->getMessage());
                $certificado_id = 'Desconhecido';
            }
            
            // Registrar atividade no log
            Logger::activity('certificado', "Cadastrou certificado digital ID: {$certificado_id} para empresa ID: {$empresa_id}");
            
            // Enviar e-mail de notificação
            enviarEmailCadastroCertificado($certificado_id, $emp_name, $emp_cnpj, $tipo_certificado, $certificado_emissao, $certificado_validade, $certificado_situacao, $certificado_responsavel);
            
            setMensagem('Certificado digital cadastrado com sucesso!', 'success');
            header('Location: /ged2.0/views/certificates/list.php');
            exit;
        } else {
            throw new Exception("Erro ao inserir dados no banco.");
        }
    } catch (Exception $e) {
        Logger::error('certificado', "Erro ao cadastrar certificado: " . $e->getMessage());
        setMensagem('Erro ao cadastrar o certificado digital. Por favor, tente novamente.', 'danger');
        header('Location: /ged2.0/views/certificates/create.php');
        exit;
    }
}

/**
 * Envia e-mail notificando o cadastro de um certificado digital
 */
function enviarEmailCadastroCertificado($certificado_id, $emp_name, $emp_cnpj, $tipo_certificado, $certificado_emissao, $certificado_validade, $certificado_situacao, $certificado_responsavel) {
    // Incluir o DAO de email
    require_once __DIR__ . '/../../...../app/Dao/CertificadoEmailDao.php';
    
    try {
        // Obter o nome do usuário logado
        $usuario = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Usuário do Sistema';
        
        // Preparar os dados para o envio do email
        $dados = [
            'certificado_id' => $certificado_id,
            'emp_name' => $emp_name,
            'emp_cnpj' => $emp_cnpj,
            'tipo_certificado' => $tipo_certificado,
            'certificado_emissao' => $certificado_emissao,
            'certificado_validade' => $certificado_validade,
            'certificado_situacao' => $certificado_situacao,
            'certificado_responsavel' => $certificado_responsavel,
            'usuario' => $usuario,
            'data_cadastro' => date('d/m/Y H:i:s'),
            'url_sistema' => 'http://' . $_SERVER['HTTP_HOST'] . '/ged2.0/views/certificates/view.php?id=' . $certificado_id
        ];
        
        // Instanciar o DAO
        $certificadoEmailDAO = new CertificadoEmailDAO();
        
        // Enviar email
        $resultado = $certificadoEmailDAO->enviarEmailNotificacao($dados);
        
        // Registrar no log se o e-mail foi enviado
        if ($resultado['sucesso']) {
            Logger::activity('email', "E-mail de notificação enviado com sucesso para certificado ID: {$certificado_id}");
        } else {
            Logger::error('email', "Falha ao enviar e-mail de notificação: " . $resultado['mensagem']);
        }
        
        return $resultado['sucesso'];
    } catch (Exception $e) {
        Logger::error('certificado', "Erro ao processar envio de email: " . $e->getMessage());
        return false;
    }
}

/**
 * Função para excluir um certificado digital
 */
function excluirCertificado() {
    // Verificar permissões
    if (!Auth::isAdmin()) {
        setMensagem('Você não tem permissão para excluir certificados digitais.', 'danger');
        header('Location: /ged2.0/views/certificates/list.php');
        exit;
    }
    
    $certificado_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$certificado_id) {
        setMensagem('ID do certificado inválido.', 'danger');
        header('Location: /ged2.0/views/certificates/list.php');
        exit;
    }
    
    // Verificar se o certificado existe
    $certificado = Database::select("SELECT * FROM certificado_digital WHERE certificado_id = ?", [$certificado_id]);
    
    if (empty($certificado)) {
        setMensagem('Certificado digital não encontrado.', 'danger');
        header('Location: /ged2.0/views/certificates/list.php');
        exit;
    }
    
    // Excluir do banco de dados (exclusão física, pois não há campo de exclusão lógica na tabela)
    try {
        $sql = "DELETE FROM certificado_digital WHERE certificado_id = ?";
        
        $params = [$certificado_id];
        
        $result = Database::execute($sql, $params);
        
        if ($result) {
            // Registrar atividade no log
            Logger::activity('certificado', "Excluiu certificado digital ID: {$certificado_id}");
            
            setMensagem('Certificado digital excluído com sucesso!', 'success');
        } else {
            throw new Exception("Erro ao excluir dados no banco.");
        }
    } catch (Exception $e) {
        Logger::error('certificado', "Erro ao excluir certificado: " . $e->getMessage());
        setMensagem('Erro ao excluir o certificado digital. Por favor, tente novamente.', 'danger');
    }
    
    header('Location: /ged2.0/views/certificates/list.php');
    exit;
}

/**
 * Função para visualizar detalhes de um certificado digital
 */
function visualizarCertificado() {
    $certificado_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$certificado_id) {
        setMensagem('ID do certificado inválido.', 'danger');
        header('Location: /ged2.0/views/certificates/list.php');
        exit;
    }
    
    // Registrar atividade no log
    Logger::activity('certificado', "Visualizou certificado digital ID: {$certificado_id}");
    
    // Redirecionar para a página de visualização
    header("Location: /ged2.0/views/certificates/view.php?id={$certificado_id}");
    exit;
}

/**
 * Função auxiliar para converter data do formato brasileiro (DD/MM/AAAA) para o formato do banco (AAAA-MM-DD)
 */
function converterDataParaBanco($data) {
    if (empty($data)) {
        return null;
    }
    
    $partes = explode('/', $data);
    
    if (count($partes) !== 3) {
        return null;
    }
    
    return $partes[2] . '-' . $partes[1] . '-' . $partes[0];
}

/**
 * Função auxiliar para definir mensagens de feedback na sessão
 */
function setMensagem($mensagem, $tipo) {
    $_SESSION['mensagem'] = $mensagem;
    $_SESSION['tipo'] = $tipo;
}
?>