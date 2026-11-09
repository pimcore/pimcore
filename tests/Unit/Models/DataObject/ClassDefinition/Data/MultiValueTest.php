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

namespace Pimcore\Tests\Unit\Model\DataObject\ClassDefinition\Data;

use Doctrine\DBAL\Connection;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Consent;
use Pimcore\Model\DataObject\ClassDefinition\Data\Image;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\MultiValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\PreGetDataInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation;
use Pimcore\Model\DataObject\Classificationstore;
use Pimcore\Model\DataObject\Classificationstore\Service;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\Consent as ConsentValue;
use Pimcore\Model\DataObject\Exception\DerivedValueNotStorableException;
use Pimcore\Model\DataObject\Exception\UnsupportedEngineFeatureException;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * What the `multiValue` wrapper forwards to the type it wraps.
 *
 * The declarations are the wrapper's; everything about the VALUE is the inner type's, and a caller
 * that asks the wrapper has to be answered as the inner type would answer. Nine of the findings on
 * the pull request of this ticket were one gap found nine ways - a behaviour the wrapper did not
 * hand on - so the set is asserted here as a set rather than one symptom at a time.
 */
class MultiValueTest extends TestCase
{
    private const GROUP_ID = 7;

    private const KEY_ID = 11;

    /**
     * The hook that filters the unpublished targets out of a read. Without the interface the wrapper
     * is never asked, and a wrapped `manyToManyObjectRelation` hands out the objects an unwrapped one
     * hides.
     */
    public function testTheWrapperIsAskedThePreReadHookAndForwardsItToTheInnerType(): void
    {
        $this->assertInstanceOf(PreGetDataInterface::class, new MultiValue());

        $relation = new ManyToManyObjectRelation();
        $relation->setName('wrapped');

        $wrapper = $this->wrapping($relation);
        $store = new Classificationstore();

        // the inner type's hook ran: a to-many relation answers a list, filtered, rather than the
        // value it was handed
        $this->assertSame([], $wrapper->preGetData($store, ['data' => null]));
    }

    /**
     * An inner type with no hook of its own - and a key with no inner type at all - answers with the
     * value the container holds, which is what the read would have got with no hook to run.
     */
    public function testThePreReadHookHandsBackTheValueForATypeThatHasNone(): void
    {
        $store = new Classificationstore();

        $this->assertSame(
            'plain',
            $this->wrapping($this->input())->preGetData($store, ['data' => 'plain'])
        );
        $this->assertSame('plain', (new MultiValue())->preGetData($store, ['data' => 'plain']));
    }

    /**
     * The elements a value refers to, as the dependency rows of the object that holds it: the
     * wrapper inherited the base class's empty list, so a wrapped relation's targets carried no
     * dependency at all.
     */
    public function testDependencyResolutionIsForwardedToTheInnerType(): void
    {
        $relation = new ManyToManyObjectRelation();
        $relation->setName('wrapped');

        $target = new Concrete();
        $target->setId(4711);

        $this->assertSame(
            ['object_4711' => ['id' => 4711, 'type' => 'object']],
            $this->wrapping($relation)->resolveDependencies([$target]),
            'the targets of a wrapped relation are the dependencies of the object holding it'
        );
        $this->assertSame([], (new MultiValue())->resolveDependencies([$target]), 'no inner type, no dependency');
    }

    public function testCacheTagsAreForwardedToTheInnerType(): void
    {
        $inner = $this->createMock(Image::class);
        $inner->expects($this->once())
            ->method('getCacheTags')
            ->with('the value', ['given' => 'given'])
            ->willReturn(['given' => 'given', 'asset_7' => 'asset_7']);

        $this->assertSame(
            ['given' => 'given', 'asset_7' => 'asset_7'],
            $this->wrapping($inner)->getCacheTags('the value', ['given' => 'given'])
        );
        $this->assertSame(['given' => 'given'], (new MultiValue())->getCacheTags(null, ['given' => 'given']));
    }

