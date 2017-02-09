<?php

namespace Pimcore\Bundle\PimcoreZendBundle\Templating\Zend;

use Pimcore\Bundle\PimcoreBundle\Templating\TagRenderer;
use Pimcore\Bundle\PimcoreBundle\View\ZendViewHelperBridge;
use Pimcore\Model\Document\PageSnippet;
use Zend\View\Renderer\PhpRenderer as BasePhpRenderer;

class PhpRenderer extends BasePhpRenderer
{
    /**
     * @var \Pimcore\Bundle\PimcoreBundle\Templating\TagRenderer
     */
    protected $tagRenderer;

    /**
     * @var ZendViewHelperBridge
     */
    protected $zendViewHelperBridge;

    /**
     * @param \Pimcore\Bundle\PimcoreBundle\Templating\TagRenderer $tagRenderer
     */
    public function setTagRenderer(TagRenderer $tagRenderer)
    {
        $this->tagRenderer = $tagRenderer;
    }

    /**
     * @param ZendViewHelperBridge $zendViewHelperBridge
     */
    public function setZendViewHelperBridge($zendViewHelperBridge)
    {
        $this->zendViewHelperBridge = $zendViewHelperBridge;
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $argv)
    {
        // TODO zf1 view helpers are used until we ported them to Zend\View 3
        if ('zf1_' === substr($method, 0, 4)) {
            return $this->renderLegacyViewHelper(substr($method, 4), $argv);
        }

        if ($this->document instanceof PageSnippet) {
            $document = $this->document;

            if ($this->tagRenderer->tagExists($method)) {
                if (!isset($argv[0])) {
                    throw new \Exception('You have to set a name for the called tag (editable): ' . $method);
                }

                // set default if there is no editable configuration provided
                if (!isset($argv[1])) {
                    $argv[1] = [];
                }

                return $this->tagRenderer->render($document, $method, $argv[0], $argv[1]);
            }

            // call method on the current document if it exists
            if (method_exists($document, $method)) {
                return call_user_func_array([$document, $method], $argv);
            }
        }

        return parent::__call($method, $argv);
    }

    /**
     * Render ZF1 view helper via zend view helper bridge
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    protected function renderLegacyViewHelper($name, array $arguments = [])
    {
        return $this->zendViewHelperBridge->execute($name, $arguments);
    }
}
