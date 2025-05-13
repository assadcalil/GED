<?php
// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(dirname(__FILE__))));
}


include '../utilidades/verificar_sessao.php';

require_once '../BancoDeDados/database.php';
require_once 'ROOT_DIR . '/app/Dao/ImpostoDao.php';

$impostoDAO = new ImpostoDAO();
$id_imposto = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$stmt = $impostoDAO->runQuery("SELECT * FROM impostos WHERE id=:id_imposto");
$stmt->execute(array(":id_imposto" => $id_imposto));
$Row = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Contabilidade Estrela">
        <meta name="author" content="Thiago Calil Assad">

        <title>Formulário de remoção de cliente</title>

        <script type="text/javascript">
            $(document).ready(function () {
                $('#formRemoverImposto').submit(function () {
                    $.ajax({
                        url: "../controller/ControllerImposto.php",
                        type: "post",
                        data: $('#formRemoverImposto').serialize(),
                        beforeSend: function () {
                            $("#btnRemover").html('Removendo...');
                            $("#btnRemover").prop('disabled', true);
                        },
                        success: function (result) {
                            if (result == 0) {
                                $('#mensagem-sucesso').fadeIn(1000);
                                $("#btnRemover").html('Removido!');
                                setTimeout(function () {
                                    $("#btnRemover").html('Remover');
                                    $("#btnRemover").prop('disabled', false);
                                    // Redirecionar após remover
                                    abreURL('viewListarImpostoUsuario.php', 'POST', 'exibe-conteudo'); 
                                }, 2000);
                            }
                            if (result == 1) {
                                $('#mensagem-erro-1').fadeIn(1000);
                                $("#btnRemover").html('Remover');
                                $("#btnRemover").prop('disabled', false);
                                setTimeout(function () {
                                    $('#mensagem-erro-1').fadeOut(1000);
                                }, 3000);
                            }
                        },
                        error: function() {
                            $('#mensagem-erro-1').fadeIn(1000);
                            $("#btnRemover").html('Remover');
                            $("#btnRemover").prop('disabled', false);
                            setTimeout(function () {
                                $('#mensagem-erro-1').fadeOut(1000);
                            }, 3000);
                        }
                    });
                    return false;
                });
            });
        </script>
        
        <script type="text/javascript">
            $(document).ready(function () {
                $('.date').mask('00/00/0000');
                $('.time').mask('00:00:00');
                $('.date_time').mask('00/00/0000 00:00:00');
                $('.telefone').mask('(00) 0000-0000');
                $('.cep').mask('00000-000');
                $('.cnpjemp').mask('00.000.000/0000-00');
                $('.estaemp').mask('000.000.000.000');
                $('.cpf').mask('000.000.000-00');
                $('.codemp').mask('0000');
                $('.celular').mask('(00) 0.0000-0000');
                $('.money').mask('000.000.000.000.000,00', {reverse: true});
            });
        </script>
        
        <style>
            .alert-warning-custom {
                color: #856404;
                background-color: #fff3cd;
                border-color: #ffeeba;
                padding: 15px;
                margin-bottom: 20px;
                border: 1px solid transparent;
                border-radius: 5px;
            }
            
            .alert-title {
                font-size: 24px;
                margin-bottom: 10px;
                font-weight: bold;
            }
            
            .field-display {
                background-color: #f8f9fa;
                padding: 10px;
                border-radius: 5px;
                margin-bottom: 5px;
            }
        </style>
</head>
    <body style="background-color: #f8f8f8;">
        <br><br><br><br><br><br>
        <div class="container-fluid py-0" style="margin-top: -40px;">
            <div class="row">
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-10 mx-auto">
                            <!-- form card login -->
                            <div class="card shadow p-3 mb-5">
                                <div class="card-header bg-danger text-white">
                                    <h3 class="mb-0 text-center">Formulário para Remoção de Cliente - Imposto de Renda</h3>
                                </div>
                                <div class="card-body">
                                    <form class="form" id="formRemoverImposto" name="formRemoverImposto">
                                        <input type="hidden" name="acao" id="acao" value="remover"/>
                                        <input type="hidden" name="id" id="id" value="<?=$Row['id']?>"/>
                                        
                                        <div class="alert-warning-custom text-center mb-4">
                                            <div class="alert-title"><i class="fas fa-exclamation-triangle mr-2"></i>ATENÇÃO!</div>
                                            <h5>Deseja realmente remover este cliente? Esta ação não pode ser desfeita.</h5>
                                        </div>
                                        
                                        <div id="mensagem-sucesso" class="alert alert-success alert-dismissible fade show" role="alert" style="display: none;">
                                            Cliente removido com sucesso!
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        
                                        <div id="mensagem-erro-1" class="alert alert-danger alert-dismissible fade show" role="alert" style="display: none;">
                                            Não foi possível remover o cliente.
                                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                            
                                        <!-- Informações do cliente para confirmação -->
                                        <div class="row mb-4">
                                            <div class="col-md-12">
                                                <h5 class="border-bottom pb-2 mb-3">Informações do Cliente</h5>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Código:</label>
                                                    <div class="field-display"><?= $Row['codigo'] ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>CPF:</label>
                                                    <div class="field-display"><?= $Row['cpf'] ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Nome:</label>
                                                    <div class="field-display"><?= $Row['nome'] ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>E-mail:</label>
                                                    <div class="field-display"><?= $Row['email'] ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Telefone:</label>
                                                    <div class="field-display"><?= $Row['tel'] ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Celular:</label>
                                                    <div class="field-display"><?= $Row['cel'] ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Endereço:</label>
                                                    <div class="field-display">
                                                        <?= $Row['ende'] ?>, <?= $Row['num'] ?> 
                                                        <?= !empty($Row['comple']) ? ', ' . $Row['comple'] : '' ?> - 
                                                        <?= $Row['bairro'] ?>, <?= $Row['cidade'] ?>/<?= $Row['estado'] ?> - 
                                                        CEP: <?= $Row['cep'] ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Valor 2024:</label>
                                                    <div class="field-display">R$ <?= $Row['valor2024'] ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Valor 2025:</label>
                                                    <div class="field-display">R$ <?= $Row['valor2025'] ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Vencimento:</label>
                                                    <div class="field-display">
                                                        <?= date('d/m/Y', strtotime($Row['vencimento'])) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Funcionário Responsável:</label>
                                                    <div class="field-display"><?= $Row['usuario'] ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-row mt-4">
                                            <div class="col-12 text-center">
                                                <button type="submit" class="btn btn-danger btn-lg" id="btnRemover">Remover</button>
                                                <button type="button" class="btn btn-secondary btn-lg ml-2" onclick="abreURL('viewListarImpostoUsuario.php', 'POST', 'exibe-conteudo')">Cancelar</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <footer class="bg-dark text-white text-center fixed-bottom py-2">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <strong>CONTABILIDADE ESTRELA LTDA</strong><br>
                        © 2017-<?php echo date('Y'); ?><br>
                        <small>Versão 2.0.0</small>
                    </div>
                </div>
            </div>
        </footer>
    </body>
</html>