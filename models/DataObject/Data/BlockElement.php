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

use Pimcore\Model\AbstractModel;
use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

class BlockElement extends AbstractModel implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var mixed
     */
    protected $data;

    /**
     * BlockElement constructor.
     *
     * @param string $name
     * @param string $type
     * @param mixed $data
     */
    public function __construct($name, $type, $data)
    {
        $this->name = $name;
        $this->type = $type;
        $this->data = $data;
        $this->markMeDirty();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        if ($name != $this->name) {
            $this->name = $name;
            $this->markMeDirty();
        }
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        if ($type != $this->type) {
            $this->type = $type;
            $this->markMeDirty();
        }
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
        $this->markMeDirty();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name . '; ' . $this->type;
    }
}
