<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Bundle\PimcoreEcommerceFrameworkBundle\Tracking;

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
