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

namespace Pimcore\Bundle\ElementDependenciesBundle\EventListener;

use Pimcore\Event\AssetEvents;
use Pimcore\Event\DataObjectEvents;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Bundle\ElementDependenciesBundle\Model\Dependency;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
class SavingDependenciesListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            DataObjectEvents::POST_ADD => 'onElementPostAdd',
            DocumentEvents::POST_ADD => 'onElementPostAdd',
            AssetEvents::POST_ADD => 'onElementPostAdd',

            DataObjectEvents::POST_UPDATE => 'onElementPostUpdate',
            DocumentEvents::POST_UPDATE => 'onElementPostUpdate',
            AssetEvents::POST_UPDATE => 'onElementPostUpdate',
        ];
    }

    /**
     * Save Dependencies
     */
    public function onElementPostUpdate(ElementEventInterface $e): void
    {
        $object = $e->getObject();

        $d = new Dependency();
        $d->setSourceType('object');
        $d->setSourceId($object->getId());

        foreach ($object->resolveDependencies() as $requirement) {
            if ($requirement['id'] == $object->getId() && $requirement['type'] === 'object') {
                // dont't add a reference to yourself
                continue;
            }

            $d->addRequirement($requirement['id'], $requirement['type']);
        }

        $d->save();
    }
}
