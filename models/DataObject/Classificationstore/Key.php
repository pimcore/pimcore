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

namespace Pimcore\Model\DataObject\Classificationstore;

use Pimcore\Model\DataObject\ClassDefinition;

final class Key
{
    protected KeyConfig $configuration;

    protected Group $group;

    public function __construct(Group $group, KeyConfig $configuration)
    {
        $this->group = $group;
        $this->configuration = $configuration;
    }

    public function getConfiguration(): KeyConfig
    {
        return $this->configuration;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function getValue(
        ?string $language = 'default',
        bool $ignoreFallbackLanguage = false,
        bool $ignoreDefaultLanguage = false
    ): mixed {
        $classificationstore = $this->group->getClassificationStore();

        return $classificationstore->getLocalizedKeyValue(
            $this->group->getConfiguration()->getId(),
            $this->configuration->getId(),
            $language,
            $ignoreFallbackLanguage,
            $ignoreDefaultLanguage
        );
    }

    public function getFieldDefinition(): ClassDefinition\Data
    {
        return Service::getFieldDefinitionFromKeyConfig($this->configuration);
    }
}