    /**
     * Whether a value may be read off an ancestor is the inner type's answer: `consent` declines
     * inheritance because a consent is the record of what one data subject agreed to, and letting a
     * value carry qualifiers says nothing about that.
     */
    public function testInheritanceSupportIsTheInnerTypesAnswer(): void
    {
        $consent = new Consent();
        $consent->setName('wrapped');

        $this->assertFalse($this->wrapping($consent)->supportsInheritance());
        $this->assertFalse(
            Service::supportsInheritance($this->wrapping($consent)),
            'the one place every store path asks the question has to answer the same'
        );

        $this->assertTrue($this->wrapping($this->input())->supportsInheritance());
        $this->assertTrue((new MultiValue())->supportsInheritance());
    }

    /**
     * The boundary every engine and the store itself resolve the value's definition through: the
     * inner type for a wrapper, the definition itself for everything else, and never the inner type
     * of an `encryptedField` - that wrapper carries the stored form itself.
     */
    public function testTheValueDefinitionBoundaryAnswersTheInnerTypeOfAMultiValueKeyAlone(): void
    {
        $input = $this->input();

        $this->assertSame($input, Service::getValueDefinition($this->wrapping($input)));
        $this->assertSame($input, Service::getValueDefinition($input));
        $this->assertNull(Service::getValueDefinition(null));

        $wrapperWithoutAnInnerType = new MultiValue();
        $this->assertSame(
            $wrapperWithoutAnInnerType,
            Service::getValueDefinition($wrapperWithoutAnInnerType),
            'a wrapper naming no inner type answers itself rather than nothing'
        );

        $encrypted = new Data\EncryptedField();
        $encrypted->setDelegate($input);
        $this->assertSame($encrypted, Service::getValueDefinition($encrypted));
    }

    /**
     * And it answers a CHAIN of wrappers, because nothing refuses one: `multiValue` is an accepted
     * delegate type and the loader rebuilds a nested definition layer by layer. Resolving one layer
     * alone answered this question with a wrapper, and a `multiValue<multiValue<encryptedField>>`
     * key then stored its secret in cleartext - the engine, asked whether the value reaches the
     * document through its marshaller, was looking at a `multiValue`.
     *
     * It still stops ON an `encryptedField`: that wrapper carries the stored form itself.
     */
    public function testTheBoundaryUnwrapsEveryConsecutiveWrapperAndStopsAtAnEncryptedField(): void
    {
        $input = $this->input();

        $this->assertSame(
            $input,
            Service::getValueDefinition($this->wrapping($this->wrapping($this->wrapping($input)))),
            'three wrappers deep is still the inner type'
        );

        $encrypted = new Data\EncryptedField();
        $encrypted->setDelegateDatatype('input');
        $encrypted->setDelegate($input);

        $this->assertSame(
            $encrypted,
            Service::getValueDefinition($this->wrapping($this->wrapping($encrypted))),
            'the walk stops on the wrapper that carries the stored form itself'
        );

        $cycle = new MultiValue();
        $cycle->setDelegate($cycle);

        $this->assertSame(
            $cycle,
            Service::getValueDefinition($cycle),
            'a delegate that is its own ancestor ends the walk rather than running forever'
        );
    }

    /**
     * A key whose definition blob names NO inner type. The loader builds a delegate-less wrapper for
     * it rather than refusing the blob, and the capability check accepts it as the key that
     * "normalizes the plain value unchanged" - so every question about the value has to be answered
     * as it is answered with no wrapper at all, which is with the value. Answering null stored
     * nothing for such a key and read nothing back, losing the value with no error.
     */
    public function testAWrapperWithNoInnerTypeAnswersAboutTheValueWithTheValue(): void
    {
        $wrapper = new MultiValue();

        $this->assertSame('plain', $wrapper->normalize('plain'));
        $this->assertSame('plain', $wrapper->denormalize('plain'));
        $this->assertSame(['a', 'b'], $wrapper->normalize(['a', 'b']));
        $this->assertSame('plain', $wrapper->getDataForEditmode('plain'));
        $this->assertSame('plain', $wrapper->getDataFromEditmode('plain'));

        $this->assertFalse($wrapper->isEmpty('plain'), 'a value it was given is not nothing');
        $this->assertTrue($wrapper->isEmpty(null));
    }

