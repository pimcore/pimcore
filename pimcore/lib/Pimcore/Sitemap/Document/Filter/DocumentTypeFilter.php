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

namespace Pimcore\Sitemap\Document\Filter;

use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Sitemap\Document\FilterInterface;

class DocumentTypeFilter implements FilterInterface
{
    /**
     * @var array
     */
    private $documentTypes = [
        'page',
        'link',
        'hardlink'
    ];

    /**
     * @var array
     */
    private $containerTypes = [
        'page',
        'folder',
        'link',
        'hardlink'
    ];

    public function __construct(array $documentTypes = null, array $containerTypes = null)
    {
        if (null !== $documentTypes) {
            $this->documentTypes = $documentTypes;
        }

        if (null !== $containerTypes) {
            $this->containerTypes = $containerTypes;
        }
    }

    public function canBeAdded(Document $document, Site $site = null): bool
    {
        return in_array($document->getType(), $this->documentTypes);
    }

    public function handlesChildren(Document $document, Site $site = null): bool
    {
        return in_array($document->getType(), $this->containerTypes);
    }
}
