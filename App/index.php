<?php
    define('SOURCES_ROOT', '/home/www/api.sc4buddy.sexyfishhorse.com/Sources');
    define('CONFIGURATION_FILE', 'configuration.ini');

    $classSources = null;

    function __autoload($className)
    {
        if($classSources === null)
        {            
            $classSources = [];
            
            $iterator = new RecursiveDirectoryIterator(SOURCES_ROOT);
            foreach (new RecursiveIteratorIterator($iterator) as $filename => $file)
            {
                if(is_file($filename))
                {
                    $classSources[basename($filename, '.class.php')]  = $filename;
                }
            }
        }
        
        if(array_key_exists($className, $classSources))
        {
            require_once($classSources[$className]);
        }
    }
    
    Api::Main();
