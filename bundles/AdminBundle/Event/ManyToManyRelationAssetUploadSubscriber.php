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

namespace Pimcore\Bundle\AdminBundle\Event;

use Pimcore\Bundle\AdminBundle\Helper\AssetTypeHelper;
use Pimcore\Bundle\AdminBundle\Helper\ManyToManyRelationValidator;
use Pimcore\Event\AssetEvents;
use Pimcore\Event\Model\Asset\ResolveUploadTargetEvent;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ValidationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ManyToManyRelationAssetUploadSubscriber implements EventSubscriberInterface
{
    private AssetTypeHelper $assetTypeHelper;

    private ManyToManyRelationValidator $manyToManyRelationValidator;

    public function __construct(
        AssetTypeHelper $assetTypeHelper,
        ManyToManyRelationValidator $manyToManyRelationValidator
    ) {
        $this->assetTypeHelper = $assetTypeHelper;
        $this->manyToManyRelationValidator = $manyToManyRelationValidator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            AssetEvents::RESOLVE_UPLOAD_TARGET => 'onAssetUpload',
        ];
    }

    /**
     * @throws ValidationException
     */
    public function onAssetUpload(ResolveUploadTargetEvent $event): void
    {
        $context = $event->getContext();
        if ('object' === $context['containerType'] && $object = Concrete::getById($context['objectId'])) {
            $fieldDefinition = $object->getClass()?->getFieldDefinition($context['fieldname']);
            if (!$fieldDefinition instanceof ManyToManyRelation) {
                return;
            }

            $type = $this->assetTypeHelper->getAssetType($event->getFilename(), $context['sourcePath']);
            if (!$this->manyToManyRelationValidator->isValidAssetType($fieldDefinition, $type)) {
                throw new ValidationException(sprintf('Invalid relation in field `%s` [type: %s]', $context['fieldname'], $type));
            }
        }
    }
}
