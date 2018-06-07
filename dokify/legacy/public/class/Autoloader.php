<?php

class Autoloader
{
    public static function register($usuario = null)
    {
        ini_set('unserialize_callback_func', 'spl_autoload_call');
        spl_autoload_register(array(new self, 'autoload'));
    }

    public static function autoload($className)
    {
        $classPath = DIR_CLASS . $className . ".class.php";
        if (file_exists($classPath)) {
            require_once $classPath;
            return;
        }

        $ifacePath = DIR_ROOT ."/iface/{$className}.iface.php";
        if (file_exists($ifacePath)) {
            require_once $ifacePath;
            return;
        }

        if ('HTML2PDF' === $className) {
            require_once DIR_CLASS . 'html2pdf4/html2pdf.class.php';
            return;
        }
    }
}

