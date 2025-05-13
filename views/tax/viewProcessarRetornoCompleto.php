<?php
// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(dirname(__FILE__))));
}


include '../utilidades/verificar_sessao.php';

require_once '../BancoDeDados/database.php';
require_once("ROOT_DIR . '/app/Dao/ImpostoDao.php");

$impostoDAO = new ImpostoDAO();
$xdata_processamento = date("Y/m/d");

// Variáveis de controle
$z = 0;
$total_itens = 0;
$total_itens_processados = 0;
$total_valor_nominal = 0;
$frase_motivo = "";
$bg_color = "";
$total_recebido = 0; // Inicializa total recebido aqui para uso global
$pagamentos_processados = []; // Array para armazenar todos os pagamentos processados

##########################################################################################################################################
// FUNCOES AUXILIARES - Mantidas como estavam
##########################################################################################################################################

function php_fnumber($var1)
{
    return number_format($var1, 2, ',', '.');
}

function datasql($data1)
{
    $data1 = substr($data1, 0, 2) . '/' . substr($data1, 2, 2) . '/' . substr($data1, 4, 4);
    if (!empty($data1)) {
        $p_dt = explode('/', $data1);
        $data_sql = $p_dt[2] . '-' . $p_dt[1] . '-' . $p_dt[0];
        return $data_sql;
    }
}

function datacx_databr($var1)
{
    // Converter uma string data brasileira em uma data brasileira com as barras
    // Entrada: DDMMAAAA / Saida: DD/MM/AAAA
    $j_dia = substr($var1, 0, 2);
    $j_mes = substr($var1, 2, 2);
    $j_ano = substr($var1, 4, 4);
    $j_dtf = $j_dia . "/" . $j_mes . "/" . $j_ano;
    return $j_dtf;
}

function remove_zero_esq($var1)
{
    $tam = strlen($var1);
    for ($i = 0; $i < $tam; $i++) {
        if (substr($var1, $i, 1) == "0") {
            $y = substr($var1, ($i + 1), ($tam));
        } else {
            return $y;
        }
    }
    return $y;
}

function numero_usa($var1)
{
    $tam  = strlen($var1);
    $ped1 = substr($var1, 0, ($tam - 2));
    $ped2 = substr($var1, -2);
    $num2 = $ped1 . "." . $ped2;
    if ($num2 == ".") {
        $num2 = "0.00";
    }
    return $num2;
}

function motivo_liquidacao($var1)
{
    $xfra = "";
    switch ($var1) {
        case "01": $xfra = " "; break;
        case "02": $xfra = "PG CASA <br>LOTERICA"; break;
        case "03": $xfra = "PG AGENCIA <br>CAIXA"; break;
        case "04": $xfra = "COMPENSACAO <br>ELETRONICA"; break;
        case "05": $xfra = "COMPENSACAO <br>CONVENCIONAL"; break;
        case "06": $xfra = "INTERNET <br>BANKING"; break;
        case "07": $xfra = "CORRESPONDENTE <br>BANCARIO"; break;
        case "08": $xfra = "EM CARTORIO"; break;
        case "61": $xfra = "PIX CAIXA"; break;
        case "62": $xfra = "PIX OUTROS BANCOS"; break;
        default: $xfra = "MOTIVO PG: " . $var1 . " <br>CONSULTAR MANUAL"; break;
    }
    return ($xfra);
}

function motivo_rejeicao($var1)
{
    $xfra = "";
    switch ($var1) {
        case "08": $xfra = "NOSSO NUMERO<br>INVALIDO"; break;
        case "09": $xfra = "NOSSO NUMERO<br>DUPLICADO"; break;
        case "48": $xfra = "CEP INVALIDO"; break;
        case "49": $xfra = "CEP SEM PRACA DE<br> COBRANCA (NAO LOCALIZADO)"; break;
        case "50": $xfra = "CEP REFERENTE A <br>UM BANCO CORRESPONDENTE"; break;
        case "51": $xfra = "CEP INCOMPATIVEL COM<br> A UNIDADE DA FEDERACAO"; break;
        case "52": $xfra = "UNIDADE DA FEDERACAO<br> INVALIDA"; break;
        case "87": $xfra = "NUMERO DA REMESSA<br> INVALIDO"; break;
        case "63": $xfra = "ENTRADA PARA TITULO<br> JA CADASTRADO"; break;
        case "16": $xfra = "DATA DE VENCIMENTO<br> INVALIDA"; break;
        case "10": $xfra = "CARTEIRA INVALIDA"; break;
        case "06": $xfra = "NUMERO INSCRICAO DO <br>BENEFICIARIO INVALIDO"; break;
        case "07": $xfra = "AG/CONTA/DV<br>INVALIDOS"; break;
        default: $xfra = "ERRO: " . $var1 . " "; break;
    }
    return ($xfra);
}

