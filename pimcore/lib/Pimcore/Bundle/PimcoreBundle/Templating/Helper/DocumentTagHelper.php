<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating\Helper;

use Pimcore\Bundle\PimcoreBundle\Document\TagRenderer;
use Pimcore\Bundle\PimcoreBundle\Service\Request\DocumentResolver;
use Symfony\Component\Templating\Helper\Helper;

class DocumentTagHelper extends Helper
{
    /**
     * @var TagRenderer
     */
    protected $tagRenderer;

    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * @param TagRenderer $tagRenderer
     * @param DocumentResolver $documentResolver
     */
    public function __construct(TagRenderer $tagRenderer, DocumentResolver $documentResolver)
    {
        $this->tagRenderer  = $tagRenderer;
        $this->documentResolver = $documentResolver;
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName()
    {
        return 'pimcoreDocumentTag';
    }

    /**
     * @param string $type
     * @param string $inputName
     * @param array $options
     * @return \Pimcore\Model\Document\Tag|string
     */
    public function render($type, $inputName, array $options = [])
    {
        $document = $this->documentResolver->getDocument();
        if (!$document) {
            return '';
        }

        return $this->tagRenderer->render($document, $type, $inputName, $options);
    }
}
