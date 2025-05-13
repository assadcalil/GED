<?php
// Diretório raiz
$rootDir = __DIR__;

// Encontrar todos os arquivos PHP
function findPhpFiles($dir) {
    $files = [];
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            $files = array_merge($files, findPhpFiles($path));
        } elseif (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) == 'php') {
            $files[] = $path;
        }
    }
    return $files;
}

// Função para corrigir o arquivo
function fixFile($file) {
    $content = file_get_contents($file);
    
    // Verificar padrões problemáticos
    if (strpos($content, '**DIR**') !== false || 
        strpos($content, '../../../.....') !== false) {
        
        // Padrão 1: **DIR** . '/../../../...../app/Config/App.php'
        $content = preg_replace(
            '/(\*\*DIR\*\*|__DIR__)\s*\.\s*[\'"](\.\.\/)+\.\.\.\.\.\/app\/Config\/([^\'"]*)[\'"]/i',
            'ROOT_DIR . \'/app/Config/$3\'',
            $content
        );
        
        // Padrão 2: require_once **DIR** . '/../../../...../app/Config/App.php'
        $content = preg_replace(
            '/(require|include)(_once)?\s+(\*\*DIR\*\*|__DIR__)\s*\.\s*[\'"](\.\.\/)+\.\.\.\.\.\/app\/Config\/([^\'"]*)[\'"]/i',
            '$1$2 ROOT_DIR . \'/app/Config/$5\'',
            $content
        );
        
        // Padrão 3: require_once(ROOT_DIR . '/app/Config/App.php')
        $content = preg_replace(
            '/(require|include)(_once)?\s*\(\s*[\'"](\.\.\/)+\.\.\.\.\.\/app\/Config\/([^\'"]*)[\'"]\s*\)/i',
            '$1$2(ROOT_DIR . \'/app/Config/$4\')',
            $content
        );
        
        // Adicionar definição de ROOT_DIR se não existir
        if (strpos($content, "define('ROOT_DIR'") === false && 
            strpos($content, 'define("ROOT_DIR"') === false) {
            
            $rootDirDef = "\n// Definição da raiz do projeto\nif (!defined('ROOT_DIR')) {\n    define('ROOT_DIR', dirname(dirname(dirname(__FILE__))));\n}\n\n";
            
            // Inserir após <?php
            if (preg_match('/^(\s*<\?php.*?\n)/s', $content, $matches)) {
                $content = str_replace($matches[1], $matches[1] . $rootDirDef, $content);
            } else {
                $content = "<?php\n" . $rootDirDef . substr($content, 5);
            }
        }
        
        // Salvar arquivo
        file_put_contents($file, $content);
        return true;
    }
    return false;
}

// Processar arquivos
$files = findPhpFiles($rootDir);
$corrected = 0;

echo "<h1>Correção de Caminhos</h1>";
echo "<ul>";

foreach ($files as $file) {
    $relFile = str_replace($rootDir . '/', '', $file);
    
    if (fixFile($file)) {
        echo "<li style='color:green;'><b>Corrigido:</b> $relFile</li>";
        $corrected++;
    }
}

echo "</ul>";
echo "<h3>Total de arquivos corrigidos: $corrected</h3>";