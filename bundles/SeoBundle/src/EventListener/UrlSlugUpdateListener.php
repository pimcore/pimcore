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

use Pimcore;
use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Bundle\SeoBundle\PimcoreSeoBundle;
use Pimcore\Db;
use Pimcore\Event\Model\DataObject\ClassDefinition\UrlSlugEvent;
use Pimcore\Event\UrlSlugEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UrlSlugUpdateListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            UrlSlugEvents::POST_SAVE => 'onURLSlugUpdate',
        ];
    }

    public function onURLSlugUpdate(UrlSlugEvent $event): void
    {
        if (!PimcoreSeoBundle::isInstalled()) {
            return;
        }

        $pimcore_seo_redirects = Pimcore::getContainer()->getParameter('pimcore_seo.redirects');
        $data = $event->getData();
        // check for previous slugs and create redirects
        if (!$pimcore_seo_redirects['auto_create_redirects']) {
            return;
        }

        $db = Db::get();
        foreach ($data as $slug) {
            if ($previousSlug = $slug->getPreviousSlug()) {
                if ($previousSlug === $slug->getSlug() || !$slug->getSlug()) {
                    continue;
                }

                $checkSql = 'SELECT id FROM redirects WHERE source = :sourcePath AND `type` = :typeAuto';
                if ($slug->getSiteId()) {
                    $checkSql .= ' AND sourceSite = ' . $db->quote($slug->getSiteId());
                } else {
                    $checkSql .= ' AND sourceSite IS NULL';
                }

                $existingCheck = $db->fetchOne($checkSql, ['sourcePath' => $previousSlug, 'typeAuto' => Redirect::TYPE_AUTO_CREATE]);
                if (!$existingCheck) {
                    $redirect = new Redirect();
                    $redirect->setType(Redirect::TYPE_AUTO_CREATE);
                    $redirect->setRegex(false);
                    $redirect->setTarget($slug->getSlug());
                    $redirect->setSource($previousSlug);
                    $redirect->setStatusCode(301);
                    $redirect->setExpiry(time() + 86400 * 365); // this entry is removed automatically after 1 year

                    if ($slug->getSiteId()) {
                        $redirect->setSourceSite($slug->getSiteId());
                        $redirect->setTargetSite($slug->getSiteId());
                    }

                    $redirect->save();
                }

                $slug->setPreviousSlug(null);
            }
        }
    }
}
