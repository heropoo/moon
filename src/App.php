<?php
/**
 * User: Heropoo
 * Date: 2018/1/12
 * Time: 15:39
 */

class App
{
    /** @var \Moon\Application $app */
    public static $instance;

    /** @var \Moon\Container\Container $container */
    public static $container;

    public static function environment()
    {
        return static::$instance->getEnvironment();
    }

    public static function version()
    {
        return 'v0.11';
    }
}
