<?php
// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(dirname(__FILE__))));
}


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Controller para gerenciamento de Empresas Ativas
 * Sistema Contabilidade Estrela 2.0
 */
class EmpresasController {
    private $empresaDAO;
    private $errorHandler;
    
    /**
     * Construtor
     */
    public function __construct() {
        require_once('../model/Empresa.php');
        require_once('../dao/EmpresaDAO.php');
        require_once('../bancoDeDados/Database.php');
        
        $db = new Database();
        $this->empresaDAO = new EmpresaDAO($db);
        $this->errorHandler = new ErrorHandler(true); // true = modo debug
    }
    
    /**
     * Processa as requisições para este controller
     */
    public function processarAcao() {
        // Verificar se temos uma ação via GET (para AJAX)
        if (isset($_GET["acao"])) {
            $acao = $_GET["acao"];
            
            switch ($acao) {
                case 'obter':
                    $this->obterEmpresa();
                    return;
                    
                case 'documentos_recentes':
                    $this->listarDocumentosRecentes();
                    return;
            }
        }
        
        // Verificar se a ação foi definida via POST
        if (!isset($_POST["acao"])) {
            echo $this->errorHandler->handleError(
                ErrorHandler::VALIDATION_ERROR,
                "Nenhuma ação especificada.",
                "O parâmetro 'acao' não foi enviado no POST."
            );
            exit;
        }

        $acao = $_POST["acao"];

        switch ($acao) {
            case 'cadastrar':
                $this->cadastrarEmpresa();
                break;

            case 'alterar':
                $this->alterarEmpresa();
                break;

            case 'remover':
                $this->removerEmpresa();
                break;
                
            case 'listar':
                $this->listarEmpresas();
                break;
                
            case 'buscar':
                $this->buscarEmpresa();
                break;
                
            default:
                echo $this->errorHandler->handleError(
                    ErrorHandler::VALIDATION_ERROR,
                    "Ação inválida.",
                    "O valor do parâmetro 'acao' não é reconhecido: " . $acao
                );
                exit;
        }
    }
    
