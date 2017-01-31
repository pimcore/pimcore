<?php

namespace PimcoreBundle\Templating\Zend;

use PimcoreBundle\Document\TagRenderer;
use Zend\View\Renderer\PhpRenderer as BasePhpRenderer;

class PhpRenderer extends BasePhpRenderer
{
    /**
     * @var TagRenderer
     */
    protected $tagRenderer;

    /**
     * @param TagRenderer $tagRenderer
     */
    public function setTagRenderer(TagRenderer $tagRenderer)
    {
        $this->tagRenderer = $tagRenderer;
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $argv)
    {
        if ($this->tagRenderer->tagExists($method)) {
            if (!isset($argv[0])) {
                throw new \Exception('You have to set a name for the called tag (editable): ' . $method);
            }

            // set default if there is no editable configuration provided
            if (!isset($argv[1])) {
                $argv[1] = [];
            }

            // delegate to documentTag view helper
            return $this->documentTag($method, $argv[0], $argv[1]);
        }

        return parent::__call($method, $argv);
    }
}
