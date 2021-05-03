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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Classificationstore;

use Pimcore\Model\DataObject\ClassDefinition;

final class Key
{
    /**
     * @var KeyConfig
     */
    protected $configuration;

    /**
     * @var Group
     */
    protected $group;

    /**
     * @param Group $group
     * @param KeyConfig $configuration
     */
    public function __construct(Group $group, KeyConfig $configuration)
    {
        $this->group = $group;
        $this->configuration = $configuration;
    }

    /**
     * @return KeyConfig
     */
    public function getConfiguration(): KeyConfig
    {
        return $this->configuration;
    }

    /**
     * @return Group
     */
    public function getGroup(): Group
    {
        return $this->group;
    }

    /**
     * @param string|null $language
     * @param bool $ignoreFallbackLanguage
     * @param bool $ignoreDefaultLanguage
     *
     * @return mixed
     */
    public function getValue(
        ?string $language = 'default',
        bool $ignoreFallbackLanguage = false,
        bool $ignoreDefaultLanguage = false
    ) {
        $classificationstore = $this->group->getClassificationStore();

        return $classificationstore->getLocalizedKeyValue(
            $this->group->getConfiguration()->getId(),
            $this->configuration->getId(),
            $language,
            $ignoreFallbackLanguage,
            $ignoreDefaultLanguage
        );
    }

    /**
     * @return ClassDefinition\Data
     */
    public function getFieldDefinition(): ClassDefinition\Data
    {
        return Service::getFieldDefinitionFromKeyConfig($this->configuration);
    }
}