##########################################################################################################################################
// Controle de arquivos processados
##########################################################################################################################################

// 1. Configuração inicial
$logFile = '../RetornoCaixa/processed_files.log'; // Arquivo de log
$processedFiles = []; // Array para armazenar arquivos processados

// 2. Função para carregar arquivos processados
function loadProcessedFiles($logFile) {
    if (file_exists($logFile)) {
        $content = file_get_contents($logFile);
        return json_decode($content, true) ?: [];
    }
    return [];
}

// 3. Função para salvar arquivos processados
function saveProcessedFile($logFile, $filename, $fileContent) {
    $processed = loadProcessedFiles($logFile);
    
    // Criar hash do conteúdo para verificação única
    $fileHash = md5($fileContent);
    
    $processed[$filename] = [
        'processed_at' => date('Y-m-d H:i:s'),
        'file_hash' => $fileHash,
        'status' => 'processed'
    ];
    
    // Adicionar também pelo hash para verificação cruzada
    $processed[$fileHash] = $processed[$filename];
    
    file_put_contents($logFile, json_encode($processed, JSON_PRETTY_PRINT));
}

// 4. Função para verificar se o arquivo já foi processado
function isFileProcessed($logFile, $filename, $fileContent = null) {
    $processed = loadProcessedFiles($logFile);
    
    // Verifica pelo nome do arquivo
    if (isset($processed[$filename])) {
        return $processed[$filename];
    }
    
    // Verifica pelo hash do conteúdo (se fornecido)
    if ($fileContent) {
        $fileHash = md5($fileContent);
        if (isset($processed[$fileHash])) {
            return $processed[$fileHash];
        }
    }
    
    return false;
}

// 5. No início do processamento, verifique se o arquivo já foi processado
$fileContent = file_get_contents($_FILES['arquivo']['tmp_name']);
$processingInfo = isFileProcessed($logFile, $_FILES['arquivo']['name'], $fileContent);

if ($processingInfo) {
    // Arquivo já processado
    $processedTime = date('d/m/Y H:i:s', strtotime($processingInfo['processed_at']));
    
    echo "<div style='"
    . "padding: 15px;"
    . "margin: 20px 0;"
    . "border: 1px solid rgb(255, 72, 0);"
    . "background-color: rgb(231, 132, 18);"
    . "color: rgb(250, 253, 253);"
    . "border-radius: 4px;"
    . "'>";
    echo "<strong>ATENÇÃO:</strong> Este arquivo já foi processado anteriormente em <strong>{$processedTime}</strong>.";
    echo "<br>Nome do arquivo: <strong>" . htmlspecialchars($_FILES['arquivo']['name']) . "</strong>";
    
    if (isset($processingInfo['file_hash'])) {
        echo "<br>Identificador único: " . substr($processingInfo['file_hash'], 0, 8) . "...";
    }
    
    echo "</div>";
    
    // Remove o arquivo temporário
    unlink($_FILES['arquivo']['tmp_name']);
    exit;
}

##########################################################################################################################################
// Processamento do arquivo de retorno
##########################################################################################################################################

$nome = $_FILES['arquivo']['name'];
$type = $_FILES['arquivo']['type'];
$size = $_FILES['arquivo']['size'];
$tmp  = $_FILES['arquivo']['tmp_name'];

$b = 4;

$pasta = "update"; // NOME DA PASTA ONDE IRAO FICAR TODOS OS UPLOADS DE RETORNO

$_UP['pasta'] = '../RetornoCaixa/';
$_UP['tamanho'] = 1024 * 1024 * 5;
$_UP['extensoes'] = array('ret');

$_UP['erros'][0] = 'Não houve erro';
$_UP['erros'][1] = 'O arquivo no upload é maior do que o limite do PHP';
$_UP['erros'][2] = 'O arquivo ultrapassa o limite de tamanho especifiado no HTML';
$_UP['erros'][3] = 'O upload do arquivo foi feito parcialmente';
$_UP['erros'][4] = 'Não foi feito o upload do arquivo';

