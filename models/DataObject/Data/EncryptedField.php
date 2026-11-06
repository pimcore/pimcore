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

            return ['encrypted', '_owner'];
        }

        return [];
    }

    /**
     * Takes the owner off every owner-aware value the plaintext is made of, recording what each one
     * held so that {@see self::__sleep()} can put it back.
     *
     * The walk is the one {@see \Pimcore\Model\DataObject\Classificationstore::attachOwner()} makes,
     * in reverse and for the same reason: an array is descended into, so is an owner-aware value's
     * own members, and nothing else - which stops it at the elements a value references, an asset
     * or an object, rather than walking an element graph. The identity guard keeps a value that
     * holds itself from looping, and this field is never descended into: it is the thing being
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

        // the members of the value, whatever their visibility: a container holds its children in a
        // protected property, and the one that reads them back is a getter this code cannot name
        foreach ((array) $value as $member) {
            if ($member !== $this) {
                $this->detachOwners($member, $detached, $seen);
            }
        }
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

                if ($data instanceof OwnerAwareFieldInterface) {
                    $data->_setOwner($this->_owner);
                    $data->_setOwnerFieldname('_owner');
                }

                $this->plain = $data;
            } catch (Exception $e) {
                Logger::error((string) $e);

                throw new Exception('could not load key');
            }
        }
        $this->encrypted = null;
    }
}
