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

namespace Pimcore\Bundle\CoreBundle\OptionsProvider;

use Exception;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\SelectOptions\Config;
use Pimcore\Model\DataObject\SelectOptions\Data\SelectOption;

class SelectOptionsOptionsProvider implements SelectOptionsProviderInterface
{
    public function getOptions(array $context, Data $fieldDefinition): array
    {
        if (!$fieldDefinition instanceof Data\OptionsProviderInterface) {
            return [];
        }

        $configurationId = $fieldDefinition->getOptionsProviderData();
        $selectOptionsConfiguration = Config::getById($configurationId);
        if ($selectOptionsConfiguration === null) {
            throw new Exception('Missing select options configuration ' . $configurationId, 1677137682677);
        }

        return array_map(
            fn (SelectOption $selectOption) => [
                'value' => $selectOption->getValue(),
                'key' => $selectOption->getLabel(),
            ],
            $selectOptionsConfiguration->getSelectOptions(),
        );
    }

    public function hasStaticOptions(array $context, Data $fieldDefinition): bool
    {
        return true;
    }

    public function getDefaultValue(array $context, Data $fieldDefinition): ?string
    {
        if ($fieldDefinition instanceof Data\Select) {
            return $fieldDefinition->getDefaultValue();
        }

        return null;
    }
}
