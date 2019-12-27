<?php
/**
 * User: Heropoo
 * Date: 2017/3/8
 * Time: 14:30
 */

namespace Moon;


use Symfony\Component\HttpFoundation\Response;

/**
 * Controller
 */
class Controller
{
    protected $viewPath;

    /** @var View $view */
    protected $view;

    /**
     * @return View
     */
    public function getView($viewPath = null)
    {
//        if(is_null($this->view) || !$this->view instanceof View){
//
//            if(is_null($this->viewPath)){
//                $this->viewPath = \Moon::$app->getRootPath().'/views';
//            }else{
//                if(!is_dir($this->viewPath)){
//                    throw new Exception("Directory '$this->viewPath' is not exists!");
//                }
//                $this->viewPath = realpath($this->viewPath);
//            }
//            $this->view = new View($this->viewPath);
//        }
        $this->viewPath = is_null($this->viewPath) ? \Moon::$app->getRootPath() . '/views' : $this->viewPath;
        $this->view = is_null($this->view) ? new View($this->viewPath) : $this->view;
        return $this->view;
    }

    /**
     * @param string $view
     * @param array $data
     * @return Response
     */
    public function render($view, $data = [])
    {
        $string = $this->getView()->render($view, $data);
        return new Response($string);
    }
}