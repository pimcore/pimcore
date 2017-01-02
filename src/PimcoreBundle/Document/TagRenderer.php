<?php

namespace PimcoreBundle\Document;

use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Document\Tag;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TagRenderer
{
    use LoggerAwareTrait;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     * @param LoggerInterface $logger
     */
    public function __construct(RequestStack $requestStack, LoggerInterface $logger)
    {
        $this->requestStack = $requestStack;
        $this->logger = $logger;
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
     * TODO find a cleaner way to resolve edit mode (or a more central way, maybe an event listener?)
     *
     * @return bool
     */
    protected function isEditmode()
    {
        if ($this->getRequest()->get('pimcore_editmode')) {
            return true;
        }

        return false;
    }

    /**
     * @param PageSnippet $document
     * @param $type
     * @param $inputName
     * @param array $options
     * @return mixed|Tag|string
     *
     * @see \Pimcore\View::tag
     */
    public function render(PageSnippet $document, $type, $inputName, array $options = [])
    {
        $type = strtolower($type);
        $name = Tag::buildTagName($type, $inputName, $document);

        try {
            if ($document instanceof PageSnippet) {
                $tag = $document->getElement($name);

                if ($tag instanceof Tag && $tag->getType() === $type) {
                    // call the load() method if it exists to reinitialize the data (eg. from serializing, ...)
                    if (method_exists($tag, 'load')) {
                        $tag->load();
                    }

                    $tag->setEditmode($this->isEditmode());
                    $tag->setOptions($options);
                } else {
                    $tag = Tag::factory($type, $name, $document->getId(), $options, null, null, $this->isEditmode());
                    $document->setElement($name, $tag);
                }

                // set the real name of this editable, without the prefixes and suffixes from blocks and areablocks
                $tag->setRealName($inputName);
            }

            return $tag;
        } catch (\Exception $e) {
            $this->logger->warning($e);
        }

        return '';
    }
}
