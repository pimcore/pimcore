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

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker\WorkerInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Traits\OptionsResolverTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IdList implements InterpreterInterface
{
    use OptionsResolverTrait;

    public function interpret($value, $config = null)
    {
        $config = $this->resolveOptions($config ?? []);

        $ids = [];

        if (is_array($value)) {
            foreach ($value as $val) {
                if (method_exists($val, 'getId')) {
                    $ids[] = $val->getId();
                }
            }
        } elseif (method_exists($value, 'getId')) {
            $ids[] = $value->getId();
        }

        $delimiter = ',';

        if ($config['multiSelectEncoded']) {
            $delimiter = WorkerInterface::MULTISELECT_DELIMITER;
        }

        $ids = implode($delimiter, $ids);

        return $ids ? $delimiter . $ids . $delimiter : null;
    }

    protected function configureOptionsResolver(string $resolverName, OptionsResolver $resolver)
    {
        $resolver
            ->setDefault('multiSelectEncoded', false)
            ->setAllowedTypes('multiSelectEncoded', 'bool');
    }
}
