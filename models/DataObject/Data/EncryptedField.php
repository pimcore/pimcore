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

namespace Pimcore\Model\DataObject\Data;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Exception;
use Pimcore;
use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;
use Pimcore\Tool\Serialize;

class EncryptedField implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    protected Data $delegate;

    protected mixed $plain = null;

    protected mixed $encrypted = null;

    public function __construct(Data $delegate, mixed $plain)
    {
        $this->plain = $plain;
        $this->delegate = $delegate;
        $this->markMeDirty();
    }

    public function getDelegate(): Data
    {
        return $this->delegate;
    }

    public function setDelegate(Data $delegate): void
    {
        $this->delegate = $delegate;
    }

    public function getPlain(): mixed
    {
        return $this->plain;
    }

    public function setPlain(mixed $plain): void
    {
        $this->plain = $plain;
        $this->markMeDirty();
    }

    /**
     *
     * @throws Exception
     */
    public function __sleep(): array
    {
        if ($this->plain) {
            $detached = [];

            try {
                $key = Pimcore::getContainer()->getParameter('pimcore.encryption.secret');
                $key = Key::loadFromAsciiSafeString($key);
                $data = $this->plain;

                // An owner anywhere in the plaintext is a way out of the plaintext and into the
                // container this field sits in - which holds this field, whose __sleep() encrypts
                // the plaintext by serializing it again. PHP's own cycle detection does not reach
                // across that: each Serialize::serialize() call below is a fresh serialization in
                // which this object has not been seen yet, so the nesting does not terminate and
                // the serialization of a version (Model\Version::save()) exhausts memory.
                //
                // The whole graph has to go, not only its root. The plaintext of a key is as often
                // a plain array of owner-aware values - a `geopolygon` is an array of
                // Data\GeoCoordinates - as it is one of them, and an owner-aware value has
                // owner-aware members of its own: an `imageGallery` holds its Data\Hotspotimage
                // items, a `geobounds` its two corners. Each of those carries the owner that
                // DataObject\Classificationstore::attachOwner() gave it so that its own setters
                // reach the store's dirty flag, and each of them is the same way back out.
                //
                // They are put back in `finally`, whatever happened in between: the owner is what
                // makes a later `$gallery->getItems()[0]->setCrop(...)` mark the store dirty, and
                // serializing an object for a version must not quietly stop the next edit of that
                // value from being saved.
                $this->detachOwners($data, $detached);

                $data = Serialize::serialize($data);

                $data = Crypto::encrypt($data, $key, true);
                $this->encrypted = $data;
            } catch (Exception $e) {
                Logger::error((string) $e);

                throw new Exception('could not load key');
            } finally {
                foreach ($detached as $owned) {
                    $owned['value']
                        ->_setOwner($owned['owner'])
                        ->_setOwnerFieldname($owned['fieldname'])
                        ->_setOwnerLanguage($owned['language']);
                }
            }

            // `_fieldname` and `_language` travel beside the owner because an owner on its own
            // reaches nothing: `markMeDirty()` calls `markFieldDirty($this->_fieldname)`, so the
            // field name is what decides WHICH field of the owner a later edit marks dirty, and the
            // language is what marks the right language of a Localizedfield. For a classification
            // store key that field name is `_self`, the one name that store's dirty map uses
            // ({@see \Pimcore\Model\DataObject\Classificationstore::attachOwner()}); for a field of
            // an object it is the field's own name
            // ({@see \Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField::getDataFromResource()}).
            // Neither can be reconstructed on the other side, so {@see self::__wakeup()} is given
            // them rather than left to guess - it used to write the literal `_owner`, which names no
            // field of anything.
            return ['encrypted', '_owner', '_fieldname', '_language'];
        }

        return [];
    }

    /**
     * Takes the owner off every owner-aware value the plaintext is made of, recording what each one
     * held so that {@see self::__sleep()} can put it back.
     *
     * The walk is the one {@see \Pimcore\Model\DataObject\Classificationstore::attachOwner()} makes,
     * in reverse and for the same reason: an array is descended into, so are an owner-aware value's
     * own members ({@see self::membersOf()}, which is the value's properties less the owner
     * back-reference), and nothing else - which stops it at the elements a value references, an
     * asset or an object, rather than walking an element graph. The identity guard keeps a value
     * that holds itself from looping, and this field is never descended into: it is the thing being
     * serialized.
     *
     * @param array<int, array{value: OwnerAwareFieldInterface, owner: mixed, fieldname: ?string,
     *     language: ?string}> $detached
     * @param array<int, true> $seen the values already visited on this walk, by object id
     */
    private function detachOwners(mixed $value, array &$detached, array &$seen = []): void
    {
        if (is_array($value)) {
            foreach ($value as $member) {
                $this->detachOwners($member, $detached, $seen);
            }

            return;
        }

        if (!$value instanceof OwnerAwareFieldInterface || isset($seen[spl_object_id($value)])) {
            return;
        }

        $seen[spl_object_id($value)] = true;

        $detached[] = [
            'value' => $value,
            'owner' => $value->_getOwner(),
            'fieldname' => $value->_getOwnerFieldname(),
            'language' => $value->_getOwnerLanguage(),
        ];

        $value->_setOwner(null);
        $value->_setOwnerFieldname('');

        foreach ($this->membersOf($value) as $member) {
            if ($member !== $this) {
                $this->detachOwners($member, $detached, $seen);
            }
        }
    }

    /**
     * Gives the owner back to every owner-aware value the decrypted plaintext is made of - the
     * whole graph {@see self::detachOwners()} took it off, not only its root.
     *
     * The owner is what makes a value's own setters reach the container's dirty flag, so a value
     * that comes back from a serialization without one is a value whose next edit is discarded with
     * no error: a `geopolygon` whose vertices are plain {@see \Pimcore\Model\DataObject\Data\GeoCoordinates},
     * an `imageGallery` whose items are {@see \Pimcore\Model\DataObject\Data\Hotspotimage}, a
     * `geobounds` and its two corners. A version is read exactly this way - unserialized and then
     * edited - and this is the one field type that drops ownership on the way in.
     *
     * The root is reattached by the same walk and not separately, because the root is as often an
     * array of owner-aware values as it is one of them: `geopolygon` normalizes to a plain array, so
     * the `instanceof` test the root used to be reattached by skipped it entirely.
     *
     * The walk reaches the plaintext and stops there: the owner this method assigns is not itself a
     * member of the values it assigns it to, {@see self::membersOf()}.
     *
     * @param array<int, true> $seen the values already visited on this walk, by object id
     */
    private function attachOwners(mixed $value, array &$seen = []): void
    {
        if (is_array($value)) {
            foreach ($value as $member) {
                $this->attachOwners($member, $seen);
            }

            return;
        }

        if (!$value instanceof OwnerAwareFieldInterface || isset($seen[spl_object_id($value)])) {
            return;
        }

        $seen[spl_object_id($value)] = true;

        $value->_setOwner($this->_owner);
        $value->_setOwnerFieldname($this->_fieldname);
        $value->_setOwnerLanguage($this->_language);

        foreach ($this->membersOf($value) as $member) {
            if ($member !== $this) {
                $this->attachOwners($member, $seen);
            }
        }
    }

    /**
     * The members of an owner-aware value: every property it holds, whatever the visibility - a
     * container keeps its children in a protected one and the getter that reads them back is a name
     * this code cannot know - except the one property that points back OUT of the value.
     *
     * `_owner` is a property like any other to `(array)`, and following it leaves the plaintext for
     * the container this field sits in. That container is owner-aware itself whenever the field is
     * localized: {@see \Pimcore\Model\DataObject\Localizedfield} implements
     * {@see OwnerAwareFieldInterface}, so the walk gave it this field's owner - itself, since
     * {@see self::attachOwners()} assigns `$this->_owner` - and this field's own field name and
     * language, and then walked its `items` and did the same to every localized value of every
     * field of the object. One encrypted key's metadata, written over the whole container.
     *
     * {@see self::detachOwners()} walks the same property and was saved from the same outcome only
     * by the order of its own statements: it nulls the owner before descending, so the member it
     * reaches is `null` rather than the container. Neither walk relies on that any more.
     *
     * The property is matched by name because the cast mangles it: a protected property's key is
     * "\0*\0_owner" and a private one's is "\0<declaring class>\0_owner"
     * ({@see \Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait} declares it protected).
     *
     * @return array<int, mixed>
     */
    private function membersOf(OwnerAwareFieldInterface $value): array
    {
        $members = [];

        foreach ((array) $value as $property => $member) {
            if (!$this->namesTheOwnerBackReference($property)) {
                $members[] = $member;
            }
        }

        return $members;
    }

    private function namesTheOwnerBackReference(int|string $property): bool
    {
        if (!is_string($property)) {
            return false;
        }

        $separator = strrpos($property, "\0");

        return ($separator === false ? $property : substr($property, $separator + 1)) === '_owner';
    }

    /**
     * @throws Exception
     */
    public function __wakeup(): void
    {
        if ($this->encrypted) {
            try {
                $key = Pimcore::getContainer()->getParameter('pimcore.encryption.secret');
                $key = Key::loadFromAsciiSafeString($key);

                $data = Crypto::decrypt($this->encrypted, $key, true);

                $data = Serialize::unserialize($data, true);

                // a payload written before the field name travelled with the owner carries none,
                // and `markMeDirty()` hands it straight to `markFieldDirty(string $field)`. The
                // empty string is the "no field name" this class already writes when it detaches
                // one, and it reaches no field of the owner - exactly as the literal `_owner`
                // written here before reached none. Normalised on this field as well as on the
                // plaintext, because this field's own setters go through the same method.
                $this->_fieldname ??= '';

                $this->attachOwners($data);

                $this->plain = $data;
            } catch (Exception $e) {
                Logger::error((string) $e);

                throw new Exception('could not load key');
            }
        }
        $this->encrypted = null;
    }
}
