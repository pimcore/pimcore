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

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\UrlSlug;
use Pimcore\Model\Site;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Route;

/**
 * @internal
 */
final class DataObjectRoute extends Route implements RouteObjectInterface
{
    protected ?Concrete $object = null;

    protected ?UrlSlug $slug = null;

    protected ?Site $site = null;

    public function getObject(): ?Concrete
    {
        return $this->object;
    }

    /**
     * @return $this
     */
    public function setObject(Concrete $object): static
    {
        $this->object = $object;

        return $this;
    }

    public function getSlug(): ?UrlSlug
    {
        return $this->slug;
    }

    /**
     * @return $this
     */
    public function setSlug(UrlSlug $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }

    /**
     * @return $this
     */
    public function setSite(?Site $site): static
    {
        $this->site = $site;

        return $this;
    }

    public function getContent(): ?object
    {
        return null;
    }

    public function getRouteKey(): ?string
    {
        if ($this->object) {
            return sprintf('data_object_%d_%d_%s', $this->object->getId(), $this->site?->getId(), $this->getPath());
        }

        return null;
    }
}
