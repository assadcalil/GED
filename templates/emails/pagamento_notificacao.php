<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * Template de Email para Notificação de Pagamento
 * Arquivo: templates/email_pagamento_notificacao.php
 */
class EmailTemplatePagamentoNotificacao {
    
    /**
     * Gera o HTML para o email de notificação de pagamento
     * 
     * @param array $cliente Dados do cliente (código, nome, CPF)
     * @param array $payment Dados do pagamento (data, valor, motivo)
     * @param array $user Dados do usuário destinatário
     * @return string HTML do email
     */
    public static function gerarHTML($cliente, $payment, $user) {
        // Format values for email
        $valor_formatado = 'R$ ' . number_format($payment['valor'], 2, ',', '.');
        
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <title>Notificação de Pagamento</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #3498db; color: white; padding: 15px; text-align: center; }
                .content { padding: 20px; border: 1px solid #ddd; }
                .footer { font-size: 12px; color: #777; margin-top: 20px; text-align: center; }
                table { width: 100%; border-collapse: collapse; }
                table, th, td { border: 1px solid #ddd; }
                th, td { padding: 10px; text-align: left; }
                th { background-color: #f2f2f2; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Notificação de Pagamento</h2>
                </div>
                <div class="content">
                    <p>Olá ' . htmlspecialchars($user['name']) . ',</p>
                    <p>Informamos que o seguinte cliente efetuou o pagamento do boleto:</p>
                    
                    <table>
                        <tr>
                            <th>Código</th>
                            <td>' . htmlspecialchars($cliente['codigo']) . '</td>
                        </tr>
                        <tr>
                            <th>Nome</th>
                            <td>' . htmlspecialchars($cliente['nome']) . '</td>
                        </tr>
                        <tr>
                            <th>CPF</th>
                            <td>' . htmlspecialchars($cliente['cpf']) . '</td>
                        </tr>
                        <tr>
                            <th>Data do Pagamento</th>
                            <td>' . htmlspecialchars($payment['data_pagamento']) . '</td>
                        </tr>
                        <tr>
                            <th>Valor Pago</th>
                            <td>' . htmlspecialchars($valor_formatado) . '</td>
                        </tr>
                        <tr>
                            <th>Forma de Pagamento</th>
                            <td>' . htmlspecialchars($payment['motivo']) . '</td>
                        </tr>
                    </table>
                    
                    <p>Este email é uma notificação automática. O status do boleto já foi atualizado no sistema.</p>
                </div>
                <div class="footer">
                    <p>Sistema Contabilidade Estrela 2.0 &copy; ' . date('Y') . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
}
?>