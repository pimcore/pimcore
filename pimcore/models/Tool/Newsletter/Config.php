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
 * @package    Tool
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Tool\Newsletter;

use Pimcore\Model;

class Config extends Model\AbstractModel
{

    /**
     * @var string
     */
    public $name = "";

    /**
     * @var string
     */
    public $description = "";

    /**
     * @var int
     */
    public $document;

    /**
     * @var string
     */
    public $class;

    /**
     * @var string
     */
    public $objectFilterSQL;

    /**
     * @var string
     */
    public $personas;

    /**
     * @var string
     */
    public $testEmailAddress;

    /**
     * @var bool
     */
    public $googleAnalytics = true;

    /**
     * @var int
     */
    public $modificationDate;

    /**
     * @var int
     */
    public $creationDate;

    /**
     * @param $name
     * @return null|Config
     */
    public static function getByName($name)
    {
        try {
            $letter = new self();
            $letter->getDao()->getByName($name);
        } catch (\Exception $e) {
            return null;
        }

        return $letter;
    }

    /**
     * @return string
     */
    public function getPidFile()
    {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . "/newsletter__" . $this->getName() . ".pid";
    }

    /**
     * @param $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }

    /**
     * @return int
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param $googleAnalytics
     * @return $this
     */
    public function setGoogleAnalytics($googleAnalytics)
    {
        $this->googleAnalytics = (bool) $googleAnalytics;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getGoogleAnalytics()
    {
        return $this->googleAnalytics;
    }

    /**
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param string $objectFilterSQL
     */
    public function setObjectFilterSQL($objectFilterSQL)
    {
        $this->objectFilterSQL = $objectFilterSQL;
    }

    /**
     * @return string
     */
    public function getObjectFilterSQL()
    {
        return $this->objectFilterSQL;
    }

    /**
     * @param string $testEmailAddress
     */
    public function setTestEmailAddress($testEmailAddress)
    {
        $this->testEmailAddress = $testEmailAddress;
    }

    /**
     * @return string
     */
    public function getTestEmailAddress()
    {
        return $this->testEmailAddress;
    }

    /**
     * @param string $personas
     */
    public function setPersonas($personas)
    {
        $this->personas = $personas;
    }

    /**
     * @return string
     */
    public function getPersonas()
    {
        return $this->personas;
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param int $modificationDate
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = $modificationDate;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param int $creationDate
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    }
}
