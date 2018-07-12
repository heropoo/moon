<?php
/**
 * Created by PhpStorm.
 * User: ttt
 * Date: 2018/1/29
 * Time: 11:30
 */

namespace Moon\Container;


class Container
{
    protected $instances = [];
    protected $binds = [];

    public function add($key, $value)
    {
        if ($value instanceof \Closure) {
            $this->instances[$key] = $value;
        } else if (is_object($value)) {
            $this->instances[$key] = $value;
        } else {
            $this->binds[$key] = $value;
        }
    }

    /**
     * @param string $key
     * @return mixed
     * @throws Exception
     */
    public function get($key)
    {
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        } else if (isset($this->binds[$key])) {
            if (!class_exists($this->binds[$key])) {
                throw new Exception('Class ' . $this->binds[$key] . ' is not exists!');
            }
            $this->instances[$key] = new $this->binds[$key];  // todo DependencyInjection
            return $this->instances[$key];
        }
        throw new Exception("Class or instance named '$key' is not found!");
    }

    /**
     * @param string $componentName
     * @param array $params
     * @return mixed
     */
    protected function makeComponent($componentName, $params){
        $className = $params['class'];
        unset($params['class']);
        $object = new $className();
        if (!empty($params)) {
            foreach ($params as $attribute => $value) {
                $object->$attribute = $value;
            }
        }
        $this->add($componentName, $object);
        return $object;
    }
}