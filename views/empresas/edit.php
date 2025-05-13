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
 * Edição de Empresas
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

// Apenas administradores e editores podem editar empresas
if (!Auth::isAdmin() && !Auth::isUserType(Auth::EDITOR)) {
    header('Location: /access-denied.php');
    exit;
}

// Verificar se foi fornecido um ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$id = (int) $_GET['id'];

// Registrar acesso
Logger::activity('acesso', 'Acessou o formulário de edição da empresa ID: ' . $id);

// Inicializar sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_type'] = $_SESSION['user_type'] ?? 1; // Admin por padrão
$_SESSION['user_name'] = $_SESSION['user_name'] ?? 'Usuário do Sistema';

// Arrays para campos de seleção
$camposDatas = [
    'emp_ult_reg', 
    'emp_cer_dig_data', 
    'soc1_entrada', 
    'soc2_entrada', 
    'soc3_entrada'
];

$camposNumericos = [
    'soc1_capit', 
    'soc2_capit', 
    'soc3_capit'
];

$situacoesCadastrais = [
    'ATIVA' => 'Ativa',
    'INATIVA' => 'Inativa',
    'SUSPENSA' => 'Suspensa',
    'BAIXADA' => 'Baixada'
];

$orgaoRegistro = [
    'JUNTA COMERCIAL' => 'Junta Comercial (Soc. Empresária)', 
    'CARTORIO DE REGISTROS' => 'Cartório de Registro (Soc. Simples)'
];

$portes = [
    'MEI' => 'Microempreendedor Individual',
    'ME' => 'Microempresa',
    'EPP' => 'Empresa de Pequeno Porte',
    'MEDIO' => 'Médio Porte',
    'GRANDE' => 'Grande Porte'
];

$tiposJuridicos = [
    'EI' => 'Empresário Individual',
    'EIRELI' => 'Empresa Individual de Responsabilidade Limitada',
    'LTDA' => 'Sociedade Limitada',
    'SA' => 'Sociedade Anônima',
    'SLU' => 'Sociedade Limitada Unipessoal',
    'OUTROS' => 'Outros'
];

$regimesApuracao = [
    'SIMPLES' => 'Simples Nacional',
    'LUCRO_PRESUMIDO' => 'Lucro Presumido',
    'LUCRO_REAL' => 'Lucro Real'
];

$ufs = [
    'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia',
    'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás',
    'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
    'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí',
    'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
    'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
    'SE' => 'Sergipe', 'TO' => 'Tocantins'
];

$naturejaJuridica = [
    '201-1' => '201-1 - Empresa Pública',
    '203-8' => '203-8 - Sociedade de Economia Mista',
    '204-6' => '204-6 - Sociedade Anônima Aberta',
    '205-4' => '205-4 - Sociedade Anônima Fechada',
    '206-2' => '206-2 - Sociedade Empresária Limitada',
    '207-0' => '207-0 - Sociedade Empresária em Nome Coletivo',
    '208-9' => '208-9 - Sociedade Empresária em Comandita Simples',
    '209-7' => '209-7 - Sociedade Empresária em Comandita por Ações',
    '212-7' => '212-7 - Sociedade em Conta de Participação',
    '213-5' => '213-5 - Empresário (Individual)',
    '214-3' => '214-3 - Cooperativa',
    '215-1' => '215-1 - Consórcio de Sociedades',
    '216-0' => '216-0 - Grupo de Sociedades',
    '217-8' => '217-8 - Estabelecimento, no Brasil, de Sociedade Estrangeira',
    '219-4' => '219-4 - Estabelecimento, no Brasil, de Empresa Binacional Argentino-Brasileira',
    '221-6' => '221-6 - Empresa Domiciliada no Exterior',
    '222-4' => '222-4 - Clube/Fundo de Investimento',
    '223-2' => '223-2 - Sociedade Simples Pura',
    '224-0' => '224-0 - Sociedade Simples Limitada',
    '225-9' => '225-9 - Sociedade Simples em Nome Coletivo',
    '226-7' => '226-7 - Sociedade Simples em Comandita Simples',
    '227-5' => '227-5 - Empresa Binacional',
    '228-3' => '228-3 - Consórcio de Empregadores',
    '229-1' => '229-1 - Consórcio Simples',
    '230-5' => '230-5 - Empresa Individual de Responsabilidade Limitada (de Natureza Empresária)',
    '231-3' => '231-3 - Empresa Individual de Responsabilidade Limitada (de Natureza Simples)',
    '232-1' => '232-1 - Sociedade Unipessoal de Advogados',
    '233-0' => '233-0 - Cooperativas de Consumo'
];

// Inicializar variáveis
$empresaData = [
    // Dados da empresa
    'emp_sit_cad' => 'ATIVA',
    'emp_code' => '',
    'emp_name' => '',
    'emp_tel' => '',
    'emp_cnpj' => '',
    'emp_iest' => '',
    'emp_imun' => '',
    'emp_reg_apu' => 'SIMPLES',
    'emp_porte' => 'ME',
    'emp_tipo_jur' => 'LTDA',
    'emp_nat_jur' => '206-2',
    'emp_cep' => '',
    'emp_ende' => '',
    'emp_nume' => '',
    'emp_comp' => '',
    'emp_bair' => '',
    'emp_cid' => '',
    'emp_uf' => '',
    'emp_org_reg' => '',
    'emp_reg_nire' => '',
    'emp_ult_reg' => '',
    'emp_cod_ace' => '',
    'emp_cod_pre' => '',
    'senha_pfe' => '',
    'emp_cer_dig_data' => '',
    'name' => '',
    'email_empresa' => '',
    
    // Sócio 1
    'soc1_name' => '',
    'soc1_cpf' => '',
    'soc1_entrada' => '',
    'soc1_email' => '',
    'soc1_tel' => '',
    'soc1_cel' => '',
    'soc1_cep' => '',
    'soc1_ende' => '',
    'soc1_nume' => '',
    'soc1_comp' => '',
    'soc1_bair' => '',
    'soc1_cid' => '',
    'soc1_uf' => '',
    'soc1_quali' => '',
    'soc1_ass' => '',
    'soc1_capit' => '',
    'soc1_govbr' => '',
    'soc1_qualif_govbr' => '',
    
    // Sócio 2
    'soc2_name' => '',
    'soc2_cpf' => '',
    'soc2_entrada' => '',
    'soc2_email' => '',
    'soc2_tel' => '',
    'soc2_cel' => '',
    'soc2_cep' => '',
    'soc2_ende' => '',
    'soc2_nume' => '',
    'soc2_comp' => '',
    'soc2_bair' => '',
    'soc2_cid' => '',
    'soc2_uf' => '',
    'soc2_quali' => '',
    'soc2_ass' => '',
    'soc2_capit' => '',
    'soc2_govbr' => '',
    'soc2_qualif_govbr' => '',
    
    // Sócio 3
    'soc3_name' => '',
    'soc3_cpf' => '',
    'soc3_entrada' => '',
    'soc3_email' => '',
    'soc3_tel' => '',
    'soc3_cel' => '',
    'soc3_cep' => '',
    'soc3_ende' => '',
    'soc3_nume' => '',
    'soc3_comp' => '',
    'soc3_bair' => '',
    'soc3_cid' => '',
    'soc3_uf' => '',
    'soc3_quali' => '',
    'soc3_ass' => '',
    'soc3_capit' => '',
    'soc3_govbr' => '',
    'soc3_qualif_govbr' => '',
    
    // Outras informações
    'email' => '',
    'usuario' => '',
    'pasta' => ''
];

