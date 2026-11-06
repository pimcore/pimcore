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

use Pimcore\Model\DataObject\Classificationstore\Engine\DocumentEngine;
use Pimcore\Model\DataObject\Classificationstore\Engine\EavEngine;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineFeature;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineInterface;
use Pimcore\Model\DataObject\Classificationstore\Engine\EngineResolver;
use Pimcore\Model\DataObject\Exception\UnknownClassificationStoreEngineException;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionClass;
use ReflectionException;
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
        $names = [
            EngineInterface::class,
            EngineFeature::class,
            EngineResolver::class,
            EavEngine::class,
            DocumentEngine::class,
        ];

        foreach ($names as $name) {
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

    public function testEavEngineSupportsPerKeyLocalizationOnly(): void
    {
        $this->assertSupportsPerKeyLocalizationOnly(EavEngine::class);
    }

    /**
     * The document engine offers the one capability the EAV engine offers - per key localization is
     * a property of the key configuration, not of the storage - and Relations on top of it: a
     * document holds whatever normalize() returns, so a relation's element descriptors need no
     * addressing of their own. Neither MultiValue nor Variants has a model API for a qualified
     * value yet, so those two stay false for both engines.
     */
    public function testDocumentEngineSupportsPerKeyLocalizationAndRelations(): void
    {
        // the engine needs no collaborator to answer supports(), and must not open a connection for it
        $instance = (new ReflectionClass(DocumentEngine::class))->newInstanceWithoutConstructor();

        $this->assertInstanceOf(EngineInterface::class, $instance);

        foreach ([EngineFeature::PerKeyLocalization, EngineFeature::Relations] as $feature) {
            $this->assertTrue($instance->supports($feature), $feature->name . ' must be supported');
        }

        foreach ([EngineFeature::MultiValue, EngineFeature::Variants] as $feature) {
            $this->assertFalse($instance->supports($feature), $feature->name . ' must not be supported');
        }
    }

    /**
     * @param class-string $engine
     */
    private function assertSupportsPerKeyLocalizationOnly(string $engine): void
    {
        // the engine needs no collaborator to answer supports(), and must not open a connection for it
        $instance = (new ReflectionClass($engine))->newInstanceWithoutConstructor();

        $this->assertInstanceOf(EngineInterface::class, $instance);

        $this->assertTrue(
            $instance->supports(EngineFeature::PerKeyLocalization),
            'PerKeyLocalization must be supported'
        );

        foreach ([EngineFeature::MultiValue, EngineFeature::Variants, EngineFeature::Relations] as $feature) {
            $this->assertFalse($instance->supports($feature), $feature->name . ' must not be supported');
        }
    }

    /**
     * The write budget of the document engine rests on the document being canonical and on the save
     * comparing the serialized document against the stored one. The behavioural guard is
     * DocumentEngineTest; this is the one that also fires without a database.
     */
    public function testTheDocumentEngineComparesSerializedDocumentsInsteadOfRewritingThem(): void
    {
        $source = $this->engineSource(DocumentEngine::class);

        foreach ([
            'persistDocuments',
            'writeDocumentDiff',
            'readRowsForUpdate',
            'documentDiffers',
            'getLanguageAlias',
            'ksort(',
            'FOR UPDATE',
            'ON DUPLICATE KEY UPDATE',
            'KeyGroupRelationCache',
        ] as $marker) {
            $this->assertStringContainsString($marker, $source, $marker . ' is part of the document save/load path');
        }

        $this->assertStringContainsString(
            "(string) \$storedRow['values'] !== \$document['values']",
            $source,
            'the save compares the serialized document against the stored string'
        );
    }

    /**
     * The group of a document store lives in the document table alone - a second table holding the
     * active groups is what the engine exists to do without.
     */
    public function testTheDocumentEngineNeverTouchesTheEavTables(): void
    {
        $source = $this->engineSource(DocumentEngine::class);

        $this->assertStringNotContainsString('object_classificationstore_groups_', $source);
        $this->assertStringNotContainsString('object_classificationstore_data_', $source);
    }

    /**
     * A write of a value the engine cannot store is refused rather than skipped or written into
     * whatever column its normalized form happens to fit - which is what the exception exists for.
     */
    public function testAnUnsupportedFeatureIsRefusedWithItsOwnException(): void
    {
        $exceptionDirectory = dirname(
            (string) (new ReflectionClass(UnknownClassificationStoreEngineException::class))->getFileName()
        );

        $this->assertFileExists($exceptionDirectory . '/UnsupportedEngineFeatureException.php');

        foreach ([EavEngine::class, DocumentEngine::class] as $engine) {
            $this->assertStringContainsString(
                'assertStorable',
                $this->engineSource($engine),
                $engine . ' has to refuse a value its storage cannot address'
            );
        }
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

        // The shape this forbids is the pre-diff save, which cleared the whole (object, fieldname)
        // pair and wrote it again. Clearing a pair is also what one method of this engine exists to
        // do - {@see EavEngine::deletePairRows()}, the hand-off that drops the EAV rows of a pair
        // the document engine has taken over - so that method is left out and the rest of the file,
        // including every method added to it later, still has to stay clear of the shape.
        $this->assertStringNotContainsString(
            "'fieldname' => \$fieldname]",
            $this->engineSourceWithout(EavEngine::class, 'deletePairRows'),
            'the save must not clear the whole field before writing it again'
        );
    }

    /**
     * @param class-string $engine
     */
    private function engineSource(string $engine = EavEngine::class): string
    {
        return (string) file_get_contents((string) (new ReflectionClass($engine))->getFileName());
    }

    /**
     * The engine source with the bodies of the named methods removed, for a guard that holds over
     * everything but a method whose own purpose is the shape being guarded against.
     *
     * A renamed or removed method fails here rather than silently stopping the exclusion:
     * {@see ReflectionClass::getMethod()} throws when the name is gone.
     *
     * @param class-string $engine
     *
     * @throws ReflectionException
     */
    private function engineSourceWithout(string $engine, string ...$methods): string
    {
        $class = new ReflectionClass($engine);
        $lines = (array) file((string) $class->getFileName());

        foreach ($methods as $name) {
            $method = $class->getMethod($name);

            for ($line = $method->getStartLine(); $line <= $method->getEndLine(); $line++) {
                unset($lines[$line - 1]);
            }
        }

        return implode('', $lines);
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
