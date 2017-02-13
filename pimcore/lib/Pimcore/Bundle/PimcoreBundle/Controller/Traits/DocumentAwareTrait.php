<?php

namespace Pimcore\Bundle\PimcoreBundle\Controller\Traits;

use Pimcore\Model\Document;

trait DocumentAwareTrait
{
    /**
     * @var Document|Document\PageSnippet
     */
    protected $document;

    /**
     * @param Document $document
     */
    public function setDocument(Document $document)
    {
        $this->document = $document;
    }

    /**
     * @return Document
     */
    public function getDocument()
    {
        return $this->document;
    }
}
