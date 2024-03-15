<?php

namespace peels\handlebars;

use orange\framework\ViewAbstract;
use orange\framework\DirectorySearch;
use peels\handlebars\exceptions\ViewNotFound;
use orange\framework\interfaces\DataInterface;
use orange\framework\interfaces\ViewInterface;

class HandlebarsView extends ViewAbstract implements ViewInterface
{
    private static ?HandlebarsView $instance = null;

    protected Handlebars $handlebars;
    protected array $config;
    protected DataInterface $data;
    public DirectorySearch $viewSearch;

    protected function __construct(array $config, ?DataInterface $data = null)
    {
        parent::__construct($config, $data);

        // use the ViewAbstract temp folder for the cache folder if one isn't provided
        if (isset($this->config['cache folder'])) {
            $this->config['cache folder'] = $this->tempFolder;
        }

        // replace the view with our templates
        if (isset($this->config['templates'])) {
            $this->viewSearch = new DirectorySearch([
                'directories' => $this->config['template directories'],
                'extension' => $this->config['template extension'],
            ]);
        }

        $this->handlebars = new Handlebars($this->config);
    }

    public static function getInstance(array $config, ?DataInterface $data = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config, $data);
        }

        return self::$instance;
    }

    public function render(string $view = '', array $data = []): string
    {
        if (!$this->handlebars->viewExists($view)) {
            // let's see if we can locate it and add it!
            $foundView = $this->viewSearch->find($view, false);

            if ($foundView) {
                $this->handlebars->addView($view, $foundView);
            } else {
                throw new ViewNotFound($view);
            }
        }

        return $this->handlebars->render($view, array_replace((array)$this->data, $data));
    }

    public function renderString(string $string, array $data = []): string
    {
        return $this->handlebars->renderString($string, array_replace((array)$this->data, $data));
    }

    public function change(string $name, mixed $value): self
    {
        $this->handlebars->change($name, $value);

        return $this;
    }
}