$success = '';
$error = '';
$errors = [];
$debugInfo = '';

try {
    // Obter conexão com o banco de dados
    $conn = Database::getConnection();
    
    // Buscar dados da empresa
    $query = "SELECT * FROM empresas WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id]);
    $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$empresa) {
        header('Location: list.php');
        exit;
    }
    
    // Preencher array empresaData com os valores do banco de dados
    foreach ($empresaData as $key => $value) {
        if (isset($empresa[$key])) {
            $empresaData[$key] = $empresa[$key];
        }
    }
    
} catch (PDOException $e) {
    $error = "Erro ao buscar dados da empresa: " . $e->getMessage();
    error_log("Erro PDO ao buscar empresa: " . $e->getMessage());
}

// Processar formulário se for uma requisição POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar campos obrigatórios
        $camposObrigatorios = ['emp_code', 'emp_name', 'emp_cnpj', 'name', 'soc1_name', 'soc1_cpf'];
        $camposFaltantes = [];
        
        foreach ($camposObrigatorios as $campo) {
            if (empty($_POST[$campo])) {
                $camposFaltantes[] = $campo;
            }
        }
        
        if (!empty($camposFaltantes)) {
            $error = "Os seguintes campos obrigatórios não foram preenchidos: " . implode(', ', $camposFaltantes);
            throw new Exception($error);
        }
        
        // Verificar se nome é igual ao nome do primeiro sócio
        if ($_POST['name'] !== $_POST['soc1_name']) {
            throw new Exception("O nome do responsável deve ser igual ao nome do primeiro sócio.");
        }
        
        // Verificar se o código da empresa já existe (exceto para a empresa atual)
        if ($_POST['emp_code'] !== $empresaData['emp_code']) {
            $verificaCodigo = $conn->prepare("SELECT * FROM empresas WHERE emp_code = ? AND id != ?");
            $verificaCodigo->execute([$_POST['emp_code'], $id]);
            $empresaExistente = $verificaCodigo->fetch(PDO::FETCH_ASSOC);

            if ($empresaExistente) {
                $error = "Já existe uma empresa cadastrada com o código {$_POST['emp_code']}. 
                          Detalhes da empresa existente:
                          - Nome: {$empresaExistente['emp_name']}
                          - CNPJ: {$empresaExistente['emp_cnpj']}";
                
                // Adicionar JavaScript para mostrar popup
                $scriptPopup = "
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Empresa Já Cadastrada',
                            html: `" . addslashes($error) . "`,
                            confirmButtonText: 'Entendi',
                            confirmButtonColor: '#3085d6'
                        });
                    });
                </script>";
                
                throw new Exception($error);
            }
        }
        
        // Atualizar array $empresaData com os valores do POST
        foreach ($_POST as $key => $value) {
            if (array_key_exists($key, $empresaData)) {
                // Transformar campos de data e numéricos vazios em NULL
                if ((in_array($key, $camposDatas) || in_array($key, $camposNumericos)) && $value === '') {
                    $empresaData[$key] = null;
                } else {
                    $empresaData[$key] = $value;
                }
            }
        }

        // Converter campos numéricos para o formato do banco de dados
        foreach ($camposNumericos as $campo) {
            if (isset($empresaData[$campo]) && !is_null($empresaData[$campo]) && $empresaData[$campo] !== '') {
                // Remover R$ se existir
                $valor = str_replace('R$', '', $empresaData[$campo]);
                // Remover pontos de milhar e substituir vírgula por ponto
                $valor = str_replace('.', '', $valor);
                $valor = str_replace(',', '.', $valor);
                // Atribuir valor convertido
                $empresaData[$campo] = $valor;
                }
            }
        
        
        // Adicionar o usuário que está fazendo a edição
        $empresaData['usuario'] = $_SESSION['user_name'] ?? 'Sistema';
        
        // Atualizar nome da pasta se o código ou nome da empresa mudou
        if ($_POST['emp_code'] !== $empresa['emp_code'] || $_POST['emp_name'] !== $empresa['emp_name']) {
            $novaPasta = $_POST['emp_code'] . " - " . $_POST['emp_name'];
            $empresaData['pasta'] = $novaPasta;
            
            // Renomear diretório se ele existir
            $diretorioAntigo = __DIR__ . "/../../documentos/empresas/" . $empresa['pasta'];
            $diretorioNovo = __DIR__ . "/../../documentos/empresas/" . $novaPasta;
            
            if (file_exists($diretorioAntigo) && $empresa['pasta'] !== $novaPasta) {
                rename($diretorioAntigo, $diretorioNovo);
            } elseif (!file_exists($diretorioNovo)) {
                mkdir($diretorioNovo, 0755, true);
            }
        } else {
            // Manter pasta atual
            $empresaData['pasta'] = $empresa['pasta'];
        }
        
        // Campos para update (excluindo botões e campos não mapeados)
        $camposValidos = array_filter(array_keys($empresaData), function($key) {
            return !in_array($key, ['submit', 'reset', 'acao']);
        });
        
        // Construir query SQL para UPDATE
        $setClause = array_map(function($campo) {
            return "$campo = :$campo";
        }, $camposValidos);
        
        $sql = "UPDATE empresas SET " . implode(', ', $setClause) . " WHERE id = :id";

        try {
            // Iniciar transação
            $conn->beginTransaction();
            
            // Preparar e executar a query
            $stmt = $conn->prepare($sql);
            
            // Vincular parâmetro do ID
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            // Vincular parâmetros com tratamento de NULL para campos de data e numéricos
            foreach ($camposValidos as $campo) {
                $valor = $empresaData[$campo];
                
                if ((in_array($campo, $camposDatas) || in_array($campo, $camposNumericos)) && $valor === null) {
                    $stmt->bindValue(':' . $campo, null, PDO::PARAM_NULL);
                } else {
                    $stmt->bindParam(':' . $campo, $empresaData[$campo]);
                }
            }
            
            // Executar a query
            $resultado = $stmt->execute();
            
            // Variável para controlar se a atualização foi bem-sucedida
            $atualizacaoBemSucedida = false;
            
            if ($resultado) {
                // Processar upload de arquivos
                $diretorio = __DIR__ . "/../../documentos/empresas/" . $empresaData['pasta'];
                
                if (!empty($_FILES)) {
                    // Criar diretório se não existir
                    if (!file_exists($diretorio)) {
                        mkdir($diretorio, 0755, true);
                    }
                    
                    // Processar upload do Contrato Social
                    if (isset($_FILES['contrato_social']) && $_FILES['contrato_social']['error'] === UPLOAD_ERR_OK) {
                        $arquivo = $_FILES['contrato_social'];
                        $nomeArquivo = $empresaData['emp_code'] . ' - Contrato Social - ' . $arquivo['name'];
                        $destino = $diretorio . '/' . $nomeArquivo;
                        
                        move_uploaded_file($arquivo['tmp_name'], $destino);
                    }
                    
                    // Processar upload do Cartão CNPJ
                    if (isset($_FILES['cartao_cnpj']) && $_FILES['cartao_cnpj']['error'] === UPLOAD_ERR_OK) {
                        $arquivo = $_FILES['cartao_cnpj'];
                        $nomeArquivo = $empresaData['emp_code'] . ' - Cartão CNPJ - ' . $arquivo['name'];
                        $destino = $diretorio . '/' . $nomeArquivo;
                        
                        move_uploaded_file($arquivo['tmp_name'], $destino);
                    }
                    
                    // Processar upload do Certificado Digital
                    if (isset($_FILES['certificado_digital']) && $_FILES['certificado_digital']['error'] === UPLOAD_ERR_OK) {
                        $arquivo = $_FILES['certificado_digital'];
                        $nomeArquivo = $empresaData['emp_code'] . ' - Certificado Digital - ' . $arquivo['name'];
                        $destino = $diretorio . '/' . $nomeArquivo;
                        
                        move_uploaded_file($arquivo['tmp_name'], $destino);
                    }
                    
                    // Processar upload de Outros Documentos (múltiplos)
                    if (isset($_FILES['outros_documentos']) && is_array($_FILES['outros_documentos']['name'])) {
                        $total = count($_FILES['outros_documentos']['name']);
                        
                        for ($i = 0; $i < $total; $i++) {
                            if ($_FILES['outros_documentos']['error'][$i] === UPLOAD_ERR_OK) {
                                $nomeOriginal = $_FILES['outros_documentos']['name'][$i];
                                $nomeArquivo = $empresaData['emp_code'] . ' - Outros Documentos - ' . $nomeOriginal;
                                $destino = $diretorio . '/' . $nomeArquivo;
                                
                                move_uploaded_file($_FILES['outros_documentos']['tmp_name'][$i], $destino);
                            }
                        }
                    }
                }
                
                // Confirmar a transação no banco de dados
                $conn->commit();
                
                // Marcar que a atualização foi bem-sucedida
                $atualizacaoBemSucedida = true;
                
                // Mensagem de sucesso inicial (será atualizada com info do e-mail)
                $success = "Empresa atualizada com sucesso!";
                
                // Registrar a ação no log
                Logger::activity('empresa', "Empresa ID $id ({$empresaData['emp_name']}) foi atualizada");
                
                // SOMENTE NESTE PONTO, APÓS COMMIT DA TRANSAÇÃO, TENTAR ENVIAR E-MAIL
                $emailEnviado = false;
                
                // Verificar se o arquivo de e-mail existe
                $emailClassPath = __DIR__ . '/emailAlteracao.php';
                
                if (file_exists($emailClassPath)) {
                    try {
                        // Incluir a classe de e-mail
                        require_once $emailClassPath;
                        
                        // Criar instância do gerenciador de e-mail
                        $emailAlteracao = new EmailAlteracao($conn);
                        
                        // Enviar e-mail de atualização
                        $emailAlteracao->enviarEmailAtualizacao($empresaData, $id);
                        
                        // Se chegou aqui, o e-mail foi enviado com sucesso
                        $emailEnviado = true;
                        $success .= " Um e-mail de notificação foi enviado.";
                    } catch (Exception $e) {
                        // Registrar erro no log, mas NÃO afetar o resultado da atualização
                        error_log("Aviso: Não foi possível enviar e-mail de notificação de atualização: " . $e->getMessage());
                    }
                } else {
                    // Registrar aviso no log
                    error_log("Aviso: Arquivo de classe de e-mail não encontrado em: " . $emailClassPath);
                }
            } else {
                // Desfazer transação em caso de falha na atualização
                $conn->rollBack();
                
                $error = "Não foi possível atualizar a empresa no banco de dados.";
            }
        } catch (PDOException $e) {
            // Desfazer transação em caso de erro no banco de dados
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            
            $error = "Erro de banco de dados: " . $e->getMessage();
            error_log("Erro PDO ao atualizar empresa: " . $e->getMessage());
        } catch (Exception $e) {
            // Desfazer transação em caso de erro geral
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            
            $error = "Erro durante a atualização: " . $e->getMessage();
            error_log("Erro geral ao atualizar empresa: " . $e->getMessage());
        }
    } catch (PDOException $e) {
        $error = "Erro de banco de dados: " . $e->getMessage();
        error_log("Erro PDO: " . $e->getMessage());
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Erro geral: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Empresa - <?php echo SITE_NAME; ?></title>
    
    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Outras tags head -->
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    
    <!-- Estilo personalizado -->
    <link rel="stylesheet" href="/GED2.0/assets/css/dashboard.css">
    
    <style>
        .tab-content {
            padding: 20px 0;
        }
        
        .required-field::after {
            content: " *";
            color: red;
        }
        
        .sticky-buttons {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 15px;
            border-top: 1px solid #ddd;
            z-index: 100;
            margin-top: 20px;
        }
        
        .form-section {
            border: 1px solid #e9ecef;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-section-title {
            font-weight: 600;
            color: #495057;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        /* Estilo para o ícone de consulta CNPJ */
        .cnpj-search-btn {
            background-color: #f8f9fa;
            border-color: #ced4da;
        }
        
        .cnpj-search-btn:hover {
            background-color: #e9ecef;
        }
        
        /* Estilo para o spinner de carregamento */
        .loading-spinner {
            display: none;
            margin-right: 5px;
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
            
            <!-- Conteúdo do Dashboard -->
            <div class="dashboard-content">
                <div class="container-fluid">
                    <!-- Mensagens de Sucesso -->
                    <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Mensagens de Erro -->
                    <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Informações de Debug -->
                    <?php if (!empty($debugInfo)): ?>
                    <div class="debug-info">
                        <?php echo $debugInfo; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Formulário de Edição -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Editar Empresa: <?php echo htmlspecialchars($empresaData['emp_name']); ?></h5>
                        </div>
                        <div class="card-body">
                            <form action="" method="post" enctype="multipart/form-data" id="empresaForm" class="needs-validation" novalidate>
                                <!-- Campo oculto para o ID -->
                                <input type="hidden" name="id" value="<?php echo $id; ?>">
                                
                                <!-- Abas para organizar os campos -->
                                <ul class="nav nav-tabs" id="empresaTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="dados-tab" data-bs-toggle="tab" data-bs-target="#dados" type="button" role="tab" aria-controls="dados" aria-selected="true">
                                            <i class="fas fa-building me-2"></i>Dados da Empresa
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="endereco-tab" data-bs-toggle="tab" data-bs-target="#endereco" type="button" role="tab" aria-controls="endereco" aria-selected="false">
                                            <i class="fas fa-map-marker-alt me-2"></i>Endereço
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="certificado-tab" data-bs-toggle="tab" data-bs-target="#certificado" type="button" role="tab" aria-controls="certificado" aria-selected="false">
                                            <i class="fas fa-certificate me-2"></i>Certificados
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="socio1-tab" data-bs-toggle="tab" data-bs-target="#socio1" type="button" role="tab" aria-controls="socio1" aria-selected="false">
                                            <i class="fas fa-user me-2"></i>Sócio 1
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="socio2-tab" data-bs-toggle="tab" data-bs-target="#socio2" type="button" role="tab" aria-controls="socio2" aria-selected="false">
                                            <i class="fas fa-user me-2"></i>Sócio 2
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="socio3-tab" data-bs-toggle="tab" data-bs-target="#socio3" type="button" role="tab" aria-controls="socio3" aria-selected="false">
                                            <i class="fas fa-user me-2"></i>Sócio 3
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="documentos-tab" data-bs-toggle="tab" data-bs-target="#documentos" type="button" role="tab" aria-controls="documentos" aria-selected="false">
                                            <i class="fas fa-file-alt me-2"></i>Documentos
                                        </button>
                                    </li>
                                </ul>
                                
                                <div class="tab-content" id="empresaTabsContent">
                                    <!-- Aba: Dados da Empresa -->
                                    <div class="tab-pane fade show active" id="dados" role="tabpanel" aria-labelledby="dados-tab">
                                        <div class="form-section">
                                            <h6 class="form-section-title"><i class="fas fa-info-circle me-2"></i>Informações Básicas</h6>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-3">
                                                    <label for="emp_code" class="form-label required-field">Código da Empresa</label>
                                                    <input type="text" class="form-control <?php echo isset($errors['emp_code']) ? 'is-invalid' : ''; ?>" id="emp_code" name="emp_code" value="<?php echo htmlspecialchars($empresaData['emp_code']); ?>" required>
                                                    <?php if (isset($errors['emp_code'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['emp_code']; ?></div>
                                                    <?php endif; ?>
                                                    <div class="form-text">Código único para identificação interna.</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="emp_name" class="form-label required-field">Razão Social</label>
                                                    <input type="text" class="form-control <?php echo isset($errors['emp_name']) ? 'is-invalid' : ''; ?>" id="emp_name" name="emp_name" value="<?php echo htmlspecialchars($empresaData['emp_name']); ?>" required>
                                                    <?php if (isset($errors['emp_name'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['emp_name']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="emp_sit_cad" class="form-label required-field">Situação Cadastral</label>
                                                    <select class="form-select <?php echo isset($errors['emp_sit_cad']) ? 'is-invalid' : ''; ?>" id="emp_sit_cad" name="emp_sit_cad" required>
                                                        <?php foreach ($situacoesCadastrais as $key => $value): ?>
                                                            <option value="<?php echo $key; ?>" <?php echo $empresaData['emp_sit_cad'] == $key ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($value); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <?php if (isset($errors['emp_sit_cad'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['emp_sit_cad']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="emp_cnpj" class="form-label required-field">CNPJ</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control mask-cnpj <?php echo isset($errors['emp_cnpj']) ? 'is-invalid' : ''; ?>" id="emp_cnpj" name="emp_cnpj" value="<?php echo htmlspecialchars($empresaData['emp_cnpj']); ?>" required>
                                                        <button class="btn cnpj-search-btn" type="button" id="consultarCnpj">
                                                            <span class="spinner-border spinner-border-sm loading-spinner" id="cnpjSpinner" role="status" aria-hidden="true"></span>
                                                            <i class="fas fa-search"></i>
                                                        </button>
                                                        <?php if (isset($errors['emp_cnpj'])): ?>
                                                            <div class="invalid-feedback"><?php echo $errors['emp_cnpj']; ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="form-text">Formato: 00.000.000/0000-00</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="emp_iest" class="form-label">Inscrição Estadual</label>
                                                    <input type="text" class="form-control" id="emp_iest" name="emp_iest" value="<?php echo htmlspecialchars($empresaData['emp_iest']); ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="emp_imun" class="form-label">Inscrição Municipal</label>
                                                    <input type="text" class="form-control" id="emp_imun" name="emp_imun" value="<?php echo htmlspecialchars($empresaData['emp_imun']); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="emp_tel" class="form-label">Telefone</label>
                                                    <input type="text" class="form-control mask-phone" id="emp_tel" name="emp_tel" value="<?php echo htmlspecialchars($empresaData['emp_tel']); ?>">
                                                    <div class="form-text">Formato: (00) 0000-0000</div>
                                                </div>
                                                <div class="col-md-8">
                                                    <label for="email_empresa" class="form-label">E-mail da Empresa</label>
                                                    <input type="email" class="form-control <?php echo isset($errors['email_empresa']) ? 'is-invalid' : ''; ?>" id="email_empresa" name="email_empresa" value="<?php echo htmlspecialchars($empresaData['email_empresa']); ?>">
                                                    <?php if (isset($errors['email_empresa'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['email_empresa']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-section">
                                            <h6 class="form-section-title"><i class="fas fa-balance-scale me-2"></i>Classificação Fiscal</h6>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="emp_reg_apu" class="form-label">Regime de Apuração</label>
                                                    <select class="form-select" id="emp_reg_apu" name="emp_reg_apu">
                                                        <?php foreach ($regimesApuracao as $key => $value): ?>
                                                            <option value="<?php echo $key; ?>" <?php echo $empresaData['emp_reg_apu'] == $key ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($value); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="emp_porte" class="form-label">Porte da Empresa</label>
                                                    <select class="form-select" id="emp_porte" name="emp_porte">
                                                        <?php foreach ($portes as $key => $value): ?>
                                                            <option value="<?php echo $key; ?>" <?php echo $empresaData['emp_porte'] == $key ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($value); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="emp_tipo_jur" class="form-label">Tipo Jurídico</label>
                                                    <select class="form-select" id="emp_tipo_jur" name="emp_tipo_jur">
                                                        <?php foreach ($tiposJuridicos as $key => $value): ?>
                                                            <option value="<?php echo $key; ?>" <?php echo $empresaData['emp_tipo_jur'] == $key ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($value); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <label for="emp_nat_jur" class="form-label">Natureza Jurídica</label>
                                                    <select class="form-select" id="emp_nat_jur" name="emp_nat_jur">
                                                        <?php foreach ($naturejaJuridica as $key => $value): ?>
                                                            <option value="<?php echo $key; ?>" <?php echo $empresaData['emp_nat_jur'] == $key ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($value); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-section">
                                            <h6 class="form-section-title"><i class="fas fa-file-contract me-2"></i>Dados de Registro</h6>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="emp_org_reg" class="form-label">Órgão de Registro</label>
                                                    <select class="form-select" id="emp_org_reg" name="emp_org_reg">
                                                        <?php foreach ($orgaoRegistro as $key => $value): ?>
                                                            <option value="<?php echo $key; ?>" <?php echo $empresaData['emp_org_reg'] == $key ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($value); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="emp_reg_nire" class="form-label">NIRE</label>
                                                    <input type="text" class="form-control" id="emp_reg_nire" name="emp_reg_nire" value="<?php echo htmlspecialchars($empresaData['emp_reg_nire']); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="emp_ult_reg" class="form-label">Data da Última Alteração</label>
                                                    <input type="date" class="form-control" id="emp_ult_reg" name="emp_ult_reg" value="<?php echo htmlspecialchars($empresaData['emp_ult_reg']); ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="name" class="form-label required-field">Nome do Responsável</label>
                                                    <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" id="name" name="name" value="<?php echo htmlspecialchars($empresaData['name']); ?>" required>
                                                    <?php if (isset($errors['name'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                                                    <?php endif; ?>
                                                    <div class="form-text">Este deve ser igual ao nome do primeiro sócio.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Aba: Endereço -->
                                    <div class="tab-pane fade" id="endereco" role="tabpanel" aria-labelledby="endereco-tab">
                                        <div class="form-section">
                                            <h6 class="form-section-title"><i class="fas fa-map-marker-alt me-2"></i>Endereço da Empresa</h6>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="emp_cep" class="form-label">CEP</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control mask-cep" id="emp_cep" name="emp_cep" value="<?php echo htmlspecialchars($empresaData['emp_cep']); ?>">
                                                        <button class="btn cnpj-search-btn" type="button" id="consultarCep">
                                                            <span class="spinner-border spinner-border-sm loading-spinner" id="cepSpinner" role="status" aria-hidden="true"></span>
                                                            <i class="fas fa-search"></i>
                                                        </button>
                                                    </div>
                                                    <div class="form-text">Formato: 00000-000</div>
                                                </div>
                                                <div class="col-md-8">
                                                    <label for="emp_ende" class="form-label">Logradouro</label>
                                                    <input type="text" class="form-control" id="emp_ende" name="emp_ende" value="<?php echo htmlspecialchars($empresaData['emp_ende']); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-2">
                                                    <label for="emp_nume" class="form-label">Número</label>
                                                    <input type="text" class="form-control" id="emp_nume" name="emp_nume" value="<?php echo htmlspecialchars($empresaData['emp_nume']); ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="emp_comp" class="form-label">Complemento</label>
                                                    <input type="text" class="form-control" id="emp_comp" name="emp_comp" value="<?php echo htmlspecialchars($empresaData['emp_comp']); ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="emp_bair" class="form-label">Bairro</label>
                                                    <input type="text" class="form-control" id="emp_bair" name="emp_bair" value="<?php echo htmlspecialchars($empresaData['emp_bair']); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-8">
                                                    <label for="emp_cid" class="form-label">Cidade</label>
                                                    <input type="text" class="form-control" id="emp_cid" name="emp_cid" value="<?php echo htmlspecialchars($empresaData['emp_cid']); ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="emp_uf" class="form-label">Estado</label>
                                                    <select class="form-select" id="emp_uf" name="emp_uf">
                                                        <option value="">Selecione</option>
                                                        <?php foreach ($ufs as $sigla => $nome): ?>
                                                            <option value="<?php echo $sigla; ?>" <?php echo $empresaData['emp_uf'] == $sigla ? 'selected' : ''; ?>>
                                                                <?php echo $sigla . ' - ' . htmlspecialchars($nome); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Aba: Certificados -->
                                    <div class="tab-pane fade" id="certificado" role="tabpanel" aria-labelledby="certificado-tab">
                                        <div class="form-section">
                                            <h6 class="form-section-title"><i class="fas fa-lock me-2"></i>Certificado Digital</h6>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="emp_cod_ace" class="form-label">Código de Acesso</label>
                                                    <input type="text" class="form-control" id="emp_cod_ace" name="emp_cod_ace" value="<?php echo htmlspecialchars($empresaData['emp_cod_ace']); ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="emp_cod_pre" class="form-label">Código de Prova</label>
                                                    <input type="text" class="form-control" id="emp_cod_pre" name="emp_cod_pre" value="<?php echo htmlspecialchars($empresaData['emp_cod_pre']); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="senha_pfe" class="form-label">Senha PFE</label>
                                                    <div class="input-group">
                                                        <input type="password" class="form-control" id="senha_pfe" name="senha_pfe" value="<?php echo htmlspecialchars($empresaData['senha_pfe']); ?>">
                                                        <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1">
                                                            <i class="far fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="emp_cer_dig_data" class="form-label">Data de Validade do Certificado</label>
                                                    <input type="date" class="form-control" id="emp_cer_dig_data" name="emp_cer_dig_data" value="<?php echo htmlspecialchars($empresaData['emp_cer_dig_data']); ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Aba: Sócio 1 -->
                                    <div class="tab-pane fade" id="socio1" role="tabpanel" aria-labelledby="socio1-tab">
                                        <div class="form-section">
                                            <h6 class="form-section-title"><i class="fas fa-user me-2"></i>Dados do Sócio 1</h6>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-8">
                                                    <label for="soc1_name" class="form-label required-field">Nome Completo</label>
                                                    <input type="text" class="form-control <?php echo isset($errors['soc1_name']) ? 'is-invalid' : ''; ?>" id="soc1_name" name="soc1_name" value="<?php echo htmlspecialchars($empresaData['soc1_name']); ?>" required>
                                                    <?php if (isset($errors['soc1_name'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['soc1_name']; ?></div>
                                                    <?php endif; ?>
                                                    <div class="form-text">Deve ser igual ao nome do responsável.</div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="soc1_cpf" class="form-label required-field">CPF</label>
                                                    <input type="text" class="form-control mask-cpf <?php echo isset($errors['soc1_cpf']) ? 'is-invalid' : ''; ?>" id="soc1_cpf" name="soc1_cpf" value="<?php echo htmlspecialchars($empresaData['soc1_cpf']); ?>" required>
                                                    <?php if (isset($errors['soc1_cpf'])): ?>
                                                        <div class="invalid-feedback"><?php echo $errors['soc1_cpf']; ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="soc1_entrada" class="form-label">Data de Entrada</label>
                                                    <input type="date" class="form-control" id="soc1_entrada" name="soc1_entrada" value="<?php echo htmlspecialchars($empresaData['soc1_entrada']); ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="soc1_email" class="form-label">E-mail</label>
                                                    <input type="email" class="form-control" id="soc1_email" name="soc1_email" value="<?php echo htmlspecialchars($empresaData['soc1_email']); ?>">
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="soc1_tel" class="form-label">Telefone</label>
                                                    <input type="text" class="form-control mask-phone" id="soc1_tel" name="soc1_tel" value="<?php echo htmlspecialchars($empresaData['soc1_tel']); ?>">
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="soc1_cel" class="form-label">Celular</label>
                                                    <input type="text" class="form-control mask-celphone" id="soc1_cel" name="soc1_cel" value="<?php echo htmlspecialchars($empresaData['soc1_cel']); ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-section">
                                            <h6 class="form-section-title"><i class="fas fa-map-marker-alt me-2"></i>Endereço do Sócio 1</h6>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-3">
                                                    <label for="soc1_cep" class="form-label">CEP</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control mask-cep" id="soc1_cep" name="soc1_cep" value="<?php echo htmlspecialchars($empresaData['soc1_cep']); ?>">
                                                        <button class="btn cnpj-search-btn" type="button" id="consultarCepSoc1">
                                                            <i class="fas fa-search"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-md-7">
                                                    <label for="soc1_ende" class="form-label">Logradouro</label>
                                                    <input type="text" class="form-control" id="soc1_ende" name="soc1_ende" value="<?php echo htmlspecialchars($empresaData['soc1_ende']); ?>">
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="soc1_nume" class="form-label">Número</label>
                                                    <input type="text" class="form-control" id="soc1_nume" name="soc1_nume" value="<?php echo htmlspecialchars($empresaData['soc1_nume']); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="soc1_comp" class="form-label">Complemento</label>
                                                    <input type="text" class="form-control" id="soc1_comp" name="soc1_comp" value="<?php echo htmlspecialchars($empresaData['soc1_comp']); ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="soc1_bair" class="form-label">Bairro</label>
                                                    <input type="text" class="form-control" id="soc1_bair" name="soc1_bair" value="<?php echo htmlspecialchars($empresaData['soc1_bair']); ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="soc1_cid" class="form-label">Cidade</label>
                                                    <input type="text" class="form-control" id="soc1_cid" name="soc1_cid" value="<?php echo htmlspecialchars($empresaData['soc1_cid']); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-3">
                                                    <label for="soc1_uf" class="form-label">Estado</label>
                                                    <select class="form-select" id="soc1_uf" name="soc1_uf">
                                                        <option value="">Selecione</option>
                                                        <?php foreach ($ufs as $sigla => $nome): ?>
                                                            <option value="<?php echo $sigla; ?>" <?php echo $empresaData['soc1_uf'] == $sigla ? 'selected' : ''; ?>>
                                                                <?php echo $sigla . ' - ' . htmlspecialchars($nome); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="soc1_quali" class="form-label">Qualificação</label>
                                                    <input type="text" class="form-control" id="soc1_quali" name="soc1_quali" value="<?php echo htmlspecialchars($empresaData['soc1_quali']); ?>">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="soc1_ass" class="form-label">Assinatura</label>
                                                    <input type="text" class="form-control" id="soc1_ass" name="soc1_ass" value="<?php echo htmlspecialchars($empresaData['soc1_ass']); ?>">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="soc1_capit" class="form-label">Capital Social (R$)</label>
                                                    <input type="text" class="form-control mask-money" id="soc1_capit" name="soc1_capit" value="<?php echo htmlspecialchars($empresaData['soc1_capit']); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="soc1_govbr" class="form-label">Login Gov.BR</label>
                                                    <input type="text" class="form-control" id="soc1_govbr" name="soc1_govbr" value="<?php echo htmlspecialchars($empresaData['soc1_govbr']); ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="soc1_qualif_govbr" class="form-label">Qualificação Gov.BR</label>
                                                    <input type="text" class="form-control" id="soc1_qualif_govbr" name="soc1_qualif_govbr" value="<?php echo htmlspecialchars($empresaData['soc1_qualif_govbr']); ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Aba: Sócio 2 -->
                                    <div class="tab-pane fade" id="socio2" role="tabpanel" aria-labelledby="socio2-tab">
                                        <div class="alert alert-info mb-4">
                                            <i class="fas fa-info-circle me-2"></i> O preenchimento desta aba é opcional. Preencha apenas se a empresa tiver um segundo sócio.
                                        </div>
                                        
                                        <div class="form-section">
                                            <h6 class="form-section-title"><i class="fas fa-user me-2"></i>Dados do Sócio 2</h6>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-8">
                                                    <label for="soc2_name" class="form-label">Nome Completo</label>
                                                    <input type="text" class="form-control" id="soc2_name" name="soc2_name" value="<?php echo htmlspecialchars($empresaData['soc2_name']); ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="soc2_cpf" class="form-label">CPF</label>
                                                    <input type="text" class="form-control mask-cpf" id="soc2_cpf" name="soc2_cpf" value="<?php echo htmlspecialchars($empresaData['soc2_cpf']); ?>">
                                                </div>
                                            </div>
                                            
                                            <!-- Continuação dos campos do Sócio 2, similar ao Sócio 1 -->
                                            <!-- Implementação similar à seção do Sócio 1 -->
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="soc2_entrada" class="form-label">Data de Entrada</label>
                                                    <input type="date" class="form-control" id="soc2_entrada" name="soc2_entrada" value="<?php echo htmlspecialchars($empresaData['soc2_entrada']); ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="soc2_email" class="form-label">E-mail</label>
                                                    <input type="email" class="form-control" id="soc2_email" name="soc2_email" value="<?php echo htmlspecialchars($empresaData['soc2_email']); ?>">
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="soc2_tel" class="form-label">Telefone</label>
                                                    <input type="text" class="form-control mask-phone" id="soc2_tel" name="soc2_tel" value="<?php echo htmlspecialchars($empresaData['soc2_tel']); ?>">
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="soc2_cel" class="form-label">Celular</label>
                                                    <input type="text" class="form-control mask-celphone" id="soc2_cel" name="soc2_cel" value="<?php echo htmlspecialchars($empresaData['soc2_cel']); ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-section">
                                            <h6 class="form-section-title"><i class="fas fa-map-marker-alt me-2"></i>Endereço do Sócio 2</h6>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-3">
                                                    <label for="soc2_cep" class="form-label">CEP</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control mask-cep" id="soc2_cep" name="soc2_cep" value="<?php echo htmlspecialchars($empresaData['soc2_cep']); ?>">
                                                        <button class="btn cnpj-search-btn" type="button" id="consultarCepSoc2">
                                                            <i class="fas fa-search"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-md-7">
                                                    <label for="soc2_ende" class="form-label">Logradouro</label>
                                                    <input type="text" class="form-control" id="soc2_ende" name="soc2_ende" value="<?php echo htmlspecialchars($empresaData['soc2_ende']); ?>">
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="soc2_nume" class="form-label">Número</label>
                                                    <input type="text" class="form-control" id="soc2_nume" name="soc2_nume" value="<?php echo htmlspecialchars($empresaData['soc2_nume']); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="soc2_comp" class="form-label">Complemento</label>
                                                    <input type="text" class="form-control" id="soc2_comp" name="soc2_comp" value="<?php echo htmlspecialchars($empresaData['soc2_comp']); ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="soc2_bair" class="form-label">Bairro</label>
                                                    <input type="text" class="form-control" id="soc2_bair" name="soc2_bair" value="<?php echo htmlspecialchars($empresaData['soc2_bair']); ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="soc2_cid" class="form-label">Cidade</label>
                                                    <input type="text" class="form-control" id="soc2_cid" name="soc2_cid" value="<?php echo htmlspecialchars($empresaData['soc2_cid']); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-3">
                                                    <label for="soc2_uf" class="form-label">Estado</label>
                                                    <select class="form-select" id="soc2_uf" name="soc2_uf">
                                                        <option value="">Selecione</option>
                                                        <?php foreach ($ufs as $sigla => $nome): ?>
                                                            <option value="<?php echo $sigla; ?>" <?php echo $empresaData['soc2_uf'] == $sigla ? 'selected' : ''; ?>>
                                                                <?php echo $sigla . ' - ' . htmlspecialchars($nome); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="soc2_quali" class="form-label">Qualificação</label>
                                                    <input type="text" class="form-control" id="soc2_quali" name="soc2_quali" value="<?php echo htmlspecialchars($empresaData['soc2_quali']); ?>">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="soc2_ass" class="form-label">Assinatura</label>
                                                    <input type="text" class="form-control" id="soc2_ass" name="soc2_ass" value="<?php echo htmlspecialchars($empresaData['soc2_ass']); ?>">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="soc2_capit" class="form-label">Capital Social (R$)</label>
                                                    <input type="text" class="form-control mask-money" id="soc2_capit" name="soc2_capit" value="<?php echo htmlspecialchars($empresaData['soc2_capit']); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="soc2_govbr" class="form-label">Login Gov.BR</label>
                                                    <input type="text" class="form-control" id="soc2_govbr" name="soc2_govbr" value="<?php echo htmlspecialchars($empresaData['soc2_govbr']); ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="soc2_qualif_govbr" class="form-label">Qualificação Gov.BR</label>
                                                    <input type="text" class="form-control" id="soc2_qualif_govbr" name="soc2_qualif_govbr" value="<?php echo htmlspecialchars($empresaData['soc2_qualif_govbr']); ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Aba: Sócio 3 -->
                                    <div class="tab-pane fade" id="socio3" role="tabpanel" aria-labelledby="socio3-tab">
                                        <div class="alert alert-info mb-4">
                                            <i class="fas fa-info-circle me-2"></i> O preenchimento desta aba é opcional. Preencha apenas se a empresa tiver um terceiro sócio.
                                        </div>
                                        
                                        <!-- Implementação de campos similar à do Sócio 2 -->
                                        <!-- Mesma estrutura e campos dos sócios anteriores -->
                                        <div class="form-section">
                                            <h6 class="form-section-title"><i class="fas fa-user me-2"></i>Dados do Sócio 3</h6>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-8">
                                                    <label for="soc3_name" class="form-label">Nome Completo</label>
                                                    <input type="text" class="form-control" id="soc3_name" name="soc3_name" value="<?php echo htmlspecialchars($empresaData['soc3_name']); ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="soc3_cpf" class="form-label">CPF</label>
                                                    <input type="text" class="form-control mask-cpf" id="soc3_cpf" name="soc3_cpf" value="<?php echo htmlspecialchars($empresaData['soc3_cpf']); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="soc3_entrada" class="form-label">Data de Entrada</label>
                                                    <input type="date" class="form-control" id="soc3_entrada" name="soc3_entrada" value="<?php echo htmlspecialchars($empresaData['soc3_entrada']); ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="soc3_email" class="form-label">E-mail</label>
                                                    <input type="email" class="form-control" id="soc3_email" name="soc3_email" value="<?php echo htmlspecialchars($empresaData['soc3_email']); ?>">
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="soc3_tel" class="form-label">Telefone</label>
                                                    <input type="text" class="form-control mask-phone" id="soc3_tel" name="soc3_tel" value="<?php echo htmlspecialchars($empresaData['soc3_tel']); ?>">
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="soc3_cel" class="form-label">Celular</label>
                                                    <input type="text" class="form-control mask-celphone" id="soc3_cel" name="soc3_cel" value="<?php echo htmlspecialchars($empresaData['soc3_cel']); ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-section">
                                            <h6 class="form-section-title"><i class="fas fa-map-marker-alt me-2"></i>Endereço do Sócio 3</h6>
                                            
                                            <!-- Restante dos campos de endereço do Sócio 3 -->
                                            <!-- Similar aos sócios anteriores -->
                                            <div class="row mb-3">
                                                <div class="col-md-3">
                                                    <label for="soc3_cep" class="form-label">CEP</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control mask-cep" id="soc3_cep" name="soc3_cep" value="<?php echo htmlspecialchars($empresaData['soc3_cep']); ?>">
                                                        <button class="btn cnpj-search-btn" type="button" id="consultarCepSoc3">
                                                            <i class="fas fa-search"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-md-7">
                                                    <label for="soc3_ende" class="form-label">Logradouro</label>
                                                    <input type="text" class="form-control" id="soc3_ende" name="soc3_ende" value="<?php echo htmlspecialchars($empresaData['soc3_ende']); ?>">
                                                </div>
                                                <div class="col-md-2">
                                                    <label for="soc3_nume" class="form-label">Número</label>
                                                    <input type="text" class="form-control" id="soc3_nume" name="soc3_nume" value="<?php echo htmlspecialchars($empresaData['soc3_nume']); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-4">
                                                    <label for="soc3_comp" class="form-label">Complemento</label>
                                                    <input type="text" class="form-control" id="soc3_comp" name="soc3_comp" value="<?php echo htmlspecialchars($empresaData['soc3_comp']); ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="soc3_bair" class="form-label">Bairro</label>
                                                    <input type="text" class="form-control" id="soc3_bair" name="soc3_bair" value="<?php echo htmlspecialchars($empresaData['soc3_bair']); ?>">
                                                </div>
                                                <div class="col-md-4">
                                                    <label for="soc3_cid" class="form-label">Cidade</label>
                                                    <input type="text" class="form-control" id="soc3_cid" name="soc3_cid" value="<?php echo htmlspecialchars($empresaData['soc3_cid']); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-3">
                                                    <label for="soc3_uf" class="form-label">Estado</label>
                                                    <select class="form-select" id="soc3_uf" name="soc3_uf">
                                                        <option value="">Selecione</option>
                                                        <?php foreach ($ufs as $sigla => $nome): ?>
                                                            <option value="<?php echo $sigla; ?>" <?php echo $empresaData['soc3_uf'] == $sigla ? 'selected' : ''; ?>>
                                                                <?php echo $sigla . ' - ' . htmlspecialchars($nome); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="soc3_quali" class="form-label">Qualificação</label>
                                                    <input type="text" class="form-control" id="soc3_quali" name="soc3_quali" value="<?php echo htmlspecialchars($empresaData['soc3_quali']); ?>">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="soc3_ass" class="form-label">Assinatura</label>
                                                    <input type="text" class="form-control" id="soc3_ass" name="soc3_ass" value="<?php echo htmlspecialchars($empresaData['soc3_ass']); ?>">
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="soc3_capit" class="form-label">Capital Social (R$)</label>
                                                    <input type="text" class="form-control mask-money" id="soc3_capit" name="soc3_capit" value="<?php echo htmlspecialchars($empresaData['soc3_capit']); ?>">
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <label for="soc3_govbr" class="form-label">Login Gov.BR</label>
                                                    <input type="text" class="form-control" id="soc3_govbr" name="soc3_govbr" value="<?php echo htmlspecialchars($empresaData['soc3_govbr']); ?>">
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="soc3_qualif_govbr" class="form-label">Qualificação Gov.BR</label>
                                                    <input type="text" class="form-control" id="soc3_qualif_govbr" name="soc3_qualif_govbr" value="<?php echo htmlspecialchars($empresaData['soc3_qualif_govbr']); ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Aba: Documentos -->
                                    <div class="tab-pane fade" id="documentos" role="tabpanel" aria-labelledby="documentos-tab">
                                        <div class="form-section">
                                            <h6 class="form-section-title"><i class="fas fa-file-alt me-2"></i>Documentos da Empresa</h6>
                                            
                                            <div class="alert alert-info mb-4">
                                                <i class="fas fa-info-circle me-2"></i> Faça o upload de novos documentos da empresa. Formatos aceitos: PDF, JPG, PNG (máx. 5MB por arquivo).
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="contrato_social" class="form-label">Contrato Social</label>
                                                <input class="form-control" type="file" id="contrato_social" name="contrato_social" accept="application/pdf,image/jpeg,image/png">
                                                <div class="form-text">Deixe em branco para manter o documento atual.</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="cartao_cnpj" class="form-label">Cartão CNPJ</label>
                                                <input class="form-control" type="file" id="cartao_cnpj" name="cartao_cnpj" accept="application/pdf,image/jpeg,image/png">
                                                <div class="form-text">Deixe em branco para manter o documento atual.</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="certificado_digital" class="form-label">Certificado Digital</label>
                                                <input class="form-control" type="file" id="certificado_digital" name="certificado_digital" accept="application/pdf">
                                                <div class="form-text">Deixe em branco para manter o documento atual.</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="outros_documentos" class="form-label">Outros Documentos</label>
                                                <input class="form-control" type="file" id="outros_documentos" name="outros_documentos[]" multiple accept="application/pdf,image/jpeg,image/png">
                                                <div class="form-text">Você pode selecionar múltiplos arquivos para upload.</div>
                                            </div>
                                            
                                            <!-- Lista de documentos existentes -->
                                            <?php

// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}

                                            $diretorio = __DIR__ . "/../../documentos/empresas/" . $empresaData['pasta'];
                                            if (file_exists($diretorio)):
                                                $documentos = scandir($diretorio);
                                                $documentos = array_diff($documentos, ['.', '..']);
                                                
                                                if (!empty($documentos)):
                                            ?>
                                            <div class="mt-4">
                                                <h5>Documentos Atuais</h5>
                                                <div class="table-responsive">
                                                    <table class="table table-striped table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Nome do Arquivo</th>
                                                                <th>Tipo</th>
                                                                <th>Data</th>
                                                                <th>Ações</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($documentos as $documento): ?>
                                                                <?php

// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}

                                                                $caminho = $diretorio . '/' . $documento;
                                                                $tipo = pathinfo($caminho, PATHINFO_EXTENSION);
                                                                $data = date("d/m/Y H:i:s", filemtime($caminho));
                                                                $urlDocumento = "/documentos/empresas/" . urlencode($empresaData['pasta']) . "/" . urlencode($documento);
                                                                ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars($documento); ?></td>
                                                                    <td><?php echo strtoupper($tipo); ?></td>
                                                                    <td><?php echo $data; ?></td>
                                                                    <td>
                                                                        <a href="<?php echo $urlDocumento; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                                            <i class="fas fa-eye"></i> Visualizar
                                                                        </a>
                                                                        <a href="<?php echo $urlDocumento; ?>" class="btn btn-sm btn-outline-success" download>
                                                                            <i class="fas fa-download"></i> Download
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <?php 
                                                else:
                                                    echo '<div class="alert alert-warning mt-4">Nenhum documento encontrado para esta empresa.</div>';
                                                endif;
                                            else:
                                                echo '<div class="alert alert-warning mt-4">O diretório de documentos desta empresa ainda não foi criado.</div>';
                                            endif;
                                            ?>
                                        </div>
                                    </div>

                                    <!-- Botões fixos na parte inferior -->
                                    <div class="sticky-buttons">
                                        <div class="row">
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i> Salvar Alterações
                                                </button>
                                                <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                                                    <i class="fas fa-eye me-2"></i> Visualizar Empresa
                                                </a>
                                                <a href="list.php" class="btn btn-outline-secondary">
                                                    <i class="fas fa-list me-2"></i> Voltar para Lista
                                                </a>
                                            </div>
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
    
    <!-- jQuery Mask Plugin -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    
    <!-- Script personalizado -->
    <script src="/ged2.0/assets/js/dashboard.js"></script>
    
    <script>
    $(document).ready(function() {
        // Inicializar máscaras
        $('.mask-cnpj').mask('00.000.000/0000-00');
        $('.mask-cpf').mask('000.000.000-00');
        $('.mask-phone').mask('(00) 0000-0000');
        $('.mask-celphone').mask('(00) 00000-0000');
        $('.mask-cep').mask('00000-000');
        $('.mask-money').mask('#,##0.00', {reverse: true});
        
        // Sincronizar Nome do Responsável com Nome do Sócio 1
        $('#name').on('input', function() {
            $('#soc1_name').val($(this).val());
        });
        
        $('#soc1_name').on('input', function() {
            $('#name').val($(this).val());
        });
        
        // Consulta de CNPJ
        $('#consultarCnpj').click(function() {
            const cnpj = $('#emp_cnpj').val().replace(/[^\d]/g, '');
            
            if (cnpj.length !== 14) {
                alert('CNPJ inválido. Por favor, insira um CNPJ válido.');
                return;
            }
            
            // Mostrar spinner
            $('#cnpjSpinner').show();
            
            // Fazer requisição à API
            $.getJSON(`https://brasilapi.com.br/api/cnpj/v1/${cnpj}`, function(data) {
                // Preencher campos com os dados retornados
                $('#emp_name').val(data.razao_social);
                $('#emp_cep').val(data.cep);
                $('#emp_ende').val(data.logradouro);
                $('#emp_nume').val(data.numero);
                $('#emp_comp').val(data.complemento);
                $('#emp_bair').val(data.bairro);
                $('#emp_cid').val(data.municipio);
                $('#emp_uf').val(data.uf);
                $('#emp_tel').val(data.ddd_telefone_1);
                $('#name').val(data.nome_fantasia || data.razao_social);
                $('#soc1_name').val(data.nome_fantasia || data.razao_social);
                
                // Mostrar mensagem de sucesso
                alert('Dados do CNPJ carregados com sucesso!');
            })
            .fail(function() {
                alert('Erro ao consultar CNPJ. Verifique se o CNPJ está correto.');
            })
            .always(function() {
                // Esconder spinner
                $('#cnpjSpinner').hide();
            });
        });
        
        // Consulta de CEP para Empresa
        $('#consultarCep').click(function() {
            consultarCep($('#emp_cep').val(), 'emp_ende', 'emp_bair', 'emp_cid', 'emp_uf');
        });
        
        // Consulta de CEP para Sócio 1
        $('#consultarCepSoc1').click(function() {
            consultarCep($('#soc1_cep').val(), 'soc1_ende', 'soc1_bair', 'soc1_cid', 'soc1_uf');
        });
        
        // Consulta de CEP para Sócio 2
        $('#consultarCepSoc2').click(function() {
            consultarCep($('#soc2_cep').val(), 'soc2_ende', 'soc2_bair', 'soc2_cid', 'soc2_uf');
        });
        
        // Consulta de CEP para Sócio 3
        $('#consultarCepSoc3').click(function() {
            consultarCep($('#soc3_cep').val(), 'soc3_ende', 'soc3_bair', 'soc3_cid', 'soc3_uf');
        });
        
        // Função para consultar CEP
        function consultarCep(cep, endereco, bairro, cidade, uf) {
            const cepLimpo = cep.replace(/[^\d]/g, '');
            
            if (cepLimpo.length !== 8) {
                alert('CEP inválido. Por favor, insira um CEP válido.');
                return;
            }
            
            // Mostrar spinner
            $('#cepSpinner').show();
            
            // Fazer requisição à API
            $.getJSON(`https://viacep.com.br/ws/${cepLimpo}/json/`, function(data) {
                if (!data.erro) {
                    // Preencher campos com os dados retornados
                    $(`#${endereco}`).val(data.logradouro);
                    $(`#${bairro}`).val(data.bairro);
                    $(`#${cidade}`).val(data.localidade);
                    $(`#${uf}`).val(data.uf);
                } else {
                    alert('CEP não encontrado.');
                }
            })
            .fail(function() {
                alert('Erro ao consultar CEP. Verifique se o CEP está correto.');
            })
            .always(function() {
                // Esconder spinner
                $('#cepSpinner').hide();
            });
        }
        
        // Visualização da senha
        $('.toggle-password').click(function() {
            const input = $(this).prev('input');
            const icon = $(this).find('i');
            
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
        
        // Validação do formulário
        $('#empresaForm').submit(function(event) {
            // Verificar se o nome do responsável é igual ao nome do sócio 1
            if ($('#name').val() !== $('#soc1_name').val()) {
                alert('O nome do responsável deve ser igual ao nome do primeiro sócio.');
                event.preventDefault();
                $('#socio1-tab').tab('show');
                return false;
            }
            
            // Verificar campos obrigatórios na aba de Dados da Empresa
            if (!$('#emp_code').val() || !$('#emp_name').val() || !$('#emp_cnpj').val()) {
                alert('Preencha todos os campos obrigatórios na aba Dados da Empresa.');
                event.preventDefault();
                $('#dados-tab').tab('show');
                return false;
            }
            
            // Verificar campos obrigatórios na aba de Sócio 1
            if (!$('#soc1_name').val() || !$('#soc1_cpf').val()) {
                alert('Preencha todos os campos obrigatórios na aba Sócio 1.');
                event.preventDefault();
                $('#socio1-tab').tab('show');
                return false;
            }
            
            // Se tiver preenchido algum campo do Sócio 2, verificar os campos obrigatórios
            if ($('#soc2_name').val() || $('#soc2_cpf').val()) {
                if (!$('#soc2_name').val() || !$('#soc2_cpf').val()) {
                    alert('Para cadastrar o Sócio 2, preencha o Nome e CPF.');
                    event.preventDefault();
                    $('#socio2-tab').tab('show');
                    return false;
                }
            }
            
            // Se tiver preenchido algum campo do Sócio 3, verificar os campos obrigatórios
            if ($('#soc3_name').val() || $('#soc3_cpf').val()) {
                if (!$('#soc3_name').val() || !$('#soc3_cpf').val()) {
                    alert('Para cadastrar o Sócio 3, preencha o Nome e CPF.');
                    event.preventDefault();
                    $('#socio3-tab').tab('show');
                    return false;
                }
            }
        });
    });
</script>
<?php 
    // Adicionar o script de popup se existir
    if (isset($scriptPopup)) {
        echo $scriptPopup;
    }
?>
</body>
</html>