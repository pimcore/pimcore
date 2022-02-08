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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\CoreBundle\EventListener;


use Pimcore\Event\AssetEvents;
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Model\Asset;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use enshrined\svgSanitize\Sanitizer;

/**
 * @internal
 */
class AssetSanitizationListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            AssetEvents::PRE_ADD => 'sanitizeAsset',
            AssetEvents::PRE_UPDATE => 'sanitizeAsset',
        ];
    }

    /**
     * @param ElementEventInterface $e
     */
    public function sanitizeAsset(ElementEventInterface $e)
    {
        $element = $e->getElement();
        if ($element instanceof Asset) {
            $mime = mime_content_type($element->getStream());

            if ($mime == 'image/svg+xml') {
                $sanitizedData = $this->sanitizeSVG($element->getData());
                $element->setData($sanitizedData);
            }

        }
    }

    /**
     * @param string $fileContent
     *
     * @return string
     *
     * @throws \Exception
     */

    protected function sanitizeSVG(string $fileContent)
    {
        $sanitizer = new Sanitizer();
        $sanitizedFileContent = $sanitizer->sanitize($fileContent);

        if (!$sanitizedFileContent) {
            throw new \Exception('SVG Sanitization failed, probably due badly formatted XML.');
        }

        return $sanitizedFileContent;
    }
}
