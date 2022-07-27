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

namespace Pimcore\Sitemap\EventListener;

use Pimcore\Sitemap\GeneratorInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SitemapGeneratorListener implements EventSubscriberInterface
{
    /**
     * @var \Iterator|GeneratorInterface[]
     */
    private $generators;

    /**
     * @param \Iterator|GeneratorInterface[] $generators
     *
     * TODO type hint against iterable after dropping PHP 7.0 support
     */
    public function __construct($generators)
    {
        $this->generators = $generators;
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents()// : array
    {
        return [
            SitemapPopulateEvent::ON_SITEMAP_POPULATE => 'onPopulateSitemap',
        ];
    }

    public function onPopulateSitemap(SitemapPopulateEvent $event)
    {
        $container = $event->getUrlContainer();
        $section = $event->getSection();

        foreach ($this->generators as $generator) {
            $generator->populate($container, $section);
        }
    }
}
