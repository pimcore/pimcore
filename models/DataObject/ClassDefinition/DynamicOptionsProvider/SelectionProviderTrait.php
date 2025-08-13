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

namespace Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;

/**
 * @internal
 */
trait SelectionProviderTrait
{
    protected function doEnrichDefinitionDefinition(?DataObject\Concrete $object, string $fieldname, string $purpose, int $mode, array $context = []): void
    {
        if ($this->getOptionsProviderType() === Data\OptionsProviderInterface::TYPE_CONFIGURE) {
            return;
        }

        $optionsProvider = DataObject\ClassDefinition\Helper\OptionsProviderResolver::resolveProvider(
            $this->getOptionsProviderClass(),
            $mode
        );
        if ($optionsProvider) {
            $context['object'] = $context['object'] ?? $object;
            if ($object) {
                $context['class'] = $object->getClass();
            }

            $context['fieldname'] = $fieldname;
            if (!isset($context['purpose'])) {
                $context['purpose'] = $purpose;
            }

            $options = DataObject\Service::useInheritedValues(
                true,
                fn () => $optionsProvider->getOptions($context, $this)
            );

            $this->setOptions($options);

            $defaultValue = $optionsProvider->getDefaultValue($context, $this);
            $this->setDefaultValue($defaultValue);

            $hasStaticOptions = $optionsProvider->hasStaticOptions($context, $this);
            $this->dynamicOptions = !$hasStaticOptions;
        }
    }
}
