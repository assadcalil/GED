<?php

// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Função para segurança de dados
function sanitizeData($data) {
    if (is_array($data)) {
        return array_map('sanitizeData', $data);
    }
    
    // Converte para string, remove tags HTML e escapa caracteres especiais
    return htmlspecialchars(strval($data), ENT_QUOTES, 'UTF-8');
}

// Função para converter valor seguramente
function converterValor($valor) {
    // Remove qualquer caractere que não seja número, vírgula ou ponto
    $valor = preg_replace('/[^0-9,.]/', '', $valor);
    
    // Substitui vírgula por ponto se for formato brasileiro
    $valor = str_replace(',', '.', $valor);
    
    // Converte para float
    return floatval($valor);
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Sistema Contabilidade Estrela 2.0
 * Gerador de Boletos CEF com gravação na base
 */

// Verificar se as configurações já foram incluídas
if (!defined('ROOT_DIR')) {
    require_once __DIR__ . '/../../../...../app/Config/App.php';
    require_once __DIR__ . '/../../../...../app/Config/Database.php';
    require_once __DIR__ . '/../../../...../app/Config/Auth.php';
    require_once __DIR__ . '/../../../...../app/Config/Logger.php';
    require_once __DIR__ . '/../../../...../app/Dao/ImpostoDao.php';
}

// Verificar autenticação
Auth::requireLogin();

// Registrar acesso
Logger::activity('acesso', 'Geração de boleto CEF');

// Obter ID do imposto da URL
$id_imposto = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_imposto) {
    echo "<div class='alert alert-danger'>ID do imposto inválido</div>";
    exit;
}

// Buscar dados do imposto
$impostoDAO = new ImpostoDAO();
$stmt = $impostoDAO->runQuery("SELECT * FROM impostos WHERE id=:id_imposto");
$stmt->execute(array(":id_imposto" => $id_imposto));
$Row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$Row) {
    echo "<div class='alert alert-danger'>Imposto não encontrado</div>";
    exit;
}

// Sanitizar dados para uso seguro
$Row = sanitizeData($Row);

// Atualizar status do boleto para "Esperando Pagamento"
$stmt = $impostoDAO->runQuery("UPDATE impostos SET status_boleto_2025 = '5' WHERE id = :id_imposto");
$stmt->execute(array(":id_imposto" => $id_imposto));

// Configurações do boleto
$dias_de_prazo_para_pagamento = 5;
$taxa_boleto = 0.00;
$data_venc = date('d/m/Y', strtotime($Row['vencimento']));

// Conversão segura de valor
$valor_cobrado = converterValor($Row['valor2025']);
$valor_boleto = number_format($valor_cobrado + $taxa_boleto, 2, ',', '');

// Dados do boleto
$dadosboleto = [
    "inicio_nosso_numero" => "14",
    "nosso_numero" => ($id_imposto + 20250000),
    "numero_documento" => $Row['id'],
    "data_vencimento" => $data_venc,
    "data_documento" => date("d/m/Y"),
    "data_processamento" => date("d/m/Y"),
    "valor_boleto" => $valor_boleto
];

// Dados do cliente
$dadosboleto["sacado"] = $Row['nome'];
$dadosboleto["endereco1"] = "{$Row['ende']}, {$Row['num']}, {$Row['comple']}";
$dadosboleto["endereco2"] = "{$Row['cidade']} - {$Row['estado']} - CEP: {$Row['cep']}";

// Informações para o cliente
$dadosboleto["demonstrativo1"] = "PAGAMENTO REFERENTE A IRPF 2025";
$dadosboleto["demonstrativo2"] = "IMPOSTO DE RENDA PESSOA FISICA - ANO BASE 2024 - EXERCICIO 2025<br>TAXA BANCÁRIA - R$ " . number_format($taxa_boleto, 2, ',', '');
$dadosboleto["demonstrativo3"] = "{$Row['nome']} - {$Row['cpf']}<br>IMPOSTO DE RENDA FEITO PELO FUNCIONARIO: {$Row['usuario']}";

// Instruções para o caixa
$dadosboleto["instrucoes1"] = "- <b>SR. CAIXA, COBRAR MULTA DE 2% APÓS O VENCIMENTO</b>";
$dadosboleto["instrucoes2"] = "- Receber até 05 DIAS após o vencimento";
$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: cestrela@terra.com.br";
$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema da CONTABILIDADE ESTRELA";

