<?php

namespace Pimcore\Event\Element;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\Document;
use Symfony\Component\EventDispatcher\Event;

class DocumentEvent extends Event {

    use ArgumentsAwareTrait;

    /**
     * @var Document
     */
    protected $document;

    /**
     * DocumentEvent constructor.
     * @param Document $document
     * @param array $arguments
     */
    function __construct(Document $document, array $arguments = [])
    {
        $this->document = $document;
        $this->arguments = $arguments;

    }

    /**
     * @return Document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param Document $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }
}