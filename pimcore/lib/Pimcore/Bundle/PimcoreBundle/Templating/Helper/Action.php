<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating\Helper;

use Pimcore\Bundle\PimcoreBundle\Templating\Renderer\ActionRenderer;
use Pimcore\Model\Document\PageSnippet;
use Symfony\Component\Templating\Helper\Helper;

class Action extends Helper
{
    /**
     * @var ActionRenderer
     */
    protected $actionRenderer;

    /**
     * @param ActionRenderer $actionRenderer
     */
    public function __construct(ActionRenderer $actionRenderer)
    {
        $this->actionRenderer = $actionRenderer;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'action';
    }

    /**
     * @param $action
     * @param $controller
     * @param $module
     * @param array $params
     * @return mixed
     */
    public function __invoke($action, $controller, $module, array $params = [])
    {
        $document = $params['document'];
        if ($document && $document instanceof PageSnippet) {
            $params = $this->actionRenderer->addDocumentParams($document, $params);
        }

        $controller = $this->actionRenderer->createControllerReference(
            $module,
            $controller,
            $action,
            $params
        );

        return $this->actionRenderer->render($controller);
    }
}
