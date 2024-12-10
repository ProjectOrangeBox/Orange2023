<?php

namespace peels\handlebars;

use orange\framework\ViewAbstract;
use orange\framework\supporting\DirectorySearch;
use peels\handlebars\exceptions\ViewNotFound;
use orange\framework\interfaces\DataInterface;
use orange\framework\interfaces\ViewInterface;
use orange\framework\interfaces\DirectorySearchInterface;

class HandlebarsView extends ViewAbstract implements ViewInterface
{
    protected Handlebars $handlebars;

    protected function __construct(array $config, ?DataInterface $data = null)
    {
        parent::__construct($config, $data, null);

        // use the ViewAbstract temp folder for the cache folder if one isn't provided
        if (isset($this->config['cache folder'])) {
            $this->config['cache folder'] = $this->tempFolder;
        }

        // replace the view with our templates
        if (isset($this->config['templates'])) {
            $this->search = new DirectorySearch([
                'directories' => $this->config['template directories'],
                'extension' => $this->config['template extension'],
                'recursive' => true,
                'pend' => DirectorySearchInterface::FIRST,
            ]);
        }

        $this->handlebars = new Handlebars($this->config);
    }

    public function render(string $view = '', array $data = [], array $options = []): string
    {
        if (!$this->handlebars->viewExists($view)) {
            // let's see if we can locate it and add it!
            $foundView = $this->search->findFirst($view, false);

            if ($foundView) {
                $this->handlebars->addView($view, $foundView);
            } else {
                throw new ViewNotFound($view);
            }
        }

        return $this->handlebars->render($view, $this->data($data));
    }

    public function renderString(string $string, array $data = [], array $options = []): string
    {
        return $this->handlebars->renderString($string, $this->data($data));
    }

    public function change(string $name, mixed $value): self
    {
        $this->handlebars->change($name, $value);

        return $this;
    }
}
