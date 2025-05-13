<?php
/**
 * Script de diagnóstico para o serviço de notícias
 * Coloque este arquivo no diretório raiz do seu projeto e acesse via navegador
 */

// Habilitar exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Definir cabeçalho como HTML
header('Content-Type: text/html; charset=utf-8');

// Carregar configurações mínimas
define('ROOT_DIR', __DIR__);
$configPath = __DIR__ . 'app/Config/App.php';
$authPath = __DIR__ . 'app/Config/Auth.php';

// Verificar arquivos existentes
echo "<html><head><title>Diagnóstico do Feed de Notícias</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px; }
    h1, h2 { color: #333; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    .section { margin-bottom: 30px; border: 1px solid #ddd; padding: 20px; border-radius: 5px; }
</style>";
echo "</head><body>";
echo "<h1>Diagnóstico do Feed de Notícias</h1>";

// 1. Verificar estrutura de arquivos
echo "<div class='section'>";
echo "<h2>1. Verificação de Estrutura de Arquivos</h2>";
$requiredFiles = [
    'app/Services/NoticiasContabeisService.php',
    'app/Controllers/NoticiaController.php',
    'views/partials/news_widget.php',
];

$requiredDirectories = [
    'services',
    'views/partials',
    'cache'
];

// Verificar diretorios
echo "<h3>Diretórios:</h3>";
$allDirsOk = true;
foreach ($requiredDirectories as $dir) {
    $fullPath = __DIR__ . '/' . $dir;
    if (file_exists($fullPath) && is_dir($fullPath)) {
        $writable = is_writable($fullPath) ? 'gravável' : 'não gravável';
        echo "<p class='" . (is_writable($fullPath) ? 'success' : 'error') . "'>";
        echo "✓ Diretório '<code>{$dir}</code>' existe e é {$writable}";
        echo "</p>";
        
        if (!is_writable($fullPath) && $dir == 'cache') {
            $allDirsOk = false;
            echo "<p class='error'>⚠️ O diretório de cache precisa ter permissão de escrita. Execute:</p>";
            echo "<pre>chmod -R 755 " . __DIR__ . "/cache</pre>";
        }
    } else {
        $allDirsOk = false;
        echo "<p class='error'>✗ Diretório '<code>{$dir}</code>' não existe.</p>";
        echo "<p>Para criar, execute:</p>";
        echo "<pre>mkdir -p " . __DIR__ . "/{$dir}</pre>";
    }
}

// Verificar arquivos
echo "<h3>Arquivos:</h3>";
$allFilesOk = true;
foreach ($requiredFiles as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        echo "<p class='success'>✓ Arquivo '<code>{$file}</code>' existe</p>";
    } else {
        $allFilesOk = false;
        echo "<p class='error'>✗ Arquivo '<code>{$file}</code>' não existe.</p>";
    }
}

echo "</div>";

// 2. Verificar PHP e extensões
echo "<div class='section'>";
echo "<h2>2. Verificação de PHP e Extensões</h2>";

// Versão do PHP
echo "<p>Versão do PHP: <strong>" . phpversion() . "</strong></p>";

// Extensões necessárias
$requiredExtensions = [
    'curl',
    'json',
    'libxml',
    'simplexml'
];

$allExtsOk = true;
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>✓ Extensão '{$ext}' está carregada</p>";
    } else {
        $allExtsOk = false;
        echo "<p class='error'>✗ Extensão '{$ext}' não está disponível!</p>";
    }
}

echo "</div>";

// 3. Testar requisições externas
echo "<div class='section'>";
echo "<h2>3. Teste de Requisições Externas</h2>";

$testUrls = [
    'https://www.contabeis.com.br/feed/' => 'Portal Contábeis',
    'https://www.jornalcontabil.com.br/feed/' => 'Jornal Contábil',
    'https://cfc.org.br/feed/' => 'CFC'
];

$allRequestsOk = true;
foreach ($testUrls as $url => $name) {
    echo "<h3>Testando: {$name}</h3>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        echo "<p class='success'>✓ Conexão bem-sucedida (HTTP {$httpCode})</p>";
    } else {
        $allRequestsOk = false;
        echo "<p class='error'>✗ Erro na conexão (HTTP {$httpCode}): {$error}</p>";
    }
}

echo "</div>";

