<?php

namespace Moon;

use Dotenv\Dotenv;
use Dotenv\Exception\ExceptionInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Moon\Config\Config;
use Moon\Console\Console;
use Moon\Routing\Router;
use Moon\Routing\Route;
use Moon\Container\Container;
use Moon\Routing\UrlMatchException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Moon\Request\Request;
use Symfony\Component\HttpFoundation\Response;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;

/**
 * Class Application
 * @method string getEnvironment()
 * @method string getRootPath()
 * @method string getConfigPath()
 * @method string getAppPath()
 * @method string getCharset()
 * @method string getTimezone()
 * @method array getConfig()
 * @method bool getDebug()
 * @package Moon
 */
class Application
{
    protected $rootPath;
    protected $configPath;
    protected $appPath;

    protected $config = [];

    protected $environment = 'production';
    protected $debug = false;
    protected $charset = 'UTF-8';
    protected $timezone = 'UTC';

    /** @var Container $container */
    public $container;

    /**
     * Application constructor.
     * @param $rootPath
     * @param array $options
     * @param Container $container
     */
    public function __construct($rootPath, array $options = [], Container $container = null)
    {
        if (!is_dir($rootPath)) {
            throw new Exception("Directory '$rootPath' is not exists!");
        }
        $this->rootPath = realpath($rootPath);

        foreach ($options as $option => $value) {
            $this->$option = $value;
        }

        $this->configPath = is_null($this->configPath) ? $this->rootPath . '/config' : $this->configPath;

        $this->appPath = is_null($this->appPath) ? $this->rootPath . '/app' : $this->appPath;

        $this->container = is_null($container) ? new Container() : $container;

        \App::$instance = $this;
        \App::$container = $this->container;

        $this->init();
    }

    /**
     * handle application errors
     */
    protected function handleError()
    {
        $logger = new Logger('app');
        $this->container->add('logger', $logger);
        $filename = $this->rootPath . '/runtime/logs/app-' . date('Y-m-d') . '.log';
        $logger->pushHandler(new StreamHandler($filename, Logger::DEBUG));

        $whoops = new Run();

        if (is_cli()) {
            $whoops->pushHandler(new PlainTextHandler());
        } else {
            if ($this->debug) {
                if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"] == 'xmlhttprequest')) {
                    $whoops->pushHandler(new JsonResponseHandler());
                } else {
                    $handler = new PrettyPageHandler();
                    $handler->setPageTitle('Moon App Error');
                    $whoops->pushHandler($handler);
                }
            }
        }

