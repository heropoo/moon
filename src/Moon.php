<?php
/**
 * User: Heropoo
 * Date: 2018/1/12
 * Time: 15:39
 */

class Moon
{
    /** @var \Moon\Application $app */
    public static $app;

    /** @var \Moon\Container\Container $container */
    public static $container;

    public static function environment(){
        return static::$app->getEnvironment();
    }

    public static function version(){
        return 'v0.9.3';
    }
}
