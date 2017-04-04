<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit839a9ac56df4c7ad43538f9e767aac11
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Component\\Process\\' => 26,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symfony\\Component\\Process\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/process',
        ),
    );

    public static $prefixesPsr0 = array (
        'K' => 
        array (
            'Knp\\Snappy' => 
            array (
                0 => __DIR__ . '/..' . '/knplabs/knp-snappy/src',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit839a9ac56df4c7ad43538f9e767aac11::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit839a9ac56df4c7ad43538f9e767aac11::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit839a9ac56df4c7ad43538f9e767aac11::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