$nameRetorno = md5(date('Y-m-dH:i:s') . rand(10000, 99999)) . '.ret';

if (move_uploaded_file($tmp, $_UP['pasta'] . "/" . $nameRetorno)) {
    $lendo = @fopen($_UP['pasta'] . "/" . $nameRetorno, "r");

    if (!$lendo) {
        echo "Erro ao abrir a URL.";
        exit;
    }

    $i = 1;
    $x = 1;
    $cod_motivo = "  ";
    $dataHoraAtual = date('d/m/Y H:i');
    
    // Caminho do arquivo onde guardamos os documentos processados
    $arquivoProcessados = 'documentos_processados.json';
    
    // Lê os dados já processados
    $dadosProcessados = file_exists($arquivoProcessados) 
        ? json_decode(file_get_contents($arquivoProcessados), true) 
        : [];

    // Array para armazenar informações de todos os pagamentos encontrados no arquivo
    $pagamentos = [];

    while (!feof($lendo)) {
        $linha = fgets($lendo, 241);
        $rr = "<pre>" . $linha . "</pre>";
        $xtamanho_linha = strlen($linha);

        if ($xtamanho_linha == 240) {
            // Processa linha segmento T
            if ($i > 2 && substr($rr, $b + 14, 1) == "T" && substr($rr, $b + 16, 2) != 28) {
                $num_sequencial_t       = substr($rr, $b + 9, 5);
                $modalidade_nosso_numero = substr($rr, $b + 40, 2);
                $nosso_numero_caixa     = substr($rr, $b + 42, 15);
                $nosso_num              = substr($rr, $b + 43, 14);
                $nosso_numero_alex      = remove_zero_esq($nosso_num);
                $vencimento             = substr($rr, $b + 74, 8);
                $vm                     = substr($rr, $b + 82, 15);
                $valor_nominal          = numero_usa(remove_zero_esq($vm));
                $cod_movimento          = substr($rr, $b + 16, 2);

                switch ($cod_movimento) {
                    case 06:
                        $xfrase_movimento = "TITULO LIQUIDADO";
                        $bg_color = "#98FB98"; // verde
                        $cod_motivo_liquidacao = substr($rr, $b + 214, 10);
                        $cod_motivo = $cod_motivo_liquidacao;
                        $frase_motivo = motivo_liquidacao(substr(trim($cod_motivo_liquidacao), -2));
                        break;
                    case 02:
                        $xfrase_movimento = "REMESSA ENTRADA CONFIRMADA";
                        $bg_color = "#FFF"; // branco
                        break;
                    case 03:
                        $xfrase_movimento = "REMESSA ENTRADA REJEITADA";
                        $bg_color = "#FFC4C4"; // vermelho
                        $cod_motivo_rejeicao = substr($rr, $b + 214, 10);
                        $cod_motivo = $cod_motivo_rejeicao;
                        $frase_motivo = motivo_rejeicao(substr(trim($cod_motivo_rejeicao), -2));
                        break;
                    case 28:
                        $xfrase_movimento = "DEBITO DE TARIFAS/CUSTAS";
                        break;
                    case 27:
                        $xfrase_movimento = "CONFIRMACAO DO PEDIDO DE ALTERACAO OUTROS DADOS";
                        break;
                    case 30:
                        $xfrase_movimento = "ALTERACAO DE DADOS REJEITADA";
                        break;
                    case 45:
                        $xfrase_movimento = "ALTERACAO DE DADOS";
                        break;
                }
            }

            // Processa linha segmento U
            if ($i > 3 && substr($rr, $b + 14, 1) == "U" && substr($rr, $b + 16, 2) != 28) {
                $total_itens_processados++;

                $cod_movimento_u         = $cod_movimento;
                $num_sequencial_u        = substr($rr, $b + 9, 5);
                $jumu                    = substr($rr, $b + 18, 15);
                $juros_multa             = numero_usa(remove_zero_esq($jumu));
                $desco                   = substr($rr, $b + 33, 15);
                $desconto                = numero_usa(remove_zero_esq($desco));
                $vp                      = substr($rr, $b + 78, 15);
                $valor_pago              = numero_usa(remove_zero_esq($vp));
                $vl                      = substr($rr, $b + 93, 15);
                $valor_liquido           = numero_usa(remove_zero_esq($vl));
                $outdes                  = substr($rr, $b + 108, 15);
                $outras_despesas         = numero_usa(remove_zero_esq($outdes));
                $data_ocorrencia         = substr($rr, $b + 138, 8);
                $data_credito            = substr($rr, $b + 146, 8);
                $data_deb_tarifa         = substr($rr, $b + 158, 8);

                $array_retorno = [
                    'nosso_numero' => $nosso_numero_alex,
                    'cod_movimento' => $cod_movimento,
                    'data_pagamento_2025' => datacx_databr($data_ocorrencia),
                    'valor_pagamento_2025' => php_fnumber($valor_pago),
                    'nosso_numero1' => $nosso_numero_caixa,
                    'nosso_numero2' => $nosso_num,
                    'juros_multa' => $juros_multa,
                    'data_credito' => $data_credito,
                    'data_ocorrencia' => $data_ocorrencia,
                    'data_deb_tarifa' => $data_deb_tarifa
                ];

                if ($cod_movimento_u == "06") { // título liquidado (pago)
                    // Pegando o ID do imposto
                    $id_imposto = remove_zero_esq(substr($array_retorno['nosso_numero'], 4, 10));
                
                    // Consultando o banco de dados para pegar o nome e CPF
                    $stmt = $impostoDAO->runQuery("SELECT id, codigo, nome, cpf, usuario FROM impostos WHERE id=:id_imposto");
                    $stmt->execute(array(":id_imposto" => $id_imposto));
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                    if ($row) {
                        $id_imposto = $row['id'];
                
                        // Processa e grava no banco primeiro
                        $valor_banco1 = str_replace('.', '', $array_retorno['valor_pagamento_2025']);
                        $valor_banco = str_replace(',', '.', $valor_banco1);
                
                        // Acumula o valor para o total
                        $total_recebido += $valor_banco;
                
                        $explode_data = explode('/', $array_retorno['data_pagamento_2025']);
                        $data_banco = $explode_data['2'] . '-' . $explode_data['1'] . '-' . $explode_data['0'];
                
                        $stmt = $impostoDAO->runQuery("UPDATE impostos SET status_boleto_2025 = '1', data_pagamento_2025 = :data_banco, valor_pagamento_2025 = :valor_banco WHERE id = :id_imposto");
                        $stmt->execute(array(
                            ":data_banco" => $data_banco,
                            ":valor_banco" => $valor_banco,
                            ":id_imposto" => $id_imposto
                        ));
                
                        // Consulta para pegar os dados atualizados incluindo o valor
                        $stmt = $impostoDAO->runQuery("SELECT id, codigo, nome, cpf, usuario, valor_pagamento_2025 FROM impostos WHERE id=:id_imposto");
                        $stmt->execute(array(":id_imposto" => $id_imposto));
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                        // Verifica se o documento já foi processado
                        $documento_ja_processado = isset($dadosProcessados[$id_imposto]);
                
                        // Armazena todos os dados no JSON de forma organizada
                        $dadosProcessados[$id_imposto] = [
                            'data_processamento' => $dataHoraAtual,
                            'dia_processado' => date('d/m/Y'),
                            'id' => $row['id'],
                            'codigo' => $row['codigo'],
                            'nome' => $row['nome'],
                            'cpf' => $row['cpf'],
                            'valor' => $row['valor_pagamento_2025'],
                            'valor_formatado' => 'R$ ' . number_format($row['valor_pagamento_2025'], 2, ',', '.'),
                            'data_pagamento' => $array_retorno['data_pagamento_2025'],
                            'data_ocorrencia' => datacx_databr($array_retorno['data_ocorrencia']),
                            'data_credito' => datacx_databr($array_retorno['data_credito']),
                            'usuario' => $row['usuario'],
                            'funcionario' => 'Thiago Calil Assad',
                            'ja_processado' => $documento_ja_processado
                        ];
                        
                        // Adiciona ao array de pagamentos para exibição na tabela
                        $pagamentos[] = [
                            'id' => $row['id'],
                            'codigo' => $row['codigo'],
                            'nome' => $row['nome'],
                            'cpf' => $row['cpf'],
                            'valor' => $row['valor_pagamento_2025'],
                            'data_pagamento' => $array_retorno['data_pagamento_2025'],
                            'data_ocorrencia' => datacx_databr($array_retorno['data_ocorrencia']),
                            'data_credito' => datacx_databr($array_retorno['data_credito']),
                            'usuario' => $row['usuario'],
                            'ja_processado' => $documento_ja_processado
                        ];
                    } else {
                        // Pagamentos sem ID correspondente na tabela
                        $pagamentos[] = [
                            'id' => 'N/A',
                            'codigo' => 'N/A',
                            'nome' => 'ID não encontrado',
                            'cpf' => 'N/A',
                            'valor' => $array_retorno['valor_pagamento_2025'],
                            'data_pagamento' => $array_retorno['data_pagamento_2025'],
                            'data_ocorrencia' => datacx_databr($array_retorno['data_ocorrencia']),
                            'data_credito' => datacx_databr($array_retorno['data_credito']),
                            'usuario' => 'N/A',
                            'ja_processado' => false
                        ];
                    }
                }
            }
            $i++;
        }
    }
    
    // Fecha o arquivo
    fclose($lendo);
    
    // Salva os dados processados no arquivo JSON
    file_put_contents($arquivoProcessados, json_encode($dadosProcessados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Registra o arquivo como processado
    saveProcessedFile($logFile, $_FILES['arquivo']['name'], $fileContent);
    
    // Exibe o relatório único com todos os pagamentos
    exibirRelatorio($pagamentos, $total_recebido, $dataHoraAtual);
}

/**
 * Função para exibir o relatório formatado com todos os pagamentos
 */
function exibirRelatorio($pagamentos, $total_recebido, $dataHoraAtual) {
    // CSS para relatório
    echo "
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .cabecalho {
            text-align: center;
            padding: 20px;
            background-color: #f8f9fa;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .titulo {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .subtitulo {
            font-size: 18px;
        }
        .conteudo {
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .valor {
            text-align: right;
            font-weight: bold;
        }
        .ja-processado {
            background-color: #ffe0e0 !important;
            color: #d32f2f;
        }
        .total {
            text-align: right;
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            font-size: 18px;
        }
        .valor-total {
            font-weight: bold;
            color: #28a745;
        }
        .rodape {
            margin-top: 30px;
            padding: 15px;
            border-top: 1px solid #ddd;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
        }
        .alerta {
            padding: 10px;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            margin-bottom: 15px;
            border-radius: 4px;
        }
    </style>";

    // Cabeçalho do relatório
    echo "<div class='cabecalho'>
            <div class='titulo'>CONTABILIDADE ESTRELA</div>
            <div class='subtitulo'>Imposto de Renda 2025</div>
          </div>";

    echo "<div class='conteudo'>";
    
    // Verifica se há pagamentos
    if (empty($pagamentos)) {
        echo "<div class='alerta'>Nenhum pagamento encontrado no arquivo de retorno.</div>";
    } else {
        // Exibe a tabela com todos os pagamentos
        echo "<table>
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
                <tbody>";
        
        foreach ($pagamentos as $pagamento) {
            $classeRow = $pagamento['ja_processado'] ? 'ja-processado' : '';
            $alertaProcessado = $pagamento['ja_processado'] ? ' (JÁ FOI PROCESSADO ANTERIORMENTE)' : '';
            
            echo "<tr class='{$classeRow}'>
                    <td>{$pagamento['id']}</td>
                    <td>{$pagamento['codigo']}</td>
                    <td>{$pagamento['nome']}{$alertaProcessado}</td>
                    <td>{$pagamento['cpf']}</td>
                    <td class='valor'>R$ {$pagamento['valor']}</td>
                    <td>{$pagamento['data_pagamento']}</td>
                    <td>{$pagamento['data_ocorrencia']}</td>
                    <td>{$pagamento['data_credito']}</td>
                    <td>{$pagamento['usuario']}</td>
                  </tr>";
        }
        
        echo "</tbody>
              </table>";
        
        // Total recebido
        echo "<div class='total'>
                <span>TOTAL GERAL RECEBIDO: </span>
                <span class='valor-total'>R$ " . number_format($total_recebido, 2, ',', '.') . "</span>
              </div>";
    }
    
    // Rodapé
    echo "<div class='rodape'>
            <div>Processado por: Contabilidade Estrela - Thiago Calil Assad</div>
            <div>{$dataHoraAtual}</div>
          </div>";
    
    echo "</div>"; // Fecha div conteúdo
}
?>