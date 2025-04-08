<?php

declare(strict_types=1);

namespace peels\mergeView;

use orange\framework\abstract\ViewAbstract;
use orange\framework\interfaces\DataInterface;
use orange\framework\interfaces\ViewInterface;

/**
 * Based On the original PyroCMS LEX Parser
 *
 * https://github.com/pyrocms/lex
 *
 * This can be used for some simple "mail merging"
 *
 * Has basic looping && logic
 * It can also handles plugins
 *
 * To get a better idea of what can and can't be done see the basic unit test file
 */

class MergeView extends ViewAbstract implements ViewInterface
{
    protected Merge $merge;
    protected array $pluginHandler;

    protected function __construct(array $config, ?DataInterface $data = null)
    {
        $this->merge = new Merge($config);
        $this->pluginHandler = [$this->merge, 'pluginCallBackHandler'];

        parent::__construct($config, $data);
    }

    public function render(string $view = '', array $data = [], array $options = []): string
    {
        return $this->merge->parse(file_get_contents($view), $this->data($data), $this->pluginHandler);
    }

    public function renderString(string $string, array $data = [], array $options = []): string
    {
        return $this->merge->parse($string, $this->data($data), $this->pluginHandler);
    }

    public function change(string $name, mixed $value): self
    {
        $this->merge->change($name, $value);

        return $this;
    }
}
