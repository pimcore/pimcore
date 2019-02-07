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

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\ObjectVarTrait;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

class Video implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;
    use ObjectVarTrait;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var Asset|string
     */
    protected $data;

    /**
     * @var Asset
     */
    protected $poster;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $description;

    /**
     * @param Asset|string $data
     */
    public function setData($data)
    {
        $this->data = $data;
        $this->markMeDirty();
    }

    /**
     * @return Asset|string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
        $this->markMeDirty();
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
        $this->markMeDirty();
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param Asset|string $poster
     */
    public function setPoster($poster)
    {
        $this->poster = $poster;
        $this->markMeDirty();
    }

    /**
     * @return Asset|string
     */
    public function getPoster()
    {
        return $this->poster;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
        $this->markMeDirty();
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
