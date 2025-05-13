<header class="dashboard-header">
    <div class="menu-toggle">
        <i class="fas fa-bars"></i>
    </div>
    
    <div class="brasilia-time">
        <i class="far fa-clock"></i> Horário de Brasília: <span id="brasilia-clock"><?php echo Config::getCurrentBrasiliaHour(); ?></span>
    </div>
    
    <div class="header-right">
        <div class="notifications dropdown">
            <button class="btn dropdown-toggle" type="button" id="notificationsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="far fa-bell"></i>
                <span class="notification-badge">3</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                <li><h6 class="dropdown-header">Notificações</h6></li>
                <li><a class="dropdown-item" href="#">Novo documento adicionado</a></li>
                <li><a class="dropdown-item" href="#">Certificado expirando em 10 dias</a></li>
                <li><a class="dropdown-item" href="#">Solicitação de acesso pendente</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-center" href="#">Ver todas</a></li>
            </ul>
        </div>
        
        <div class="user-profile dropdown">
            <button class="btn dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="user-avatar">
                    <img src="/GED2.0/assets/img/avatar.png" alt="Avatar do Usuário">
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo $_SESSION['user_name']; ?></span>
                    <span class="user-role"><?php echo Auth::getUserTypeName(); ?></span>
                </div>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="/profile"><i class="fas fa-user-circle me-2"></i> Meu Perfil</a></li>
                <li><a class="dropdown-item" href="/settings"><i class="fas fa-cog me-2"></i> Configurações</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="/?logout=1"><i class="fas fa-sign-out-alt me-2"></i> Sair</a></li>
            </ul>
        </div>
    </div>
</header>