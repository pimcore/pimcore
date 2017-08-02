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
use Pimcore\Model\Object\Data\StructuredTable;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DefaultStructuredTable implements IInterpreter
{
    use OptionsResolverTrait;

    public function interpret($value, $config = null)
    {
        $config = $this->resolveOptions($config ?? []);

        if ($value instanceof StructuredTable) {
            $data = $value->getData();

            return $data[$config['row']][$config['column']];
        }

        return null;
    }

    protected function configureOptionsResolver(string $resolverName, OptionsResolver $resolver)
    {
        foreach (['column', 'row'] as $field) {
            $resolver
                ->setDefined($field)
                ->setAllowedTypes($field, ['string', 'int']);
        }
    }
}
