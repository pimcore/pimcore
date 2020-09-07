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

namespace Pimcore\Document\Tag\NamingStrategy\Migration;

use Pimcore\Model\Document;

/**
 * @deprecated
 */
final class MappingError
{
    /**
     * @var int
     */
    private $documentId;

    /**
     * @var string
     */
    private $documentPath;

    /**
     * @var \Throwable
     */
    private $exception;

    /**
     * @param Document $document
     * @param \Throwable $exception
     */
    public function __construct(Document $document, \Throwable $exception)
    {
        $this->documentId = $document->getId();
        $this->documentPath = $document->getRealFullPath();
        $this->exception = $exception;
    }

    /**
     * @return int
     */
    public function getDocumentId(): int
    {
        return $this->documentId;
    }

    /**
     * @return string
     */
    public function getDocumentPath(): string
    {
        return $this->documentPath;
    }

    /**
     * @return \Throwable
     */
    public function getException(): \Throwable
    {
        return $this->exception;
    }
}
