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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Tracking;

abstract class AbstractData implements \JsonSerializable
{
    /** @var string */
    protected $id;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Merge values into properties
     *
     * @param array $data
     * @param bool|false $overwrite
     *
     * @return $this
     */
    public function mergeValues(array $data, $overwrite = false)
    {
        foreach ($data as $key => $value) {
            $getter = 'get' . ucfirst($key);
            $setter = 'set' . ucfirst($key);

            if (method_exists($this, $getter) && method_exists($this, $setter)) {
                if (null !== $this->$getter() && !$overwrite) {
                    continue;
                } else {
                    $this->$setter($value);
                }
            }
        }

        return $this;
    }

    /**
     * Serialize all non-null properties
     *
     * @implements \JsonSerializable
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $json = [];
        foreach ($this as $key => $value) {
            if (null !== $value) {
                $json[$key] = $value;
            }
        }

        return $json;
    }
}
