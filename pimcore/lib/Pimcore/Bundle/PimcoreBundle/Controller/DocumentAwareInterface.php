<?php

namespace Pimcore\Bundle\PimcoreBundle\Controller;

use Pimcore\Model\Document;

interface DocumentAwareInterface
{
    /**
     * @param Document $document
     */
    public function setDocument(Document $document);
}
