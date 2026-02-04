<?php
// load_env.php - Compatible with older PHP
function loadEnv($path = null): bool {
    if ($path === null) {
        $path = __DIR__ . '/../.env';
    }
    
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        
        $pos = strpos($line, '=');
        if ($pos !== false) {
            $key = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));
            
            // Remove surrounding quotes if present
            $valueLength = strlen($value);
            if ($valueLength >= 2) {
                $firstChar = $value[0];
                $lastChar = $value[$valueLength - 1];
                
                if (($firstChar === '"' && $lastChar === '"') || 
                    ($firstChar === "'" && $lastChar === "'")) {
                    $value = substr($value, 1, -1);
                }
            }
            
            if (!getenv($key)) {
                putenv("$key=$value");
            }
            
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
    
    return true;
}
?>