        $handler = new PlainTextHandler($logger);
        $handler->loggerOnly(true);
        $whoops->pushHandler($handler);
        $whoops->register();
    }

    protected function init()
    {
        try {
            (new Dotenv($this->rootPath))->load();
        } catch (ExceptionInterface $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }

        $config = new Config($this->configPath);
        $this->container->add('config', $config);

        $this->config = $config->get('app', true);

        if (isset($this->config['timezone'])) {
            $this->timezone = $this->config['timezone'];
            date_default_timezone_set($this->timezone);
        }

        if (isset($this->config['charset'])) {
            $this->charset = $this->config['charset'];
        }

        if (isset($this->config['environment'])) {
            $this->environment = $this->config['environment'];
        }

        if (isset($this->config['debug'])) {
            $this->debug = $this->config['debug'];
        }

        $this->initLogger();

        if (isset($_SERVER)) {
            $this->handleError();
        }

        $this->initRoutes();

        $this->initComponents();
    }

    public function initComponents()
    {
        isset($this->config['components']) ?: $this->config['components'] = [];
        foreach ($this->config['components'] as $componentName => $params) {

            $className = $params['class'];
            unset($params['class']);

            if(!isset($params['auto_inject_by_class']) || $params['auto_inject_by_class'] !== false){
                $this->container->alias($className, $componentName);
            }
            unset($params['auto_inject_by_class']);

            $this->container->bind($componentName, function () use ($className, $params) {
                $ref = new \ReflectionClass($className);
                return $ref->newInstanceArgs($params);
            }, true);

        }
    }

    protected function initLogger()
    {
        $logger = new Logger('app');
        $this->container->add('logger', $logger);
        $filename = $this->rootPath . '/runtime/logs/app-' . date('Y-m-d') . '.log';
        $logger->pushHandler(new StreamHandler($filename, Logger::DEBUG));
    }

    public function initRoutes()
    {
        $route_config = $this->container->get('config')->get('route');

        $router = new Router([
            'namespace' => isset($route_config['namespace']) ? $route_config['namespace'] : 'App\Controllers',
            'prefix' => isset($route_config['prefix']) ? $route_config['prefix'] : null,
            'middleware' => isset($route_config['middleware']) ? $route_config['middleware'] : [],
        ]);

        $this->container->add('router', $router);

        if (isset($route_config['groups'])) {
            foreach ($route_config['groups'] as $group) {
                $router->group([
                    'namespace' => isset($group['namespace']) ? $group['namespace'] : null,
                    'prefix' => isset($group['prefix']) ? $group['prefix'] : null,
                    'middleware' => isset($group['middleware']) ? $group['middleware'] : [],
                ], function (Router $router) use ($group) {
                    require_once $group['file'];
                });
            }
        }
    }

    public function run()
    {
        $this->container->add('request', Request::createFromGlobals());

        $router = $this->container->get('router');

        try {
            $response = $this->resolveRequest($this->container->get('request'), $router);
        } catch (UrlMatchException $e) {
            $response = $this->makeResponse($e->getMessage(), $e->getCode());
        }

        $response->send();
    }

    public function handleSwooleRequest(SwooleHttpRequest $swooleHttpRequest, SwooleHttpResponse $swooleHttpResponse)
    {
        $request = Request::createFromSwooleHttpRequest($swooleHttpRequest);
        $this->container->add('request', $request);

        /** @var Router $router */
        $router = $this->container->get('router');

        try {
            $response = $this->resolveRequest($request, $router);
        } catch (UrlMatchException $e) {
            $response = $this->makeResponse($e->getMessage(), $e->getCode());
        } catch (\Throwable $throwable) {
            echo $throwable->__toString() . PHP_EOL;

            $logger = $this->container->get('logger');
            $logger->error($throwable->__toString());

            $response = $this->makeResponse('Internal Server Error', 500);
        }

        // set session
        if ($request->getSession()) {
            $session = $request->getSession();
            $sessionCookieParams = $request->getSession()->getCookieParams();
            $swooleHttpResponse->setCookie($session->getName(), $session->getId(), $sessionCookieParams['lifetime'],
                $sessionCookieParams['path'], $sessionCookieParams['domain'], $sessionCookieParams['secure'],
                $sessionCookieParams['httponly'], $sessionCookieParams['samesite']);

            $session->write();
        }

        //set headers
        $headers = [];
        foreach ($response->headers->all() as $key => $value) {
            $headers[$key] = $value[0];
        }
        $swooleHttpResponse->header = $headers;

        //set http status code
        $swooleHttpResponse->setStatusCode($response->getStatusCode());

        //send
        $swooleHttpResponse->end($response->getContent());

        return $response;
    }


    public function runConsole()
    {
        return $this->handleCommand();
    }

    protected function handleCommand()
    {
        $argv = $_SERVER['argv'];
        foreach ($argv as $key => $arg) {
            if ((strpos($arg, 'App') + 4) == strlen($arg) || $arg === 'App') {
                break;
            } else {
                unset($argv[$key]);
            }
        }
        $console = new Console();
        $this->container->add('console', $console);
        if (!file_exists($this->rootPath . '/routes/console.php')) {
            throw new Exception('Console route file ' . $this->rootPath . '/routes/console.php is not exists.');
        }
        require $this->rootPath . '/routes/console.php';

        if (!isset($argv[1])) {
            echo 'Moon Console ' . \App::version() . PHP_EOL;
            echo '------------------------------------------------' . PHP_EOL;
            // command list
            ksort($console->commands);
            foreach ($console->commands as $command => $options) {
                echo $command . "\t\t" . $options['description'] . PHP_EOL;
            }
            return 0;
        }
        $command = $argv[1];
        unset($argv[0], $argv[1]);

        return $console->runCommand($command, $argv);
    }

    /**
     * @param Request $request
     * @param Router $router
     * @return JsonResponse|Response
     * @throws Exception
     * @throws UrlMatchException
     */
    protected function resolveRequest(Request $request, Router $router)
    {
        $matchResult = $router->dispatch($request->getPathInfo(), $request->getMethod());
        return $this->resolveController($matchResult);
    }

    /**
     * @param mixed $data
     * @param int $status
     * @return JsonResponse|Response
     */
    protected function makeResponse($data, $status = 200)
    {
        if ($data instanceof Response) {
            return $data;
        } else if ($data instanceof View) {
            return new Response(strval($data), $status);
        } else if (is_array($data) || is_object($data)) {
            return new JsonResponse($data, $status);
        } else {
            return new Response(strval($data), $status);
        }
    }

    /**
     * @param Request $request
     * @param array $middlewareList
     * @return mixed
     * @throws Exception
     */
    protected function filterMiddleware($request, $middlewareList)
    {
        if (empty($middlewareList)) {
            return null;
        }
        $middleware = array_shift($middlewareList);
        if (!class_exists($middleware)) {
            throw new Exception('Class ' . $middleware . ' is not exists!');
        }
        $middlewareObj = new $middleware();
        return $middlewareObj->handle($request, function ($request) use ($middlewareList) {
            return $this->filterMiddleware($request, $middlewareList);
        });
    }

    /**
     * @param array $matchResult
     * @return JsonResponse|Response
     * @throws Exception
     */
    protected function resolveController($matchResult)
    {
        /**
         * @var Router $router
         */
        //$router = $this->container->get('router');
        /** @var Route $route */
        $route = $matchResult['route'];
        $params = $matchResult['params'];

        $params = array_map(function ($param) {
            return urldecode($param);
        }, $params);

        $middlewareList = $route->getMiddleware();
        $request = $this->container->get('request');
        $result = $this->filterMiddleware($request, $middlewareList);

        if (!is_null($result)) {
            return $this->makeResponse($result);
        }

        try {
            /**
             * resolve controller
             */
            $action = $route->getAction();
            if ($action instanceof \Closure) {
                $data = $this->container->callFunction($action, $params);
                return $this->makeResponse($data);
            } else {
                $actionArr = explode('::', $action);
                $controllerName = $actionArr[0];
                if (!class_exists($controllerName)) {
                    throw new Exception("Controller class '$controllerName' is not exists!");
                }
                $methodName = isset($actionArr[1]) ? $actionArr[1] : null;
                $data = $this->container->callMethod($controllerName, $methodName, $params);
                return $this->makeResponse($data);
            }
        } catch (HttpException $e) {
            return $this->makeResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'get') === 0) { //get protected attribute
            $attribute = lcfirst(substr($name, 3));
            if (isset($this->$attribute)) {
                return $this->$attribute;
            }
        }
        throw new Exception('Call to undefined method ' . get_class($this) . '::' . $name . '()');
    }
}
