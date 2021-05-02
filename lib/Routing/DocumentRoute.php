<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
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
    /**
     * @var Document|null
     */
    protected $document;

    /**
     * @return Document|null
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param Document $document
     *
     * @return $this
     */
    public function setDocument($document)
    {
        $this->document = $document;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        return $this->getDocument();
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteKey()
    {
        if ($this->document) {
            return sprintf('document_%d', $this->document->getId());
        }

        return null;
    }
}
