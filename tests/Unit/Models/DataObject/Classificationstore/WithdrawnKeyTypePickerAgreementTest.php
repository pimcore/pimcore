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
use Pimcore\Model\DataObject\Classificationstore\KeyTypeSupport;
use Pimcore\Tests\Support\Test\TestCase;
use ReflectionClass;

/**
 * The withdrawal is decided twice, in two repositories that cannot see each other. Core refuses the
 * three names in {@see \Pimcore\Model\DataObject\Classificationstore\KeyConfig::save()}, off
 * {@see KeyTypeSupport}; the Studio key-type pickers never offer them in the first place, off a
 * second list of the same three names in studio-ui-bundle, {@see self::STUDIO_UI_COUNTERPART}. The
 * upgrade note of this release states both halves, so both have to hold.
 *
 * Nothing compares the two lists, and nothing can: core's suite never sees that file, and the jest
 * job that runs the picker's tests installs npm only, so it has no core checkout to read. What this
 * test does instead is make core's half unchangeable in silence - the names are pinned here, and the
 * counterpart test in studio-ui-bundle pins the same names there, each naming the other's file. An
 * edit to one list fails a test that says where the other one is.
 *
 * That is a convention held by two tests, not one source of truth. The source of truth is the
 * follow-up: the backend publishes the withdrawn list and the picker reads it, at which point this
 * test and its counterpart are replaced by the contract test of that endpoint.
 */
class WithdrawnKeyTypePickerAgreementTest extends TestCase
{
    /** The three names, as the picker in studio-ui-bundle also spells them. */
    private const WITHDRAWN_KEY_TYPES = [
        'consent',
        'permissionManyToManyRelation',
        'permissionManyToOneRelation',
    ];

    private const STUDIO_UI_COUNTERPART = 'studio-ui-bundle assets/js/src/core/modules/field-definitions'
        . '/dynamic-types/utils/classification-store-key-types.ts';

    /**
     * Read by reflection on purpose: the constant is private because nothing in core may consume the
     * withdrawal as a list, and pinning it is the one thing that has to see it as one. The predicate
     * alone cannot say the list holds nothing else - it answers a name at a time.
     */
    public function testTheWithdrawnListIsTheThreeNamesThePickerAlsoFilters(): void
    {
        $this->assertSame(
            self::WITHDRAWN_KEY_TYPES,
            (new ReflectionClass(KeyTypeSupport::class))->getConstant('WITHDRAWN_KEY_TYPES'),
            'the withdrawn key types changed; ' . self::STUDIO_UI_COUNTERPART
            . ' carries the same three names and has to be changed with them'
        );
    }

    /**
     * And that the predicate is what reads that list, so pinning it pins the answer the picker and
     * `KeyConfig::save()` are held to rather than a constant nothing consumes.
     */
    public function testThePredicateAnswersExactlyThoseNames(): void
    {
        $support = Pimcore::getContainer()->get(KeyTypeSupport::class);

        foreach (self::WITHDRAWN_KEY_TYPES as $type) {
            $this->assertTrue(
                $support->isWithdrawnKeyType($type),
                $type . ' is withdrawn here and filtered out of the picker by ' . self::STUDIO_UI_COUNTERPART
            );
        }

        // the neighbours a withdrawal is most likely to be widened over by accident: the two core
        // relation types the permission ones extend, and the two wrappers the refusal follows into
        foreach (['manyToOneRelation', 'manyToManyRelation', 'encryptedField', 'multiValue'] as $type) {
            $this->assertFalse(
                $support->isWithdrawnKeyType($type),
                $type . ' is not withdrawn and ' . self::STUDIO_UI_COUNTERPART . ' still offers it'
            );
        }
    }
}
