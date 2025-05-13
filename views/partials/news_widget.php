<?php
// Definir diretório raiz para includes
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(dirname(__FILE__)));
}


?>
<!-- Widget de Notícias Contábeis para o Dashboard -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Notícias Contábeis</h5>
        <div>
            <span class="badge bg-primary" id="news-update-time">Atualizado às <?php echo date('H:i'); ?></span>
            <button class="btn btn-sm btn-outline-primary ms-2" id="refresh-news">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="list-group list-group-flush" id="news-container">
            <?php
            // Incluir o serviço de notícias
            require_once __DIR__ . '/../../../...../app/Services/NoticiasContabeisService.php';
            
            // Obter as notícias
            $newsService = new AccountingNewsService();
            $news = $newsService->getNews(10);
            
            if (empty($news)) {
                echo '<div class="text-center p-4">Não foram encontradas notícias. Tente novamente mais tarde.</div>';
            } else {
                foreach ($news as $item) {
                    // Limitar a descrição a 150 caracteres
                    $description = strlen($item['description']) > 150 ? 
                                  substr($item['description'], 0, 147) . '...' : 
                                  $item['description'];
                    
                    // Formatar data
                    $formattedDate = AccountingNewsService::formatDate($item['date']);
                    
                    // Exibir item de notícia
                    echo '
                    <a href="' . htmlspecialchars($item['link']) . '" class="list-group-item list-group-item-action" target="_blank">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">' . htmlspecialchars($item['title']) . '</h6>
                            <small class="text-muted">' . $formattedDate . '</small>
                        </div>
                        <p class="mb-1 small text-muted">' . htmlspecialchars($description) . '</p>
                        <small class="text-primary">Fonte: ' . htmlspecialchars($item['source']) . '</small>
                    </a>';
                }
            }
            ?>
        </div>
    </div>
</div>

<!-- Script para atualização automática das notícias -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configuração do temporizador (1 hora = 3600000ms)
        const updateInterval = 3600000;
        let updateTimer;
        
        // Função para atualizar o tempo restante
        function updateTimeRemaining() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('news-update-time').textContent = `Atualizado às ${hours}:${minutes}`;
        }
        
        // Função para atualizar as notícias via AJAX
        function refreshNews() {
            // Mostrar indicador de carregamento
            const newsContainer = document.getElementById('news-container');
            newsContainer.innerHTML = '<div class="text-center p-4"><i class="fas fa-spinner fa-spin me-2"></i> Carregando notícias...</div>';
            
            // Fazer requisição AJAX
            fetch('/GED2.0/../...../app/Controllers/NoticiaController.php')
                .then(response => response.json())
                .then(data => {
                    // Limpar container
                    newsContainer.innerHTML = '';
                    
                    // Se não houver notícias
                    if (data.length === 0) {
                        newsContainer.innerHTML = '<div class="text-center p-4">Não foram encontradas notícias. Tente novamente mais tarde.</div>';
                        return;
                    }
                    
                    // Adicionar cada notícia ao container
                    data.forEach(item => {
                        // Limitar a descrição a 150 caracteres
                        const description = item.description.length > 150 ? 
                                          item.description.substring(0, 147) + '...' : 
                                          item.description;
                        
                        // Criar elemento de notícia
                        const newsItem = document.createElement('a');
                        newsItem.href = item.link;
                        newsItem.className = 'list-group-item list-group-item-action';
                        newsItem.target = '_blank';
                        
                        // Estrutura interna do item
                        newsItem.innerHTML = `
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${item.title}</h6>
                                <small class="text-muted">${item.formatted_date}</small>
                            </div>
                            <p class="mb-1 small text-muted">${description}</p>
                            <small class="text-primary">Fonte: ${item.source}</small>
                        `;
                        
                        // Adicionar ao container
                        newsContainer.appendChild(newsItem);
                    });
                    
                    // Atualizar timestamp
                    updateTimeRemaining();
                    
                    // Reiniciar temporizador
                    clearTimeout(updateTimer);
                    updateTimer = setTimeout(refreshNews, updateInterval);
                })
                .catch(error => {
                    console.error('Erro ao buscar notícias:', error);
                    newsContainer.innerHTML = '<div class="text-center p-4 text-danger">Erro ao carregar notícias. Tente novamente mais tarde.</div>';
                });
        }
        
        // Inicializar temporizador para próxima atualização
        updateTimer = setTimeout(refreshNews, updateInterval);
        
        // Adicionar evento ao botão de atualização
        document.getElementById('refresh-news').addEventListener('click', function() {
            refreshNews();
        });
    });
</script>