<?php

namespace PimcoreBundle\Templating\Helper;

use PimcoreBundle\Document\TagRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Templating\Helper\Helper;

class DocumentTagHelper extends Helper
{
    /**
     * @var TagRenderer
     */
    protected $tagRenderer;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param TagRenderer $tagRenderer
     * @param RequestStack $requestStack
     */
    public function __construct(TagRenderer $tagRenderer, RequestStack $requestStack)
    {
        $this->tagRenderer  = $tagRenderer;
        $this->requestStack = $requestStack;
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
     * @return Request
     */
    protected function getRequest()
    {
        if (!$this->requestStack->getCurrentRequest()) {
            throw new \LogicException('A Request must be available.');
        }

        return $this->requestStack->getCurrentRequest();
    }

    /**
     * @param string $type
     * @param string $inputName
     * @param array $options
     * @return \Pimcore\Model\Document\Tag|string
     */
    public function render($type, $inputName, array $options = [])
    {
        $document = $this->getRequest()->get('contentDocument');
        if (!$document) {
            return '';
        }

        return $this->tagRenderer->render($document, $type, $inputName, $options);
    }
}
