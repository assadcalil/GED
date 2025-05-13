<?php
/**
 * Template para e-mail de notificação de cadastro de certificado digital
 * Sistema Contabilidade Estrela 2.0
 */

/**
 * Gera o HTML do e-mail de notificação de cadastro de certificado
 * 
 * @param array $dados Array com os dados do certificado
 * @return string HTML formatado para o e-mail
 */
function gerarTemplateEmailCertificado($dados) {
    // Extrair variáveis do array $dados
    extract($dados);
    
    // Preparar situação do certificado para exibição
    $situacaoTexto = '';
    switch ($certificado_situacao) {
        case 'VIGENTE':
            $situacaoTexto = 'Vigente';
            break;
        case 'VENCIDO':
            $situacaoTexto = 'Vencido';
            break;
        case 'PRESTES_A_VENCER':
            $situacaoTexto = 'Prestes a Vencer';
            break;
        case 'RENOVACAO_PENDENTE':
            $situacaoTexto = 'Renovação Pendente';
            break;
        default:
            $situacaoTexto = $certificado_situacao;
    }
    
    // Iniciar buffer de saída
    ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Novo Certificado Digital Cadastrado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px 5px 0 0;
            margin: -20px -20px 20px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 10px 20px;
            border-top: 1px solid #ddd;
            margin: 20px -20px -20px;
            border-radius: 0 0 5px 5px;
            font-size: 12px;
            text-align: center;
            color: #6c757d;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            width: 40%;
        }
        .alert {
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-weight: bold;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin-bottom: 10px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            text-align: center;
        }
        .logo {
            max-width: 150px;
            height: auto;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Novo Certificado Digital Cadastrado</h2>
        </div>
        
        <p>Um novo certificado digital foi cadastrado no sistema com sucesso.</p>
        
        <div class="alert alert-info">
            Certificado para empresa: <?php echo htmlspecialchars($emp_name); ?>
        </div>
        
        <h3>Detalhes do Certificado:</h3>
        <table>
            <tr>
                <th>ID do Certificado</th>
                <td><?php echo htmlspecialchars($certificado_id); ?></td>
            </tr>
            <tr>
                <th>Empresa</th>
                <td><?php echo htmlspecialchars($emp_name); ?></td>
            </tr>
            <tr>
                <th>CNPJ</th>
                <td><?php echo htmlspecialchars($emp_cnpj); ?></td>
            </tr>
            <tr>
                <th>Tipo do Certificado</th>
                <td><?php echo htmlspecialchars($tipo_certificado); ?></td>
            </tr>
            <tr>
                <th>Data de Emissão</th>
                <td><?php echo htmlspecialchars($certificado_emissao); ?></td>
            </tr>
            <tr>
                <th>Data de Validade</th>
                <td><?php echo htmlspecialchars($certificado_validade); ?></td>
            </tr>
            <tr>
                <th>Situação</th>
                <td><?php echo htmlspecialchars($situacaoTexto); ?></td>
            </tr>
            <?php if (!empty($certificado_responsavel)): ?>
            <tr>
                <th>Responsável</th>
                <td><?php echo htmlspecialchars($certificado_responsavel); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <th>Cadastrado por</th>
                <td><?php echo htmlspecialchars($usuario); ?></td>
            </tr>
            <tr>
                <th>Data do Cadastro</th>
                <td><?php echo htmlspecialchars($data_cadastro); ?></td>
            </tr>
        </table>
        
        <p>Para visualizar mais detalhes ou fazer alterações, acesse o sistema.</p>
        
        <a href="<?php echo htmlspecialchars($url_sistema); ?>" class="btn">Acessar o Sistema</a>
        
        <div class="footer">
            <p>Este é um e-mail automático enviado pelo Sistema Contabilidade Estrela 2.0.</p>
            <p>© <?php echo date('Y'); ?> Contabilidade Estrela - Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>
<?php
    // Retornar o conteúdo do buffer
    return ob_get_clean();
}
?>