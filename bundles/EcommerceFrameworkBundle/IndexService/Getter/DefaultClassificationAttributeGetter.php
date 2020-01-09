<?php

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Getter;

use Pimcore\Bundle\EcommerceFrameworkBundle\Traits\OptionsResolverTrait;
use Pimcore\Model\DataObject\Classificationstore;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DefaultClassificationAttributeGetter implements GetterInterface
{
    use OptionsResolverTrait;

    /**
     * gets a classification store attribute of an object using provided getter_options (key_id, group_id, fieldname)
     * ** key_id    - id of the classification store attribute
     * ** group_id  - id of the group related to the id | key can occur multiple times in classification store field through multiple groups
     * ** fieldname - name of the field upon which the classification store is saved on the specific object [defaults to attributes]
     * note that this getter does not support localization at the moment
     *
     * @param object $object
     * @param array|null $config
     *
     * @return mixed
     */
    public function get($object, $config = null)
    {
        $config = $this->resolveOptions($config ?? []);
        $sourceList = $config['source'];

        foreach ($sourceList as $source) {
            $attributeGetter = 'get' . ucfirst($source['fieldname']);
            if (!method_exists($object, $attributeGetter) || !($classificationStore = $object->$attributeGetter()) instanceof Classificationstore) {
                continue;
            }
            /** @var Classificationstore $classificationStore */
            $val = $classificationStore->getLocalizedKeyValue($source['group_id'], $source['key_id']);

            if ($val !== null) {
                return $val;
            }
        }

        return null;
    }

    protected function configureOptionsResolver(string $resolverName, OptionsResolver $resolver)
    {
        if ('default' === $resolverName) {
            $resolver->setRequired('source');
            $resolver->setAllowedTypes('source', 'array');
        } elseif ('source' === $resolverName) {
            foreach (['key_id', 'group_id'] as $field) {
                $resolver->setRequired($field);
                $resolver->setAllowedTypes($field, 'int');
            }

            $resolver->setRequired('fieldname');
            $resolver->setDefault('fieldname', 'attributes');
            $resolver->setAllowedTypes('fieldname', 'string');
        } else {
            throw new \InvalidArgumentException(sprintf('Resolver with name "%s" is not defined', $resolverName));
        }
    }
}
