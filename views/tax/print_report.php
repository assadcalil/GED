<?php

// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}

/**
 * Sistema Contabilidade Estrela 2.0
 * Relatório de Impressão - Retorno Bancário
 */

// Verificar se as configurações já foram incluídas
if (!defined('ROOT_DIR')) {
    require_once __DIR__ . '/../../../...../app/Config/App.php';
    require_once __DIR__ . '/../../../...../app/Config/Database.php';
    require_once __DIR__ . '/../../../...../app/Config/Auth.php';
    require_once __DIR__ . '/../../../...../app/Config/Logger.php';
    require_once __DIR__ . '/../../../...../app/Dao/ImpostoDao.php';
}

// Verificar autenticação e permissão
Auth::requireLogin();

// Apenas administradores podem acessar esta funcionalidade
if (!Auth::isAdmin()) {
    header('Location: /Ged2.0/views/errors/access-denied.php');
    exit;
}

// Inicializar variáveis
$impostoDAO = new ImpostoDAO();
$processedPayments = [];
$totalProcessed = 0;

// Configurações de paths
$retornoPath = ROOT_PATH . '/RetornoCaixa/';
$processedDataFile = $retornoPath . 'documentos_processados.json';

// Verificar se há dados para impressão no arquivo temporário ou na sessão
if (isset($_SESSION['last_processed_payments']) && isset($_SESSION['last_processed_total'])) {
    $processedPayments = $_SESSION['last_processed_payments'];
    $totalProcessed = $_SESSION['last_processed_total'];
} elseif (file_exists($processedDataFile)) {
    $jsonData = file_get_contents($processedDataFile);
    $data = json_decode($jsonData, true);
    if ($data && isset($data['payments']) && isset($data['total'])) {
        $processedPayments = $data['payments'];
        $totalProcessed = $data['total'];
    }
}

// Obter usuário atual
$currentUser = $_SESSION['user'] ?? $_SESSION['username'] ?? 'SISTEMA';
if (isset($_SESSION['nome_completo']) && !empty($_SESSION['nome_completo'])) {
    $currentUser = $_SESSION['nome_completo'];
}

// Se não houver dados para impressão, redirecionar
if (empty($processedPayments)) {
    header('Location: viewProcessarRetorno.php?erro=sem_dados');
    exit;
}

// Gerar relatório HTML
$html = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Pagamentos - Contabilidade Estrela</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 18px;
            margin: 0;
            font-weight: bold;
        }
        h2 {
            font-size: 15px;
            margin: 5px 0 15px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            font-size: 11px;
            text-align: right;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f8f8;
        }
        @media print {
            body {
                font-size: 11pt;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>';

// Adicionar cabeçalho
$html .= '
<div class="header">
    <h1>CONTABILIDADE ESTRELA</h1>
    <h2>Imposto de Renda 2025</h2>
</div>';

// Adicionar tabela
$html .= '
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Código</th>
            <th>Nome</th>
            <th>CPF</th>
            <th>Valor Pago</th>
            <th>Data Pagamento</th>
            <th>Data Ocorrência</th>
            <th>Data Crédito</th>
            <th>Usuário</th>
        </tr>
    </thead>
    <tbody>';

// Adicionar linhas da tabela e calcular o total
$totalAmount = 0;
foreach ($processedPayments as $payment) {
    $totalAmount += (float)$payment['valor'];
    
    $html .= '
        <tr>
            <td>' . htmlspecialchars($payment['id']) . '</td>
            <td>' . htmlspecialchars($payment['codigo']) . '</td>
            <td>' . htmlspecialchars($payment['nome']) . '</td>
            <td>' . htmlspecialchars($payment['cpf']) . '</td>
            <td>R$ ' . number_format((float)$payment['valor'], 2, '.', '') . '</td>
            <td>' . htmlspecialchars($payment['data_pagamento']) . '</td>
            <td>' . htmlspecialchars($payment['data_pagamento']) . '</td>
            <td>' . htmlspecialchars($payment['data_credito']) . '</td>
            <td>' . htmlspecialchars($_SESSION['user'] ?? $_SESSION['username'] ?? 'SISTEMA') . '</td>
        </tr>';
}

// Adicionar linha de total
$html .= '
    </tbody>
    <tfoot>
        <tr class="total-row">
            <td colspan="4" style="text-align: right;">TOTAL GERAL RECEBIDO:</td>
            <td colspan="5">R$ ' . number_format($totalAmount, 2, ',', '.') . '</td>
        </tr>
    </tfoot>
</table>';

// Adicionar rodapé
$processedDateTime = date('d/m/Y H:i');
$html .= '
<div class="footer">
    Processado por: Contabilidade Estrela - ' . htmlspecialchars($currentUser) . ' ' . $processedDateTime . '
</div>';

// Adicionar botão de impressão para versão na tela
$html .= '
<div class="no-print" style="text-align: center; margin-top: 20px;">
    <button onclick="window.print();" style="padding: 8px 16px; background-color: #4285f4; color: white; border: none; border-radius: 4px; cursor: pointer;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="vertical-align: text-bottom; margin-right: 5px;">
            <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
            <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
        </svg>
        Imprimir Relatório
    </button>
    <button onclick="window.location.href=\'viewProcessarRetorno.php\';" style="padding: 8px 16px; background-color: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px;">
        Voltar
    </button>
</div>';

// Adicionar script para impressão automática
$html .= '
<script>
    // Auto-print quando a página carregar completamente
    window.onload = function() {
        // Pequeno delay para garantir que a página seja renderizada corretamente
        setTimeout(function() {
            window.print();
        }, 500);
    };
</script>
</body>
</html>';

// Enviar o HTML para o navegador
echo $html;

// Registrar no log
Logger::activity('financeiro', "Imprimiu relatório de retorno bancário com {$totalAmount} pagamentos");