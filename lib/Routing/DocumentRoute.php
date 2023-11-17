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

namespace Pimcore\Routing;

use Pimcore\Model\Document;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Route;

/**
 * @internal
 */
final class DocumentRoute extends Route implements RouteObjectInterface
{
    protected ?Document $document = null;

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(Document $document): static
    {
        $this->document = $document;

        return $this;
    }

    public function getContent(): ?object
    {
        return $this->getDocument();
    }

    public function getRouteKey(): ?string
    {
        if ($this->document) {
            return sprintf('document_%d', $this->document->getId());
        }

        return null;
    }
}
