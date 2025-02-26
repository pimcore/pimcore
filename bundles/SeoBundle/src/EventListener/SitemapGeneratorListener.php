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

namespace Pimcore\Bundle\SeoBundle\EventListener;

use IteratorAggregate;
use Pimcore\Bundle\SeoBundle\PimcoreSeoBundle;
use Pimcore\Bundle\SeoBundle\Sitemap\GeneratorInterface;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SitemapGeneratorListener implements EventSubscriberInterface
{
    /**
     * @var IteratorAggregate|GeneratorInterface[]
     */
    private array|IteratorAggregate $generators;

    public function __construct(array|IteratorAggregate $generators)
    {
        $this->generators = $generators;
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SitemapPopulateEvent::class => 'onPopulateSitemap',
        ];
    }

    public function onPopulateSitemap(SitemapPopulateEvent $event): void
    {
        if (!PimcoreSeoBundle::isInstalled()) {
            return;
        }

        $container = $event->getUrlContainer();
        $section = $event->getSection();

        foreach ($this->generators as $generator) {
            $generator->populate($container, $section);
        }
    }
}
