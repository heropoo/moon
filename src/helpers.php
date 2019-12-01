<?php
/**
 * User: Heropoo
 * Date: 2018/1/12
 * Time: 15:37
 */

if (!function_exists('is_cli')) {
    /**
     * check if php running in cli mode
     */
    function is_cli()
    {
        return preg_match("/cli/i", php_sapi_name()) ? true : false;
    }
}

if (!function_exists('dump')) {
    /**
     * pretty var_dump
     * @param $var
     * @params mixed $var
     */
    function dump($var)
    {
        if (is_cli()) {
            foreach (func_get_args() as $var) {
                var_dump($var);
            }
        } else {
            echo '<pre>';
            foreach (func_get_args() as $var) {
                var_dump($var);
            }
            echo '</pre>';
        }
    }
}

if (!function_exists('dd')) {
    /**
     * pretty var_dump and exit 1
     * @param mixed $var
     */
    function dd($var)
    {
        call_user_func_array('dump', func_get_args());
        exit(1);
    }
}

if (!function_exists('root_path')) {
    /**
     * @param string $path
     * @return string
     */
    function root_path($path = '')
    {
        return \Moon::$app->getRootPath() . (strlen($path) ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('app_path')) {
    /**
     * @param string $path
     * @return string
     */
    function app_path($path = '')
    {
        return \Moon::$app->getAppPath() . (strlen($path) ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('storage_path')) {
    /**
     * @param string $path
     * @return string
     */
    function storage_path($path = '')
    {
        return \Moon::$app->getRootPath() . DIRECTORY_SEPARATOR . 'storage' . (strlen($path) ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('runtime_path')) {
    /**
     * @param string $path
     * @return string
     */
    function runtime_path($path = '')
    {
        return \Moon::$app->getRootPath() . DIRECTORY_SEPARATOR . 'runtime' . (strlen($path) ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('public_path')) {
    /**
     * @param string $path
     * @return string
     */
    function public_path($path = '')
    {
        return \Moon::$app->getRootPath() . DIRECTORY_SEPARATOR . 'public' . (strlen($path) ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('asset')) {
    /**
     * @param string $path
     * @param bool $full
     * @return string
     */
    function asset($path, $full = true)
    {
        /**
         * @var \Symfony\Component\HttpFoundation\Request $request
         */
        $request = \Moon::$container->get('request');
        if ($full) {
            return $request->getSchemeAndHttpHost() . $request->getBasePath() . '/' . $path;
        }
        return $request->getBasePath() . '/' . $path;
    }
}

if (!function_exists('app')) {
    /**
     * @param string $key
     * @return \Moon\Application|mixed
     */
    function app($key = null)
    {
        $app = \Moon::$app;
        if (is_null($key)) {
            return $app;
        }
        return $app->container->get($key);
    }
}

if (!function_exists('request')) {
    /**
     * @param null|string $key
     * @param null|mixed $default
     * @return null|mixed|\Symfony\Component\HttpFoundation\Request $request
     */
    function request($key = null, $default = null)
    {
        $request = \Moon::$container->get('request');
        if (is_null($key)) {
            return $request;
        }
        $value = $request->get($key);
        return is_null($value) || strlen($value) == 0 ? $default : $value;
    }
}

if (!function_exists('url')) {
    /**
     * @param string $path
     * @return string
     * @throws \Moon\Container\Exception
     */
    function url($path = '')
    {
        if (strpos($path, 'http://') === 0 || strrpos($path, 'https://') === 0) {
            return $path;
        }
        if($path == '/'){
            $path = '';
        }
        /**
         * @var \Symfony\Component\HttpFoundation\Request $request
         */
        $request = \Moon::$container->get('request');
        return $request->getSchemeAndHttpHost() . $request->getBasePath() . '/' . $path;
    }
}

if (!function_exists('redirect')) {
    /**
     * @param string $to
     * @param int $status
     * @return string
     */
    function redirect($to, $status = 302)
    {
        $url = url($to);
        return new \Symfony\Component\HttpFoundation\RedirectResponse($url, $status);
    }
}

if (!function_exists('abort')) {
    /**
     * @param int $code
     * @param string $message
     * @return string
     * @throws \Symfony\Component\Routing\Exception\ResourceNotFoundException|\Moon\HttpException //todo
     */
    function abort($code = 404, $message = '')
    {
        if ($code == 404) {
            throw new \Symfony\Component\Routing\Exception\ResourceNotFoundException($message);
        }
        throw new \Moon\HttpException($message, $code);
    }
}

if (! function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
                return true;
            case 'false':
                return false;
            case 'null':
                return null;
        }

        return $value;
    }
}

if(!function_exists('config')){
    /**
     * get a config
     * @param string $key
     * @param bool $throw
     * @return mixed|null|\Moon\Config\Exception
     */
    function config($key, $throw = false){
        $config = \Moon::$container->get('config');
        return $config->get($key, $throw);
    }
}