    /**
     * And forwards the same questions to the inner type when there is one, which is what the
     * delegate-less answers above must not quietly replace: an inner type whose stored form differs
     * from its value is the case that tells the two apart.
     */
    public function testTheValueQuestionsAreForwardedToTheInnerType(): void
    {
        $value = new ConsentValue(true, 11);
        $normalized = ['consent' => true, 'noteId' => 11];

        $inner = $this->createMock(Consent::class);
        $inner->expects($this->once())->method('normalize')->with($value)->willReturn($normalized);
        $inner->expects($this->once())->method('denormalize')->with($normalized)->willReturn($value);
        $inner->expects($this->once())->method('isEmpty')->with($value)->willReturn(false);

        $wrapper = $this->wrapping($inner);

        $this->assertSame($normalized, $wrapper->normalize($value));
        $this->assertSame($value, $wrapper->denormalize($normalized));
        $this->assertFalse($wrapper->isEmpty($value));
    }

    /**
     * The engine refusal, which is the same boundary read from the outside: a wrapped FORWARD
     * relation is refused by an engine that cannot hold relations, exactly as the unwrapped one is.
     *
     * Both engines reach {@see Classificationstore\RelationResolver::assertStorable()} with the key's
     * own definition, and a wrapper is a relation of no kind whatever it wraps. Asked as it stood, a
     * wrapped forward relation on an EAV store passed the check and reached persistence, where its
     * descriptors have no column to go into.
     */
    public function testAWrappedForwardRelationIsRefusedByAnEngineWithoutRelations(): void
    {
        $resolver = $this->resolver();
        $wrapped = $this->wrapping($this->relation());

        $this->expectException(UnsupportedEngineFeatureException::class);

        $resolver->assertStorable($wrapped, self::GROUP_ID, self::KEY_ID, false);
    }

    /**
     * And is accepted by an engine that can, so the refusal is the engine's answer rather than the
     * wrapper's: unwrapping must not turn every wrapped relation into a refusal.
     */
    public function testAWrappedForwardRelationIsAcceptedByAnEngineWithRelations(): void
    {
        $this->resolver()->assertStorable($this->wrapping($this->relation()), self::GROUP_ID, self::KEY_ID, true);

        $this->addToAssertionCount(1);
    }

    /**
     * A wrapped REVERSE relation is refused on BOTH engines, as the unwrapped one is: its value is
     * computed from the objects pointing here and a store has nowhere to put it, so no engine's
     * `Relations` answer makes it storable.
     */
    public function testAWrappedReverseRelationIsRefusedWhateverTheEngineSupports(): void
    {
        foreach ([true, false] as $relationsSupported) {
            try {
                $this->resolver()->assertStorable(
                    $this->wrapping(new ReverseObjectRelation()),
                    self::GROUP_ID,
                    self::KEY_ID,
                    $relationsSupported
                );

                $this->fail('a wrapped reverse relation is not storable on either engine');
            } catch (DerivedValueNotStorableException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    /**
     * What the wrapper deliberately does not forward, pinned beside what it does: the type the key
     * is DEFINED with is `multiValue`, which is what resolves this definition from a key config in
     * the first place.
     */
    public function testTheFieldTypeIsTheWrappersOwn(): void
    {
        $this->assertSame('multiValue', $this->wrapping($this->input())->getFieldType());
    }

    private function resolver(): Classificationstore\RelationResolver
    {
        return new Classificationstore\RelationResolver($this->createMock(Connection::class));
    }

    private function relation(): ManyToManyObjectRelation
    {
        $relation = new ManyToManyObjectRelation();
        $relation->setName('wrapped');

        return $relation;
    }

    private function wrapping(Data $inner): MultiValue
    {
        $wrapper = new MultiValue();
        $wrapper->setName('mvKey');
        $wrapper->setDelegateDatatype($inner->getFieldType());
        $wrapper->setDelegate($inner);

        return $wrapper;
    }

    private function input(): Input
    {
        $input = new Input();
        $input->setName('wrapped');

        return $input;
    }
}
