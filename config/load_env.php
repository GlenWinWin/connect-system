<?php
// load_env.php
function loadEnv($path = null) {
    if ($path === null) {
        $path = __DIR__ . '/.env';
    }
    
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        $pos = strpos($line, '=');
        if ($pos !== false) {
            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));
            
            // Remove quotes
            if (($value[0] === '"' && $value[strlen($value)-1] === '"') ||
                ($value[0] === "'" && $value[strlen($value)-1] === "'")) {
                $value = substr($value, 1, -1);
            }
            
            if (!getenv($key)) {
                putenv("$key=$value");
            }
            $_ENV[$key] = $value;
        }
    }
    
    return true;
}
?>