<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * Menu Lateral (Sidebar)
 */

// Obter o caminho atual para marcar o item ativo
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
$currentPath = strtok($currentPath, '?'); // Remover parâmetros de consulta

// Função para verificar se um item de menu está ativo
function isMenuActive($path, $currentPath) {
    if ($path == '/dashboard' && $currentPath == '/dashboard') {
        return true;
    }
    
    if ($path != '/dashboard' && strpos($currentPath, $path) === 0) {
        return true;
    }
    
    return false;
}

// Obter tipo de usuário para personalizar o menu
$userType = $_SESSION['user_type'] ?? 0;
?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo" style="text-align: center;">
            <img src="/GED2.0/assets/img/logo.png" alt="Logo Contabilidade Estrela">
        </div>
        <button class="sidebar-toggle" id="sidebar-toggle">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
    
    <?php
    // Menu para cada tipo de usuário
    switch ($userType) {
        case Auth::ADMIN:
            include_once(ROOT_DIR . '/views/components/menus/admin_menu.php');
            break;
        case Auth::EDITOR:
            include_once(ROOT_DIR . '/views/components/menus/editor_menu.php');
            break;
        case Auth::TAX:
            include_once(ROOT_DIR . '/views/components/menus/tax_menu.php');
            break;
        case Auth::EMPLOYEE:
            include_once(ROOT_DIR . '/views/components/menus/employee_menu.php');
            break;
        case Auth::FINANCIAL:
            include_once(ROOT_DIR . '/views/components/menus/financial_menu.php');
            break;
        case Auth::CLIENT:
            include_once(ROOT_DIR . '/views/components/menus/client_menu.php');
            break;
        default:
            // Se não reconhecer o tipo ou se houver erro, usar o menu padrão
            include_once(ROOT_DIR . '/views/components/menus/default_menu.php');
    }
    ?>
    
    <div class="sidebar-footer">
        GED Contabilidade Estrela &copy; <?php echo date('Y'); ?>
    </div>
</div>

<!-- JavaScript para controle do menu lateral -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle para colapsar/expandir a sidebar
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const sidebar = document.getElementById('sidebar');
        
        if (sidebarToggle && sidebar) {
            // Adicionar uma classe ao toggle para mantê-lo sempre visível
            sidebarToggle.classList.add('always-visible');
            
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                
                // Alternar ícone do botão
                const icon = this.querySelector('i');
                if (sidebar.classList.contains('collapsed')) {
                    icon.classList.remove('fa-chevron-left');
                    icon.classList.add('fa-chevron-right');
                } else {
                    icon.classList.remove('fa-chevron-right');
                    icon.classList.add('fa-chevron-left');
                }
                
                // Salvar estado no localStorage
                localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
            });
        }
        
        // Verificar estado salvo no localStorage
        const sidebarCollapsed = localStorage.getItem('sidebar_collapsed');
        if (sidebarCollapsed === 'true') {
            sidebar.classList.add('collapsed');
            const icon = sidebarToggle.querySelector('i');
            icon.classList.remove('fa-chevron-left');
            icon.classList.add('fa-chevron-right');
        }
        
         // Adicionar botão de restauração flutuante
         const body = document.body;
        const restoreButton = document.createElement('button');
        restoreButton.id = 'restore-sidebar';
        restoreButton.innerHTML = '<i class="fas fa-chevron-right"></i>';
        restoreButton.style.display = sidebar.classList.contains('collapsed') ? 'block' : 'none';
        restoreButton.addEventListener('click', function() {
            sidebar.classList.remove('collapsed');
            const icon = sidebarToggle.querySelector('i');
            icon.classList.remove('fa-chevron-right');
            icon.classList.add('fa-chevron-left');
            localStorage.setItem('sidebar_collapsed', 'false');
            this.style.display = 'none';
        });
        body.appendChild(restoreButton);
        
        // Atualizar visibilidade do botão de restauração quando o sidebar mudar
        sidebarToggle.addEventListener('click', function() {
            restoreButton.style.display = sidebar.classList.contains('collapsed') ? 'block' : 'none';
        });
        
        // Toggle para submenus
        const submenuToggle = document.querySelectorAll('.has-submenu');
        submenuToggle.forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const menuItem = this.closest('.menu-item');
                menuItem.classList.toggle('open');
                
                // Abrir/fechar submenu
                const submenu = this.nextElementSibling;
                if (menuItem.classList.contains('open')) {
                    submenu.style.maxHeight = submenu.scrollHeight + "px";
                } else {
                    submenu.style.maxHeight = "0px";
                }
            });
        });
        
        // Expandir submenu ativo
        const activeSubmenu = document.querySelector('.submenu.open');
        if (activeSubmenu) {
            activeSubmenu.style.maxHeight = activeSubmenu.scrollHeight + "px";
        }
        
        // Toggle para dispositivos móveis
        const mobileToggle = document.querySelector('.menu-toggle');
        if (mobileToggle) {
            mobileToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
        }
    });
</script>