// Dados opcionais
$dadosboleto["quantidade"] = "";
$dadosboleto["valor_unitario"] = "";
$dadosboleto["aceite"] = "";
$dadosboleto["especie"] = "R$";
$dadosboleto["especie_doc"] = "";

// Dados da conta CEF
$dadosboleto["agencia"] = "4105";
$dadosboleto["conta"] = "578739158";
$dadosboleto["conta_dv"] = "2";
$dadosboleto["conta_cedente"] = "720907";
$dadosboleto["conta_cedente_dv"] = "";
$dadosboleto["carteira"] = "CR";

// Dados do cedente
$dadosboleto["identificacao"] = "CONTABILIDADE ESTRELA LTDA";
$dadosboleto["cpf_cnpj"] = "02.032.391/0001-51";
$dadosboleto["endereco"] = "AVENIDA JULIO BUONO, Nº 2525, 2º ANDAR";
$dadosboleto["cidade_uf"] = "SÃO PAULO";
$dadosboleto["cedente"] = "CONTABILIDADE ESTRELA LTDA";

// Incluir funções específicas do boleto
include __DIR__ . "/../../boletophp/funcoesboletocef.php";

// Incluir API da Caixa
require_once __DIR__ . '/../../api_caixa/_validator/Validator.class.php';
require_once __DIR__ . '/../../api_caixa/_api/Integra.class.php';
$int = new Integra();
$nosso_numero_integracao = $dadosboleto["nosso_numero"];

// Função para formatar a linha digitável
function Mask($mask, $str) {
    // Converter para string e remover espaços
    $str = str_replace(" ", "", strval($str));
    
    // Garantir que a máscara e string sejam strings
    $mask = strval($mask);
    
    for ($i = 0; $i < strlen($str); $i++) {
        $pos = strpos($mask, "#");
        if ($pos === false) break;
        $mask[$pos] = $str[$i];
    }
    return $mask;
}

// Flag para controlar se o boleto foi registrado com sucesso
$boleto_registrado = false;
$url_pdf = null;

// Armazenar o código de retorno do boleto - crucial para o template
$codigo_retorno_boleto = '1'; // Valor padrão para "Boleto não existe"

// Consultar se o boleto já existe
try {
    // Chamar a API para consultar o boleto
    $consulta_result = $int->ConsultaBoletoCaixa($nosso_numero_integracao);
    
    // Verificar se é um array e tem a estrutura esperada
    if (is_array($consulta_result) && 
        isset($consulta_result['CONTROLE_NEGOCIAL']) && 
        isset($consulta_result['CONTROLE_NEGOCIAL']['COD_RETORNO'])) {
        
        // Extrair o código de retorno
        $codigo_retorno_boleto = $consulta_result['CONTROLE_NEGOCIAL']['COD_RETORNO'];
        
        // Armazenar o resultado completo para uso no template
        $consulta = $consulta_result;
    } else {
        // Criar uma estrutura padrão para o template usar
        $consulta = [
            'CONTROLE_NEGOCIAL' => [
                'COD_RETORNO' => $codigo_retorno_boleto
            ]
        ];
        
        // Log do erro
        Logger::activity('boleto_warning', 'Consulta retornou estrutura inválida: ' . 
                         (is_array($consulta_result) ? json_encode($consulta_result) : print_r($consulta_result, true)));
    }
} catch (Exception $e) {
    // Criar uma estrutura padrão para o template usar
    $consulta = [
        'CONTROLE_NEGOCIAL' => [
            'COD_RETORNO' => $codigo_retorno_boleto
        ]
    ];
    
    // Log do erro
    Logger::activity('boleto_erro', 'Erro ao consultar boleto: ' . $e->getMessage());
}

// Obter o nome do usuário atual da sessão - com fallback para evitar erro
if (isset($_SESSION['user'])) {
    $usuario_atual = $_SESSION['user'];
} elseif (isset($_SESSION['username'])) {
    $usuario_atual = $_SESSION['username'];
} elseif (isset($_SESSION['usuario'])) {
    $usuario_atual = $_SESSION['usuario'];
} elseif (isset($Row['usuario'])) {
    $usuario_atual = $Row['usuario'];
} else {
    $usuario_atual = 'Sistema';
}

// Definir uma variável que o template pode verificar para saber se o boleto existe
$boleto_precisa_registrar = ($codigo_retorno_boleto == '1');

// Incluir template de exibição
include __DIR__ . '/patch.php';
?>