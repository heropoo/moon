<?php
/**
 * User: Heropoo
 * Date: 2018/1/12
 * Time: 15:39
 */

class App
{
    private static $_version = 'v0.12';

    /** @var \Moon\Application $app */
    public static $instance;

    /** @var \Moon\Container\Container $container */
    public static $container;

    public static function get($name)
    {
        return static::$container->get($name);
    }

    public static function environment()
    {
        return static::$instance->getEnvironment();
    }

    public static function version()
    {
        return self::$_version;
    }

    public static function setVersion($version)
    {
        self::$_version = $version;
    }
}
