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

namespace Pimcore\Bundle\CoreBundle\OptionsProvider;

use Exception;
use Pimcore;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider\SelectOptionsProviderInterface;
use Pimcore\Model\DataObject\SelectOptions\Config;
use Pimcore\Model\DataObject\SelectOptions\Data\SelectOption;
use Pimcore\Tool\Admin;

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

        $translator = Pimcore::getContainer()->get('translator');
        $currentUserLocale = Admin::getCurrentUser()?->getLanguage();
        if ($currentUserLocale) {
            $translator->setLocale($currentUserLocale);
        }

        return array_map(
            fn (SelectOption $selectOption) => [
                'value' => $selectOption->getValue(),
                'key' => $translator->trans($selectOption->getLabel(), [], 'admin'),
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
