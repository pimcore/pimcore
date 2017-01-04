<?php

namespace PimcoreBundle\Twig;

use Pimcore\Model\Document\PageSnippet;
use PimcoreBundle\Document\TagRenderer;

class DocumentTagExtension extends \Twig_Extension
{
    /**
     * @var TagRenderer
     */
    protected $tagRenderer;

    /**
     * @param TagRenderer $tagRenderer
     */
    public function __construct(TagRenderer $tagRenderer)
    {
        $this->tagRenderer = $tagRenderer;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('pimcore_*', [$this, 'renderTag'], [
                'needs_context' => true,
                'is_safe'       => ['html'],
            ])
        ];
    }

    /**
     * @see \Pimcore\View::tag
     *
     * @param array $context
     * @param string $name
     * @param string $inputName
     * @param array $options
     * @return \Pimcore\Model\Document\Tag|string
     */
    public function renderTag($context, $name, $inputName, array $options = [])
    {
        $document = $context['document'];
        if (!($document instanceof PageSnippet)) {
            return '';
        }

        return $this->tagRenderer->render($document, $name, $inputName, $options);
    }
}
