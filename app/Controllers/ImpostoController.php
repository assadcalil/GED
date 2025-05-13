<?php
// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(dirname(__FILE__))));
}



$acao = $_POST["acao"] ?? '';

switch ($acao) {
    case 'cadastrar':
        cadastrarImposto();
        break;

    case 'alterar':
        alterarImposto();
        break;

    case 'remover':
        removerImposto();
        break;
        
    case 'atualizar_status':
        atualizarStatusBoleto();
        break;
        
    default:
        echo "Ação não reconhecida";
        break;
}

function cadastrarImposto() {
    require_once '../model/Imposto.php';
    require_once 'ROOT_DIR . '/app/Dao/ImpostoDao.php';
    require_once '../BancoDeDados/database.php';

    $db = new Database();
    $impostoDAO = new ImpostoDAO();

    // Filtrando os dados de entrada
    $codigo = filter_input(INPUT_POST, 'codigo', FILTER_SANITIZE_STRING);
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $cpf = filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_STRING);
    $cep = filter_input(INPUT_POST, 'cep', FILTER_SANITIZE_STRING);
    $ende = filter_input(INPUT_POST, 'ende', FILTER_SANITIZE_STRING);
    $num = filter_input(INPUT_POST, 'num', FILTER_SANITIZE_STRING);
    $comple = filter_input(INPUT_POST, 'comple', FILTER_SANITIZE_STRING);
    $bairro = filter_input(INPUT_POST, 'bairro', FILTER_SANITIZE_STRING);
    $cidade = filter_input(INPUT_POST, 'cidade', FILTER_SANITIZE_STRING);
    $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $tel = filter_input(INPUT_POST, 'tel', FILTER_SANITIZE_STRING);
    $cel = filter_input(INPUT_POST, 'cel', FILTER_SANITIZE_STRING);
    $valor2024 = filter_input(INPUT_POST, 'valor2024', FILTER_SANITIZE_STRING);
    $valor2025 = filter_input(INPUT_POST, 'valor2025', FILTER_SANITIZE_STRING);
    $vencimento = filter_input(INPUT_POST, 'vencimento', FILTER_SANITIZE_STRING);
    $status_boleto_2024 = filter_input(INPUT_POST, 'status_boleto_2024', FILTER_SANITIZE_STRING) ?? '0';
    $status_boleto_2025 = filter_input(INPUT_POST, 'status_boleto_2025', FILTER_SANITIZE_STRING) ?? '0';
    $usuario = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_STRING);

    $imposto = new Imposto();

    $imposto->setCodigo($codigo);
    $imposto->setNome($nome);
    $imposto->setCpf($cpf);
    $imposto->setCep($cep);
    $imposto->setEnde($ende);
    $imposto->setNum($num);
    $imposto->setComple($comple);
    $imposto->setBairro($bairro);
    $imposto->setCidade($cidade);
    $imposto->setEstado($estado);
    $imposto->setEmail($email);
    $imposto->setTel($tel);
    $imposto->setCel($cel);
    $imposto->setValor2024($valor2024);
    $imposto->setValor2025($valor2025);
    $imposto->setVencimento($vencimento);
    $imposto->setStatus_boleto_2024($status_boleto_2024);
    $imposto->setStatus_boleto_2025($status_boleto_2025);
    $imposto->setUsuario($usuario);

    try {
        $resultado = $impostoDAO->adicionarImposto($imposto);
        echo $resultado;
    } catch (Exception $e) {
        error_log("Erro ao cadastrar imposto: " . $e->getMessage());
        echo "1"; // Código de erro
    }
}

