<?php
/**
 * User: Heropoo
 * Date: 2018/1/11
 * Time: 22:08
 */

/**
 * Class Route
 * todo
 */
class Route{

    public static function __callStatic($name, $arguments)
    {
        /**
         * @var \Moon\Routing\Router $router
         */
        $router = \App::$container->get('router');
        return call_user_func_array([$router, $name], $arguments);
    }
}