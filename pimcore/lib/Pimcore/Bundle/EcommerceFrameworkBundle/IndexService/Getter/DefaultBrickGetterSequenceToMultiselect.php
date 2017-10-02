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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter;

use Pimcore\Bundle\EcommerceFrameworkBundle\Traits\OptionsResolverTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DefaultBrickGetterSequenceToMultiselect implements IGetter
{
    use OptionsResolverTrait;

    public function get($object, $config = null)
    {
        $config = $this->resolveOptions($config ?? []);
        $sourceList = $config['source'];

        // normalize single entry to list
        if (isset($sourceList['brickfield'])) {
            $sourceList = [$sourceList];
        }

        $values = [];
        foreach ($sourceList as $source) {
            $source = $this->resolveOptions((array)$source, 'source');

            $brickContainerGetter = 'get' . ucfirst($source['brickfield']);

            if (method_exists($object, $brickContainerGetter)) {
                $brickContainer = $object->$brickContainerGetter();

                $brickGetter = 'get' . ucfirst($source['bricktype']);
                $brick = $brickContainer->$brickGetter();
                if ($brick) {
                    $fieldGetter = 'get' . ucfirst($source['fieldname']);
                    $value = $brick->$fieldGetter();

                    if ($source['invert']) {
                        $value = !$value;
                    }

                    if ($value) {
                        if (is_bool($value) || $source['forceBool']) {
                            $values[] = $source['fieldname'];
                        } else {
                            $values[] = $value;
                        }
                    }
                }
            } else {
                $fieldGetter = 'get' . ucfirst($source['fieldname']);
                if (method_exists($object, $fieldGetter)) {
                    $value = $object->$fieldGetter();

                    if ($source['invert']) {
                        $value = !$value;
                    }

                    if ($value) {
                        if (is_bool($value) || $source['forceBool']) {
                            $values[] = $source['fieldname'];
                        } else {
                            $values[] = $value;
                        }
                    }
                }
            }
        }
    }

    protected function configureOptionsResolver(string $resolverName, OptionsResolver $resolver)
    {
        if ('default' === $resolverName) {
            $resolver
                ->setDefined('source')
                ->setAllowedTypes('source', 'array');
        } elseif ('source' === $resolverName) {
            // brickfield, bricktype, fieldname
            DefaultBrickGetter::setupBrickGetterOptionsResolver($resolver);

            $resolver->setDefaults([
                'invert' => false,
                'forceBool' => false
            ]);

            foreach (['invert', 'forceBool'] as $boolType) {
                $resolver->setAllowedTypes($boolType, 'bool');
            }
        } else {
            throw new \InvalidArgumentException(sprintf('Resolver with name "%s" is not defined', $resolverName));
        }
    }
}
