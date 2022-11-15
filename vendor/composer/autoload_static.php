<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit6f37514257589fb3a97c10ec9f403622
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'AraSolutions\\KiwiCloudCmsBundle\\' => 32,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'AraSolutions\\KiwiCloudCmsBundle\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit6f37514257589fb3a97c10ec9f403622::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit6f37514257589fb3a97c10ec9f403622::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit6f37514257589fb3a97c10ec9f403622::$classMap;

        }, null, ClassLoader::class);
    }
}