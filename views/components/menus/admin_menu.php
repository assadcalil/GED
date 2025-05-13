<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Menu para usuários do tipo ADMIN
 */


?>

<div class="sidebar-menu">
    <!-- Dashboard (comum a todos) -->
    <div class="menu-item <?php echo isMenuActive('/dashboard', $currentPath) ? 'active' : ''; ?>">
        <a href="/GED2.0/views/dashboard/index.php">
            <i class="fas fa-tachometer-alt"></i>
            <span class="menu-text">Dashboard</span>
        </a>
    </div>
    
    <!-- Menu de Usuários -->
    <div class="menu-item <?php echo isMenuActive('/users', $currentPath) ? 'active open' : ''; ?>">
        <a href="#" class="has-submenu">
            <i class="fas fa-users"></i>
            <span class="menu-text">Usuários</span>
            <i class="fas fa-chevron-right menu-arrow"></i>
        </a>
        <div class="submenu <?php echo isMenuActive('/users', $currentPath) ? 'open' : ''; ?>">
            <a href="/GED2.0/views/users/create.php" class="<?php echo $currentPath == '/users/create.php' ? 'active' : ''; ?>">
                Cadastro de Usuários
            </a>
            <a href="/GED2.0/views/users/list.php" class="<?php echo $currentPath == '/users/list.php' ? 'active' : ''; ?>">
                Listagem de Usuários
            </a>
            <a href="/GED2.0/views/clients/create.php" class="<?php echo $currentPath == '/clients/create.php' ? 'active' : ''; ?>">
                Cadastro de Clientes
            </a>
            <a href="/GED2.0/views/clients/list.php" class="<?php echo $currentPath == '/clients/list.php' ? 'active' : ''; ?>">
                Listagem de Clientes
            </a>
        </div>
    </div>
    
    <!-- Menu de Empresas -->
    <div class="menu-item <?php echo isMenuActive('/empresas', $currentPath) ? 'active open' : ''; ?>">
        <a href="#" class="has-submenu">
            <i class="fas fa-building"></i>
            <span class="menu-text">Empresas</span>
            <i class="fas fa-chevron-right menu-arrow"></i>
        </a>
        <div class="submenu <?php echo isMenuActive('/empresas', $currentPath) ? 'open' : ''; ?>">
            <a href="/GED2.0/views/empresas/create.php" class="<?php echo $currentPath == '/empresas/create.php' ? 'active' : ''; ?>">
                Cadastro
            </a>
            <a href="/GED2.0/views/empresas/list.php" class="<?php echo $currentPath == '/empresas/list.php' ? 'active' : ''; ?>">
                Listagem
            </a>
            <a href="/GED2.0/views/companies/upload.php" class="<?php echo $currentPath == '/companies/upload.php' ? 'active' : ''; ?>">
                Adicionar Arquivos
            </a>
            <a href="/GED2.0/views/companies/send.php" class="<?php echo $currentPath == '/companies/send.php' ? 'active' : ''; ?>">
                Enviar Arquivos
            </a>
        </div>
    </div>
    
    <!-- Menu de Certificados Digitais -->
    <div class="menu-item <?php echo isMenuActive('/certificates', $currentPath) ? 'active open' : ''; ?>">
        <a href="#" class="has-submenu">
            <i class="fas fa-certificate"></i>
            <span class="menu-text">Certif. Digitais</span>
            <i class="fas fa-chevron-right menu-arrow"></i>
        </a>
        <div class="submenu <?php echo isMenuActive('/certificates', $currentPath) ? 'open' : ''; ?>">
            <a href="/GED2.0/views/certificates/list.php" class="<?php echo $currentPath == '/certificates/list.php' ? 'active' : ''; ?>">
                Listar Certificados
            </a>
            <a href="/GED2.0/views/certificates/upload.php" class="<?php echo $currentPath == '/certificates/upload.php' ? 'active' : ''; ?>">
                Enviar Certificados
            </a>
        </div>
    </div>
    
    <!-- Menu de Imposto de Renda -->
    <div class="menu-item <?php echo isMenuActive('/tax', $currentPath) ? 'active open' : ''; ?>">
        <a href="#" class="has-submenu">
            <i class="fas fa-file-invoice-dollar"></i>
            <span class="menu-text">Imp. de Renda</span>
            <i class="fas fa-chevron-right menu-arrow"></i>
        </a>
        <div class="submenu <?php echo isMenuActive('/tax', $currentPath) ? 'open' : ''; ?>">
            <a href="/GED2.0/views/tax/ListAdmin.php" class="<?php echo $currentPath == '/tax/ListAdmin.php' ? 'active' : ''; ?>">
                Listar Imposto
            </a>
            <a href="/GED2.0/views/tax/viewRetornoBancario.php" class="<?php echo $currentPath == '/tax/viewRetornoBancario.php' ? 'active' : ''; ?>">
                Processar Retorno
            </a>
            <a href="/GED2.0/views/tax/payment.php" class="<?php echo $currentPath == '/tax/payment.php' ? 'active' : ''; ?>">
                Boleto
            </a>
            <a href="/GED2.0/views/tax/receipt.php" class="<?php echo $currentPath == '/tax/receipt.php' ? 'active' : ''; ?>">
                Recibo
            </a>
            <a href="/GED2.0/views/tax/dca.php" class="<?php echo $currentPath == '/tax/dca.php' ? 'active' : ''; ?>">
                Recibo Recebimento (DCA)
            </a>
        </div>
    </div>

    <!-- Menu NewsLetter -->
    <div class="menu-item <?php echo isMenuActive('/newsletter', $currentPath) ? 'active open' : ''; ?>">
        <a href="#" class="has-submenu">
            <i class="fas fa-file-invoice-dollar"></i>
            <span class="menu-text">Newsletter</span>
            <i class="fas fa-chevron-right menu-arrow"></i>
        </a>
        <div class="submenu <?php echo isMenuActive('/newsletter', $currentPath) ? 'open' : ''; ?>">
            <a href="/GED2.0/views/newsletter/List.php" class="<?php echo $currentPath == '/newsletter/List.php' ? 'active' : ''; ?>">
                Listar Newsletter
            </a>
            <a href="/GED2.0/views/newsletter/create.php" class="<?php echo $currentPath == '/newsletter/create.php' ? 'active' : ''; ?>">
                Criar Newsletter
            </a>
            <a href="/GED2.0/views/newsletter/subscribers.php" class="<?php echo $currentPath == '/newsletter/subscribers.php' ? 'active' : ''; ?>">
                Subscribers 
            </a>
        </div>
    </div>
    
    <!-- Menu Financeiro -->
    <div class="menu-item <?php echo isMenuActive('/financial', $currentPath) ? 'active open' : ''; ?>">
        <a href="#" class="has-submenu">
            <i class="fas fa-money-bill-wave"></i>
            <span class="menu-text">Financeiro</span>
            <i class="fas fa-chevron-right menu-arrow"></i>
        </a>
        <div class="submenu <?php echo isMenuActive('/financial', $currentPath) ? 'open' : ''; ?>">
            <a href="/GED2.0/views/financial/dashboard.php" class="<?php echo $currentPath == '/financial/dashboard.php' ? 'active' : ''; ?>">
                Painel Financeiro
            </a>
            <!-- Mais itens serão adicionados conforme definidos -->
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
    
    <!-- Configurações -->
    <div class="menu-item <?php echo isMenuActive('/settings', $currentPath) ? 'active' : ''; ?>">
        <a href="/ged2.0/views/settings/settings.php">
            <i class="fas fa-cog"></i>
            <span class="menu-text">Configurações</span>
        </a>
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