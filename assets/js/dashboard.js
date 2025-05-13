/**
 * Sistema Contabilidade Estrela 2.0
 * Scripts do Dashboard
 */

// Executar quando o documento estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar DataTables
    initDataTables();
    
    // Inicializar Tooltips
    initTooltips();
    
    // Configurar tema com base no tipo de usuário
    setupUserTheme();
    
    // Configurar tratamento de formulários
    setupForms();
    
    // Configurar animações de elementos
    setupAnimations();
    
    // Configurar manipuladores de eventos
    setupEventHandlers();
});

/**
 * Inicializa as tabelas de dados
 */
function initDataTables() {
    // Verificar se existem tabelas que precisam ser inicializadas
    const dataTables = document.querySelectorAll('.datatable');
    
    if (dataTables.length > 0) {
        dataTables.forEach(table => {
            const options = {
                language: {
                    url: '/assets/js/dataTables.portuguese.json'
                },
                responsive: true,
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
                order: []
            };
            
            // Verificar configurações adicionais através de atributos data-*
            if (table.dataset.order) {
                try {
                    options.order = JSON.parse(table.dataset.order);
                } catch (e) {
                    console.error('Erro ao processar atributo data-order:', e);
                }
            }
            
            if (table.dataset.pageLength) {
                options.pageLength = parseInt(table.dataset.pageLength);
            }
            
            // Inicializar DataTable
            new DataTable(table, options);
        });
    }
}

/**
 * Inicializa tooltips para elementos com o atributo data-bs-toggle="tooltip"
 */
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Configura o tema com base no tipo de usuário
 */
function setupUserTheme() {
    // Verificar se há um tipo de usuário definido como data attribute no body
    const userType = document.body.dataset.userType;
    
    if (userType) {
        // Adicionar classe de tema com base no tipo de usuário
        switch (userType) {
            case '1': // Admin
                document.body.classList.add('theme-admin');
                break;
            case '2': // Editor
                document.body.classList.add('theme-editor');
                break;
            case '3': // Tax
                document.body.classList.add('theme-tax');
                break;
            case '4': // Employee
                document.body.classList.add('theme-employee');
                break;
            case '5': // Financial
                document.body.classList.add('theme-financial');
                break;
            case '6': // Client
                document.body.classList.add('theme-client');
                break;
            default:
                document.body.classList.add('theme-default');
        }
    }
}

/**
 * Configura o tratamento de formulários
 */
function setupForms() {
    // Validação de formulários Bootstrap
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Máscaras para campos de formulário
    setupInputMasks();
}

/**
 * Configura máscaras para inputs
 */
function setupInputMasks() {
    // CPF
    const cpfInputs = document.querySelectorAll('.mask-cpf');
    if (cpfInputs.length > 0) {
        cpfInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value;
                value = value.replace(/\D/g, '');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                e.target.value = value;
            });
        });
    }
    
    // CNPJ
    const cnpjInputs = document.querySelectorAll('.mask-cnpj');
    if (cnpjInputs.length > 0) {
        cnpjInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value;
                value = value.replace(/\D/g, '');
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
                e.target.value = value;
            });
        });
    }
    
    // Telefone
    const phoneInputs = document.querySelectorAll('.mask-phone');
    if (phoneInputs.length > 0) {
        phoneInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value;
                value = value.replace(/\D/g, '');
                
                if (value.length > 10) {
                    // Celular com DDD
                    value = value.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
                } else {
                    // Telefone fixo com DDD
                    value = value.replace(/^(\d{2})(\d{4})(\d{4})$/, '($1) $2-$3');
                }
                
                e.target.value = value;
            });
        });
    }
    
    // CEP
    const cepInputs = document.querySelectorAll('.mask-cep');
    if (cepInputs.length > 0) {
        cepInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value;
                value = value.replace(/\D/g, '');
                value = value.replace(/^(\d{5})(\d)/, '$1-$2');
                e.target.value = value;
            });
        });
    }
    
    // Data
    const dateInputs = document.querySelectorAll('.mask-date');
    if (dateInputs.length > 0) {
        dateInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value;
                value = value.replace(/\D/g, '');
                value = value.replace(/(\d{2})(\d)/, '$1/$2');
                value = value.replace(/(\d{2})(\d)/, '$1/$2');
                e.target.value = value;
            });
        });
    }
    
    // Moeda (R$)
    const currencyInputs = document.querySelectorAll('.mask-currency');
    if (currencyInputs.length > 0) {
        currencyInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value;
                value = value.replace(/\D/g, '');
                value = (parseInt(value) / 100).toFixed(2) + '';
                value = value.replace(".", ",");
                value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
                e.target.value = 'R$ ' + value;
            });
        });
    }
}

/**
 * Configura animações de elementos
 */
function setupAnimations() {
    // Adicionar classe de animação a elementos específicos
    const animatedElements = document.querySelectorAll('.animate-on-load');
    
    animatedElements.forEach((element, index) => {
        // Adicionar atraso com base no índice para efeito cascata
        setTimeout(() => {
            element.classList.add('fade-in');
        }, 100 * index);
    });
}

/**
 * Configura manipuladores de eventos para elementos interativos
 */
