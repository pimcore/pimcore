<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Event\Model;

use Pimcore\Event\Traits\ArgumentsAwareTrait;
use Pimcore\Model\Document;
use Symfony\Contracts\EventDispatcher\Event;

class DocumentEvent extends Event implements ElementEventInterface
{
    use ArgumentsAwareTrait;

    protected Document $document;

    /**
     * DocumentEvent constructor.
     *
     */
    public function __construct(Document $document, array $arguments = [])
    {
        $this->document = $document;
        $this->arguments = $arguments;
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    public function setDocument(Document $document): void
    {
        $this->document = $document;
    }

    public function getElement(): Document
    {
        return $this->getDocument();
    }
}
