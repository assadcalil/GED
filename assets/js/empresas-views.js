// /GED2.0/assets/js/empresas-views.js

// Funções auxiliares de formatação
function formatarCnpj(cnpj) {
    if (!cnpj) return '-';
    cnpj = cnpj.replace(/[^\d]/g, '');
    if (cnpj.length != 14) return cnpj;
    
    return cnpj.slice(0, 2) + '.' + 
           cnpj.slice(2, 5) + '.' + 
           cnpj.slice(5, 8) + '/' + 
           cnpj.slice(8, 12) + '-' + 
           cnpj.slice(12);
}

function formatarCpf(cpf) {
    if (!cpf) return '-';
    cpf = cpf.replace(/[^\d]/g, '');
    if (cpf.length != 11) return cpf;
    
    return cpf.slice(0, 3) + '.' + 
           cpf.slice(3, 6) + '.' + 
           cpf.slice(6, 9) + '-' + 
           cpf.slice(9);
}

function getSituacaoCadastral(situacao) {
    if (!situacao) return '-';
    const situacoes = {
        'ATIVA': 'Ativa',
        'INATIVA': 'Inativa',
        'SUSPENSA': 'Suspensa',
        'CANCELADA': 'Cancelada',
        'RETIRADA': 'Retirada',
        'DISPENSADA': 'Dispensada',
        'PARADA': 'Parada'
    };
    
    return situacoes[situacao] || situacao;
}

function getTipoJuridico(tipo) {
    if (!tipo) return '-';
    const tipos = {
        'EI': 'Empresário Individual',
        'EIRELI': 'EIRELI',
        'LTDA': 'Ltda',
        'SA': 'S.A.',
        'SLU': 'SLU',
        'OUTROS': 'Outros'
    };
    
    return tipos[tipo] || tipo;
}

function formatarData(data) {
    if (!data) return '-';
    
    const date = new Date(data);
    return date.toLocaleDateString('pt-BR');
}

function formatarMoeda(valor) {
    if (!valor) return '-';
    
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valor);
}

function isCertificadoValido(data) {
    if (!data) return false;
    
    const dataVencimento = new Date(data);
    const hoje = new Date();
    return dataVencimento > hoje;
}

