<?php

namespace Pimcore\Bundle\PimcoreZendBundle\Templating\Zend\Helper;

use Pimcore\Bundle\PimcoreBundle\Document\TagRenderer;
use Pimcore\Bundle\PimcoreBundle\Service\Request\DocumentResolver;
use Zend\View\Helper\AbstractHelper;

class DocumentTag extends AbstractHelper
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
        $this->tagRenderer      = $tagRenderer;
        $this->documentResolver = $documentResolver;
    }

    /**
     * @param string $type      Tag type (e.g. textarea)
     * @param $inputName        Input name
     * @param array $options
     * @return mixed|\Pimcore\Model\Document\Tag|string
     */
    public function __invoke($type, $inputName, array $options = [])
    {
        $document = $this->documentResolver->getDocument();
        if (!$document) {
            return '';
        }

        return $this->tagRenderer->render($document, $type, $inputName, $options);
    }
}
