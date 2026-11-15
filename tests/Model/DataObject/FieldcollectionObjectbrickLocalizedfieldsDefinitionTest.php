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

namespace Pimcore\Tests\Model\DataObject;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Tests\Support\Test\ModelTestCase;

/**
 * Regression test: a field collection / object brick layout with more than one localizedfields container gets
 * aggregated into the first container on every setLayoutDefinitions() call. The referenced fields of that first
 * container must be reset before aggregating, otherwise each save appends the other containers again.
 *
 * @group model.dataobject.definition
 */
class FieldcollectionObjectbrickLocalizedfieldsDefinitionTest extends ModelTestCase
{
    private const ITERATIONS = 3;

    public function testFieldcollectionDefinitionDoesNotAccumulateReferencedLocalizedFields(): void
    {
        $definition = new Fieldcollection\Definition();
        $definition->setKey('unittestlfaggregation');

        $this->assertRepeatedAggregationIsStable($definition);
    }

    public function testObjectbrickDefinitionDoesNotAccumulateReferencedLocalizedFields(): void
    {
        $definition = new Objectbrick\Definition();
        $definition->setKey('unittestlfaggregation');

        $this->assertRepeatedAggregationIsStable($definition);
    }

    private function assertRepeatedAggregationIsStable(Fieldcollection\Definition $definition): void
    {
        // the same layout instance is reused on purpose: after the first call its first container already holds the
        // second one as referenced field, which is exactly the state that used to be aggregated again
        $layout = $this->createLayoutWithTwoLocalizedFieldsContainers();

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $definition->setLayoutDefinitions($layout);
            $this->assertSingleReferencedContainer($definition);
        }

        // the definition file stores the layout including the referenced fields of the first container
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $definition = $this->exportAndReload($definition);
            $this->assertSingleReferencedContainer($definition);

            $definition->setLayoutDefinitions($definition->getLayoutDefinitions());
            $this->assertSingleReferencedContainer($definition);
        }
    }

    private function assertSingleReferencedContainer(Fieldcollection\Definition $definition): void
    {
        $localizedFields = $definition->getFieldDefinition('localizedfields');
        $this->assertInstanceOf(ClassDefinition\Data\Localizedfields::class, $localizedFields);

        $referencedFields = $localizedFields->getReferencedFields();
        $this->assertCount(1, $referencedFields);
        $this->assertInstanceOf(ClassDefinition\Data\Localizedfields::class, $referencedFields[0]);
        $this->assertSame(['lfield2'], array_keys($referencedFields[0]->getFieldDefinitions()));

        $this->assertSame(['lfield1', 'lfield2'], array_keys($localizedFields->getFieldDefinitions()));
        $this->assertInstanceOf(ClassDefinition\Data\Input::class, $definition->getFieldDefinition('lfield1'));
        $this->assertInstanceOf(ClassDefinition\Data\Input::class, $definition->getFieldDefinition('lfield2'));
    }

    /**
     * Mirrors what Fieldcollection\Definition::dump() / Objectbrick\Definition::dump() write to the definition file
     * and what getByKey() loads from it.
     */
    private function exportAndReload(Fieldcollection\Definition $definition): Fieldcollection\Definition
    {
        /** @var Fieldcollection\Definition $clone */
        $clone = DataObject\Service::cloneDefinition($definition);
        $clone->setDao(null);
        ClassDefinition::cleanupForExport($clone->layoutDefinitions);

        $file = tempnam(sys_get_temp_dir(), 'pimcore_definition_');
        file_put_contents($file, '<?php return ' . var_export($clone, true) . ";\n");

        try {
            $reloaded = include $file;
        } finally {
            unlink($file);
        }

        $this->assertInstanceOf(get_class($definition), $reloaded);

        return $reloaded;
    }

    private function createLayoutWithTwoLocalizedFieldsContainers(): ClassDefinition\Layout\Panel
    {
        $root = new ClassDefinition\Layout\Panel();
        $root->setName('pimcore_root');

        foreach (['lfield1', 'lfield2'] as $fieldName) {
            $input = new ClassDefinition\Data\Input();
            $input->setName($fieldName);

            $localizedFields = new ClassDefinition\Data\Localizedfields();
            $localizedFields->setName('localizedfields');
            $localizedFields->addChild($input);

            $root->addChild($localizedFields);
        }

        return $root;
    }
}
