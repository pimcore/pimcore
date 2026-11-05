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

namespace Pimcore\Tests\Unit\Model\DataObject\Classificationstore;

use Pimcore;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Classificationstore\KeyFilter;
use Pimcore\Model\DataObject\Classificationstore\KeyTypeSupport;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Tests\Support\Test\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RegexIterator;
use SplFileInfo;

/**
 * Which field types a classification store key may be defined with, and what the two value columns
 * of an EAV row can hold of them.
 *
 * The accepted set is enumerated from the shipped field types rather than written down here, so a
 * type file added to core is accepted by this very test run without the predicate being edited.
 * The exclusions are names, because they are the one part that cannot be derived.
 */
class KeyTypeSupportTest extends TestCase
{
    /**
     * The eight types a key is never defined with. {@see KeyTypeSupport} carries the reason of each.
     */
    private const EXCLUDED_TYPES = [
        'block',
        'objectbricks',
        'fieldcollections',
        'localizedfields',
        'classificationstore',
        'reverseObjectRelation',
        'urlSlug',
        'calculatedValue',
    ];

    /**
     * The types the document engine unlocks: accepted, and held by no value column. The relation
     * types are accepted and structured as well, and are a ticket of their own.
     */
    private const STRUCTURED_TYPES = [
        'consent', 'dateRange', 'externalImage', 'geobounds', 'geopoint', 'geopolygon', 'geopolyline',
        'hotspotimage', 'imageGallery', 'link', 'numericRange', 'structuredTable', 'video',
    ];

    private const ENCRYPTED_FIELD_TYPE = 'encryptedField';

    public function testEveryShippedFieldTypeIsAcceptedExceptTheEightExclusions(): void
    {
        $support = $this->support();
        $seen = [];

        foreach ($this->shippedFieldTypes() as $type) {
            $seen[] = $type;
            $this->assertSame(
                !in_array($type, self::EXCLUDED_TYPES, true),
                $support->isAcceptedType($type),
                $type . ' is answered wrongly by the acceptance predicate'
            );
        }

        // the walk has to have reached the exclusions, or the assertion above never ran for them
        $this->assertSame([], array_diff(self::EXCLUDED_TYPES, $seen));
    }

    public function testATypeNoLoaderResolvesIsNotAccepted(): void
    {
        $this->assertFalse($this->support()->isAcceptedType('noSuchFieldType'));
    }

    public function testAScalarNormalisingTypeAndAMarshalledTypeAreHeldInTheValueColumns(): void
    {
        $support = $this->support();

        // declares a scalar-compatible normalize() return type
        foreach (['input', 'select', 'numeric', 'checkbox', 'date', 'datetime', 'time'] as $type) {
            $this->assertTrue($support->isHeldInValueColumns($type), $type . ' normalizes to a scalar');
        }

        // a classificationstore marshaller packs the value into the two columns - the set
        // bundles/CoreBundle/config/marshaller.yaml registers, which names one key no shipped type
        // answers (inputQuantityValueRange) and is asserted as the marshaller list it is
        foreach ([
            'booleanSelect', 'countrymultiselect', 'image', 'inputQuantityValue', 'inputQuantityValueRange',
            'languagemultiselect', 'multiselect', 'quantityValue', 'quantityValueRange', 'rgbaColor', 'table',
        ] as $type) {
            $this->assertTrue($support->isHeldInValueColumns($type), $type . ' has a classificationstore marshaller');
        }
    }

    public function testTheThirteenUnlockedTypesAreAcceptedAndHeldByNoValueColumn(): void
    {
        $support = $this->support();

        foreach (self::STRUCTURED_TYPES as $type) {
            $this->assertTrue($support->isAcceptedType($type), $type . ' is part of the accepted set');
            $this->assertFalse(
                $support->isHeldInValueColumns($type),
                $type . ' has no classificationstore marshaller and does not normalize to a scalar'
            );
        }
    }

    /**
     * The check follows an encryptedField into its delegate instead of stopping at the marshaller
     * the encryptedField itself has: that marshaller encrypts what the delegate's marshaller packed,
     * so a delegate with no marshaller leaves it nothing to encrypt and stores null.
     */
    public function testAnEncryptedFieldIsHeldExactlyWhenItsDelegateIs(): void
    {
        $support = $this->support();

        $this->assertTrue($support->isHeldInValueColumns(self::ENCRYPTED_FIELD_TYPE, 'input'));
        $this->assertTrue($support->isHeldInValueColumns(self::ENCRYPTED_FIELD_TYPE, 'image'));
        $this->assertFalse($support->isHeldInValueColumns(self::ENCRYPTED_FIELD_TYPE, 'geopoint'));

        // no delegate configured normalizes the plain value unchanged, which the column takes
        $this->assertTrue($support->isHeldInValueColumns(self::ENCRYPTED_FIELD_TYPE));
    }

    public function testEveryShippedFieldTypeAnswersItsFilterDeclaration(): void
    {
        foreach ($this->shippedFieldTypeDefinitions() as $type => $fieldDefinition) {
            $this->assertInstanceOf(
                KeyFilter::class,
                $fieldDefinition->getClassificationStoreKeyFilter(),
                $type . ' has to answer the filter declaration'
            );
        }
    }

    public function testATypeThatOverridesNothingDeclaresNoFilter(): void
    {
        $fieldDefinition = new class extends Data {
            public function getDataForEditmode(mixed $data, ?Concrete $object = null, array $params = []): mixed
            {
                return $data;
            }

            public function getDataFromEditmode(mixed $data, ?Concrete $object = null, array $params = []): mixed
            {
                return $data;
            }

            public function getFieldType(): string
            {
                return 'thirdPartyType';
            }

            public function getParameterTypeDeclaration(): ?string
            {
                return null;
            }

            public function getReturnTypeDeclaration(): ?string
            {
                return null;
            }

            public function getPhpdocInputType(): ?string
            {
                return null;
            }

            public function getPhpdocReturnType(): ?string
            {
                return null;
            }
        };

        $this->assertSame(KeyFilter::None, $fieldDefinition->getClassificationStoreKeyFilter());
    }

    private function support(): KeyTypeSupport
    {
        return Pimcore::getContainer()->get(KeyTypeSupport::class);
    }

    /**
     * @return string[]
     */
    private function shippedFieldTypes(): array
    {
        return array_keys($this->shippedFieldTypeDefinitions());
    }

    /**
     * Every concrete field type shipped under models/DataObject/ClassDefinition/Data, keyed by the
     * field type name it answers.
     *
     * @return array<string, Data>
     */
    private function shippedFieldTypeDefinitions(): array
    {
        $directory = dirname((string) (new ReflectionClass(Data::class))->getFileName()) . '/Data';
        $definitions = [];

        /** @var SplFileInfo $file */
        foreach ($this->phpFilesIn($directory) as $file) {
            $relative = substr($file->getPathname(), strlen($directory) + 1, -strlen('.php'));
            $class = Data::class . '\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $relative);

            if (!class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract() || !$reflection->isSubclassOf(Data::class)) {
                continue;
            }

            $fieldDefinition = $reflection->newInstanceWithoutConstructor();
            $this->assertInstanceOf(Data::class, $fieldDefinition);
            $definitions[$fieldDefinition->getFieldType()] = $fieldDefinition;
        }

        $this->assertNotSame([], $definitions, 'the field type directory has to be readable');

        return $definitions;
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function phpFilesIn(string $directory): iterable
    {
        return new RegexIterator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)),
            '/\.php$/'
        );
    }
}
