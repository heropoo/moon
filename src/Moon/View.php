<?php
/**
 * User: Heropoo
 * Date: 2017/3/8
 * Time: 14:34
 */

namespace Moon;


class View
{
    protected $viewPath;

    public $layout;

    public $title;

    public function __construct($viewPath, $layout = null)
    {
        $this->viewPath = $viewPath;
        $this->layout = $layout;
    }

    /**
     * render a view
     * @param string $view
     * @param array $data
     * @return string
     */
    public function render($view, array $data = [])
    {
        $content = $this->renderPart($view, $data);
        if ($this->layout) {
            return $this->renderPart($this->layout, ['content' => $content]);
        }
        return $content;
    }

    /**
     * render a part of view
     * @param string $view
     * @param array $data
     * @return string
     */
    public function renderPart($view, array $data = [])
    {
        $viewFile = $this->viewPath . '/' . $view . '.php';
        if (!file_exists($viewFile)) {
            throw new Exception("View file `$viewFile` is not exists");
        }

        ob_start();
        extract($data);
        include $viewFile;
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    public function e($var)
    {
        return htmlspecialchars($var);
    }
}