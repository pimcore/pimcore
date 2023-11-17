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

namespace Pimcore\Model\DataObject\ClassDefinition\DynamicOptionsProvider;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;

/**
 * @internal
 */
trait SelectionProviderTrait
{
    protected function doEnrichDefinitionDefinition(/*?Concrete */ ?DataObject\Concrete $object, string $fieldname, string $purpose, int $mode, /**  array */ array $context = []): void
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

            $options = DataObject\Service::useInheritedValues(true,
                fn () => $optionsProvider->getOptions($context, $this),
            );

            $this->setOptions($options);

            if ($this instanceof Data\Select) {
                $defaultValue = $optionsProvider->{'getDefaultValue'}($context, $this);
                $this->setDefaultValue($defaultValue);
            }

            $hasStaticOptions = $optionsProvider->{'hasStaticOptions'}($context, $this);
            $this->dynamicOptions = !$hasStaticOptions;
        }
    }
}
