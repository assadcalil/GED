/**
 * JavaScript para o Calendário de Obrigações Fiscais
 */
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips do Bootstrap
    var tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(function(tooltip) {
        new bootstrap.Tooltip(tooltip);
    });
    
    // Selecionar todos os dias com obrigações
    var daysWithObligations = document.querySelectorAll('.calendar-day.has-obligations');
    
    // Adicionar evento de clique para exibir o modal
    daysWithObligations.forEach(function(day) {
        day.addEventListener('click', function() {
            var dayNumber = this.getAttribute('data-day');
            var popupContent = document.getElementById('obligations-day-' + dayNumber).innerHTML;
            
            // Preencher e exibir o modal
            document.getElementById('obligationModalBody').innerHTML = popupContent;
            var modal = new bootstrap.Modal(document.getElementById('obligationModal'));
            modal.show();
        });
    });
    
    // Navegação do calendário
    var navButtons = document.querySelectorAll('.calendar-nav');
    navButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var month = this.getAttribute('data-month');
            var year = this.getAttribute('data-year');
            
            // Navegar para o mês selecionado (recarregar a página com parâmetros)
            window.location.href = window.location.pathname + '?month=' + month + '&year=' + year;
        });
    });
});