    /**
     * Obtém dados de uma empresa específica por ID, formatado para JSON
     */
    private function obterEmpresa() {
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            
            if (!$id) {
                // Resposta para erro de validação
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'ID da empresa não fornecido ou inválido.'
                ]);
                return;
            }
            
            $empresa = $this->empresaDAO->obterEmpresaPorId($id);
            
            if ($empresa) {
                // Registrar visualização no log
                $this->errorHandler->logAction(
                    'VISUALIZACAO', 
                    "Empresa visualizada: " . $empresa->getEmp_name() . " (CNPJ: " . $empresa->getEmp_cnpj() . ")", 
                    ['id' => $id, 'usuario' => isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'Não identificado']
                );
                
                // Formatar resposta como JSON
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id' => $empresa->getId(),
                        'emp_code' => $empresa->getEmp_code(),
                        'emp_name' => $empresa->getEmp_name(),
                        'emp_cnpj' => $empresa->getEmp_cnpj(),
                        'emp_sit_cad' => $empresa->getEmp_sit_cad(),
                        'emp_tipo_jur' => $empresa->getEmp_tipo_jur(),
                        'emp_porte' => $empresa->getEmp_porte(),
                        'emp_cid' => $empresa->getEmp_cid(),
                        'emp_uf' => $empresa->getEmp_uf(),
                        'name' => $empresa->getName(),
                        'emp_tel' => $empresa->getEmp_tel(),
                        'email_empresa' => $empresa->getEmail_empresa(),
                        'data' => $empresa->getData()
                    ]
                ]);
            } else {
                // Resposta para empresa não encontrada
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Empresa não encontrada.'
                ]);
            }
            
        } catch (Exception $e) {
            // Resposta para erro do sistema
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Ocorreu um erro ao buscar a empresa.',
                'details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Lista documentos recentes de uma empresa
     */
    private function listarDocumentosRecentes() {
        try {
            $empresaId = filter_input(INPUT_GET, 'empresa_id', FILTER_VALIDATE_INT);
            $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 5;
            
            if (!$empresaId) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'ID da empresa não fornecido ou inválido.'
                ]);
                return;
            }
            
            // Incluir DAO de documentos
            require_once('ROOT_DIR . '/app/Dao/DocumentoDao.php');
            $db = new Database();
            $documentoDAO = new DocumentoDAO($db);
            
            $documentos = $documentoDAO->listarDocumentosPorEmpresa($empresaId, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $documentos
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Ocorreu um erro ao listar os documentos.',
                'details' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Cadastra uma nova empresa
     */
    private function cadastrarEmpresa() {
        try {
            // Validação básica dos campos obrigatórios
            $camposObrigatorios = ['emp_code', 'emp_name', 'emp_cnpj', 'name', 'soc1_name', 'soc1_cpf'];
            foreach ($camposObrigatorios as $campo) {
                if (empty($_POST[$campo])) {
                    echo $this->errorHandler->handleError(
                        ErrorHandler::VALIDATION_ERROR,
                        "O campo '$campo' é obrigatório.",
                        "Validação de campo obrigatório falhou: $campo"
                    );
                    return;
                }
            }
            
            // Validar que name é igual a soc1_name
            $name = filter_input(INPUT_POST, 'name');
            $soc1_name = filter_input(INPUT_POST, 'soc1_name');
            
            if ($name != $soc1_name) {
                echo $this->errorHandler->handleError(
                    ErrorHandler::VALIDATION_ERROR,
                    "O nome do responsável deve ser igual ao nome do primeiro sócio.",
                    "Inconsistência entre nome do responsável ($name) e nome do primeiro sócio ($soc1_name)"
                );
                return;
            }

            // Criar objeto Empresa com os dados do POST
            $empresa = $this->criarEmpresaDePost();
            
            // Verificar se já existe empresa com mesmo código, nome ou CNPJ
            if ($this->empresaDAO->verificarCodigo($empresa)) {
                echo $this->errorHandler->handleError(
                    ErrorHandler::VALIDATION_ERROR,
                    "Este código de empresa já está cadastrado no sistema.",
                    "Erro: Código já Cadastrado: " . $empresa->getEmp_code()
                );
                return;
            }
            
            if ($this->empresaDAO->verificarNome($empresa)) {
                echo $this->errorHandler->handleError(
                    ErrorHandler::VALIDATION_ERROR,
                    "Este nome de empresa já está cadastrado no sistema.",
                    "Erro: Nome já Cadastrado: " . $empresa->getEmp_name()
                );
                return;
            }
            
            if ($this->empresaDAO->verificarCnpj($empresa)) {
                echo $this->errorHandler->handleError(
                    ErrorHandler::VALIDATION_ERROR,
                    "Este CNPJ já está cadastrado no sistema.",
                    "Erro: CNPJ já Cadastrado: " . $empresa->getEmp_cnpj()
                );
                return;
            }

            // Tentar adicionar a empresa
            if ($this->empresaDAO->adicionarEmpresa($empresa)) {
                // Registrar ação de cadastro no log
                $dadosLog = [
                    'usuario' => $empresa->getUsuario(),
                    'codigo' => $empresa->getEmp_code(),
                    'nome' => $empresa->getEmp_name(),
                    'cnpj' => $empresa->getEmp_cnpj(),
                    'tipo_juridico' => $empresa->getEmp_tipo_jur(),
                    'porte' => $empresa->getEmp_porte()
                ];
                $this->errorHandler->logAction(
                    'CADASTRO', 
                    "Nova empresa cadastrada: " . $empresa->getEmp_name() . " (CNPJ: " . $empresa->getEmp_cnpj() . ")", 
                    $dadosLog
                );
                
                // Enviar email de confirmação
                try {
                    $this->enviarEmailConfirmacao($empresa);
                } catch (Exception $e) {
                    // Apenas logar o erro, mas não impedir o cadastro
                    $this->errorHandler->logError(
                        ErrorHandler::SYSTEM_ERROR,
                        "Erro ao enviar email de confirmação",
                        "Falha ao enviar email para empresa recém-cadastrada: " . $e->getMessage(),
                        $e
                    );
                }
                
                // Retornar sucesso
                echo $this->errorHandler->success("Empresa cadastrada com sucesso!");
            } else {
                echo $this->errorHandler->handleError(
                    ErrorHandler::DATABASE_ERROR,
                    "Não foi possível cadastrar a empresa. Por favor, tente novamente.",
                    "Erro ao cadastrar empresa no banco de dados."
                );
            }
        } catch (PDOException $e) {
            echo $this->errorHandler->handleError(
                ErrorHandler::DATABASE_ERROR,
                "Erro ao cadastrar empresa. Verifique os dados e tente novamente.",
                "Erro de PDO ao cadastrar empresa: " . $e->getMessage(),
                $e
            );
        } catch (Exception $e) {
            echo $this->errorHandler->handleError(
                ErrorHandler::SYSTEM_ERROR,
                "Ocorreu um erro ao processar seu cadastro. Por favor, tente novamente mais tarde.",
                "Erro não esperado durante cadastro: " . $e->getMessage(),
                $e
            );
        }
    }
    
    /**
     * Altera dados de uma empresa existente
     */
    private function alterarEmpresa() {
        try {
            // Validação do ID
            $id = filter_input(INPUT_POST, 'id');
            if (empty($id)) {
                echo $this->errorHandler->handleError(
                    ErrorHandler::VALIDATION_ERROR,
                    "ID da empresa não fornecido.",
                    "Campo ID obrigatório não fornecido para alteração"
                );
                return;
            }
            
            // Tentar obter empresa atual para log de alterações
            $empresaAtual = null;
            try {
                $empresaAtual = $this->empresaDAO->obterEmpresaPorId($id);
            } catch (Exception $e) {
                // Ignorar erro e continuar
            }
            
            // Validar que name é igual a soc1_name
            $name = filter_input(INPUT_POST, 'name');
            $soc1_name = filter_input(INPUT_POST, 'soc1_name');
            
            if ($name != $soc1_name) {
                echo $this->errorHandler->handleError(
                    ErrorHandler::VALIDATION_ERROR,
                    "O nome do responsável deve ser igual ao nome do primeiro sócio.",
                    "Inconsistência entre nome do responsável ($name) e nome do primeiro sócio ($soc1_name)"
                );
                return;
            }

            // Criar objeto Empresa com os dados do POST
            $empresa = $this->criarEmpresaDePost();
            $empresa->setId($id);
            
            // Tentar alterar a empresa
            if ($this->empresaDAO->alterarEmpresa($empresa)) {
                // Detectar alterações
                $alteracoes = [];
                if ($empresaAtual) {
                    // Comparar campos relevantes
                    if ($empresaAtual->getEmp_sit_cad() != $empresa->getEmp_sit_cad()) {
                        $alteracoes[] = "Situação: " . $empresaAtual->getEmp_sit_cad() . " → " . $empresa->getEmp_sit_cad();
                    }
                    if ($empresaAtual->getEmp_porte() != $empresa->getEmp_porte()) {
                        $alteracoes[] = "Porte da Empresa: " . $empresaAtual->getEmp_porte() . " → " . $empresa->getEmp_porte();
                    }
                    if ($empresaAtual->getEmp_tel() != $empresa->getEmp_tel()) {
                        $alteracoes[] = "Telefone: " . $empresaAtual->getEmp_tel() . " → " . $empresa->getEmp_tel();
                    }
                    if ($empresaAtual->getEmail_empresa() != $empresa->getEmail_empresa()) {
                        $alteracoes[] = "Email da Empresa: " . $empresaAtual->getEmail_empresa() . " → " . $empresa->getEmail_empresa();
                    }
                    // Adicione mais comparações conforme necessário
                }
                
                // Registrar ação de alteração no log
                $dadosLog = [
                    'usuario' => $empresa->getUsuario(),
                    'id' => $id,
                    'codigo' => $empresa->getEmp_code(),
                    'nome' => $empresa->getEmp_name(),
                    'cnpj' => $empresa->getEmp_cnpj(),
                    'tipo_juridico' => $empresa->getEmp_tipo_jur(),
                    'porte' => $empresa->getEmp_porte()
                ];
                
                if (!empty($alteracoes)) {
                    $dadosLog['alteracoes'] = $alteracoes;
                }
                
                $this->errorHandler->logAction(
                    'ALTERACAO', 
                    "Empresa alterada: " . $empresa->getEmp_name() . " (CNPJ: " . $empresa->getEmp_cnpj() . ")", 
                    $dadosLog
                );
                
                // Enviar email de confirmação de alteração
                try {
                    $this->enviarEmailAlteracao($empresa);
                } catch (Exception $e) {
                    // Apenas logar o erro, mas não impedir a alteração
                    $this->errorHandler->logError(
                        ErrorHandler::SYSTEM_ERROR,
                        "Erro ao enviar email de confirmação de alteração",
                        "Falha ao enviar email para empresa alterada: " . $e->getMessage(),
                        $e
                    );
                }
                
                // Retornar sucesso
                echo $this->errorHandler->success("Empresa alterada com sucesso!");
            } else {
                echo $this->errorHandler->handleError(
                    ErrorHandler::DATABASE_ERROR,
                    "Não foi possível alterar a empresa. Por favor, tente novamente.",
                    "Erro ao alterar empresa no banco de dados."
                );
            }
        } catch (PDOException $e) {
            echo $this->errorHandler->handleError(
                ErrorHandler::DATABASE_ERROR,
                "Erro ao alterar empresa. Verifique os dados e tente novamente.",
                "Erro de PDO ao alterar empresa: " . $e->getMessage(),
                $e
            );
        } catch (Exception $e) {
            echo $this->errorHandler->handleError(
                ErrorHandler::SYSTEM_ERROR,
                "Ocorreu um erro ao processar sua alteração. Por favor, tente novamente mais tarde.",
                "Erro não esperado durante alteração: " . $e->getMessage(),
                $e
            );
        }
    }
    
    /**
     * Remove uma empresa do sistema
     */
    private function removerEmpresa() {
        try {
            $id = filter_input(INPUT_POST, 'id');
            $usuario = filter_input(INPUT_POST, 'usuario');
            
            // Validação do ID
            if (empty($id)) {
                echo $this->errorHandler->handleError(
                    ErrorHandler::VALIDATION_ERROR,
                    "ID da empresa não fornecido.",
                    "Campo ID obrigatório não fornecido para remoção"
                );
                return;
            }
            
            // Tentar obter informações da empresa antes de remover
            $empresaInfo = null;
            $nome = "ID: $id";
            $cnpj = "";
            try {
                $empresaInfo = $this->empresaDAO->obterEmpresaPorId($id);
                if ($empresaInfo) {
                    $nome = $empresaInfo->getEmp_name();
                    $cnpj = $empresaInfo->getEmp_cnpj();
                }
            } catch (Exception $e) {
                // Ignorar erros e continuar
            }

            $empresa = new Empresa();
            $empresa->setId($id);

            // Tentar remover a empresa
            if ($this->empresaDAO->removerEmpresa($empresa)) {
                // Registrar ação de remoção no log
                $dadosLog = [
                    'usuario' => $usuario ?? 'Não informado',
                    'id' => $id
                ];
                
                // Adicionar informações detalhadas se disponíveis
                if ($empresaInfo) {
                    $dadosLog['nome'] = $nome;
                    $dadosLog['codigo'] = $empresaInfo->getEmp_code();
                    $dadosLog['cnpj'] = $cnpj;
                    $dadosLog['porte'] = $empresaInfo->getEmp_porte();
                    $dadosLog['tipo_juridico'] = $empresaInfo->getEmp_tipo_jur();
                }
                
                $this->errorHandler->logAction('REMOCAO', "Empresa removida: $nome", $dadosLog);
                
                // Retornar sucesso
                echo $this->errorHandler->success("Empresa removida com sucesso!");
            } else {
                echo $this->errorHandler->handleError(
                    ErrorHandler::DATABASE_ERROR,
                    "Não foi possível remover a empresa. Por favor, tente novamente.",
                    "Erro ao remover empresa no banco de dados."
                );
            }
        } catch (PDOException $e) {
            echo $this->errorHandler->handleError(
                ErrorHandler::DATABASE_ERROR,
                "Erro ao remover empresa. Pode haver registros dependentes.",
                "Erro de PDO ao remover empresa: " . $e->getMessage(),
                $e
            );
        } catch (Exception $e) {
            echo $this->errorHandler->handleError(
                ErrorHandler::SYSTEM_ERROR,
                "Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente mais tarde.",
                "Erro não esperado durante remoção: " . $e->getMessage(),
                $e
            );
        }
    }
    
    /**
     * Lista todas as empresas ou filtra por parâmetros
     */
    private function listarEmpresas() {
        try {
            $filtro = filter_input(INPUT_POST, 'filtro', FILTER_SANITIZE_STRING);
            $valor = filter_input(INPUT_POST, 'valor', FILTER_SANITIZE_STRING);
            
            $empresas = $this->empresaDAO->listarEmpresas($filtro, $valor);
            
            // Formatar resposta como JSON
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => $empresas
            ]);
            
        } catch (Exception $e) {
            echo $this->errorHandler->handleError(
                ErrorHandler::SYSTEM_ERROR,
                "Ocorreu um erro ao listar as empresas.",
                "Erro ao listar empresas: " . $e->getMessage(),
                $e
            );
        }
    }
    
    /**
     * Busca uma empresa específica por ID
     */
    private function buscarEmpresa() {
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            
            if (!$id) {
                echo $this->errorHandler->handleError(
                    ErrorHandler::VALIDATION_ERROR,
                    "ID da empresa não fornecido ou inválido.",
                    "ID inválido para busca de empresa"
                );
                return;
            }
            
            $empresa = $this->empresaDAO->obterEmpresaPorId($id);
            
            if ($empresa) {
                // Formatar resposta como JSON
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'data' => $empresa
                ]);
            } else {
                echo $this->errorHandler->handleError(
                    ErrorHandler::VALIDATION_ERROR,
                    "Empresa não encontrada.",
                    "Nenhuma empresa encontrada com o ID: $id"
                );
            }
            
        } catch (Exception $e) {
            echo $this->errorHandler->handleError(
                ErrorHandler::SYSTEM_ERROR,
                "Ocorreu um erro ao buscar a empresa.",
                "Erro ao buscar empresa: " . $e->getMessage(),
                $e
            );
        }
    }

        /**
     * Busca uma empresa específica por código
     */
    private function buscarEmpresaPorCodigo() {
        try {
            $codigo = filter_input(INPUT_GET, 'codigo', FILTER_SANITIZE_STRING);
            
            if (empty($codigo)) {
                // Resposta para erro de validação
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Código da empresa não fornecido.'
                ]);
                return;
            }
            
            $empresa = $this->empresaDAO->obterEmpresaPorCodigo($codigo);
            
            if ($empresa) {
                // Registrar visualização no log
                $this->errorHandler->logAction(
                    'VISUALIZACAO', 
                    "Empresa visualizada por código: " . $empresa->getEmp_name() . " (CNPJ: " . $empresa->getEmp_cnpj() . ")", 
                    ['codigo' => $codigo, 'usuario' => isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'Não identificado']
                );
                
                // Formatar resposta como JSON
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id' => $empresa->getId(),
                        'emp_code' => $empresa->getEmp_code(),
                        'emp_name' => $empresa->getEmp_name(),
                        'emp_cnpj' => $empresa->getEmp_cnpj(),
                        'emp_sit_cad' => $empresa->getEmp_sit_cad(),
                        'emp_tipo_jur' => $empresa->getEmp_tipo_jur(),
                        'emp_porte' => $empresa->getEmp_porte(),
                        'emp_cid' => $empresa->getEmp_cid(),
                        'emp_uf' => $empresa->getEmp_uf(),
                        'name' => $empresa->getName(),
                        'emp_tel' => $empresa->getEmp_tel(),
                        'email_empresa' => $empresa->getEmail_empresa(),
                        'data' => $empresa->getData()
                    ]
                ]);
            } else {
                // Resposta para empresa não encontrada
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Empresa não encontrada com o código informado.'
                ]);
            }
            
        } catch (Exception $e) {
            // Resposta para erro do sistema
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Ocorreu um erro ao buscar a empresa.',
                'details' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Cria um objeto Empresa a partir dos dados do POST
     */
    private function criarEmpresaDePost() {
        $empresa = new Empresa();
        
        // SITUAÇÃO DA EMPRESA
        $empresa->setEmp_sit_cad(filter_input(INPUT_POST, 'emp_sit_cad'));
        $empresa->setEmp_code(filter_input(INPUT_POST, 'emp_code'));
        $empresa->setEmp_name(filter_input(INPUT_POST, 'emp_name'));
        $empresa->setEmp_tel(filter_input(INPUT_POST, 'emp_tel'));
        $empresa->setEmp_cnpj(filter_input(INPUT_POST, 'emp_cnpj'));
        $empresa->setEmp_iest(filter_input(INPUT_POST, 'emp_iest'));
        $empresa->setEmp_imun(filter_input(INPUT_POST, 'emp_imun'));
        $empresa->setEmp_reg_apu(filter_input(INPUT_POST, 'emp_reg_apu'));
        $empresa->setEmp_porte(filter_input(INPUT_POST, 'emp_porte'));
        $empresa->setEmp_tipo_jur(filter_input(INPUT_POST, 'emp_tipo_jur'));
        $empresa->setEmp_nat_jur(filter_input(INPUT_POST, 'emp_nat_jur'));
        $empresa->setEmp_cep(filter_input(INPUT_POST, 'emp_cep'));
        $empresa->setEmp_ende(filter_input(INPUT_POST, 'emp_ende'));
        $empresa->setEmp_nume(filter_input(INPUT_POST, 'emp_nume'));
        $empresa->setEmp_comp(filter_input(INPUT_POST, 'emp_comp'));
        $empresa->setEmp_bair(filter_input(INPUT_POST, 'emp_bair'));
        $empresa->setEmp_cid(filter_input(INPUT_POST, 'emp_cid'));
        $empresa->setEmp_uf(filter_input(INPUT_POST, 'emp_uf'));
        $empresa->setEmp_org_reg(filter_input(INPUT_POST, 'emp_org_reg'));
        $empresa->setEmp_reg_nire(filter_input(INPUT_POST, 'emp_reg_nire'));
        $empresa->setEmp_ult_reg(filter_input(INPUT_POST, 'emp_ult_reg'));
        $empresa->setEmp_cod_ace(filter_input(INPUT_POST, 'emp_cod_ace'));
        $empresa->setEmp_cod_pre(filter_input(INPUT_POST, 'emp_cod_pre'));
        $empresa->setSenha_pfe(filter_input(INPUT_POST, 'senha_pfe'));
        $empresa->setEmp_cer_dig_data(filter_input(INPUT_POST, 'emp_cer_dig_data'));
        $empresa->setName(filter_input(INPUT_POST, 'name'));
        $empresa->setEmail_empresa(filter_input(INPUT_POST, 'email_empresa'));
        
        // CADASTRO SOCIO 1
        $empresa->setSoc1_name(filter_input(INPUT_POST, 'soc1_name'));
        $empresa->setSoc1_cpf(filter_input(INPUT_POST, 'soc1_cpf'));
        $empresa->setSoc1_entrada(filter_input(INPUT_POST, 'soc1_entrada'));
        $empresa->setSoc1_email(filter_input(INPUT_POST, 'soc1_email'));
        $empresa->setSoc1_tel(filter_input(INPUT_POST, 'soc1_tel'));
        $empresa->setSoc1_cel(filter_input(INPUT_POST, 'soc1_cel'));
        $empresa->setSoc1_cep(filter_input(INPUT_POST, 'soc1_cep'));
        $empresa->setSoc1_ende(filter_input(INPUT_POST, 'soc1_ende'));
        $empresa->setSoc1_nume(filter_input(INPUT_POST, 'soc1_nume'));
        $empresa->setSoc1_comp(filter_input(INPUT_POST, 'soc1_comp'));
        $empresa->setSoc1_bair(filter_input(INPUT_POST, 'soc1_bair'));
        $empresa->setSoc1_cid(filter_input(INPUT_POST, 'soc1_cid'));
        $empresa->setSoc1_uf(filter_input(INPUT_POST, 'soc1_uf'));
        $empresa->setSoc1_quali(filter_input(INPUT_POST, 'soc1_quali'));
        $empresa->setSoc1_ass(filter_input(INPUT_POST, 'soc1_ass'));
        $empresa->setSoc1_capit(filter_input(INPUT_POST, 'soc1_capit'));
        $empresa->setSoc1_govbr(filter_input(INPUT_POST, 'soc1_govbr'));
        $empresa->setSoc1_qualif_govbr(filter_input(INPUT_POST, 'soc1_qualif_govbr'));
        
        //CADASTRO SOCIO 2
        $empresa->setSoc2_name(filter_input(INPUT_POST, 'soc2_name'));
        $empresa->setSoc2_cpf(filter_input(INPUT_POST, 'soc2_cpf'));
        $empresa->setSoc2_entrada(filter_input(INPUT_POST, 'soc2_entrada'));
        $empresa->setSoc2_email(filter_input(INPUT_POST, 'soc2_email'));
        $empresa->setSoc2_tel(filter_input(INPUT_POST, 'soc2_tel'));
        $empresa->setSoc2_cel(filter_input(INPUT_POST, 'soc2_cel'));
        $empresa->setSoc2_cep(filter_input(INPUT_POST, 'soc2_cep'));
        $empresa->setSoc2_ende(filter_input(INPUT_POST, 'soc2_ende'));
        $empresa->setSoc2_nume(filter_input(INPUT_POST, 'soc2_nume'));
        $empresa->setSoc2_comp(filter_input(INPUT_POST, 'soc2_comp'));
        $empresa->setSoc2_bair(filter_input(INPUT_POST, 'soc2_bair'));
        $empresa->setSoc2_cid(filter_input(INPUT_POST, 'soc2_cid'));
        $empresa->setSoc2_uf(filter_input(INPUT_POST, 'soc2_uf'));
        $empresa->setSoc2_quali(filter_input(INPUT_POST, 'soc2_quali'));
        $empresa->setSoc2_ass(filter_input(INPUT_POST, 'soc2_ass'));
        $empresa->setSoc2_capit(filter_input(INPUT_POST, 'soc2_capit'));
        $empresa->setSoc2_govbr(filter_input(INPUT_POST, 'soc2_govbr'));
        $empresa->setSoc2_qualif_govbr(filter_input(INPUT_POST, 'soc2_qualif_govbr'));
        
        //CADASTRO SOCIO 3
        $empresa->setSoc3_name(filter_input(INPUT_POST, 'soc3_name'));
        $empresa->setSoc3_cpf(filter_input(INPUT_POST, 'soc3_cpf'));
        $empresa->setSoc3_entrada(filter_input(INPUT_POST, 'soc3_entrada'));
        $empresa->setSoc3_email(filter_input(INPUT_POST, 'soc3_email'));
        $empresa->setSoc3_tel(filter_input(INPUT_POST, 'soc3_tel'));
        $empresa->setSoc3_cel(filter_input(INPUT_POST, 'soc3_cel'));
        $empresa->setSoc3_cep(filter_input(INPUT_POST, 'soc3_cep'));
        $empresa->setSoc3_ende(filter_input(INPUT_POST, 'soc3_ende'));
        <?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Controller para gerenciamento de Empresas Ativas
 * Sistema Contabilidade Estrela 2.0
 */
class EmpresasController {
    private $empresaDAO;
    private $errorHandler;
    
    /**
     * Construtor
     */
    public function __construct() {
        require_once('../model/Empresa.php');
        require_once('../dao/EmpresaDAO.php');
        require_once('../utils/ErrorHandler.php');
        require_once('../bancoDeDados/Database.php');
        
        $db = new Database();
        $this->empresaDAO = new EmpresaDAO($db);
        $this->errorHandler = new ErrorHandler(true); // true = modo debug
    }
    
    /**
     * Processa as requisições para este controller
     */
    public function processarAcao() {
        // Verificar se temos uma ação via GET (para AJAX)
        if (isset($_GET["acao"])) {
            $acao = $_GET["acao"];
            
            switch ($acao) {
                case 'obter':
                    $this->obterEmpresa();
                    return;
                    
                case 'buscar_por_codigo':
                    $this->buscarEmpresaPorCodigo();
                    return;
                    
                case 'documentos_recentes':
                    $this->listarDocumentosRecentes();
                    return;
            }
        }
        
        // Verificar se a ação foi definida via POST
        if (!isset($_POST["acao"])) {
            echo $this->errorHandler->handleError(
                ErrorHandler::VALIDATION_ERROR,
                "Nenhuma ação especificada.",
                "O parâmetro 'acao' não foi enviado no POST."
            );
            exit;
        }

        $acao = $_POST["acao"];

        switch ($acao) {
            case 'cadastrar':
                $this->cadastrarEmpresa();
                break;

            case 'alterar':
                $this->alterarEmpresa();
                break;

            case 'remover':
                $this->removerEmpresa();
                break;
                
            case 'listar':
                $this->listarEmpresas();
                break;
                
            case 'buscar':
                $this->buscarEmpresa();
                break;
                
            default:
                echo $this->errorHandler->handleError(
                    ErrorHandler::VALIDATION_ERROR,
                    "Ação inválida.",
                    "O valor do parâmetro 'acao' não é reconhecido: " . $acao
                );
                exit;
        }
    }
    
    /**
     * Obtém dados de uma empresa específica por ID, formatado para JSON
     */
    private function obterEmpresa() {
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            
            if (!$id) {
                // Resposta para erro de validação
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'ID da empresa não fornecido ou inválido.'
                ]);
                return;
            }
            
            $empresa = $this->empresaDAO->obterEmpresaPorId($id);
            
            if ($empresa) {
                // Registrar visualização no log
                $this->errorHandler->logAction(
                    'VISUALIZACAO', 
                    "Empresa visualizada: " . $empresa->getEmp_name() . " (CNPJ: " . $empresa->getEmp_cnpj() . ")", 
                    ['id' => $id, 'usuario' => isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'Não identificado']
                );
                
                // Formatar resposta como JSON
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id' => $empresa->getId(),
                        'emp_code' => $empresa->getEmp_code(),
                        'emp_name' => $empresa->getEmp_name(),
                        'emp_cnpj' => $empresa->getEmp_cnpj(),
                        'emp_sit_cad' => $empresa->getEmp_sit_cad(),
                        'emp_tipo_jur' => $empresa->getEmp_tipo_jur(),
                        'emp_porte' => $empresa->getEmp_porte(),
                        'emp_cid' => $empresa->getEmp_cid(),
                        'emp_uf' => $empresa->getEmp_uf(),
                        'name' => $empresa->getName(),
                        'emp_tel' => $empresa->getEmp_tel(),
                        'email_empresa' => $empresa->getEmail_empresa(),
                        'data' => $empresa->getData()
                    ]
                ]);
            } else {
                // Resposta para empresa não encontrada
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Empresa não encontrada.'
                ]);
            }
            
        } catch (Exception $e) {
            // Resposta para erro do sistema
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Ocorreu um erro ao buscar a empresa.',
                'details' => $e->getMessage()
            ]);
        }
    }

    /**
     * Lista documentos recentes de uma empresa
     */
    private function listarDocumentosRecentes() {
        try {
            $empresaId = filter_input(INPUT_GET, 'empresa_id', FILTER_VALIDATE_INT);
            $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 5;
            
            if (!$empresaId) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'ID da empresa não fornecido ou inválido.'
                ]);
                return;
            }
            
            // Incluir DAO de documentos
            require_once('ROOT_DIR . '/app/Dao/DocumentoDao.php');
            $db = new Database();
            $documentoDAO = new DocumentoDAO($db);
            
            $documentos = $documentoDAO->listarDocumentosPorEmpresa($empresaId, $limit);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $documentos
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Ocorreu um erro ao listar os documentos.',
                'details' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Cadastra uma nova empresa
     */
    private function cadastrarEmpresa() {
        try {
            // Validação básica dos campos obrigatórios
            $camposObrigatorios = ['emp_code', 'emp_name', 'emp_cnpj', 'name', 'soc1_name', 'soc1_cpf'];
            foreach ($camposObrigatorios as $campo) {
                if (empty($_POST[$campo])) {
                    echo $this->errorHandler->handleError(
                        ErrorHandler::VALIDATION_ERROR,
                        "O campo '$campo' é obrigatório.",
                        "Validação de campo obrigatório falhou: $campo"
                    );
                    return;
                }
            }
            
            // Validar que name é igual a soc1_name
            $name = filter_input(INPUT_POST, 'name');
            $soc1_name = filter_input(INPUT_POST, 'soc1_name');
            
            if ($name != $soc1_name) {
                echo $this->errorHandler->handleError(
                    ErrorHandler::VALIDATION_ERROR,
                    "O nome do responsável deve ser igual ao nome do primeiro sócio.",
                    "Inconsistência entre nome do responsável ($name) e nome do primeiro sócio ($soc1_name)"
                );
                return;
            }

            // Criar objeto Empresa com os dados do POST
            $empresa = $this->criarEmpresaDePost();
            
            // Verificar se já existe empresa com mesmo código, nome ou CNPJ
            if ($this->empresaDAO->verificarCodigo($empresa)) {
                echo $this->errorHandler->handleError(
                    ErrorHandler::VALIDATION_ERROR,
                    "Este código de empresa já está cadastrado no sistema.",
                    "Erro: Código já Cadastrado: " . $empresa->getEmp_code()
                );
                return;
            }
            
            if ($this->empresaDAO->verificarNome($empresa)) {
                echo $this->errorHandler->handleError(
                    ErrorHandler::VALIDATION_ERROR,
                    "Este nome de empresa já está cadastrado no sistema.",
                    "Erro: Nome já Cadastrado: " . $empresa->getEmp_name()
                );
                return;
            }
            
            if ($this->empresaDAO->verificarCnpj($empresa)) {
                echo $this->errorHandler->handleError(
                    ErrorHandler::VALIDATION_ERROR,
                    "Este CNPJ já está cadastrado no sistema.",
                    "Erro: CNPJ já Cadastrado: " . $empresa->getEmp_cnpj()
                );
                return;
            }

            // Tentar adicionar a empresa
            if ($this->empresaDAO->adicionarEmpresa($empresa)) {
                // Registrar ação de cadastro no log
                $dadosLog = [
                    'usuario' => $empresa->getUsuario(),
                    'codigo' => $empresa->getEmp_code(),
                    'nome' => $empresa->getEmp_name(),
                    'cnpj' => $empresa->getEmp_cnpj(),
                    'tipo_juridico' => $empresa->getEmp_tipo_jur(),
                    'porte' => $empresa->getEmp_porte()
                ];
                $this->errorHandler->logAction(
                    'CADASTRO', 
                    "Nova empresa cadastrada: " . $empresa->getEmp_name() . " (CNPJ: " . $empresa->getEmp_cnpj() . ")", 
                    $dadosLog
                );
                
                // Enviar email de confirmação
                try {
                    $this->enviarEmailConfirmacao($empresa);
                } catch (Exception $e) {
                    // Apenas logar o erro, mas não impedir o cadastro
                    $this->errorHandler->logError(
                        ErrorHandler::SYSTEM_ERROR,
                        "Erro ao enviar email de confirmação",
                        "Falha ao enviar email para empresa recém-cadastrada: " . $e->getMessage(),
                        $e
                    );
                }
                
                // Retornar sucesso
                echo $this->errorHandler->success("Empresa cadastrada com sucesso!");
            } else {
                echo $this->errorHandler->handleError(
                    ErrorHandler::DATABASE_ERROR,
                    "Não foi possível cadastrar a empresa. Por favor, tente novamente.",
                    "Erro ao cadastrar empresa no banco de dados."
                );
            }
        } catch (PDOException $e) {
            echo $this->errorHandler->handleError(
                ErrorHandler::DATABASE_ERROR,
                "Erro ao cadastrar empresa. Verifique os dados e tente novamente.",
                "Erro de PDO ao cadastrar empresa: " . $e->getMessage(),
                $e
            );
        } catch (Exception $e) {
            echo $this->errorHandler->handleError(
                ErrorHandler::SYSTEM_ERROR,
                "Ocorreu um erro ao processar seu cadastro. Por favor, tente novamente mais tarde.",
                "Erro não esperado durante cadastro: " . $e->getMessage(),
                $e
            );
        }
    }
    
    /**
     * Altera dados de uma empresa existente
     */
    private function alterarEmpresa() {
        try {
            // Validação do ID
            $id = filter_input(INPUT_POST, 'id');
            if (empty($id)) {
                echo $this->errorHandler->handleError(
                    ErrorHandler::VALIDATION_ERROR,
                    "ID da empresa não fornecido.",
                    "Campo ID obrigatório não fornecido para alteração"
                );
                return;
            }
            
            // Tentar obter empresa atual para log de alterações
            $empresaAtual = null;
            try {
                $empresaAtual = $this->empresaDAO->obterEmpresaPorId($id);
            } catch (Exception $e) {
                // Ignorar erro e continuar
            }
            
            // Validar que name é igual a soc1_name
            $name = filter_input(INPUT_POST, 'name');
            $soc1_name = filter_input(INPUT_POST, 'soc1_name');
            
            if ($name != $soc1_name) {
                echo $this->errorHandler->handleError(
                    ErrorHandler::VALIDATION_ERROR,
                    "O nome do responsável deve ser igual ao nome do primeiro sócio.",
                    "Inconsistência entre nome do responsável ($name) e nome do primeiro sócio ($soc1_name)"
                );
                return;
            }

            // Criar objeto Empresa com os dados do POST
            $empresa = $this->criarEmpresaDePost();
            $empresa->setId($id);
            
            // Tentar alterar a empresa
            if ($this->empresaDAO->alterarEmpresa($empresa)) {
                // Detectar alterações
                $alteracoes = [];
                if ($empresaAtual) {
                    // Comparar campos relevantes
                    if ($empresaAtual->getEmp_sit_cad() != $empresa->getEmp_sit_cad()) {
                        $alteracoes[] = "Situação: " . $empresaAtual->getEmp_sit_cad() . " → " . $empresa->getEmp_sit_cad();
                    }
                    if ($empresaAtual->getEmp_porte() != $empresa->getEmp_porte()) {
                        $alteracoes[] = "Porte da Empresa: " . $empresaAtual->getEmp_porte() . " → " . $empresa->getEmp_porte();
                    }
                    if ($empresaAtual->getEmp_tel() != $empresa->getEmp_tel()) {
                        $alteracoes[] = "Telefone: " . $empresaAtual->getEmp_tel() . " → " . $empresa->getEmp_tel();
                    }
                    if ($empresaAtual->getEmail_empresa() != $empresa->getEmail_empresa()) {
                        $alteracoes[] = "Email da Empresa: " . $empresaAtual->getEmail_empresa() . " → " . $empresa->getEmail_empresa();
                    }
                    // Adicione mais comparações conforme necessário
                }
                
                // Registrar ação de alteração no log
                $dadosLog = [
                    'usuario' => $empresa->getUsuario(),
                    'id' => $id,
                    'codigo' => $empresa->getEmp_code(),
                    'nome' => $empresa->getEmp_name(),
                    'cnpj' => $empresa->getEmp_cnpj(),
                    'tipo_juridico' => $empresa->getEmp_tipo_jur(),
                    'porte' => $empresa->getEmp_porte()
                ];
                
                if (!empty($alteracoes)) {
                    $dadosLog['alteracoes'] = $alteracoes;
                }
                
                $this->errorHandler->logAction(
                    'ALTERACAO', 
                    "Empresa alterada: " . $empresa->getEmp_name() . " (CNPJ: " . $empresa->getEmp_cnpj() . ")", 
                    $dadosLog
                );
                
                // Enviar email de confirmação de alteração
                try {
                    $this->enviarEmailAlteracao($empresa);
                } catch (Exception $e) {
                    // Apenas logar o erro, mas não impedir a alteração
                    $this->errorHandler->logError(
                        ErrorHandler::SYSTEM_ERROR,
                        "Erro ao enviar email de confirmação de alteração",
                        "Falha ao enviar email para empresa alterada: " . $e->getMessage(),
                        $e
                    );
                }
                
                // Retornar sucesso
                echo $this->errorHandler->success("Empresa alterada com sucesso!");
            } else {
                echo $this->errorHandler->handleError(
                    ErrorHandler::DATABASE_ERROR,
                    "Não foi possível alterar a empresa. Por favor, tente novamente.",
                    "Erro ao alterar empresa no banco de dados."
                );
            }
        } catch (PDOException $e) {
            echo $this->errorHandler->handleError(
                ErrorHandler::DATABASE_ERROR,
                "Erro ao alterar empresa. Verifique os dados e tente novamente.",
                "Erro de PDO ao alterar empresa: " . $e->getMessage(),
                $e
            );
        } catch (Exception $e) {
            echo $this->errorHandler->handleError(
                ErrorHandler::SYSTEM_ERROR,
                "Ocorreu um erro ao processar sua alteração. Por favor, tente novamente mais tarde.",
                "Erro não esperado durante alteração: " . $e->getMessage(),
                $e
            );
        }
    }
    
    /**
     * Remove uma empresa do sistema
     */
    private function removerEmpresa() {
        try {
            $id = filter_input(INPUT_POST, 'id');
            $usuario = filter_input(INPUT_POST, 'usuario');
            
            // Validação do ID
            if (empty($id)) {
                echo $this->errorHandler->handleError(
                    ErrorHandler::VALIDATION_ERROR,
                    "ID da empresa não fornecido.",
                    "Campo ID obrigatório não fornecido para remoção"
                );
                return;
            }
            
            // Tentar obter informações da empresa antes de remover
            $empresaInfo = null;
            $nome = "ID: $id";
            $cnpj = "";
            try {
                $empresaInfo = $this->empresaDAO->obterEmpresaPorId($id);
                if ($empresaInfo) {
                    $nome = $empresaInfo->getEmp_name();
                    $cnpj = $empresaInfo->getEmp_cnpj();
                }
            } catch (Exception $e) {
                // Ignorar erros e continuar
            }

            $empresa = new Empresa();
            $empresa->setId($id);

            // Tentar remover a empresa
            if ($this->empresaDAO->removerEmpresa($empresa)) {
                // Registrar ação de remoção no log
                $dadosLog = [
                    'usuario' => $usuario ?? 'Não informado',
                    'id' => $id
                ];
                
                // Adicionar informações detalhadas se disponíveis
                if ($empresaInfo) {
                    $dadosLog['nome'] = $nome;
                    $dadosLog['codigo'] = $empresaInfo->getEmp_code();
                    $dadosLog['cnpj'] = $cnpj;
                    $dadosLog['porte'] = $empresaInfo->getEmp_porte();
                    $dadosLog['tipo_juridico'] = $empresaInfo->getEmp_tipo_jur();
                }
                
                $this->errorHandler->logAction('REMOCAO', "Empresa removida: $nome", $dadosLog);
                
                // Retornar sucesso
                echo $this->errorHandler->success("Empresa removida com sucesso!");
            } else {
                echo $this->errorHandler->handleError(
                    ErrorHandler::DATABASE_ERROR,
                    "Não foi possível remover a empresa. Por favor, tente novamente.",
                    "Erro ao remover empresa no banco de dados."
                );
            }
        } catch (PDOException $e) {
            echo $this->errorHandler->handleError(
                ErrorHandler::DATABASE_ERROR,
                "Erro ao remover empresa. Pode haver registros dependentes.",
                "Erro de PDO ao remover empresa: " . $e->getMessage(),
                $e
            );
        } catch (Exception $e) {
            echo $this->errorHandler->handleError(
                ErrorHandler::SYSTEM_ERROR,
                "Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente mais tarde.",
                "Erro não esperado durante remoção: " . $e->getMessage(),
                $e
            );
        }
    }
    
    /**
     * Lista todas as empresas ou filtra por parâmetros
     */
    private function listarEmpresas() {
        try {
            $filtro = filter_input(INPUT_POST, 'filtro', FILTER_SANITIZE_STRING);
            $valor = filter_input(INPUT_POST, 'valor', FILTER_SANITIZE_STRING);
            
            $empresas = $this->empresaDAO->listarEmpresas($filtro, $valor);
            
            // Formatar resposta como JSON
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => $empresas
            ]);
            
        } catch (Exception $e) {
            echo $this->errorHandler->handleError(
                ErrorHandler::SYSTEM_ERROR,
                "Ocorreu um erro ao listar as empresas.",
                "Erro ao listar empresas: " . $e->getMessage(),
                $e
            );
        }
    }
    
    /**
     * Busca uma empresa específica por ID
     */
    private function buscarEmpresa() {
        try {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            
            if (!$id) {
                echo $this->errorHandler->handleError(
                    ErrorHandler::VALIDATION_ERROR,
                    "ID da empresa não fornecido ou inválido.",
                    "ID inválido para busca de empresa"
                );
                return;
            }
            
            $empresa = $this->empresaDAO->obterEmpresaPorId($id);
            
            if ($empresa) {
                // Formatar resposta como JSON
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'data' => $empresa
                ]);
            } else {
                echo $this->errorHandler->handleError(
                    ErrorHandler::VALIDATION_ERROR,
                    "Empresa não encontrada.",
                    "Nenhuma empresa encontrada com o ID: $id"
                );
            }
            
        } catch (Exception $e) {
            echo $this->errorHandler->handleError(
                ErrorHandler::SYSTEM_ERROR,
                "Ocorreu um erro ao buscar a empresa.",
                "Erro ao buscar empresa: " . $e->getMessage(),
                $e
            );
        }
    }
    
    /**
     * Cria um objeto Empresa a partir dos dados do POST
     */
    private function criarEmpresaDePost() {
        $empresa = new Empresa();
        
        // SITUAÇÃO DA EMPRESA
        $empresa->setEmp_sit_cad(filter_input(INPUT_POST, 'emp_sit_cad'));
        $empresa->setEmp_code(filter_input(INPUT_POST, 'emp_code'));
        $empresa->setEmp_name(filter_input(INPUT_POST, 'emp_name'));
        $empresa->setEmp_tel(filter_input(INPUT_POST, 'emp_tel'));
        $empresa->setEmp_cnpj(filter_input(INPUT_POST, 'emp_cnpj'));
        $empresa->setEmp_iest(filter_input(INPUT_POST, 'emp_iest'));
        $empresa->setEmp_imun(filter_input(INPUT_POST, 'emp_imun'));
        $empresa->setEmp_reg_apu(filter_input(INPUT_POST, 'emp_reg_apu'));
        $empresa->setEmp_porte(filter_input(INPUT_POST, 'emp_porte'));
        $empresa->setEmp_tipo_jur(filter_input(INPUT_POST, 'emp_tipo_jur'));
        $empresa->setEmp_nat_jur(filter_input(INPUT_POST, 'emp_nat_jur'));
        $empresa->setEmp_cep(filter_input(INPUT_POST, 'emp_cep'));
        $empresa->setEmp_ende(filter_input(INPUT_POST, 'emp_ende'));
        $empresa->setEmp_nume(filter_input(INPUT_POST, 'emp_nume'));
        $empresa->setEmp_comp(filter_input(INPUT_POST, 'emp_comp'));
        $empresa->setEmp_bair(filter_input(INPUT_POST, 'emp_bair'));
        $empresa->setEmp_cid(filter_input(INPUT_POST, 'emp_cid'));
        $empresa->setEmp_uf(filter_input(INPUT_POST, 'emp_uf'));
        $empresa->setEmp_org_reg(filter_input(INPUT_POST, 'emp_org_reg'));
        $empresa->setEmp_reg_nire(filter_input(INPUT_POST, 'emp_reg_nire'));
        $empresa->setEmp_ult_reg(filter_input(INPUT_POST, 'emp_ult_reg'));
        $empresa->setEmp_cod_ace(filter_input(INPUT_POST, 'emp_cod_ace'));
        $empresa->setEmp_cod_pre(filter_input(INPUT_POST, 'emp_cod_pre'));
        $empresa->setSenha_pfe(filter_input(INPUT_POST, 'senha_pfe'));
        $empresa->setEmp_cer_dig_data(filter_input(INPUT_POST, 'emp_cer_dig_data'));
        $empresa->setName(filter_input(INPUT_POST, 'name'));
        $empresa->setEmail_empresa(filter_input(INPUT_POST, 'email_empresa'));
        
        // CADASTRO SOCIO 1
        $empresa->setSoc1_name(filter_input(INPUT_POST, 'soc1_name'));
        $empresa->setSoc1_cpf(filter_input(INPUT_POST, 'soc1_cpf'));
        $empresa->setSoc1_entrada(filter_input(INPUT_POST, 'soc1_entrada'));
        $empresa->setSoc1_email(filter_input(INPUT_POST, 'soc1_email'));
        $empresa->setSoc1_tel(filter_input(INPUT_POST, 'soc1_tel'));
        $empresa->setSoc1_cel(filter_input(INPUT_POST, 'soc1_cel'));
        $empresa->setSoc1_cep(filter_input(INPUT_POST, 'soc1_cep'));
        $empresa->setSoc1_ende(filter_input(INPUT_POST, 'soc1_ende'));
        $empresa->setSoc1_nume(filter_input(INPUT_POST, 'soc1_nume'));
        $empresa->setSoc1_comp(filter_input(INPUT_POST, 'soc1_comp'));
        $empresa->setSoc1_bair(filter_input(INPUT_POST, 'soc1_bair'));
        $empresa->setSoc1_cid(filter_input(INPUT_POST, 'soc1_cid'));
        $empresa->setSoc1_uf(filter_input(INPUT_POST, 'soc1_uf'));
        $empresa->setSoc1_quali(filter_input(INPUT_POST, 'soc1_quali'));
        $empresa->setSoc1_ass(filter_input(INPUT_POST, 'soc1_ass'));
        $empresa->setSoc1_capit(filter_input(INPUT_POST, 'soc1_capit'));
        $empresa->setSoc1_govbr(filter_input(INPUT_POST, 'soc1_govbr'));
        $empresa->setSoc1_qualif_govbr(filter_input(INPUT_POST, 'soc1_qualif_govbr'));
        
        //CADASTRO SOCIO 2
        $empresa->setSoc2_name(filter_input(INPUT_POST, 'soc2_name'));
        $empresa->setSoc2_cpf(filter_input(INPUT_POST, 'soc2_cpf'));
        $empresa->setSoc2_entrada(filter_input(INPUT_POST, 'soc2_entrada'));
        $empresa->setSoc2_email(filter_input(INPUT_POST, 'soc2_email'));
        $empresa->setSoc2_tel(filter_input(INPUT_POST, 'soc2_tel'));
        $empresa->setSoc2_cel(filter_input(INPUT_POST, 'soc2_cel'));
        $empresa->setSoc2_cep(filter_input(INPUT_POST, 'soc2_cep'));
        $empresa->setSoc2_ende(filter_input(INPUT_POST, 'soc2_ende'));
        $empresa->setSoc2_nume(filter_input(INPUT_POST, 'soc2_nume'));
        $empresa->setSoc2_comp(filter_input(INPUT_POST, 'soc2_comp'));
        $empresa->setSoc2_bair(filter_input(INPUT_POST, 'soc2_bair'));
        $empresa->setSoc2_cid(filter_input(INPUT_POST, 'soc2_cid'));
        $empresa->setSoc2_uf(filter_input(INPUT_POST, 'soc2_uf'));
        $empresa->setSoc2_quali(filter_input(INPUT_POST, 'soc2_quali'));
        $empresa->setSoc2_ass(filter_input(INPUT_POST, 'soc2_ass'));
        $empresa->setSoc2_capit(filter_input(INPUT_POST, 'soc2_capit'));
        $empresa->setSoc2_govbr(filter_input(INPUT_POST, 'soc2_govbr'));
        $empresa->setSoc2_qualif_govbr(filter_input(INPUT_POST, 'soc2_qualif_govbr'));
        
        //CADASTRO SOCIO 3
        $empresa->setSoc3_name(filter_input(INPUT_POST, 'soc3_name'));
        $empresa->setSoc3_cpf(filter_input(INPUT_POST, 'soc3_cpf'));
        $empresa->setSoc3_entrada(filter_input(INPUT_POST, 'soc3_entrada'));
        $empresa->setSoc3_email(filter_input(INPUT_POST, 'soc3_email'));
        $empresa->setSoc3_tel(filter_input(INPUT_POST, 'soc3_tel'));
        $empresa->setSoc3_cel(filter_input(INPUT_POST, 'soc3_cel'));
        $empresa->setSoc3_cep(filter_input(INPUT_POST, 'soc3_cep'));
        $empresa->setSoc3_ende(filter_input(INPUT_POST, 'soc3_ende'));
        $empresa->setSoc3_nume(filter_input(INPUT_POST, 'soc3_nume'));
        $empresa->setSoc3_comp(filter_input(INPUT_POST, 'soc3_comp'));
        $empresa->setSoc3_bair(filter_input(INPUT_POST, 'soc3_bair'));
        $empresa->setSoc3_cid(filter_input(INPUT_POST, 'soc3_cid'));
        $empresa->setSoc3_uf(filter_input(INPUT_POST, 'soc3_uf'));
        $empresa->setSoc3_quali(filter_input(INPUT_POST, 'soc3_quali'));
        $empresa->setSoc3_ass(filter_input(INPUT_POST, 'soc3_ass'));
        $empresa->setSoc3_capit(filter_input(INPUT_POST, 'soc3_capit'));
        $empresa->setSoc3_govbr(filter_input(INPUT_POST, 'soc3_govbr'));
        $empresa->setSoc3_qualif_govbr(filter_input(INPUT_POST, 'soc3_qualif_govbr'));
        
        $empresa->setEmail(filter_input(INPUT_POST, 'email'));
        $empresa->setUsuario(filter_input(INPUT_POST, 'usuario'));
        
        // Gerar nome da pasta para armazenamento de documentos
        $pasta = filter_input(INPUT_POST, "emp_code") . " - " . filter_input(INPUT_POST, "emp_name");
        $empresa->setPasta($pasta);
        
        return $empresa;
    }
    
    /**
     * Envia email de confirmação para empresa recém-cadastrada
     */
    private function enviarEmailConfirmacao($empresa) {
        require_once('../utils/EmailService.php');
        $emailService = new EmailService();
        
        $destinatario = $empresa->getEmail_empresa();
        $assunto = "Confirmação de Cadastro - Sistema Contabilidade Estrela 2.0";
        
        $conteudo = "
            <h2>Cadastro Realizado com Sucesso</h2>
            <p>Prezado(a) {$empresa->getName()},</p>
            <p>O cadastro da empresa <strong>{$empresa->getEmp_name()}</strong> (CNPJ: {$empresa->getEmp_cnpj()}) 
               foi realizado com sucesso em nosso sistema.</p>
            <p>Código da Empresa: {$empresa->getEmp_code()}</p>
            <p>Para acessar o sistema, utilize as credenciais enviadas anteriormente.</p>
            <p>Atenciosamente,<br>
            Equipe Contabilidade Estrela</p>
        ";
        
        return $emailService->enviarEmail($destinatario, $assunto, $conteudo);
    }
    
    /**
     * Envia email de confirmação para alteração de dados da empresa
     */
    private function enviarEmailAlteracao($empresa) {
        require_once('../utils/EmailService.php');
        $emailService = new EmailService();
        
        $destinatario = $empresa->getEmail_empresa();
        $assunto = "Confirmação de Alteração de Dados - Sistema Contabilidade Estrela 2.0";
        
        $conteudo = "
            <h2>Alteração de Dados Realizada</h2>
            <p>Prezado(a) {$empresa->getName()},</p>
            <p>Os dados da empresa <strong>{$empresa->getEmp_name()}</strong> (CNPJ: {$empresa->getEmp_cnpj()}) 
               foram atualizados em nosso sistema.</p>
            <p>Se você não reconhece esta alteração, entre em contato conosco imediatamente.</p>
            <p>Atenciosamente,<br>
            Equipe Contabilidade Estrela</p>
        ";
        
        return $emailService->enviarEmail($destinatario, $assunto, $conteudo);
    }

    private function obterEmpresa() {
        try {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            
            if (!$id) {
                // Resposta para erro de validação
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'ID da empresa não fornecido ou inválido.'
                ]);
                return;
            }
            
            $empresa = $this->empresaDAO->obterEmpresaParaJSON($id);
            
            if ($empresa) {
                // Registrar visualização no log
                $this->errorHandler->logAction(
                    'VISUALIZACAO', 
                    "Empresa visualizada: " . $empresa['emp_name'] . " (CNPJ: " . $empresa['emp_cnpj'] . ")", 
                    ['id' => $id, 'usuario' => isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'Não identificado']
                );
                
                // Formatar resposta como JSON
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'data' => $empresa
                ]);
            } else {
                // Resposta para empresa não encontrada
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Empresa não encontrada.'
                ]);
            }
            
        } catch (Exception $e) {
            // Resposta para erro do sistema
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Ocorreu um erro ao buscar a empresa.',
                'details' => $e->getMessage()
            ]);
        }
    }
}

// Instanciar e executar o controller
$controller = new EmpresasController();
$controller->processarAcao();