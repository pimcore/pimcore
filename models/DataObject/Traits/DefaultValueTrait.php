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

namespace Pimcore\Model\DataObject\Traits;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\DefaultValueGeneratorInterface;
use Pimcore\Model\DataObject\ClassDefinition\Helper\DefaultValueGeneratorResolver;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;

/**
 * @internal
 */
trait DefaultValueTrait
{
    public string $defaultValueGenerator = '';

    abstract protected function doGetDefaultValue(Concrete $object, array $context = []): mixed;

    /**
     *
     * @return mixed $data
     */
    protected function handleDefaultValue(mixed $data, Concrete $object = null, array $params = []): mixed
    {
        // 1. only for create, not on update. otherwise there is no way to null it out anymore.
        if ($params['isUpdate'] ?? true) {
            return $data;
        }

        // 2. we already have a value, no need to look for a default value.
        if (!$this->isEmpty($data)) {
            return $data;
        }

        $owner = $params['owner'] ?? null;

        // 3. if we have an object and a default value generator, use this to create a default value.
        if ($object !== null && !empty($this->defaultValueGenerator)) {
            $defaultValueGenerator = DefaultValueGeneratorResolver::resolveGenerator($this->defaultValueGenerator);

            if ($defaultValueGenerator instanceof DefaultValueGeneratorInterface) {
                $context = array_merge($params['context'] ?? [], match (true) {
                    $owner instanceof Concrete => [
                        'ownerType' => 'object',
                        'fieldname' => $this->getName(),
                    ],
                    $owner instanceof Localizedfield => [
                        'ownerType' => 'localizedfield',
                        'ownerName' => 'localizedfields',
                        'position' => $params['language'],
                        'fieldname' => $this->getName(),
                    ],
                    $owner instanceof \Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData => [
                        'ownerType' => 'fieldcollection',
                        'ownerName' => $owner->getFieldname(),
                        'fieldname' => $this->getName(),
                        'index' => $owner->getIndex(),
                    ],
                    $owner instanceof AbstractData => [
                        'ownerType' => 'objectbrick',
                        'ownerName' => $owner->getFieldname(),
                        'fieldname' => $this->getName(),
                        'index' => $owner->getType(),
                    ],
                    default => [],
                });

                return $defaultValueGenerator->getValue($object, $this, $context);
            }
        }

        $configuredDefaultValue = $this->doGetDefaultValue($object, $params['context'] ?? []);

        // 4. we check first if we even want to work with default values.
        if ($this->isEmpty($configuredDefaultValue)) {
            return $configuredDefaultValue;
        }

        $class = match (true) {
            $owner instanceof Concrete => $owner->getClass(),
            $owner instanceof AbstractData => $owner->getObject()?->getClass(),
            default => null,
        };

        /*
         * 5. if inheritance is enabled and there is no parent value then take the default value.
         * 6. if inheritance is disabled, take the default value.
         */
        if ($class?->getAllowInherit()) {
            try {
                // make sure we get the inherited value of the parent
                $parentValue = DataObject\Service::useInheritedValues(true,
                    fn () => $owner?->getValueFromParent($this->getName(), []),
                );

                if (!$this->isEmpty($parentValue)) {
                    return null;
                }
            } catch (InheritanceParentNotFoundException) {
                // no data from parent available, use the default value
            }
        }

        return $configuredDefaultValue;
    }

    public function getDefaultValueGenerator(): string
    {
        return $this->defaultValueGenerator;
    }

    public function setDefaultValueGenerator(string $defaultValueGenerator): void
    {
        $this->defaultValueGenerator = $defaultValueGenerator;
    }
}
