<?php

class bdTagMe_Listener
{
    public static function load_class($class, array &$extend)
    {
        $extend[] = 'bdTagMe_' . $class;
    }

    public static function init_dependencies(XenForo_Dependencies_Abstract $dependencies, array $data)
    {
        bdTagMe_Helper_Template::helperSnippetSetup();
    }

    public static function file_health_check(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
    {
        $hashes += bdTagMe_FileSums::getHashes();
    }
}
