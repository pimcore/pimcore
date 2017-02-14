<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating\Helper;

use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModel;
use Pimcore\Bundle\PimcoreBundle\Templating\Renderer\IncludeRenderer;
use Pimcore\Model\Document\PageSnippet;
use Symfony\Component\Templating\Helper\Helper;

class IncludeHelper extends Helper
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
     * @inheritDoc
     */
    public function getName()
    {
        return 'inc';
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
