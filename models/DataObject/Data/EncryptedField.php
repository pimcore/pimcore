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
            try {
                $key = Pimcore::getContainer()->getParameter('pimcore.encryption.secret');
                $key = Key::loadFromAsciiSafeString($key);
                $data = $this->plain;
                //clear owner to avoid recursion
                if ($data instanceof OwnerAwareFieldInterface) {
                    $data->_setOwner(null);
                    $data->_setOwnerFieldname('');
                }
                $data = Serialize::serialize($data);

                $data = Crypto::encrypt($data, $key, true);
                $this->encrypted = $data;
            } catch (Exception $e) {
                Logger::error((string) $e);

                throw new Exception('could not load key');
            }

            return ['encrypted', '_owner'];
        }

        return [];
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

                $data = Serialize::unserialize($data);

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
        unset($this->encrypted);
    }
}
