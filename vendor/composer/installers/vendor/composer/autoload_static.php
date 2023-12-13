<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit2aa04b38a1e77b7c5f3b11f9aad96cb2
{
    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'Composer\\Installers\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Composer\\Installers\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/Composer/Installers',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit2aa04b38a1e77b7c5f3b11f9aad96cb2::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit2aa04b38a1e77b7c5f3b11f9aad96cb2::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit2aa04b38a1e77b7c5f3b11f9aad96cb2::$classMap;

        }, null, ClassLoader::class);
    }
}
