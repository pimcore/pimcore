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

namespace Pimcore\Routing;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\UrlSlug;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Route;

class DataObjectRoute extends Route implements RouteObjectInterface
{
    /**
     * @var Concrete
     */
    protected $object;

    /**
     * @var UrlSlug
     */
    protected $slug;

    /**
     * @return Concrete
     */
    public function getObject(): Concrete
    {
        return $this->object;
    }

    /**
     * @param Concrete $object
     *
     * @return $this
     */
    public function setObject(Concrete $object): self
    {
        $this->object = $object;

        return $this;
    }

    /**
     * @return UrlSlug
     */
    public function getSlug(): UrlSlug
    {
        return $this->slug;
    }

    /**
     * @param UrlSlug $slug
     *
     * @return $this
     */
    public function setSlug(UrlSlug $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get the content document this route entry stands for. If non-null,
     * the ControllerClassMapper uses it to identify a controller and
     * the content is passed to the controller.
     *
     * If there is no specific content for this url (i.e. its an "application"
     * page), may return null.
     *
     * @return object the document or entity this route entry points to
     */
    public function getContent()
    {
        return null;
    }

    /**
     * Get the route name.
     *
     * Normal symfony routes do not know their name, the name is only known
     * from the route collection. In the CMF, it is possible to use route
     * documents outside of collections, and thus useful to have routes provide
     * their name.
     *
     * There are no limitations to allowed characters in the name.
     *
     * @return string|null the route name or null to use the default name
     *                     (e.g. from route collection if known)
     */
    public function getRouteKey()
    {
        if ($this->object) {
            return sprintf('data_object_%d_%s', $this->object->getId(), $this->getPath());
        }
    }
}
