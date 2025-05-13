$(document).ready(function() {
    // Visualização de detalhes do certificado
    $('.view-certificado').click(function() {
        const certificadoId = $(this).data('id');
        const viewModal = $('#viewModal');
        const detalhesContainer = $('#certificado-detalhes');
        const editarBtn = $('#editar-certificado-btn');

        // Limpar conteúdo anterior
        detalhesContainer.hide().html('');
        
        // Mostrar spinner de carregamento
        viewModal.find('.spinner-border').show();

        // Requisição AJAX para buscar detalhes
        $.ajax({
            url: '/ged2.0/controllers/ControllerEnviaEmailCertificado.php',
            method: 'GET',
            data: {
                acao: 'visualizar',
                id: certificadoId
            },
            dataType: 'json',
            success: function(response) {
                if (response.sucesso) {
                    // Construir HTML dos detalhes
                    const detalhesHtml = `
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Informações da Empresa</h5>
                                <p><strong>Código:</strong> ${response.dados.emp_code}</p>
                                <p><strong>Razão Social:</strong> ${response.dados.emp_name}</p>
                            </div>
                            <div class="col-md-6">
                                <h5>Detalhes do Certificado</h5>
                                <p><strong>Tipo:</strong> ${response.dados.certificado_tipo}</p>
                                <p><strong>Categoria:</strong> ${response.dados.certificado_categoria}</p>
                                <p><strong>Data de Emissão:</strong> ${response.dados.certificado_emissao_formatada}</p>
                                <p><strong>Data de Validade:</strong> ${response.dados.certificado_validade_formatada}</p>
                                <p><strong>Situação:</strong> 
                                    <span class="badge bg-${response.dados.situacao_cor}">
                                        ${response.dados.certificado_situacao_texto}
                                    </span>
                                </p>
                            </div>
                        </div>
                    `;

                    // Esconder spinner, mostrar detalhes
                    viewModal.find('.spinner-border').hide();
                    detalhesContainer.html(detalhesHtml).show();

                    // Configurar botão de edição
                    editarBtn.attr('href', `/ged2.0/views/certificados/edit.php?id=${certificadoId}`);
                } else {
                    // Tratamento de erro
                    detalhesContainer.html(`
                        <div class="alert alert-danger text-center">
                            ${response.mensagem || 'Erro ao carregar detalhes do certificado'}
                        </div>
                    `).show();
                    
                    viewModal.find('.spinner-border').hide();
                }
            },
            error: function() {
                // Erro na requisição
                detalhesContainer.html(`
                    <div class="alert alert-danger text-center">
                        Erro ao carregar os detalhes do certificado. Por favor, tente novamente.
                    </div>
                `).show();
                
                viewModal.find('.spinner-border').hide();
            }
        });

        // Abrir modal
        viewModal.modal('show');
    });
});