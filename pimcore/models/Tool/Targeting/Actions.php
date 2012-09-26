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
 * @package    Document
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Tool_Targeting_Actions {

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
     * @param boolean $programmaticallyEnabled
     */
    public function setProgrammaticallyEnabled($programmaticallyEnabled)
    {
        $this->programmaticallyEnabled = $programmaticallyEnabled;
    }

    /**
     * @return boolean
     */
    public function getProgrammaticallyEnabled()
    {
        return $this->programmaticallyEnabled;
    }

    /**
     * @param string $codesnippetCode
     */
    public function setCodesnippetCode($codesnippetCode)
    {
        $this->codesnippetCode = $codesnippetCode;
    }

    /**
     * @return string
     */
    public function getCodesnippetCode()
    {
        return $this->codesnippetCode;
    }

    /**
     * @param string $codesnippetPosition
     */
    public function setCodesnippetPosition($codesnippetPosition)
    {
        $this->codesnippetPosition = $codesnippetPosition;
    }

    /**
     * @return string
     */
    public function getCodesnippetPosition()
    {
        return $this->codesnippetPosition;
    }

    /**
     * @param string $codesnippetSelector
     */
    public function setCodesnippetSelector($codesnippetSelector)
    {
        $this->codesnippetSelector = $codesnippetSelector;
    }

    /**
     * @return string
     */
    public function getCodesnippetSelector()
    {
        return $this->codesnippetSelector;
    }

    /**
     * @param string $eventKey
     */
    public function setEventKey($eventKey)
    {
        $this->eventKey = $eventKey;
    }

    /**
     * @return string
     */
    public function getEventKey()
    {
        return $this->eventKey;
    }

    /**
     * @param string $eventValue
     */
    public function setEventValue($eventValue)
    {
        $this->eventValue = $eventValue;
    }

    /**
     * @return string
     */
    public function getEventValue()
    {
        return $this->eventValue;
    }

    /**
     * @param int $redirectCode
     */
    public function setRedirectCode($redirectCode)
    {
        $this->redirectCode = $redirectCode;
    }

    /**
     * @return int
     */
    public function getRedirectCode()
    {
        return $this->redirectCode;
    }

    /**
     * @param string $redirectUrl
     */
    public function setRedirectUrl($redirectUrl)
    {
        if(is_string($redirectUrl)) {
            if($doc = Document::getByPath($redirectUrl)) {
                $redirectUrl = $doc->getId();
            }
        }
        $this->redirectUrl = $redirectUrl;
    }

    /**
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * @param boolean $codesnippetEnabled
     */
    public function setCodesnippetEnabled($codesnippetEnabled)
    {
        $this->codesnippetEnabled = $codesnippetEnabled;
    }

    /**
     * @return boolean
     */
    public function getCodesnippetEnabled()
    {
        return $this->codesnippetEnabled;
    }

    /**
     * @param boolean $eventEnabled
     */
    public function setEventEnabled($eventEnabled)
    {
        $this->eventEnabled = $eventEnabled;
    }

    /**
     * @return boolean
     */
    public function getEventEnabled()
    {
        return $this->eventEnabled;
    }

    /**
     * @param boolean $redirectEnabled
     */
    public function setRedirectEnabled($redirectEnabled)
    {
        $this->redirectEnabled = $redirectEnabled;
    }

    /**
     * @return boolean
     */
    public function getRedirectEnabled()
    {
        return $this->redirectEnabled;
    }
}
