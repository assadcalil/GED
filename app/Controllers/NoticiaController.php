<?php
/**
 * Sistema Contabilidade Estrela 2.0
 * API para obter notícias contábeis
 * Arquivo: ../...../app/Controllers/NoticiaController.php
 */

// Configurações de cabeçalho
header('Content-Type: application/json');

// Verificar autenticação
require_once __DIR__ . '/../../...../app/Config/App.php';
require_once __DIR__ . '/../../...../app/Config/Auth.php';

// Verificar se o usuário está autenticado
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

// Incluir o serviço de notícias
require_once __DIR__ . '/AccountingNewsService.php';

try {
    // Obter o limite de notícias (padrão: 10)
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    
    // Validar limite
    if ($limit < 1 || $limit > 50) {
        $limit = 10;
    }
    
    // Obter as notícias
    $newsService = new AccountingNewsService();
    $news = $newsService->getNews($limit);
    
    // Formatar datas para exibição
    foreach ($news as &$item) {
        $item['formatted_date'] = AccountingNewsService::formatDate($item['date']);
    }
    
    // Retornar as notícias em formato JSON
    echo json_encode($news);
} catch (Exception $e) {
    // Retornar erro
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao obter notícias: ' . $e->getMessage()]);
    
    // Registrar erro
    error_log('Erro na API de notícias: ' . $e->getMessage());
}
?>