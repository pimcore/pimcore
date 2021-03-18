<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Data;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;
use Pimcore\Tool\Serialize;

class EncryptedField implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    /**
     * @var Data
     */
    protected $delegate;

    /**
     * @var mixed
     */
    protected $plain;

    /**
     * @var mixed
     */
    protected $encrypted;

    /**
     * EncryptedField constructor.
     *
     * @param mixed $plain
     * @param Data $delegate
     */
    public function __construct(Data $delegate, $plain)
    {
        $this->plain = $plain;
        $this->delegate = $delegate;
        $this->markMeDirty();
    }

    /**
     * @return Data
     */
    public function getDelegate(): Data
    {
        return $this->delegate;
    }

    /**
     * @param Data $delegate
     */
    public function setDelegate(Data $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * @return mixed
     */
    public function getPlain()
    {
        return $this->plain;
    }

    /**
     * @param mixed $plain
     */
    public function setPlain($plain)
    {
        $this->plain = $plain;
        $this->markMeDirty();
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    public function __sleep()
    {
        if ($this->plain) {
            try {
                $key = \Pimcore::getContainer()->getParameter('pimcore.encryption.secret');
                $key = Key::loadFromAsciiSafeString($key);
                $data = $this->plain;
                //clear owner to avoid recursion
                if ($data instanceof OwnerAwareFieldInterface) {
                    $data->setOwner(null, '');
                }
                $data = Serialize::serialize($data);

                $data = Crypto::encrypt($data, $key, true);
                $this->encrypted = $data;
            } catch (\Exception $e) {
                Logger::error($e);
                throw new \Exception('could not load key');
            }

            return ['encrypted', '_owner'];
        }

        return [];
    }

    /**
     * @throws \Exception
     */
    public function __wakeup()
    {
        if ($this->encrypted) {
            try {
                $key = \Pimcore::getContainer()->getParameter('pimcore.encryption.secret');
                $key = Key::loadFromAsciiSafeString($key);

                $data = Crypto::decrypt($this->encrypted, $key, true);

                $data = Serialize::unserialize($data);

                if ($data instanceof OwnerAwareFieldInterface) {
                    $data->setOwner($this->_owner, '_owner');
                }

                $this->plain = $data;
            } catch (\Exception $e) {
                Logger::error($e);
                throw new \Exception('could not load key');
            }
        }
        unset($this->encrypted);
    }
}
