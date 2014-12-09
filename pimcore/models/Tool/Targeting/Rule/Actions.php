<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Tool
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Tool\Targeting\Rule;

use Pimcore\Model;

class Actions {

    /**
     * @var bool
     */
    public $redirectEnabled = false;

    /**
     * @var string
     */
    public $redirectUrl;

    /**
     * @var int
     */
    public $redirectCode;

    /**
     * @var bool
     */
    public $eventEnabled = false;

    /**
     * @var string
     */
    public $eventKey;

    /**
     * @var string
     */
    public $eventValue;

    /**
     * @var bool
     */
    public $codesnippetEnabled = false;

    /**
     * @var string
     */
    public $codesnippetCode;

    /**
     * @var string
     */
    public $codesnippetSelector;

    /**
     * @var string
     */
    public $codesnippetPosition;

    /**
     * @var bool
     */
    public $programmaticallyEnabled = false;

    /**
     * @var int
     */
    public $personaId;

    /**
     * @var bool
     */
    public $personaEnabled = false;

    /**
     * @param $programmaticallyEnabled
     * @return $this
     */
    public function setProgrammaticallyEnabled($programmaticallyEnabled)
    {
        $this->programmaticallyEnabled = $programmaticallyEnabled;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getProgrammaticallyEnabled()
    {
        return $this->programmaticallyEnabled;
    }

    /**
     * @param $codesnippetCode
     * @return $this
     */
    public function setCodesnippetCode($codesnippetCode)
    {
        $this->codesnippetCode = $codesnippetCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getCodesnippetCode()
    {
        return $this->codesnippetCode;
    }

    /**
     * @param $codesnippetPosition
     * @return $this
     */
    public function setCodesnippetPosition($codesnippetPosition)
    {
        $this->codesnippetPosition = $codesnippetPosition;
        return $this;
    }

    /**
     * @return string
     */
    public function getCodesnippetPosition()
    {
        return $this->codesnippetPosition;
    }

    /**
     * @param $codesnippetSelector
     * @return $this
     */
    public function setCodesnippetSelector($codesnippetSelector)
    {
        $this->codesnippetSelector = $codesnippetSelector;
        return $this;
    }

    /**
     * @return string
     */
    public function getCodesnippetSelector()
    {
        return $this->codesnippetSelector;
    }

    /**
     * @param $eventKey
     * @return $this
     */
    public function setEventKey($eventKey)
    {
        $this->eventKey = $eventKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getEventKey()
    {
        return $this->eventKey;
    }

    /**
     * @param $eventValue
     * @return $this
     */
    public function setEventValue($eventValue)
    {
        $this->eventValue = $eventValue;
        return $this;
    }

    /**
     * @return string
     */
    public function getEventValue()
    {
        return $this->eventValue;
    }

    /**
     * @param $redirectCode
     * @return $this
     */
    public function setRedirectCode($redirectCode)
    {
        $this->redirectCode = $redirectCode;
        return $this;
    }

    /**
     * @return int
     */
    public function getRedirectCode()
    {
        return $this->redirectCode;
    }

    /**
     * @param $redirectUrl
     * @return $this
     */
    public function setRedirectUrl($redirectUrl)
    {
        if(is_string($redirectUrl)) {
            if($doc = Document::getByPath($redirectUrl)) {
                $redirectUrl = $doc->getId();
            }
        }
        $this->redirectUrl = $redirectUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * @param $codesnippetEnabled
     * @return $this
     */
    public function setCodesnippetEnabled($codesnippetEnabled)
    {
        $this->codesnippetEnabled = $codesnippetEnabled;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getCodesnippetEnabled()
    {
        return $this->codesnippetEnabled;
    }

    /**
     * @param $eventEnabled
     * @return $this
     */
    public function setEventEnabled($eventEnabled)
    {
        $this->eventEnabled = $eventEnabled;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getEventEnabled()
    {
        return $this->eventEnabled;
    }

    /**
     * @param $redirectEnabled
     * @return $this
     */
    public function setRedirectEnabled($redirectEnabled)
    {
        $this->redirectEnabled = $redirectEnabled;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getRedirectEnabled()
    {
        return $this->redirectEnabled;
    }

    /**
     * @param boolean $personaEnabled
     */
    public function setPersonaEnabled($personaEnabled)
    {
        $this->personaEnabled = $personaEnabled;
    }

    /**
     * @return boolean
     */
    public function getPersonaEnabled()
    {
        return $this->personaEnabled;
    }

    /**
     * @param int $personaId
     */
    public function setPersonaId($personaId)
    {
        $this->personaId = (int) $personaId;
    }

    /**
     * @return int
     */
    public function getPersonaId()
    {
        return $this->personaId;
    }
}
