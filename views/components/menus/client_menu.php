<?php
/**
 * Menu para usuários do tipo CLIENT (Cliente)
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
    
    <!-- Menu de Documentos (limitado) -->
    <div class="menu-item <?php echo isMenuActive('/documents', $currentPath) ? 'active open' : ''; ?>">
        <a href="#" class="has-submenu">
            <i class="fas fa-file-alt"></i>
            <span class="menu-text">Documentos</span>
            <i class="fas fa-chevron-right menu-arrow"></i>
        </a>
        <div class="submenu <?php echo isMenuActive('/documents', $currentPath) ? 'open' : ''; ?>">
            <a href="/GED2.0/views/documents/list.php" class="<?php echo $currentPath == '/documents/list.php' ? 'active' : ''; ?>">
                Todos os Documentos
            </a>
            <a href="/GED2.0/views/documents/upload.php" class="<?php echo $currentPath == '/documents/upload.php' ? 'active' : ''; ?>">
                Novo Upload
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