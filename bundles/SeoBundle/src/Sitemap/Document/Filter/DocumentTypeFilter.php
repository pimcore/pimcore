<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\SeoBundle\Sitemap\Document\Filter;

use Pimcore\Bundle\SeoBundle\Sitemap\Element\FilterInterface;
use Pimcore\Bundle\SeoBundle\Sitemap\Element\GeneratorContextInterface;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;

class DocumentTypeFilter implements FilterInterface
{
    private array $documentTypes = [
        'page',
        'link',
        'hardlink',
    ];

    private array $containerTypes = [
        'page',
        'folder',
        'link',
        'hardlink',
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

    public function canBeAdded(ElementInterface $element, GeneratorContextInterface $context): bool
    {
        if (!$element instanceof Document || $element instanceof Document\Hardlink\Wrapper\WrapperInterface) {
            return false;
        }

        return in_array($element->getType(), $this->documentTypes);
    }

    public function handlesChildren(ElementInterface $element, GeneratorContextInterface $context): bool
    {
        if (!$element instanceof Document) {
            return false;
        }

        return in_array($element->getType(), $this->containerTypes);
    }
}
