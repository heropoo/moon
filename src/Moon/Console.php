<?php
/**
 * Created by PhpStorm.
 * User: ttt
 * Date: 2018/7/14
 * Time: 0:25
 */

namespace Moon;


class Console
{
    public $namespace = 'App\\Commands';
    public $commands = [];

    public function add($command, $action, $description = ''){
        if(!$action instanceof \Closure){
            $action = $this->namespace.'\\'.$action;
        }
        $this->commands[$command] = [
            'action'=>$action,
            'description'=>$description
        ];
    }

    public function runCommand($command){
        if(!isset($this->commands[$command])){
            throw new Exception("Command '$command' is not defined");
        }
        $action = $this->commands[$command]['action'];
        if ($action instanceof \Closure) {
            return call_user_func($action);
        } else {
            $actionArr = explode('::', $action);
            $controllerName = $actionArr[0];
            if (!class_exists($controllerName)) {
                throw new Exception("Command class '$controllerName' is not exists!");
            }
            $controller = new $controllerName;
            $methodName = $actionArr[1];
            if (!method_exists($controller, $methodName)) {
                throw new Exception("Command class method '$controllerName::$methodName' is not defined!");
            }
            return call_user_func([$controller, $methodName]);
            //return call_user_func_array([$controller, $methodName], $params); //todo
        }
    }
}