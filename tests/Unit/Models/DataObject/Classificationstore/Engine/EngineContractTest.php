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

namespace Pimcore\Tests\Unit\Model\DataObject\Classificationstore\Engine;

use Pimcore\Model\DataObject\Classificationstore\Engine\EavEngine;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineFeature;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineInterface;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Exception\UnknownClassificationStoreEngineException;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

/**
 * The shape of the engine contract, so that a later package cannot widen it without saying so.
 */
class EngineContractTest extends TestCase
{
    public function testEngineInterfaceDeclaresExactlyTheExpectedMethods(): void
    {
        $signatures = [];
        foreach ((new ReflectionClass(EngineInterface::class))->getMethods() as $method) {
            $signatures[$method->getName()] = $this->describe($method);
        }

        $this->assertSame([
            'load' => 'void load(Pimcore\Model\DataObject\Classificationstore $classificationStore)',
            'save' => 'void save(Pimcore\Model\DataObject\Classificationstore $classificationStore)',
            'delete' => 'void delete(Pimcore\Model\DataObject\Classificationstore $classificationStore)',
            'createUpdateTables' => 'void createUpdateTables(Pimcore\Model\DataObject\ClassDefinition $class, ?int $storeId)',
            'dropTables' => 'void dropTables(Pimcore\Model\DataObject\ClassDefinition $class)',
            'cleanup' => 'void cleanup()',
            'deleteStoreValues' => 'void deleteStoreValues(int $storeId)',
            'supports' => 'bool supports(Pimcore\Model\DataObject\Classificationstore\Engine\EngineFeature $feature)',
        ], $signatures);
    }

    public function testEverythingInTheEngineNamespaceIsInternal(): void
    {
        foreach ([EngineInterface::class, EngineFeature::class, EngineResolver::class, EavEngine::class] as $name) {
            $this->assertStringContainsString(
                '@internal',
                (string) (new ReflectionClass($name))->getDocComment(),
                $name . ' must carry a class-level @internal'
            );
        }
    }

    public function testTheInterfaceRecordsThatInternalIsAStagingDecision(): void
    {
        $docComment = (string) (new ReflectionClass(EngineInterface::class))->getDocComment();

        $this->assertStringContainsString('staging decision', $docComment);
        $this->assertStringContainsString(
            'Publishing it is a separate decision',
            $docComment,
            'the docblock has to say that @internal is revisited once the interface stops moving'
        );
    }

    public function testEngineFeatureHasExactlyTheFourCases(): void
    {
        $this->assertSame(
            ['MultiValue', 'Variants', 'PerKeyLocalization', 'Relations'],
            array_map(static fn (EngineFeature $feature): string => $feature->name, EngineFeature::cases())
        );
    }

    public function testEavEngineSupportsNoFeature(): void
    {
        // the engine needs no collaborator to answer supports(), and must not open a connection for it
        $engine = (new ReflectionClass(EavEngine::class))->newInstanceWithoutConstructor();

        $this->assertInstanceOf(EngineInterface::class, $engine);

        foreach (EngineFeature::cases() as $feature) {
            $this->assertFalse($engine->supports($feature), $feature->name . ' must not be supported');
        }
    }

    /**
     * An unsupported feature is answered with `false`, not with an exception - this ticket adds no
     * exception for it.
     */
    public function testNoUnsupportedEngineFeatureExceptionIsIntroduced(): void
    {
        $exceptionDirectory = dirname(
            (string) (new ReflectionClass(UnknownClassificationStoreEngineException::class))->getFileName()
        );

        $this->assertFileDoesNotExist($exceptionDirectory . '/UnsupportedEngineFeatureException.php');
    }

    /**
     * The engine was extracted once from a Dao that predated the row diff, which silently gave back
     * the delete-and-reinsert save inside a file the merge had nothing to compare against. The
     * behavioural guard is SaveDiffTest; this is the one that also fires without a database.
     */
    public function testTheEngineCarriesTheDiffBasedSavePath(): void
    {
        $source = $this->engineSource();

        foreach ([
            'persistRows',
            'writeRowDiff',
            'readRowsForUpdate',
            'reconcileLanguageAliases',
            'dataRowDiffers',
            'getWriteBatchByteBudget',
            'FOR UPDATE',
            'ON DUPLICATE KEY UPDATE',
            'KeyGroupRelationCache',
        ] as $marker) {
            $this->assertStringContainsString($marker, $source, $marker . ' is part of the merged save/load path');
        }
    }

    public function testTheEngineDoesNotReintroduceTheDeleteAndReinsertSave(): void
    {
        $source = $this->engineSource();

        $this->assertStringNotContainsString('Helper::upsert', $source, 'the per-row upsert is gone');
        $this->assertStringNotContainsString('getPrimaryKey(', $source, 'the per-row upsert is gone');
        $this->assertStringNotContainsString(
            "'fieldname' => \$fieldname]",
            $source,
            'the save must not clear the whole field before writing it again'
        );
    }

    private function engineSource(): string
    {
        return (string) file_get_contents((string) (new ReflectionClass(EavEngine::class))->getFileName());
    }

    private function describe(ReflectionMethod $method): string
    {
        $parameters = array_map(
            static fn (ReflectionParameter $parameter): string => $parameter->getType() . ' $' . $parameter->getName(),
            $method->getParameters()
        );

        return $method->getReturnType() . ' ' . $method->getName() . '(' . implode(', ', $parameters) . ')';
    }
}
