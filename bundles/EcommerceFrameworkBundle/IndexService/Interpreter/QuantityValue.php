<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Interpreter;

use Pimcore\Bundle\EcommerceFrameworkBundle\Traits\OptionsResolverTrait;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuantityValue implements InterpreterInterface
{
    use OptionsResolverTrait;

    public function interpret($value, $config = null)
    {
        $config = $this->resolveOptions($config ?? []);

        if (!empty($value) && ($value instanceof \Pimcore\Model\DataObject\Data\QuantityValue || $value instanceof \Pimcore\Model\DataObject\Data\InputQuantityValue)) {
            if ($config['onlyValue']) {
                $unit = $value->getUnit();
                $value = $value->getValue();

                if ($unit instanceof Unit && $unit->getFactor()) {
                    $value *= $unit->getFactor();
                }

                return $value;
            } else {
                return $value->__toString();
            }
        }

        return null;
    }

    protected function configureOptionsResolver(string $resolverName, OptionsResolver $resolver)
    {
        $resolver
            ->setDefault('onlyValue', false)
            ->setAllowedTypes('onlyValue', 'bool');
    }
}
