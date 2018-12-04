<?php

//require_once 'vendor/autoload.php';

spl_autoload_register(function ($class) {


    $classPrefix = 'fastTable';

    $base_dir = __DIR__ . DIRECTORY_SEPARATOR. 'src'. DIRECTORY_SEPARATOR;

    $len = strlen($classPrefix);
    if (strncmp($classPrefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);

    $file = $base_dir. str_replace('\\', DIRECTORY_SEPARATOR, $relative_class). '.php';

    if (file_exists($file)) {
        require $file;
    }

});