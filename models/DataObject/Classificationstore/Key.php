<?php

declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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
