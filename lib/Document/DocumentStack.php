<?php

declare(strict_types=1);

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

namespace Pimcore\Document;

use Pimcore\Model\Document;

class DocumentStack
{
    /**
     * @var Document[]
     */
    private $documents = [];

    /**
     * @var string
     */
    private $hash = '';

    /**
     * @param Document $document
     */
    public function push(Document $document)
    {
        $this->documents[] = $document;
        $this->regenerateHash();
    }

    /**
     * @return Document|null
     */
    public function pop()
    {
        if (!$this->documents) {
            return;
        }

        $returnValue = array_pop($this->documents);
        $this->regenerateHash();

        return $returnValue;
    }

    /**
     * @return Document|null
     */
    public function getCurrentDocument()
    {
        return end($this->documents) ?: null;
    }

    /**
     * @return Document|null
     */
    public function getMasterDocument()
    {
        if (!$this->documents) {
            return;
        }

        return $this->documents[0];
    }

    /**
     * @param callable $function
     *
     * @return Document|null
     */
    public function findOneBy(callable $function)
    {
        foreach ($this->documents as $document) {
            if ($function($document)) {
                return $document;
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    private function regenerateHash(): void
    {
        $this->hash = '';
        foreach ($this->documents as $document) {
            $this->hash .= $document->getId() . '_';
        }
    }
}
