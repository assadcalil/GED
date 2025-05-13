/**
 * Sistema Contabilidade Estrela 2.0
 * Scripts para o sistema de newsletter
 */

/**
 * Função para enviar testes de email
 * @param {number} id - ID da newsletter
 * @param {string} email - Email para envio do teste
 * @returns {Promise} - Promessa com resultado do envio
 */
function sendTestEmail(id, email) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: '/GED2.0/controllers/newsletter_controller.php',
            method: 'POST',
            data: {
                acao: 'enviar_teste',
                id: id,
                email: email
            },
            success: function(response) {
                resolve(response);
            },
            error: function(error) {
                reject(error);
            }
        });
    });
}

/**
 * Função para confirmar exclusão de newsletter
 * @param {number} id - ID da newsletter 
 * @param {string} title - Título da newsletter
 */
function confirmDeleteNewsletter(id, title) {
    $('#deleteNewsletterId').val(id);
    $('#deleteNewsletterTitle').text(title);
    $('#deleteModal').modal('show');
}

/**
 * Função para confirmar exclusão de assinante
 * @param {number} id - ID do assinante
 * @param {string} name - Nome do assinante
 */
function confirmDeleteSubscriber(id, name) {
    $('#deleteSubscriberId').val(id);
    $('#deleteSubscriberName').text(name);
    $('#deleteSubscriberModal').modal('show');
}

/**
 * Função para configurar modal de envio de newsletter
 * @param {number} id - ID da newsletter
 * @param {string} title - Título da newsletter
 */
function setupSendModal(id, title) {
    $('#newsletterId').val(id);
    $('#newsletterTitle').text(title);
    $('#sendModal').modal('show');
}

/**
 * Função para preencher campos de edição de assinante
 * @param {Object} subscriber - Dados do assinante
 */
function fillSubscriberEditForm(subscriber) {
    $('#edit_id').val(subscriber.id);
    $('#edit_name').val(subscriber.name);
    $('#edit_email').val(subscriber.email);
    $('#edit_company_id').val(subscriber.company_id);
    $('#edit_status').val(subscriber.status);
    $('#editSubscriberModal').modal('show');
}

/**
 * Função para gerar pré-visualização de newsletter
 * @param {string} content - Conteúdo HTML da newsletter
 * @param {string} title - Título opcional para a preview
 * @returns {string} - HTML formatado para preview
 */
function generateNewsletterPreview(content, title = null) {
    return `
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>${title || 'Pré-visualização de Newsletter'}</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                margin: 0;
                padding: 0;
                background-color: #f5f5f5;
            }
            
            .container {
                max-width: 650px;
                margin: 0 auto;
                background: #ffffff;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            }
            
            .header {
                background-color: #0a4b78;
                color: white;
                padding: 20px;
                text-align: center;
            }
            
            .logo {
                max-width: 200px;
                margin: 0 auto;
            }
            
            .tax-alert {
                background-color: #ff7e00;
                color: white;
                padding: 15px;
                text-align: center;
                font-weight: 600;
            }
            
            .content {
                padding: 25px;
            }
            
            .footer {
                background-color: #222;
                color: #f1f1f1;
                padding: 25px;
                text-align: center;
                font-size: 13px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <!-- Cabeçalho -->
            <div class="header">
                <h2>Contabilidade Estrela</h2>
            </div>
            
            <!-- Alerta tributário IR -->
            <div class="tax-alert">
                <p>📢 ÚLTIMA CHANCE: Imposto de Renda 2025 - O prazo termina em 31 de maio! Entre em contato conosco para garantir sua declaração sem complicações.</p>
            </div>
            
            <!-- Conteúdo principal -->
            <div class="content">
                ${content}
            </div>
            
            <!-- Rodapé -->
            <div class="footer">
                <p><strong>Contabilidade Estrela</strong><br>
                Rua das Estrelas, 123 - Centro<br>
                São Paulo/SP - CEP 01234-567<br>
                Tel: (11) 1234-5678</p>
                
                <p>Newsletter enviada em ${new Date().toLocaleDateString('pt-BR')}</p>
            </div>
        </div>
    </body>
    </html>
    `;
}

/**
 * Função para atualizar preview em um iframe
 * @param {string} content - Conteúdo HTML da newsletter
 * @param {string} iframeId - ID do elemento iframe
 */
function updateIframePreview(content, iframeId) {
    const iframe = document.getElementById(iframeId);
    if (!iframe) return;
    
    const preview = generateNewsletterPreview(content);
    
    // Atualizar conteúdo do iframe
    iframe.contentWindow.document.open();
    iframe.contentWindow.document.write(preview);
    iframe.contentWindow.document.close();
}

/**
 * Função para configurar o editor de texto
 * @param {string} selector - Seletor do elemento textarea
 * @param {Object} options - Opções adicionais para o editor
 */
function setupSummernoteEditor(selector, options = {}) {
    // Opções padrão
    const defaultOptions = {
        height: 300,
        minHeight: 200,
        placeholder: 'Escreva aqui o conteúdo da sua newsletter...',
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ]
    };
    
    // Mesclar com opções personalizadas
    const editorOptions = {...defaultOptions, ...options};
    
    // Inicializar editor
    $(selector).summernote(editorOptions);
}

/**
 * Função para configurar o seletor de data/hora
 * @param {string} elementId - ID do elemento 
 */
function setupDateTimePicker(elementId) {
    const picker = new tempusDominus.TempusDominus(document.getElementById(elementId), {
        localization: {
            locale: 'pt-br',
            format: 'dd/MM/yyyy HH:mm'
        },
        display: {
            icons: {
                time: 'fas fa-clock',
                date: 'fas fa-calendar',
                up: 'fas fa-arrow-up',
                down: 'fas fa-arrow-down',
                previous: 'fas fa-chevron-left',
                next: 'fas fa-chevron-right',
                today: 'fas fa-calendar-check',
                clear: 'fas fa-trash',
                close: 'fas fa-times'
            },
            buttons: {
                today: true,
                clear: true,
                close: true
            },
            viewMode: 'calendar',
            components: {
                calendar: true,
                date: true,
                month: true,
                year: true,
                decades: true,
                clock: true,
                hours: true,
                minutes: true,
                seconds: false
            }
        }
    });
    
    return picker;
}

// Inicialização quando o documento estiver pronto
$(document).ready(function() {
    // Inicializar tooltips do Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Configuração do plugin DataTables (se estiver disponível)
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
            },
            responsive: true
        });
    }
});