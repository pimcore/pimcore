<?php

namespace Pimcore\Bundle\PimcoreZendBundle\Templating\Zend\Helper;

use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModel;
use Pimcore\Bundle\PimcoreBundle\Templating\Renderer\IncludeRenderer;
use Pimcore\Model\Document\PageSnippet;
use Zend\View\Helper\AbstractHelper;
use Zend\View\Renderer\PhpRenderer;

class Inc extends AbstractHelper
{
    /**
     * @var IncludeRenderer
     */
    protected $includeRenderer;

    /**
     * @param IncludeRenderer $includeRenderer
     */
    public function __construct(IncludeRenderer $includeRenderer)
    {
        $this->includeRenderer = $includeRenderer;
    }

    /**
     * @param PageSnippet|int|string $include
     * @param array $params
     * @param bool $cacheEnabled
     *
     * @return string
     */
    public function __invoke($include, array $params = [], $cacheEnabled = true)
    {
        // TODO remove dependency on ViewModel as it isn't really needed here (just used to switch between legacy and new rendering)
        $view = new ViewModel($params);

        return $this->includeRenderer->render($view, $include, $params, $cacheEnabled);
    }
}
