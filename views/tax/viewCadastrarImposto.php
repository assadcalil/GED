<?php
include '../utilidades/verificar_sessao.php';
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
            $('#formCadastroImposto').submit(function() {
                $.ajax({
                    url: "../controller/ControllerImposto.php",
                    type: "post",
                    data: $('#formCadastroImposto').serialize(),
                    beforeSend: function() {
                        $("#btnCadastrar").html('Cadastrando...');
                        $("#btnCadastrar").prop('disabled', true);
                    },
                    success: function(result) {
                        if (result == 0) {
                            $('#mensagem-sucesso').fadeIn(1000);
                            $("#btnCadastrar").html('Cadastrar');
                            $("#btnCadastrar").prop('disabled', false);
                            $('#formCadastroImposto').each(function() {
                                this.reset();
                            });
                            setTimeout(function() {
                                $('#mensagem-sucesso').fadeOut(1000);
                            }, 3000);
                        }
                        if (result == 1) {
                            $('#mensagem-erro-1').fadeIn(1000);
                            $("#btnCadastrar").html('Cadastrar');
                            $("#btnCadastrar").prop('disabled', false);
                            setTimeout(function() {
                                $('#mensagem-erro-1').fadeOut(1000);
                            }, 3000);
                        }
                    },
                    error: function() {
                        $('#mensagem-erro-1').fadeIn(1000);
                        $("#btnCadastrar").html('Cadastrar');
                        $("#btnCadastrar").prop('disabled', false);
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
</head>

<body style="background-color: #f8f8f8;">
    <br><br><br><br>
    <div class="container-fluid py-0" style="margin-top: -40px;">
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <!-- form card login -->
                        <div class="card shadow p-3 mb-5">
                            <div class="card-header bg-success text-white">
                                <h3 class="mb-0 text-center">Formulário para Cadastro de Clientes - Imposto de Renda</h3>
                            </div>
                            <div class="card-body">
                                <form class="form" id="formCadastroImposto" name="formCadastroImposto">
                                    <input type="hidden" name="acao" id="acao" value="cadastrar" />
                                    <input type="hidden" name="data" id="data" value="<?php echo date('Y/m/d h:i:s'); ?>" />
                                    
                                    <div id="mensagem-sucesso" class="alert alert-success alert-dismissible fade show" role="alert" style="display: none;">
                                        Cliente do Imposto de Renda cadastrado com sucesso!
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    
                                    <div id="mensagem-erro-1" class="alert alert-danger alert-dismissible fade show" role="alert" style="display: none;">
                                        Não foi possível cadastrar o cliente do Imposto de Renda.
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    
                                    <!-- Informações básicas -->
                                    <div class="form-row">
                                        <div class="form-group col-md-2">
                                            <label for="codigo">Código Empresa/Avulso</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg" name="codigo" id="codigo" required maxlength="6" />
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="nome">Nome</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg" name="nome" id="nome" required />
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="cpf">CPF</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg cpf" name="cpf" id="cpf" required />
                                        </div>
                                    </div>
                                    
                                    <!-- Endereço -->
                                    <div class="form-row">
                                        <div class="form-group col-md-2">
                                            <label for="cep">CEP</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg cep" name="cep" id="cep" required onblur="pesquisacep(this.value);" />
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="ende">Endereço</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg" name="ende" id="ende" required />
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="num">Número</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg" name="num" id="num" required />
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="comple">Complemento</label>
                                            <input type="text" class="form-control form-control-lg" name="comple" id="comple" />
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group col-md-5">
                                            <label for="bairro">Bairro</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg" name="bairro" id="bairro" required />
                                        </div>
                                        <div class="form-group col-md-5">
                                            <label for="cidade">Cidade</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg" name="cidade" id="cidade" required />
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="estado">Estado</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg" name="estado" id="estado" required />
                                        </div>
                                    </div>
                                    
                                    <!-- Contato -->
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="email">E-mail</label>
                                            <font color="red"> *</font>
                                            <input type="email" class="form-control form-control-lg" name="email" id="email" required />
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="tel">Telefone</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg telefone" name="tel" id="tel" required />
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="cel">Celular</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg celular" name="cel" id="cel" required />
                                        </div>
                                    </div>
                                    
                                    <!-- Valores e Pagamento -->
                                    <div class="form-row">
                                        <div class="form-group col-md-3">
                                            <label for="valor2024">Valor Cobrado 2024</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg money" name="valor2024" id="valor2024" required />
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="valor2025">Valor Cobrado 2025</label>
                                            <font color="red"> *</font>
                                            <input type="text" class="form-control form-control-lg money" name="valor2025" id="valor2025" required />
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="status_boleto_2025">Forma de Pagamento</label>
                                            <font color="red"> *</font>
                                            <select class="form-control form-control-lg" name="status_boleto_2025" id="status_boleto_2025">
                                                <option value="0">Boleto</option>
                                                <option value="6">Dinheiro</option>
                                                <option value="8">Cortesia</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="vencimento">Vencimento Boleto</label>
                                            <font color="red"> *</font>
                                            <input type="date" class="form-control form-control-lg" name="vencimento" id="vencimento" required />
                                        </div>
                                    </div>
                                    
                                    <!-- Campo oculto para o usuário -->
                                    <input type="hidden" name="usuario" id="usuario" value="<?php echo $_SESSION['user_session']; ?>" />
                                    
                                    <div class="form-row">
                                        <div class="col-12">
                                            <font color="red">* Campos Obrigatórios</font>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row mt-4">
                                        <div class="col-12 text-center">
                                            <button type="submit" class="btn btn-success btn-lg" id="btnCadastrar">Cadastrar</button>
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