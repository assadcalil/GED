<?php
/**
 * Menu para usuários do tipo TAX (Imposto de Renda)
 */

// Obter o caminho atual para marcar o item ativo
?>

<div class="sidebar-menu">
    <!-- Dashboard -->
    <div class="menu-item <?php echo isMenuActive('/dashboard', $currentPath) ? 'active' : ''; ?>">
        <a href="/GED2.0/views/dashboard/index.php">
            <i class="fas fa-tachometer-alt"></i>
            <span class="menu-text">Dashboard</span>
        </a>
    </div>
    
    <!-- Menu de Imposto de Renda (com apenas "Listar Imposto") -->
    <div class="menu-item <?php echo isMenuActive('/tax', $currentPath) ? 'active open' : ''; ?>">
        <a href="#" class="has-submenu">
            <i class="fas fa-file-invoice-dollar"></i>
            <span class="menu-text">Imp. de Renda</span>
            <i class="fas fa-chevron-right menu-arrow"></i>
        </a>
        <div class="submenu <?php echo isMenuActive('/tax', $currentPath) ? 'open' : ''; ?>">
            <a href="/GED2.0/views/tax/ListUsuario.php" class="<?php echo $currentPath == '/tax/ListUsuario.php' ? 'active' : ''; ?>">
                Listar Imposto
            </a>
        </div>
    </div>
    
    <!-- Menu de Documentos -->
    <div class="menu-item <?php echo isMenuActive('/documents', $currentPath) ? 'active open' : ''; ?>">
        <a href="#" class="has-submenu">
            <i class="fas fa-file-alt"></i>
            <span class="menu-text">Documentos</span>
            <i class="fas fa-chevron-right menu-arrow"></i>
        </a>
        <div class="submenu <?php echo isMenuActive('/documents', $currentPath) ? 'open' : ''; ?>">
            <a href="/GED2.0/views/documents/companies.php" class="<?php echo $currentPath == '/documents/companies.php' ? 'active' : ''; ?>">
                Listar Empresas
            </a>
            <a href="/GED2.0/views/documents/list.php" class="<?php echo $currentPath == '/documents/list.php' ? 'active' : ''; ?>">
                Todos os Documentos
            </a>
            <a href="/GED2.0/views/documents/upload.php" class="<?php echo $currentPath == '/documents/upload.php' ? 'active' : ''; ?>">
                Novo Upload
            </a>
            <a href="/GED2.0/views/documents/categories.php" class="<?php echo $currentPath == '/documents/categories.php' ? 'active' : ''; ?>">
                Categorias
            </a>
            <a href="/GED2.0/views/documents/pending.php" class="<?php echo $currentPath == '/documents/pending.php' ? 'active' : ''; ?>">
                Pendentes
            </a>
        </div>
    </div>
    
    <!-- Menu de Relatórios -->
    <div class="menu-item <?php echo isMenuActive('/reports', $currentPath) ? 'active open' : ''; ?>">
        <a href="#" class="has-submenu">
            <i class="fas fa-chart-bar"></i>
            <span class="menu-text">Relatórios</span>
            <i class="fas fa-chevron-right menu-arrow"></i>
        </a>
        <div class="submenu <?php echo isMenuActive('/reports', $currentPath) ? 'open' : ''; ?>">
            <a href="/GED2.0/views/reports/documents.php" class="<?php echo $currentPath == '/reports/documents.php' ? 'active' : ''; ?>">
                Documentos
            </a>
            <a href="/GED2.0/views/reports/access.php" class="<?php echo $currentPath == '/reports/access.php' ? 'active' : ''; ?>">
                Acessos
            </a>
            <a href="/GED2.0/views/reports/activities.php" class="<?php echo $currentPath == '/reports/activities.php' ? 'active' : ''; ?>">
                Atividades
            </a>
        </div>
    </div>
    
    <!-- Perfil -->
    <div class="menu-item <?php echo isMenuActive('/profile', $currentPath) ? 'active' : ''; ?>">
        <a href="/ged2.0/views/profile/profile.php">
            <i class="fas fa-user-circle"></i>
            <span class="menu-text">Meu Perfil</span>
        </a>
    </div>
    
    <!-- Sair -->
    <div class="menu-item">
        <a href="/?logout=1">
            <i class="fas fa-sign-out-alt"></i>
            <span class="menu-text">Sair</span>
        </a>
    </div>
</div>