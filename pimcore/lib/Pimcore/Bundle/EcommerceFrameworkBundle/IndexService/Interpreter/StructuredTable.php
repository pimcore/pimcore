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
use Symfony\Component\OptionsResolver\OptionsResolver;

class StructuredTable implements IInterpreter
{
    use OptionsResolverTrait;

    public function interpret($value, $config = null)
    {
        $config = $this->resolveOptions($config ?? []);

        $getter = 'get' . ucfirst($config['tablerow']) . '__' . ucfirst($config['tablecolumn']);

        if ($value && $value instanceof \Pimcore\Model\Object\Data\StructuredTable) {
            if (isset($config['defaultUnit'])) {
                return $value->$getter() . ' ' . $config['defaultUnit'];
            } else {
                return $value->$getter();
            }
        }

        return null;
    }

    protected function configureOptionsResolver(string $resolverName, OptionsResolver $resolver)
    {
        $resolver->setDefined('defaultUnit');

        foreach (['tablerow', 'tablecolumn'] as $field) {
            $resolver
                ->setDefined($field)
                ->setAllowedTypes($field, ['string', 'int']); // TODO does int make sense?
        }
    }
}
