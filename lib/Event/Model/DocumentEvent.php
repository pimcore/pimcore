<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\Document;
use Symfony\Component\EventDispatcher\Event;

class DocumentEvent extends Event implements ElementEventInterface
{
    use ArgumentsAwareTrait;

    /**
     * @var Document
     */
    protected $document;

    /**
     * DocumentEvent constructor.
     *
     * @param Document $document
     * @param array $arguments
     */
    public function __construct(Document $document, array $arguments = [])
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

    /**
     * @return Document
     */
    public function getElement()
    {
        return $this->getDocument();
    }
}
