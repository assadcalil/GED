<?php
// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(dirname(__FILE__))));
}


include '../utilidades/verificar_sessao.php';

require_once '../BancoDeDados/database.php';
require_once("ROOT_DIR . '/app/Dao/ImpostoDao.php");

$impostoDAO = new ImpostoDAO();
$id_imposto = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$stmt = $impostoDAO->runQuery("SELECT * FROM impostos WHERE id=:id_imposto");
$stmt->execute(array(":id_imposto" => $id_imposto));
$Row = $stmt->fetch(PDO::FETCH_ASSOC);

// Mapear códigos de status para texto descritivo
$pagamento = array(
    '0' => '<span class="badge badge-info">BOLETO NÃO EMITIDO</span>',
    '1' => '<span class="badge badge-success">BOLETO PAGO</span>',
    '5' => '<span class="badge badge-danger"><span class="spinner-border spinner-border-sm"></span> BOLETO EMITIDO - ESPERANDO PAGAMENTO</span>',
    '6' => '<span class="badge badge-success">PAGAMENTO EM DINHEIRO</span>',
    '8' => '<span class="badge badge-secondary">CORTESIA</span>'
);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Contabilidade Estrela">
    <meta name="author" content="Thiago Calil Assad">

    <title>CONTABILIDADE ESTRELA</title>
    
    <!-- jQuery Mask-->
    <script src="../assets/vendor/jquery/jquery.mask.min.js" type="text/javascript"></script>
    <script src="../assets/vendor/jquery/jquery.mask.js" type="text/javascript"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            $('#formAlterarImposto').submit(function() {
                $.ajax({
                    url: "../controller/ControllerImposto.php",
                    type: "post",
                    data: $('#formAlterarImposto').serialize(),
                    beforeSend: function() {
                        $("#btnSalvar").html('Salvando...');
                        $("#btnSalvar").prop('disabled', true);
                    },
                    success: function(result) {
                        if (result == 0) {
                            $('#mensagem-sucesso').fadeIn(1000);
                            $("#btnSalvar").html('Salvo com Sucesso!');
                            setTimeout(function() {
                                $("#btnSalvar").html('Salvar');
                                $("#btnSalvar").prop('disabled', false);
                                $('#mensagem-sucesso').fadeOut(1000);
                                // Redirecionar após salvar
                                abreURL('viewListarImpostoUsuario.php', 'POST', 'exibe-conteudo');
                            }, 2000);
                        }
                        if (result == 1) {
                            $('#mensagem-erro-1').fadeIn(1000);
                            $("#btnSalvar").html('Salvar');
                            $("#btnSalvar").prop('disabled', false);
                            setTimeout(function() {
                                $('#mensagem-erro-1').fadeOut(1000);
                            }, 3000);
                        }
                    },
                    error: function() {
                        $('#mensagem-erro-1').fadeIn(1000);
                        $("#btnSalvar").html('Salvar');
                        $("#btnSalvar").prop('disabled', false);
                        setTimeout(function() {
                            $('#mensagem-erro-1').fadeOut(1000);
                        }, 3000);
                    }
                });
                return false;
            });
        });
    </script>

    <script type="text/javascript">
        $(document).ready(function() {
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
    
    <!-- SCRIPT CEP EMPRESA -->
    <script type="text/javascript">
        function limpa_formulario_cep() {
            //Limpa valores do formulário de cep.
            document.getElementById('ende').value = ("");
            document.getElementById('bairro').value = ("");
            document.getElementById('cidade').value = ("");
            document.getElementById('estado').value = ("");
        }

        function meu_callback(conteudo) {
            if (!("erro" in conteudo)) {
                //Atualiza os campos com os valores.
                document.getElementById('ende').value = (conteudo.logradouro);
                document.getElementById('bairro').value = (conteudo.bairro);
                document.getElementById('cidade').value = (conteudo.localidade);
                document.getElementById('estado').value = (conteudo.uf);
            } //end if.
            else {
                //CEP não Encontrado.
                limpa_formulario_cep();
                alert("CEP não encontrado.");
            }
        }

        function pesquisacep(valor) {
            //Nova variável "cep" somente com dígitos.
            var cep = valor.replace(/\D/g, '');

            //Verifica se campo cep possui valor informado.
            if (cep != "") {
                //Expressão regular para validar o CEP.
                var validacep = /^[0-9]{8}$/;

                //Valida o formato do CEP.
                if (validacep.test(cep)) {
                    //Preenche os campos com "..." enquanto consulta webservice.
                    document.getElementById('ende').value = "Localizando Endereço...";
                    document.getElementById('bairro').value = "Localizando Bairro...";
                    document.getElementById('cidade').value = "Localizando Cidade...";
                    document.getElementById('estado').value = "Localizando Estado...";

                    //Cria um elemento javascript.
                    var script = document.createElement('script');

                    //Sincroniza com o callback.
                    script.src = '//viacep.com.br/ws/' + cep + '/json/?callback=meu_callback';

                    //Insere script no documento e carrega o conteúdo.
                    document.body.appendChild(script);
                } //end if.
                else {
                    //cep é inválido.
                    limpa_formulario_cep();
                    alert("Formato de CEP inválido.");
                }
            } //end if.
            else {
                //cep sem valor, limpa formulário.
                limpa_formulario_cep();
            }
        };
    </script>

    <style>
        .status-badge {
            padding: 8px 12px;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 10px;
            display: inline-block;
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
                            <div class="card-header bg-primary text-white">
                                <h3 class="mb-0 text-center">Formulário para Alteração de Cliente - Imposto de Renda</h3>
                            </div>
                            
                            <!-- Status atual -->
                            <div class="text-center my-3">
                                <h4><b>STATUS BOLETO 2025</b></h4>
                                <div class="status-badge">
                                    <?= $pagamento[$Row['status_boleto_2025']] ?>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <form class="form" method="POST" id="formAlterarImposto" name="formAlterarImposto">
                                    <input type="hidden" name="acao" id="acao" value="alterar" />
                                    <input type="hidden" name="id" id="id" value="<?= $Row['id'] ?>" />
                                    
                                    <div id="mensagem-sucesso" class="alert alert-success alert-dismissible fade show" role="alert" style="display: none;">
                                        Cliente alterado com sucesso!
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    
                                    <div id="mensagem-erro-1" class="alert alert-danger alert-dismissible fade show" role="alert" style="display: none;">
                                        Não foi possível alterar o cliente.
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    
                                    <!-- Informações básicas -->
                                    <div class="form-row">
                                        <div class="form-group col-md-2">
                                            <label for="codigo">Código Empresa/Avulso</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg" name="estado" id="estado" required value="<?= $Row['estado'] ?>" />
                                        </div>
                                    </div>
                                    
                                    <!-- Contato -->
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="email">E-mail</label>
                                            <font color="red"> *</font>
                                            <input type="email" class="form-control form-control-lg" name="email" id="email" required value="<?= $Row['email'] ?>" />
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="tel">Telefone</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg telefone" name="tel" id="tel" required value="<?= $Row['tel'] ?>" />
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="cel">Celular</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg celular" name="cel" id="cel" required value="<?= $Row['cel'] ?>" />
                                        </div>
                                    </div>
                                    
                                    <!-- Valores e Pagamento -->
                                    <div class="form-row">
                                        <div class="form-group col-md-3">
                                            <label for="valor2024">Valor Declaração 2024</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg money" name="valor2024" id="valor2024" required value="<?= $Row['valor2024'] ?>" />
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="valor2025">Valor Declaração 2025</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg money" name="valor2025" id="valor2025" required value="<?= $Row['valor2025'] ?>" />
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="status_boleto_2025">Forma de Pagamento</label>
                                            <font color="red"> *</font>
                                            <select class="form-control form-control-lg" name="status_boleto_2025" id="status_boleto_2025">
                                                <option value="0" <?= $Row['status_boleto_2025'] == '0' ? 'selected' : '' ?>>Boleto</option>
                                                <option value="1" <?= $Row['status_boleto_2025'] == '1' ? 'selected' : '' ?>>Boleto Pago</option>
                                                <option value="5" <?= $Row['status_boleto_2025'] == '5' ? 'selected' : '' ?>>Boleto Emitido - Aguardando</option>
                                                <option value="6" <?= $Row['status_boleto_2025'] == '6' ? 'selected' : '' ?>>Dinheiro</option>
                                                <option value="8" <?= $Row['status_boleto_2025'] == '8' ? 'selected' : '' ?>>Cortesia</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="vencimento">Vencimento Boleto</label>
                                            <font color="red"> *</font>
                                            <input type="date" class="form-control form-control-lg" name="vencimento" id="vencimento" required value="<?= $Row['vencimento'] ?>" />
                                        </div>
                                    </div>
                                    
                                    <!-- Status do Boleto 2024 -->
                                    <div class="form-row">
                                        <div class="form-group col-md-3">
                                            <label for="status_boleto_2024">Status do Boleto 2024</label>
                                            <font color="red"> *</font>
                                            <select class="form-control form-control-lg" name="status_boleto_2024" id="status_boleto_2024">
                                                <option value="0" <?= $Row['status_boleto_2024'] == '0' ? 'selected' : '' ?>>Boleto Não Emitido</option>
                                                <option value="1" <?= $Row['status_boleto_2024'] == '1' ? 'selected' : '' ?>>Boleto Pago</option>
                                                <option value="5" <?= $Row['status_boleto_2024'] == '5' ? 'selected' : '' ?>>Boleto Emitido - Aguardando</option>
                                                <option value="6" <?= $Row['status_boleto_2024'] == '6' ? 'selected' : '' ?>>Dinheiro</option>
                                                <option value="8" <?= $Row['status_boleto_2024'] == '8' ? 'selected' : '' ?>>Cortesia</option>
                                            </select>
                                        </div>
                                        
                                        <!-- Campo oculto para o usuário -->
                                        <input type="hidden" name="usuario" id="usuario" value="<?= $Row['usuario'] ?>" />
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="col-12">
                                            <font color="red">* Campos Obrigatórios</font>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row mt-4">
                                        <div class="col-12 text-center">
                                            <button type="submit" class="btn btn-primary btn-lg" id="btnSalvar">Salvar</button>
                                            <button type="button" class="btn btn-secondary btn-lg ml-2" onclick="abreURL('viewListarImpostoUsuario.php', 'POST', 'exibe-conteudo')">Voltar</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <!--/card-block-->
                        </div>
                        <!-- /form card login -->
                    </div>
                </div>
                <!--/row-->
            </div>
            <!--/col-->
        </div>
        <!--/row-->
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

</html>" name="codigo" id="codigo" required value="<?= $Row['codigo'] ?>" />
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="nome">Nome</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg" name="nome" id="nome" required value="<?= $Row['nome'] ?>" />
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="cpf">CPF</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg cpf" name="cpf" id="cpf" required value="<?= $Row['cpf'] ?>" />
                                        </div>
                                    </div>
                                    
                                    <!-- Endereço -->
                                    <div class="form-row">
                                        <div class="form-group col-md-2">
                                            <label for="cep">CEP</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg cep" name="cep" id="cep" required onblur="pesquisacep(this.value);" value="<?= $Row['cep'] ?>" />
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="ende">Endereço</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg" name="ende" id="ende" required value="<?= $Row['ende'] ?>" />
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="num">Número</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg" name="num" id="num" required value="<?= $Row['num'] ?>" />
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="comple">Complemento</label>
                                            <input type="text" class="form-control form-control-lg" name="comple" id="comple" value="<?= $Row['comple'] ?>" />
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-5">
                                            <label for="bairro">Bairro</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg" name="bairro" id="bairro" required value="<?= $Row['bairro'] ?>" />
                                        </div>
                                        <div class="form-group col-md-5">
                                            <label for="cidade">Cidade</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg" name="cidade" id="cidade" required value="<?= $Row['cidade'] ?>" />
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="estado">Estado</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg