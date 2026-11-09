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

namespace Pimcore\Tests\Unit\Model\DataObject\Data;

use Pimcore\Model\DataObject\ClassDefinition\Data\Geopolygon;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\EncryptedField;
use Pimcore\Model\DataObject\Data\GeoCoordinates;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Tests\Support\Test\TestCase;

/**
 * What the ownership walk of an encrypted value reaches, and what it must not.
 *
 * {@see EncryptedField::__wakeup()} gives the owner back to every owner-aware value the decrypted
 * plaintext is made of, because a value that comes back from a serialization without one is a value
 * whose next edit is discarded with no error. The walk descends an owner-aware value's own
 * properties - a container keeps its children in a protected one - and `_owner` is such a property:
 * followed, the walk leaves the plaintext for the container holding the field.
 *
 * That container is owner-aware itself whenever the field is localized
 * ({@see Localizedfield} implements `OwnerAwareFieldInterface`), so the walk used to give it this
 * field's owner - itself - and this field's own field name and language, and then walk its `items`
 * and do the same to every localized value of every field of the object.
 */
class EncryptedFieldOwnershipTest extends TestCase
{
    private const ENCRYPTED_FIELD_NAME = 'encryptedPolygon';

    private const CONTAINER_FIELD_NAME = 'localizedfields';

    public function testTheOwnershipWalkOfALocalizedEncryptedValueStopsAtTheLocalizedContainer(): void
    {
        $owningObject = new Concrete();

        // one unrelated localized value of another field of the same container
        $unrelated = new GeoCoordinates(48.0, 16.0);

        $container = new Localizedfield();
        $container->setItems(['de' => ['point' => $unrelated]]);
        $container->_setOwner($owningObject);
        $container->_setOwnerFieldname(self::CONTAINER_FIELD_NAME);

        $unrelated->_setOwner($container);
        $unrelated->_setOwnerFieldname('point');
        $unrelated->_setOwnerLanguage('de');

        $field = new EncryptedField(new Geopolygon(), [new GeoCoordinates(48.1, 16.1)]);
        $field->_setOwner($container);
        $field->_setOwnerFieldname(self::ENCRYPTED_FIELD_NAME);
        $field->_setOwnerLanguage('en');

        // The round trip is driven by hand because a real `serialize()` of this field serializes the
        // container with it - `__sleep()` keeps `_owner` - and {@see Localizedfield::__sleep()} reads
        // the field definitions of the object its container belongs to, which a container built here
        // has none of. These two methods are the ones under test, and `__wakeup()` works on the graph
        // `__sleep()` encrypted whichever call unserializes it.
        $field->__sleep();
        $field->__wakeup();

        $plain = $field->getPlain();
        $this->assertIsArray($plain);
        $revivedVertex = $plain[0] ?? null;
        $this->assertInstanceOf(GeoCoordinates::class, $revivedVertex);
        $this->assertSame(16.1, $revivedVertex->getLongitude(), 'the plaintext survived the round trip');

        // what the walk is for: an edit of the decrypted vertex reaches this field of this container
        $this->assertSame($container, $revivedVertex->_getOwner(), 'the vertex is owned by the container');
        $this->assertSame(
            self::ENCRYPTED_FIELD_NAME,
            $revivedVertex->_getOwnerFieldname(),
            'the vertex marks the encrypted field'
        );
        $this->assertSame('en', $revivedVertex->_getOwnerLanguage(), 'the vertex marks the field\'s language');

        // and what it must not reach: the container is not part of the plaintext
        $this->assertSame($owningObject, $container->_getOwner(), 'the container still belongs to its object');
        $this->assertSame(
            self::CONTAINER_FIELD_NAME,
            $container->_getOwnerFieldname(),
            'the container still marks its own field'
        );
        $this->assertNull($container->_getOwnerLanguage(), 'the container took no language from the field');

        $this->assertSame($container, $unrelated->_getOwner(), 'an unrelated localized value keeps its owner');
        $this->assertSame(
            'point',
            $unrelated->_getOwnerFieldname(),
            'an unrelated localized value keeps the field name it marks'
        );
        $this->assertSame('de', $unrelated->_getOwnerLanguage(), 'an unrelated localized value keeps its language');
    }
}