function alterarImposto() {
    require_once '../model/Imposto.php';
    require_once 'ROOT_DIR . '/app/Dao/ImpostoDao.php';
    require_once '../BancoDeDados/database.php';

    $db = new Database();
    $impostoDAO = new ImpostoDAO();

    // Filtrando os dados de entrada
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $codigo = filter_input(INPUT_POST, 'codigo', FILTER_SANITIZE_STRING);
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $cpf = filter_input(INPUT_POST, 'cpf', FILTER_SANITIZE_STRING);
    $cep = filter_input(INPUT_POST, 'cep', FILTER_SANITIZE_STRING);
    $ende = filter_input(INPUT_POST, 'ende', FILTER_SANITIZE_STRING);
    $num = filter_input(INPUT_POST, 'num', FILTER_SANITIZE_STRING);
    $comple = filter_input(INPUT_POST, 'comple', FILTER_SANITIZE_STRING);
    $bairro = filter_input(INPUT_POST, 'bairro', FILTER_SANITIZE_STRING);
    $cidade = filter_input(INPUT_POST, 'cidade', FILTER_SANITIZE_STRING);
    $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $tel = filter_input(INPUT_POST, 'tel', FILTER_SANITIZE_STRING);
    $cel = filter_input(INPUT_POST, 'cel', FILTER_SANITIZE_STRING);
    $valor2024 = filter_input(INPUT_POST, 'valor2024', FILTER_SANITIZE_STRING);
    $valor2025 = filter_input(INPUT_POST, 'valor2025', FILTER_SANITIZE_STRING);
    $vencimento = filter_input(INPUT_POST, 'vencimento', FILTER_SANITIZE_STRING);
    $status_boleto_2024 = filter_input(INPUT_POST, 'status_boleto_2024', FILTER_SANITIZE_STRING);
    $status_boleto_2025 = filter_input(INPUT_POST, 'status_boleto_2025', FILTER_SANITIZE_STRING);
    $usuario = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_STRING);

    $imposto = new Imposto();

    $imposto->setId($id);
    $imposto->setCodigo($codigo);
    $imposto->setNome($nome);
    $imposto->setCpf($cpf);
    $imposto->setCep($cep);
    $imposto->setEnde($ende);
    $imposto->setNum($num);
    $imposto->setComple($comple);
    $imposto->setBairro($bairro);
    $imposto->setCidade($cidade);
    $imposto->setEstado($estado);
    $imposto->setEmail($email);
    $imposto->setTel($tel);
    $imposto->setCel($cel);
    $imposto->setValor2024($valor2024);
    $imposto->setValor2025($valor2025);
    $imposto->setVencimento($vencimento);
    $imposto->setStatus_boleto_2024($status_boleto_2024);
    $imposto->setStatus_boleto_2025($status_boleto_2025);
    $imposto->setUsuario($usuario);

    try {
        $resultado = $impostoDAO->alterarImposto($imposto);
        echo $resultado;
    } catch (Exception $e) {
        error_log("Erro ao alterar imposto: " . $e->getMessage());
        echo "1"; // Código de erro
    }
}

function removerImposto() {
    require_once '../model/Imposto.php';
    require_once 'ROOT_DIR . '/app/Dao/ImpostoDao.php';
    require_once '../BancoDeDados/database.php';

    $db = new Database();
    $impostoDAO = new ImpostoDAO();

    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);

    $imposto = new Imposto();
    $imposto->setId($id);

    try {
        $resultado = $impostoDAO->removerImposto($imposto);
        echo $resultado;
    } catch (Exception $e) {
        error_log("Erro ao remover imposto: " . $e->getMessage());
        echo "1"; // Código de erro
    }
}

function atualizarStatusBoleto() {
    require_once '../model/Imposto.php';
    require_once 'ROOT_DIR . '/app/Dao/ImpostoDao.php';
    require_once '../BancoDeDados/database.php';

    $db = new Database();
    $impostoDAO = new ImpostoDAO();

    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $ano = filter_input(INPUT_POST, 'ano', FILTER_SANITIZE_STRING);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_NUMBER_INT);
    $data_pagamento = filter_input(INPUT_POST, 'data_pagamento', FILTER_SANITIZE_STRING) ?? null;

    try {
        $resultado = $impostoDAO->atualizarStatusBoleto($id, $ano, $status, $data_pagamento);
        echo $resultado;
    } catch (Exception $e) {
        error_log("Erro ao atualizar status do boleto: " . $e->getMessage());
        echo "1"; // Código de erro
    }
}