<?php
/**
 * Created by PhpStorm.
 * User: ttt
 * Date: 2018/1/12
 * Time: 15:39
 */

class Moon
{
    /**
     * @var \Moon\Application $app
     */
    public static $app;

    public static function command($command, $action, $description = ''){
        /**
         * @var \Moon\Console $console
         */
        $console = static::$app->get('console');
        $console->add($command, $action, $description);
    }

    public static function version(){
        return 'v0.3';
    }
}