// 4. Testar o serviço de notícias - se AccountingNewsService.php existir
if (file_exists(__DIR__ . 'app/Services/NoticiasContabeisService.php')) {
    echo "<div class='section'>";
    echo "<h2>4. Teste do Serviço de Notícias</h2>";
    
    try {
        // Carregar arquivo modificado
        require_once __DIR__ . 'app/Services/NoticiasContabeisService.php';
        
        // Instanciar o serviço
        $newsService = new AccountingNewsService();
        
        // Buscar notícias com debug
        $result = $newsService->getNews(5, true);
        
        // Exibir resultado do debug
        echo "<h3>Resultado do debug:</h3>";
        echo "<pre>";
        
        if (isset($result['debug']) && is_array($result['debug'])) {
            foreach ($result['debug'] as $log) {
                echo htmlspecialchars($log) . "\n";
            }
        }
        
        // Informações sobre cache
        if (isset($result['cache_file'])) {
            echo "\nArquivo de cache: " . htmlspecialchars($result['cache_file']) . "\n";
            echo "Cache existe: " . ($result['cache_exists'] ? 'Sim' : 'Não') . "\n";
            echo "Diretório gravável: " . ($result['cache_writable'] ? 'Sim' : 'Não') . "\n";
        }
        
        // Informações sobre fontes
        if (isset($result['sources']) && is_array($result['sources'])) {
            echo "\nFontes configuradas:\n";
            foreach ($result['sources'] as $source) {
                echo "- " . htmlspecialchars($source) . "\n";
            }
        }
        
        echo "</pre>";
        
        // Mostrar notícias encontradas
        echo "<h3>Notícias recuperadas:</h3>";
        if (isset($result['news']) && is_array($result['news']) && !empty($result['news'])) {
            echo "<p class='success'>✓ " . count($result['news']) . " notícias encontradas</p>";
            
            echo "<ul>";
            foreach (array_slice($result['news'], 0, 3) as $news) {
                echo "<li><strong>" . htmlspecialchars($news['title']) . "</strong> (Fonte: " . htmlspecialchars($news['source']) . ")</li>";
            }
            echo "</ul>";
        } elseif (isset($result['news']) && is_array($result['news']) && empty($result['news'])) {
            echo "<p class='warning'>⚠️ Nenhuma notícia encontrada, mas o serviço está funcionando.</p>";
        } else {
            echo "<p class='error'>✗ Erro ao recuperar notícias.</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>✗ Exceção: " . htmlspecialchars($e->getMessage()) . "</p>";
        
        // Mostrar stack trace
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    
    echo "</div>";
}

// 5. Resumo e Diagnóstico Final
echo "<div class='section'>";
echo "<h2>5. Diagnóstico Final</h2>";

$allOk = $allDirsOk && $allFilesOk && $allExtsOk && $allRequestsOk;

if ($allOk) {
    echo "<p class='success'>✓ Todas as verificações passaram. O sistema deve funcionar corretamente.</p>";
} else {
    echo "<p class='warning'>⚠️ Algumas verificações falharam. Corrija os problemas identificados acima.</p>";
    
    echo "<h3>Passos de Correção:</h3>";
    echo "<ol>";
    
    if (!$allDirsOk) {
        echo "<li>Crie todos os diretórios necessários e garanta as permissões adequadas.</li>";
    }
    
    if (!$allFilesOk) {
        echo "<li>Crie todos os arquivos necessários com o conteúdo correto.</li>";
    }
    
    if (!$allExtsOk) {
        echo "<li>Instale ou habilite todas as extensões PHP necessárias.</li>";
    }
    
    if (!$allRequestsOk) {
        echo "<li>Verifique a conectividade com os feeds de notícias. Pode haver um bloqueio de firewall ou restrição de rede.</li>";
    }
    
    echo "</ol>";
}

echo "</div>";

// 6. Ações recomendadas
echo "<div class='section'>";
echo "<h2>6. Ações Recomendadas</h2>";

echo "<ol>";
if (!$allDirsOk) {
    echo "<li>Execute o comando para criar todos os diretórios necessários:";
    echo "<pre>mkdir -p " . __DIR__ . "/services " . __DIR__ . "/views/partials " . __DIR__ . "/cache</pre>";
    echo "</li>";
    
    echo "<li>Configure as permissões:";
    echo "<pre>chmod -R 755 " . __DIR__ . "/cache</pre>";
    echo "</li>";
}

if (!$allFilesOk) {
    echo "<li>Copie o código para cada arquivo necessário a partir dos artefatos fornecidos.</li>";
}

echo "<li>Substitua o arquivo <code>app/Services/NoticiasContabeisService.php</code> pela versão aprimorada que inclui notícias de exemplo caso não consiga conectar às fontes externas.</li>";

echo "</ol>";

echo "</div>";

echo "</body></html>";
?>