function setupEventHandlers() {
    // Manipuladores para botões de ação
    setupActionButtons();
    
    // Manipuladores para modais
    setupModals();
    
    // Manipuladores para cards expansíveis
    setupExpandableCards();
}

/**
 * Configura botões de ação (excluir, editar, etc.)
 */
function setupActionButtons() {
    // Botões de exclusão
    const deleteButtons = document.querySelectorAll('.btn-delete');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetUrl = this.getAttribute('href') || this.dataset.target;
            const itemName = this.dataset.itemName || 'este item';
            
            // Criar e mostrar modal de confirmação
            const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal') || createConfirmDeleteModal());
            
            // Configurar texto e ação
            document.getElementById('confirmDeleteItemName').textContent = itemName;
            document.getElementById('confirmDeleteButton').onclick = function() {
                window.location.href = targetUrl;
            };
            
            modal.show();
        });
    });
    
    // Botões de impressão
    const printButtons = document.querySelectorAll('.btn-print');
    
    printButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            window.print();
        });
    });
}

/**
 * Cria um modal de confirmação de exclusão se não existir
 */
function createConfirmDeleteModal() {
    const modalHtml = `
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir <strong id="confirmDeleteItemName">este item</strong>?</p>
                    <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteButton">Excluir</button>
                </div>
            </div>
        </div>
    </div>`;
    
    // Adicionar modal ao documento
    const div = document.createElement('div');
    div.innerHTML = modalHtml;
    document.body.appendChild(div.firstChild);
    
    return document.getElementById('confirmDeleteModal');
}

/**
 * Configura comportamento de modais
 */
function setupModals() {
    // Detectar dados dinâmicos para modais
    const dynamicModals = document.querySelectorAll('[data-bs-toggle="modal"][data-dynamic="true"]');
    
    dynamicModals.forEach(trigger => {
        trigger.addEventListener('click', function() {
            const target = document.querySelector(this.dataset.bsTarget);
            const url = this.dataset.url;
            const title = this.dataset.title;
            
            // Atualizar título se fornecido
            if (title && target.querySelector('.modal-title')) {
                target.querySelector('.modal-title').textContent = title;
            }
            
            // Carregar conteúdo via AJAX se URL fornecida
            if (url && target.querySelector('.modal-body')) {
                const modalBody = target.querySelector('.modal-body');
                modalBody.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div></div>';
                
                fetch(url)
                    .then(response => response.text())
                    .then(html => {
                        modalBody.innerHTML = html;
                        setupForms(); // Reinicializar validação/máscaras para o novo conteúdo
                    })
                    .catch(error => {
                        modalBody.innerHTML = `<div class="alert alert-danger">Erro ao carregar conteúdo: ${error.message}</div>`;
                    });
            }
        });
    });
}

/**
 * Configura cards expansíveis
 */
function setupExpandableCards() {
    const expandableCards = document.querySelectorAll('.card-expandable');
    
    expandableCards.forEach(card => {
        const header = card.querySelector('.card-header');
        const body = card.querySelector('.card-body');
        
        if (header && body) {
            header.style.cursor = 'pointer';
            
            // Adicionar ícone de expansão
            const icon = document.createElement('i');
            icon.className = 'fas fa-chevron-down ms-2';
            header.appendChild(icon);
            
            // Verificar se deve iniciar colapsado
            if (card.classList.contains('collapsed')) {
                body.style.display = 'none';
                icon.className = 'fas fa-chevron-right ms-2';
            }
            
            // Adicionar manipulador de clique
            header.addEventListener('click', function() {
                if (body.style.display === 'none') {
                    body.style.display = 'block';
                    card.classList.remove('collapsed');
                    icon.className = 'fas fa-chevron-down ms-2';
                } else {
                    body.style.display = 'none';
                    card.classList.add('collapsed');
                    icon.className = 'fas fa-chevron-right ms-2';
                }
            });
        }
    });
}

/**
 * Funções utilitárias
 */
const Utils = {
    /**
     * Formata um valor como moeda brasileira
     */
    formatCurrency: function(value) {
        return new Intl.NumberFormat('pt-BR', { 
            style: 'currency', 
            currency: 'BRL' 
        }).format(value);
    },
    
    /**
     * Formata uma data no padrão brasileiro
     */
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR');
    },
    
    /**
     * Formata data e hora no padrão brasileiro
     */
    formatDateTime: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR') + ' ' + 
               date.toLocaleTimeString('pt-BR');
    },
    
    /**
     * Exibe uma mensagem toast
     */
    showToast: function(message, type = 'success') {
        // Verificar se container de toasts existe, ou criar
        let toastContainer = document.querySelector('.toast-container');
        
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(toastContainer);
        }
        
        // Criar elemento toast
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        
        const randomId = 'toast-' + Math.floor(Math.random() * 1000000);
        toastEl.id = randomId;
        
        // Criar conteúdo do toast
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button>
            </div>
        `;
        
        // Adicionar ao container
        toastContainer.appendChild(toastEl);
        
        // Criar e mostrar toast
        const toast = new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 5000
        });
        
        toast.show();
        
        // Remover do DOM após ocultar
        toastEl.addEventListener('hidden.bs.toast', function() {
            toastEl.remove();
        });
    }
};