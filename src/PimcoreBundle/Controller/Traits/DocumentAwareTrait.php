<?php

namespace PimcoreBundle\Controller\Traits;

use Pimcore\Model\Document;

trait DocumentAwareTrait
{
    /**
     * @var Document
     */
    protected $document;

    /**
     * @param Document $document
     */
    public function setDocument(Document $document)
    {
        $this->document = $document;
    }
}
