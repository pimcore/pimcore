<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Object
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Object\Data\KeyValue;

use Pimcore\Model;

class Entry
{
    /**
     * @var
     */
    private $value;

    /**
     * @var
     */
    private $translated;

    /**
     * @var
     */
    private $metadata;

    /**
     * @param $value
     * @param $translated
     * @param $metadata
     */
    public function __construct($value, $translated, $metadata)
    {
        $this->value = $value;
        $this->translated = $translated;
        $this->metadata = $metadata;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function getTranslated()
    {
        return $this->translated;
    }

    /**
     * @return mixed
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @return mixed
     */
    public function __toString()
    {
        return $this->translated !== null ? $this->translated : $this->value;
    }
}
