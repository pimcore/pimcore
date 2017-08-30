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
use Pimcore\Model\DataObject\AbstractObject;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ObjectValue implements IInterpreter
{
    use OptionsResolverTrait;

    public function interpret($value, $config = null)
    {
        $config     = $this->resolveOptions($config ?? []);
        $targetList = $this->resolveOptions($config['target'], 'target');

        if ($value instanceof AbstractObject) {
            $fieldGetter = 'get' . ucfirst($targetList['fieldname']);

            if (method_exists($value, $fieldGetter)) {
                return $value->$fieldGetter($targetList['locale']);
            }
        }

        return null;
    }

    protected function configureOptionsResolver(string $resolverName, OptionsResolver $resolver)
    {
        if ('default' === $resolverName) {
            $resolver
                ->setDefined('target')
                ->setAllowedTypes('target', 'array');
        } elseif ('target' === $resolverName) {
            $fields = ['fieldname', 'locale'];

            $resolver->setRequired($fields);
            foreach ($fields as $field) {
                $resolver->setAllowedTypes($field, 'string');
            }
        } else {
            throw new \InvalidArgumentException(sprintf('Resolver with name "%s" is not defined', $resolverName));
        }
    }
}
