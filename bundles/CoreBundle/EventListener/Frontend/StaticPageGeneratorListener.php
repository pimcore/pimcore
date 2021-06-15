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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Pimcore\Document\StaticPageGenerator;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Logger;
use Pimcore\Model\Document\PageSnippet;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
class StaticPageGeneratorListener implements EventSubscriberInterface
{
    /**
     * @var StaticPageGenerator
     */
    protected $staticPageGenerator;

    public function __construct(StaticPageGenerator $staticPageGenerator)
    {
        $this->staticPageGenerator = $staticPageGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            DocumentEvents::POST_ADD => 'onPostAddUpdateDocument',
            DocumentEvents::POST_DELETE => 'onPostDeleteDocument',
            DocumentEvents::POST_UPDATE => 'onPostAddUpdateDocument',
        ];
    }

    /**
     * @param DocumentEvent $e
     */
    public function onPostAddUpdateDocument(DocumentEvent $e)
    {
        $document = $e->getDocument();

        if($e->hasArgument('saveVersionOnly') || $e->hasArgument('autoSave')) {
            return;
        }

        if ($document instanceof PageSnippet) {
            try {
                if ($document->getStaticGeneratorEnabled()) {
                    if ($document->isPublished()) {
                        $this->staticPageGenerator->generate($document);
                    } else {
                        $this->staticPageGenerator->remove($document);
                    }
                } elseif (!is_null($document->getStaticGeneratorEnabled())
                    && $this->staticPageGenerator->pageExists($document)) {
                    $this->staticPageGenerator->remove($document);
                }
            } Catch(\Exception $e) {
                Logger::error($e);

                return;
            }

        }
    }

    /**
     * @param DocumentEvent $e
     *
     * @throws \League\Flysystem\FilesystemException
     */
    public function onPostDeleteDocument(DocumentEvent $e)
    {
        $document = $e->getDocument();
        if ($document instanceof PageSnippet && $document->getStaticGeneratorEnabled()) {
            try {
                $this->staticPageGenerator->remove($document);
            } Catch(\Exception $e) {
                Logger::error($e);

                return;
            }
        }
    }
}
