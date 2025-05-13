<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Este é o template para o boleto da Caixa Econômica Federal com design moderno
?>

<!DOCTYPE HTML>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boleto - <?php echo htmlspecialchars($Row['nome']); ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- html2pdf library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <!-- Estilo personalizado -->
    <link rel="stylesheet" href="/GED2.0/assets/css/dashboard.css">
    <link rel="stylesheet" href="/GED2.0/assets/css/boleto.css">
    
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
                <div class="container-fluid py-3">
                    <!-- Cabeçalho da Página -->
                    <div class="page-header no-print">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="page-title">Boleto de Pagamento</h1>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
                                        <li class="breadcrumb-item"><a href="viewListagemImpostos.php">Imposto de Renda</a></li>
                                        <li class="breadcrumb-item"><a href="visualizador_boletos.php?id=<?php echo $id_imposto; ?>"><?php echo htmlspecialchars($Row['nome']); ?></a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Boleto</li>
                                    </ol>
                                </nav>
                            </div>
                            <div class="col-auto">
                                <a href="visualizador_boletos.php?id=<?php echo $id_imposto; ?>" class="btn btn-outline">
                                    <i class="fas fa-arrow-left"></i> Voltar
                                </a>
                                <button onclick="generatePDF()" class="btn btn-primary">
                                    <i class="fas fa-print"></i> Imprimir/PDF
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="boleto-container">
                        <!-- Cabeçalho do Boleto -->
                        <div class="boleto-header no-print">
                            <div class="boleto-title">
                                <h2>Boleto de Pagamento</h2>
                                <div class="status-badge">
                                    <i class="fas fa-clock"></i> Aguardando Pagamento
                                </div>
                            </div>
                            <div class="boleto-info">
                                <div class="info-item">
                                    <span class="info-label">Nome</span>
                                    <span class="info-value"><?php echo htmlspecialchars($Row['nome']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">CPF/CNPJ</span>
                                    <span class="info-value"><?php echo htmlspecialchars($Row['cpf']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Vencimento</span>
                                    <span class="info-value"><?php echo $data_venc; ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Valor</span>
                                    <span class="info-value">R$ <?php echo $valor_boleto; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Conteúdo do Boleto -->
                        <div class="boleto-content">
                            <!-- Container para as mensagens -->
                            <div class="mensagens-container no-print">
                                <?php
                                    // Verificar se o boleto precisa ser registrado
                                    if ($consulta['CONTROLE_NEGOCIAL']['COD_RETORNO'] == '1') {
                                        // Boleto não existe, registrar um novo
                                        $registro = $int->RegistraBoletoCaixa(
                                            $nosso_numero_integracao,
                                            $Row['vencimento'],
                                            str_replace(',', '.', $Row['valor2025']),
                                            $id_imposto,
                                            date('Y-m-d'),
                                            $Row['nome'],
                                            $Row['cpf']
                                        );

                                        // Verificar se o registro foi bem-sucedido
                                        if (isset($registro['INCLUI_BOLETO']['LINHA_DIGITAVEL']) && isset($registro['INCLUI_BOLETO']['CODIGO_BARRAS'])) {
                                            $linha_digitavel = $registro['INCLUI_BOLETO']['LINHA_DIGITAVEL'];
                                            $codigo_barras = $registro['INCLUI_BOLETO']['CODIGO_BARRAS'];
                                            $url_pdf = isset($registro['INCLUI_BOLETO']['URL']) ? $registro['INCLUI_BOLETO']['URL'] : null;

                                            // Formatar linha digitável para exibição
                                            $linha_digitavel_formatada = Mask('#####.##### #####.###### #####.###### # ##############', $linha_digitavel);
                                            $dadosboleto["linha_digitavel"] = $linha_digitavel_formatada;
                                            $dadosboleto["codigo_barras"] = $codigo_barras;
                                            
                                            // Inserir os dados na tabela impostos_boletos
                                            try {
                                                $stmt = $impostoDAO->runQuery("
                                                    INSERT INTO impostos_boletos (
                                                        imposto_id, 
                                                        codigo_barras, 
                                                        linha_digitavel, 
                                                        data_emissao, 
                                                        data_vencimento, 
                                                        valor, 
                                                        pdf_content, 
                                                        usuario_emissor, 
                                                        status, 
                                                        observacoes, 
                                                        created_at, 
                                                        updated_at
                                                    ) VALUES (
                                                        :imposto_id, 
                                                        :codigo_barras, 
                                                        :linha_digitavel, 
                                                        :data_emissao, 
                                                        :data_vencimento, 
                                                        :valor, 
                                                        :pdf_content, 
                                                        :usuario_emissor, 
                                                        :status, 
                                                        :observacoes,
                                                        NOW(),
                                                        NOW()
                                                    )
                                                ");

                                                $stmt->execute(array(
                                                    ":imposto_id" => $id_imposto,
                                                    ":codigo_barras" => $codigo_barras,
                                                    ":linha_digitavel" => $linha_digitavel,
                                                    ":data_emissao" => date('Y-m-d H:i:s'),
                                                    ":data_vencimento" => $Row['vencimento'],
                                                    ":valor" => str_replace(',', '.', $Row['valor2025']),
                                                    ":pdf_content" => $url_pdf,
                                                    ":usuario_emissor" => $usuario_atual,
                                                    ":status" => 5, // Status 5 = pendente
                                                    ":observacoes" => "Boleto gerado automaticamente - Imposto de Renda 2025"
                                                ));

                                                // Registrar no log
                                                Logger::activity('boleto', "Gerou boleto para cliente {$Row['nome']} (ID: $id_imposto)");
                                                
                                                // Marcar que o boleto foi registrado com sucesso
                                                $boleto_registrado = true;

                                                // Exibir mensagem de sucesso
                                                echo "<div class='alert alert-success' role='alert'>
                                                        <i class='fas fa-check-circle'></i>
                                                        <div>
                                                            <strong>Sucesso!</strong> Boleto gerado e registrado com sucesso!
                                                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>
                                                        </div>
                                                    </div>";

                                            } catch (Exception $e) {
                                                // Registrar erro no log
                                                Logger::activity('erro', "Erro ao inserir boleto no banco: " . $e->getMessage());
                                                
                                                // Exibir mensagem de erro
                                                echo "<div class='alert alert-warning' role='alert'>
                                                        <i class='fas fa-exclamation-triangle'></i>
                                                        <div>
                                                            <strong>Atenção!</strong> Boleto gerado, mas houve um erro ao registrá-lo no banco de dados: " . $e->getMessage() . "
                                                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>
                                                        </div>
                                                    </div>";
                                            }
                                        } else {
                                            // Erro ao registrar boleto na Caixa
                                            echo "<div class='alert alert-danger' role='alert'>
                                                    <i class='fas fa-times-circle'></i>
                                                    <div>
                                                        <strong>Erro!</strong> Falha ao registrar boleto na Caixa Econômica Federal.
                                                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>
                                                    </div>
                                                </div>";
                                            Logger::activity('erro', "Falha ao registrar boleto na Caixa para cliente {$Row['nome']} (ID: $id_imposto)");
                                        }
                                    } else {
                                        // Boleto já existe, obter dados existentes
                                        $linha_digitavel = $consulta['CONSULTA_BOLETO']['TITULO']['LINHA_DIGITAVEL'];
                                        $codigo_barras = $consulta['CONSULTA_BOLETO']['TITULO']['CODIGO_BARRAS'];
                                        $url_pdf = isset($consulta['CONSULTA_BOLETO']['TITULO']['URL']) ? $consulta['CONSULTA_BOLETO']['TITULO']['URL'] : null;

                                        // Formatar linha digitável para exibição
                                        $linha_digitavel_formatada = Mask('#####.##### #####.###### #####.###### # ##############', $linha_digitavel);
                                        $dadosboleto["linha_digitavel"] = $linha_digitavel_formatada;
                                        $dadosboleto["codigo_barras"] = $codigo_barras;

                                        // Verificar se o boleto já está registrado na base
                                        $stmt = $impostoDAO->runQuery("
                                            SELECT id FROM impostos_boletos 
                                            WHERE imposto_id = :imposto_id AND linha_digitavel = :linha_digitavel
                                        ");
                                        $stmt->execute(array(
                                            ":imposto_id" => $id_imposto,
                                            ":linha_digitavel" => $linha_digitavel
                                        ));
                                        $boleto_existente = $stmt->fetch(PDO::FETCH_ASSOC);

                                        // Se não estiver registrado, inserir
                                        if (!$boleto_existente) {
                                            try {
                                                $stmt = $impostoDAO->runQuery("
                                                    INSERT INTO impostos_boletos (
                                                        imposto_id, 
                                                        codigo_barras, 
                                                        linha_digitavel, 
                                                        data_emissao, 
                                                        data_vencimento, 
                                                        valor, 
                                                        pdf_content, 
                                                        usuario_emissor, 
                                                        status, 
                                                        observacoes, 
                                                        created_at, 
                                                        updated_at
                                                    ) VALUES (
                                                        :imposto_id, 
                                                        :codigo_barras, 
                                                        :linha_digitavel, 
                                                        :data_emissao, 
                                                        :data_vencimento, 
                                                        :valor, 
                                                        :pdf_content, 
                                                        :usuario_emissor, 
                                                        :status, 
                                                        :observacoes,
                                                        NOW(),
                                                        NOW()
                                                    )
                                                ");

                                                $stmt->execute(array(
                                                    ":imposto_id" => $id_imposto,
                                                    ":codigo_barras" => $codigo_barras,
                                                    ":linha_digitavel" => $linha_digitavel,
                                                    ":data_emissao" => date('Y-m-d H:i:s'),
                                                    ":data_vencimento" => $Row['vencimento'],
                                                    ":valor" => str_replace(',', '.', $Row['valor2025']),
                                                    ":pdf_content" => $url_pdf,
                                                    ":usuario_emissor" => $usuario_atual,
                                                    ":status" => 5, // Status 5 = pendente
                                                    ":observacoes" => "Boleto recuperado do sistema da Caixa - Imposto de Renda 2025"
                                                ));

                                                // Registrar no log
                                                Logger::activity('boleto', "Recuperou boleto existente para cliente {$Row['nome']} (ID: $id_imposto)");

                                                // Marcar que o boleto foi registrado com sucesso
                                                $boleto_registrado = true;
                                                
                                                // Exibir mensagem informativa
                                                echo "<div class='alert alert-info' role='alert'>
                                                        <i class='fas fa-info-circle'></i>
                                                        <div>
                                                            <strong>Informação!</strong> Boleto já existia na Caixa e foi registrado em nosso sistema.
                                                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>
                                                        </div>
                                                    </div>";

                                            } catch (Exception $e) {
                                                // Registrar erro no log
                                                Logger::activity('erro', "Erro ao inserir boleto existente no banco: " . $e->getMessage());
                                                
                                                // Exibir mensagem de erro
                                                echo "<div class='alert alert-warning' role='alert'>
                                                        <i class='fas fa-exclamation-triangle'></i>
                                                        <div>
                                                            <strong>Atenção!</strong> Boleto recuperado, mas houve um erro ao registrá-lo no banco de dados: " . $e->getMessage() . "
                                                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>
                                                        </div>
                                                    </div>";
                                            }
                                        } else {
                                            // Boleto já existe na base
                                            echo "<div class='alert alert-info' role='alert'>
                                                    <i class='fas fa-info-circle'></i>
                                                    <div>
                                                        <strong>Informação!</strong> Este boleto já está registrado em nosso sistema.
                                                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>
                                                    </div>
                                                </div>";
                                            $boleto_registrado = true;
                                        }
                                    }
                                    ?>
                            </div>
                                <div class="logo-center">
                                <center><img src="/GED2.0/assets/img/logo.png" width="20%" alt="GED Contabilidade"><BR>
                             IMPOSTO DE RENDA 2025</center>
                            </div>
                            <!-- Wrapper do boleto bancário -->
                            <div class="boleto-wrapper">
                                <!-- Informações do Imposto de Renda -->
                                <div class="imposto-info">
                                    <h3>Informações do Imposto de Renda 2025</h3>
                                    <div class="imposto-details">
                                        <div class="imposto-item">
                                            <span class="imposto-label">Cliente</span>
                                            <span class="imposto-value"><?php echo htmlspecialchars($Row['nome']); ?></span>
                                        </div>
                                        <div class="imposto-item">
                                            <span class="imposto-label">CPF</span>
                                            <span class="imposto-value"><?php echo htmlspecialchars($Row['cpf']); ?></span>
                                        </div>
                                        <div class="imposto-item">
                                            <span class="imposto-label">Código</span>
                                            <span class="imposto-value"><?php echo htmlspecialchars($Row['codigo']); ?></span>
                                        </div>
                                        <div class="imposto-item">
                                            <span class="imposto-label">Ano Base</span>
                                            <span class="imposto-value">2024</span>
                                        </div>
                                        <div class="imposto-item">
                                            <span class="imposto-label">Exercício</span>
                                            <span class="imposto-value">2025</span>
                                        </div>
                                        <div class="imposto-item">
                                            <span class="imposto-label">Taxa Bancária</span>
                                            <span class="imposto-value">R$ 0,00</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Ficha de Compensação -->
                                <div class="compensacao-section">
                                    <div class="bank-info">
                                        <div class="bank-logo">
                                            <img src="/GED2.0/assets/img/boleto/logocaixa.jpg" alt="Caixa Econômica Federal">
                                        </div>
                                        <div class="bank-code">
                                            <?php echo $dadosboleto["codigo_banco_com_dv"]?>
                                        </div>
                                        <div class="barcode-line" style="font-size: 10px; width: 50%;">
                                            <?php echo $dadosboleto["linha_digitavel"]?>
                                        </div>
                                    </div>
                                    
                                    <table class="info-table">
                                        <tr>
                                            <th width="60%">Local de Pagamento</th>
                                            <th width="40%">Vencimento</th>
                                        </tr>
                                        <tr>
                                            <td>Pagável em qualquer Banco até o vencimento</td>
                                            <td><?php echo ($data_venc != "") ? $dadosboleto["data_vencimento"] : "Contra Apresentação" ?></td>
                                        </tr>
                                    </table>
                                    
                                    <table class="info-table">
                                        <tr>
                                            <th width="60%">Cedente</th>
                                            <th width="40%">Agência/Código Cedente</th>
                                        </tr>
                                        <tr>
                                            <td><?php echo $dadosboleto["cedente"]?></td>
                                            <td><?php echo $dadosboleto["agencia_codigo"]?></td>
                                        </tr>
                                    </table>
                                    
                                    <table class="info-table">
                                        <tr>
                                            <th width="15%">Data do Documento</th>
                                            <th width="15%">Nº Documento</th>
                                            <th width="10%">Espécie Doc.</th>
                                            <th width="10%">Aceite</th>
                                            <th width="20%">Data Processamento</th>
                                            <th width="30%">Nosso Número</th>
                                        </tr>
                                        <tr>
                                            <td><?php echo $dadosboleto["data_documento"]?></td>
                                            <td><?php echo $dadosboleto["numero_documento"]?></td>
                                            <td><?php echo $dadosboleto["especie_doc"]?></td>
                                            <td><?php echo $dadosboleto["aceite"]?></td>
                                            <td><?php echo $dadosboleto["data_processamento"]?></td>
                                            <td><?php echo $dadosboleto["nosso_numero"]?></td>
                                        </tr>
                                    </table>
                                    
                                    <table class="info-table">
                                        <tr>
                                            <th width="15%">Uso do Banco</th>
                                            <th width="10%">Carteira</th>
                                            <th width="10%">Espécie</th>
                                            <th width="10%">Quantidade</th>
                                            <th width="55%">Valor Documento</th>
                                        </tr>
                                        <tr>
                                            <td></td>
                                            <td><?php echo $dadosboleto["carteira"]?></td>
                                            <td><?php echo $dadosboleto["especie"]?></td>
                                            <td><?php echo $dadosboleto["quantidade"]?></td>
                                            <td><strong>R$ <?php echo $dadosboleto["valor_boleto"]?></strong></td>
                                        </tr>
                                    </table>
                                    
                                    <div class="instrucoes">
                                        <h3>Instruções para o Pagamento</h3>
                                        <ul>
                                            <li><?php echo $dadosboleto["instrucoes1"]; ?></li>
                                            <li><?php echo $dadosboleto["instrucoes2"]; ?></li>
                                            <li><?php echo $dadosboleto["instrucoes3"]; ?></li>
                                            <li><?php echo $dadosboleto["instrucoes4"]; ?></li>
                                        </ul>
                                    </div>
                                    
                                    <table class="info-table">
                                        <tr>
                                            <th>Sacado</th>
                                        </tr>
                                        <tr>
                                            <td>
                                                <?php echo $dadosboleto["sacado"]?><br>
                                                <?php echo $dadosboleto["endereco1"]?><br>
                                                <?php echo $dadosboleto["endereco2"]?>
                                            </td>
                                        </tr>
                                    </table>
                                    <div class="barcode-container">
                                        <?php fbarcode($dadosboleto["codigo_barras"]); ?>
                                    </div>
                                    
                                    <div class="sac-info">
                                        SAC CAIXA: 0800 726 0101 (informações, reclamações, sugestões e elogios)<br>
                                        Para pessoas com deficiência auditiva ou de fala: 0800 726 2492<br>
                                        Ouvidoria: 0800 725 7474<br>
                                        caixa.gov.br
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ações do Boleto -->
                        <div class="boleto-actions no-print">
                            <div class="row">
                                <div class="col-md-6">
                                    <a href="visualizador_boletos.php?id=<?php echo $id_imposto; ?>" class="btn btn-outline">
                                        <i class="fas fa-arrow-left"></i> Voltar
                                    </a>
                                </div>
                                <div class="col-md-6 text-end">
                                    <?php if ($url_pdf): ?>
                                    <a href="<?php echo $url_pdf; ?>" class="btn btn-outline" target="_blank">
                                        <i class="fas fa-file-pdf"></i> PDF da Caixa
                                    </a>
                                    <?php endif; ?>
                                    
                                    <button onclick="generatePDF()" class="btn btn-primary">
                                        <i class="fas fa-print"></i> Imprimir/PDF
                                    </button>
                                    
                                    <?php if ($boleto_registrado): ?>
                                    <a href="emailBoleto.php?id=<?php echo $id_imposto; ?>" class="btn btn-success">
                                        <i class="fas fa-envelope"></i> Enviar por Email
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Rodapé -->
            <footer class="dashboard-footer no-print">
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
    
    <!-- Script personalizado -->
    <script src="/GED2.0/assets/js/dashboard.js"></script>
    
    <!-- JavaScript para geração de PDF -->
    <script>

    // Função para gerar o PDF em uma única página
    function generatePDF() {
        // Indicador de carregamento
        const loadingIndicator = document.createElement('div');
        loadingIndicator.innerHTML = '<div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.9); display: flex; justify-content: center; align-items: center; z-index: 9999;"><div style="text-align: center; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);"><i class="fas fa-spinner fa-spin fa-3x" style="color: var(--primary-color);"></i><p style="margin-top: 15px; font-weight: 500;">Gerando PDF, aguarde...</p></div></div>';
        document.body.appendChild(loadingIndicator);
        
        // Ocultar elementos que não devem aparecer no PDF
        const noPrintElements = document.querySelectorAll('.no-print');
        noPrintElements.forEach(el => {
            el.style.display = 'none';
        });
        
        // Exibir elementos que só devem aparecer no PDF
        const printOnlyElements = document.querySelectorAll('.print-only');
        printOnlyElements.forEach(el => {
            el.style.display = 'block';
        });
        
        // Encontrar elementos que precisam ser modificados para compactar o boleto
        
        // 1. Compactar linhas pontilhadas
        const linhasPontilhadas = document.querySelectorAll('.linha-pontilhada');
        linhasPontilhadas.forEach(linha => {
            linha.style.margin = '10px 0';
            linha.style.height = '1px';
        });
        
        // 2. Reduzir espaço entre as tabelas
        const tabelas = document.querySelectorAll('.info-table');
        tabelas.forEach(tabela => {
            tabela.style.marginBottom = '10px';
        });
        
        // 3. Reduzir o tamanho da fonte
        document.body.style.fontSize = '10px';
        
        // 4. Ajustar altura das células
        const celulas = document.querySelectorAll('td, th');
        celulas.forEach(celula => {
            celula.style.paddingTop = '3px';
            celula.style.paddingBottom = '3px';
            celula.style.height = 'auto';
        });
        
        // 5. Ajustar espaço das instruções
        const instrucoes = document.querySelectorAll('.instrucoes');
        instrucoes.forEach(el => {
            el.style.padding = '8px';
            el.style.marginBottom = '10px';
        });
        
        // 6. Reduzir altura do código de barras se existir
        const barcode = document.querySelector('.barcode');
        if (barcode) {
            barcode.style.height = '40px';
            barcode.style.maxHeight = '40px';
        }
        
        // Elemento que contém o conteúdo do boleto
        const element = document.querySelector('.boleto-container');
        
        // Configuração otimizada para gerar PDF em uma única página
        const opt = {
            margin: [5, 5, 5, 5], // Margens menores
            filename: 'boleto_<?php echo htmlspecialchars($Row["codigo"]); ?>.pdf',
            image: { type: 'jpeg', quality: 0.95 },
            html2canvas: { 
                scale: 1.5, // Escala ajustada para melhor qualidade
                useCORS: true, 
                logging: false,
                letterRendering: true
            },
            jsPDF: { 
                unit: 'mm', 
                format: 'a4', 
                orientation: 'portrait',
                compress: true,
                hotfixes: ["px_scaling"]
            },
            pagebreak: { mode: ['avoid-all', 'css', 'legacy'] } // Múltiplas estratégias para evitar quebras
        };
        
        // Gerar o PDF
        html2pdf().set(opt).from(element).save().then(() => {
            // Restaurar a visibilidade dos elementos
            noPrintElements.forEach(el => {
                el.style.display = '';
            });
            
            printOnlyElements.forEach(el => {
                el.style.display = 'none';
            });
            
            // Remover as modificações de estilo
            linhasPontilhadas.forEach(linha => {
                linha.style.margin = '';
                linha.style.height = '';
            });
            
            tabelas.forEach(tabela => {
                tabela.style.marginBottom = '';
            });
            
            document.body.style.fontSize = '';
            
            celulas.forEach(celula => {
                celula.style.paddingTop = '';
                celula.style.paddingBottom = '';
                celula.style.height = '';
            });
            
            instrucoes.forEach(el => {
                el.style.padding = '';
                el.style.marginBottom = '';
            });
            
            if (barcode) {
                barcode.style.height = '';
                barcode.style.maxHeight = '';
            }
            
            // Remover o indicador de carregamento
            document.body.removeChild(loadingIndicator);
        });
    }
    
    // Script para fechar alertas
    document.addEventListener('DOMContentLoaded', function() {
        const closeButtons = document.querySelectorAll('.btn-close');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const alert = this.closest('.alert');
                if (alert) {
                    alert.remove();
                }
            });
        });
    });
    </script>
</body>
</html>