// Função principal para visualizar empresa
function visualizarEmpresa(empresaId) {
    // Mostra o modal
    $('#viewModal').modal('show');
    
    // Mostra o spinner e esconde o conteúdo
    $('#viewModal .spinner-border').parent().show();
    $('#empresa-detalhes').hide();
    
    // Faz a requisição AJAX para obter os dados da empresa
    $.ajax({
        url: '/GED2.0/views/empresas/views.php',
        method: 'GET',
        data: { id: empresaId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Esconde o spinner
                $('#viewModal .spinner-border').parent().hide();
                
                // Monta o HTML com os detalhes da empresa
                let detalhesHtml = `
                    <!-- Navegação por abas -->
                    <ul class="nav nav-tabs" id="empresaViewTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="dados-view-tab" data-bs-toggle="tab" data-bs-target="#dados-view" type="button" role="tab" aria-controls="dados-view" aria-selected="true">
                                <i class="fas fa-building me-2"></i>Dados da Empresa
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="endereco-view-tab" data-bs-toggle="tab" data-bs-target="#endereco-view" type="button" role="tab" aria-controls="endereco-view" aria-selected="false">
                                <i class="fas fa-map-marker-alt me-2"></i>Endereço
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="certificado-view-tab" data-bs-toggle="tab" data-bs-target="#certificado-view" type="button" role="tab" aria-controls="certificado-view" aria-selected="false">
                                <i class="fas fa-certificate me-2"></i>Certificados
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="socios-view-tab" data-bs-toggle="tab" data-bs-target="#socios-view" type="button" role="tab" aria-controls="socios-view" aria-selected="false">
                                <i class="fas fa-users me-2"></i>Sócios
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Conteúdo das abas -->
                    <div class="tab-content mt-3" id="empresaViewTabsContent">
                        <!-- Aba: Dados da Empresa -->
                        <div class="tab-pane fade show active" id="dados-view" role="tabpanel" aria-labelledby="dados-view-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-3">IINFORMAÇÕES BÁSICAS</h6>
                                    <p><strong>Código:</strong> ${response.data.emp_code}</p>
                                    <p><strong>Razão Social:</strong> ${response.data.emp_name}</p>
                                    <p><strong>CNPJ:</strong> ${formatarCnpj(response.data.emp_cnpj)}</p>
                                    <p><strong>Responsável:</strong> ${response.data.name}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-3">SITUAÇÃO E TIPO</h6>
                                    <p><strong>Situação:</strong> 
                                        <span class="badge status-badge status-${response.data.emp_sit_cad.toLowerCase()}">
                                            ${getSituacaoCadastral(response.data.emp_sit_cad)}
                                        </span>
                                    </p>
                                    <p><strong>Tipo Jurídico:</strong> ${getTipoJuridico(response.data.emp_tipo_jur)}</p>
                                    <p><strong>Porte:</strong> ${response.data.emp_porte}</p>
                                    <p><strong>Regime de Apuração:</strong> ${response.data.emp_reg_apu || '-'}</p>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-3">CONTATO</h6>
                                    <p><strong>Telefone:</strong> ${response.data.emp_tel || '-'}</p>
                                    <p><strong>E-mail:</strong> ${response.data.email_empresa || '-'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-3">INSCRIÇÕES</h6>
                                    <p><strong>Inscrição Estadual:</strong> ${response.data.emp_iest || '-'}</p>
                                    <p><strong>Inscrição Municipal:</strong> ${response.data.emp_imun || '-'}</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Aba: Endereço -->
                        <div class="tab-pane fade" id="endereco-view" role="tabpanel" aria-labelledby="endereco-view-tab">
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="fw-bold mb-3">ENDEREÇO DA EMPRESA</h6>
                                    <p><strong>CEP:</strong> ${response.data.emp_cep || '-'}</p>
                                    <p><strong>Logradouro:</strong> ${response.data.emp_ende || '-'}</p>
                                    <p><strong>Número:</strong> ${response.data.emp_nume || '-'}</p>
                                    <p><strong>Complemento:</strong> ${response.data.emp_comp || '-'}</p>
                                    <p><strong>Bairro:</strong> ${response.data.emp_bair || '-'}</p>
                                    <p><strong>Cidade:</strong> ${response.data.emp_cid || '-'}</p>
                                    <p><strong>Estado:</strong> ${response.data.emp_uf || '-'}</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Aba: Certificado Digital -->
                        <div class="tab-pane fade" id="certificado-view" role="tabpanel" aria-labelledby="certificado-view-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-3">CERTIFICADO DIGITAL</h6>
                                    <p><strong>Código de Acesso:</strong> ${response.data.emp_cod_ace || '-'}</p>
                                    <p><strong>Código de Prova:</strong> ${response.data.emp_cod_pre || '-'}</p>
                                    <p><strong>Senha PFE:</strong> ${response.data.senha_pfe ? '********' : '-'}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-3">VALIDADE</h6>
                                    <p><strong>Data de Validade:</strong> ${formatarData(response.data.emp_cer_dig_data) || '-'}</p>
                                    <p><strong>Status:</strong> 
                                        <span class="${isCertificadoValido(response.data.emp_cer_dig_data) ? 'text-success' : 'text-danger'}">
                                            ${isCertificadoValido(response.data.emp_cer_dig_data) ? 'Válido' : 'Vencido'}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Aba: Sócios -->
                        <div class="tab-pane fade" id="socios-view" role="tabpanel" aria-labelledby="socios-view-tab">
                            <div class="row">
                                <!-- Sócio 1 -->
                                ${response.data.soc1_name ? `
                                <div class="col-12 mb-4">
                                    <h6 class="fw-bold mb-3">SÓCIO 1</h6>
                                    <div class="card card-body bg-light">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Nome:</strong> ${response.data.soc1_name}</p>
                                                <p><strong>CPF:</strong> ${formatarCpf(response.data.soc1_cpf)}</p>
                                                <p><strong>E-mail:</strong> ${response.data.soc1_email || '-'}</p>
                                                <p><strong>Telefone:</strong> ${response.data.soc1_tel || '-'}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Entrada:</strong> ${formatarData(response.data.soc1_entrada) || '-'}</p>
                                                <p><strong>Qualificação:</strong> ${response.data.soc1_quali || '-'}</p>
                                                <p><strong>Capital Social:</strong> ${formatarMoeda(response.data.soc1_capit) || '-'}</p>
                                                <p><strong>Gov.BR:</strong> ${response.data.soc1_govbr || '-'}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <!-- Sócio 2 -->
                                ${response.data.soc2_name ? `
                                <div class="col-12 mb-4">
                                    <h6 class="fw-bold mb-3">SÓCIO 2</h6>
                                    <div class="card card-body bg-light">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Nome:</strong> ${response.data.soc2_name}</p>
                                                <p><strong>CPF:</strong> ${formatarCpf(response.data.soc2_cpf)}</p>
                                                <p><strong>E-mail:</strong> ${response.data.soc2_email || '-'}</p>
                                                <p><strong>Telefone:</strong> ${response.data.soc2_tel || '-'}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Entrada:</strong> ${formatarData(response.data.soc2_entrada) || '-'}</p>
                                                <p><strong>Qualificação:</strong> ${response.data.soc2_quali || '-'}</p>
                                                <p><strong>Capital Social:</strong> ${formatarMoeda(response.data.soc2_capit) || '-'}</p>
                                                <p><strong>Gov.BR:</strong> ${response.data.soc2_govbr || '-'}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                                
                                <!-- Sócio 3 -->
                                ${response.data.soc3_name ? `
                                <div class="col-12 mb-4">
                                    <h6 class="fw-bold mb-3">SÓCIO 3</h6>
                                    <div class="card card-body bg-light">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p><strong>Nome:</strong> ${response.data.soc3_name}</p>
                                                <p><strong>CPF:</strong> ${formatarCpf(response.data.soc3_cpf)}</p>
                                                <p><strong>E-mail:</strong> ${response.data.soc3_email || '-'}</p>
                                                <p><strong>Telefone:</strong> ${response.data.soc3_tel || '-'}</p>
                                            </div>
                                            <div class="col-md-6">
                                                <p><strong>Entrada:</strong> ${formatarData(response.data.soc3_entrada) || '-'}</p>
                                                <p><strong>Qualificação:</strong> ${response.data.soc3_quali || '-'}</p>
                                                <p><strong>Capital Social:</strong> ${formatarMoeda(response.data.soc3_capit) || '-'}</p>
                                                <p><strong>Gov.BR:</strong> ${response.data.soc3_govbr || '-'}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                                
                                ${!response.data.soc1_name ? '<p class="text-muted">Nenhum sócio cadastrado.</p>' : ''}
                            </div>
                        </div>
                    </div>
                `;
                
                // Insere o HTML no modal
                $('#empresa-detalhes').html(detalhesHtml).show();
                
                // Atualiza o link do botão editar
                $('#editar-empresa-btn').attr('href', `/GED2.0/views/empresas/edit.php?id=${empresaId}`);
            } else {
                // Mostra mensagem de erro
                $('#viewModal .spinner-border').parent().hide();
                $('#empresa-detalhes').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        ${response.message || 'Erro ao carregar informações da empresa.'}
                    </div>
                `).show();
            }
        },
        error: function(xhr, status, error) {
            console.error('Erro na requisição:', error);
            console.error('Status:', status);
            console.error('Resposta do servidor:', xhr.responseText);
            
            let errorMessage = 'Erro ao carregar informações da empresa. Por favor, tente novamente.';
            
            // Tenta analisar o erro específico
            try {
                if (xhr.responseText) {
                    const errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.message) {
                        errorMessage = errorResponse.message;
                    }
                }
            } catch (e) {
                console.error('Erro ao processar resposta:', e);
            }
            
            // Mostra mensagem de erro
            $('#viewModal .spinner-border').parent().hide();
            $('#empresa-detalhes').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    ${errorMessage}
                </div>
            `).show();
        }
    });
}

// Inicializa o evento de clique
$(document).ready(function() {
    // Evento de clique no botão de visualizar
    $('.view-empresa').click(function() {
        const empresaId = $(this).data('id');
        visualizarEmpresa(empresaId);
    });
});