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
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Traits;

trait ObjectVarTrait
{
    /**
     * returns object values without the dao
     *
     * @return array
     */
    public function getObjectVars()
    {
        $data = get_object_vars($this);
        unset($data['dao']);

        return $data;
    }

    /**
     * @param $var
     *
     * @return mixed
     */
    public function getObjectVar($var)
    {
        return $this->{$var};
    }

    /**
     * @param $var mixed
     * @param $value mixed
     *
     * @return $this
     */
    public function setObjectVar($var, $value)
    {
        if (!property_exists($this, $var)) {
            throw new \Exception('property ' . $var . ' does not exist');
        }
        $this->$var = $value;

        return $this;
    }
}
