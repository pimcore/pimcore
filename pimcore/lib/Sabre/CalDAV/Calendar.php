<?php

/**
 * This object represents a CalDAV calendar.
 *
 * A calendar can contain multiple TODO and or Events. These are represented
 * as Sabre_CalDAV_CalendarObject objects.
 * 
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Calendar implements Sabre_DAV_ICollection, Sabre_DAV_IProperties {

    /**
     * This is an array with calendar information 
     * 
     * @var array 
     */
    private $calendarInfo;

    /**
     * CalDAV backend 
     * 
     * @var Sabre_CalDAV_Backend_Abstract 
     */
    private $caldavBackend;

    /**
     * Authentication backend
     * 
     * @var Sabre_DAV_Auth_Backend_Abstract 
     */
    private $authBackend;

    /**
     * Constructor 
     * 
     * @param Sabre_CalDAV_Backend_Abstract $caldavBackend 
     * @param array $calendarInfo 
     * @return void
     */
    public function __construct(Sabre_DAV_Auth_Backend_Abstract $authBackend, Sabre_CalDAV_Backend_Abstract $caldavBackend,$calendarInfo) {

        $this->caldavBackend = $caldavBackend;
        $this->authBackend = $authBackend;
        $this->calendarInfo = $calendarInfo;


    }

    /**
     * Returns the name of the calendar 
     * 
     * @return string 
     */
    public function getName() {

        return $this->calendarInfo['uri'];

    }

    /**
     * Updates properties such as the display name and description 
     * 
     * @param array $mutations 
     * @return array 
     */
    public function updateProperties($mutations) {

        if (!$this->hasPrivilege()) throw new Sabre_DAV_Exception_Forbidden('Permission denied to access this calendar');
        return $this->caldavBackend->updateCalendar($this->calendarInfo['id'],$mutations);

    }

    /**
     * Returns the list of properties 
     * 
     * @param array $properties 
     * @return array 
     */
    public function getProperties($requestedProperties) {

        $response = array();

        if (!$this->hasPrivilege()) return array(); 

        foreach($requestedProperties as $prop) switch($prop) {

            case '{DAV:}resourcetype' : 
                $response[$prop] =  new Sabre_DAV_Property_ResourceType(array('{urn:ietf:params:xml:ns:caldav}calendar','{DAV:}collection')); 
                break;
            case '{urn:ietf:params:xml:ns:caldav}supported-calendar-data' : 
                $response[$prop] = new Sabre_CalDAV_Property_SupportedCalendarData(); 
                break;
            case '{urn:ietf:params:xml:ns:caldav}supported-collation-set' : 
                $response[$prop] =  new Sabre_CalDAV_Property_SupportedCollationSet(); 
                break;
            case '{DAV:}owner' :
                $response[$prop] = new Sabre_DAV_Property_Principal(Sabre_DAV_Property_Principal::HREF,$this->calendarInfo['principaluri']);
                break;
            default : 
                if (isset($this->calendarInfo[$prop])) $response[$prop] = $this->calendarInfo[$prop];
                break;

        }
        return $response;

    }

    /**
     * Returns a calendar object
     *
     * The contained calendar objects are for example Events or Todo's.
     * 
     * @param string $name 
     * @return Sabre_DAV_ICalendarObject 
     */
    public function getChild($name) {

        if (!$this->hasPrivilege()) throw new Sabre_DAV_Exception_Forbidden('Permission denied to access this calendar');
        $obj = $this->caldavBackend->getCalendarObject($this->calendarInfo['id'],$name);
        if (!$obj) throw new Sabre_DAV_Exception_FileNotFound('Calendar object not found');
        return new Sabre_CalDAV_CalendarObject($this->caldavBackend,$this->calendarInfo,$obj);

    }

    /**
     * Returns the full list of calendar objects  
     * 
     * @return array 
     */
    public function getChildren() {

        if (!$this->hasPrivilege()) throw new Sabre_DAV_Exception_Forbidden('Permission denied to access this calendar');
        $objs = $this->caldavBackend->getCalendarObjects($this->calendarInfo['id']);
        $children = array();
        foreach($objs as $obj) {
            $children[] = new Sabre_CalDAV_CalendarObject($this->caldavBackend,$this->calendarInfo,$obj);
        }
        return $children;

    }

    /**
     * Creates a new directory
     *
     * We actually block this, as subdirectories are not allowed in calendars.
     * 
     * @param string $name 
     * @return void
     */
    public function createDirectory($name) {

        if (!$this->hasPrivilege()) throw new Sabre_DAV_Exception_Forbidden('Permission denied to access this calendar');
        throw new Sabre_DAV_Exception_MethodNotAllowed('Creating collections in calendar objects is not allowed');

    }

    /**
     * Creates a new file
     *
     * The contents of the new file must be a valid ICalendar string.
     * 
     * @param string $name 
     * @param resource $calendarData 
     * @return void
     */
    public function createFile($name,$calendarData = null) {

        if (!$this->hasPrivilege()) throw new Sabre_DAV_Exception_Forbidden('Permission denied to access this calendar');
        $calendarData = stream_get_contents($calendarData);

        $supportedComponents = $this->calendarInfo['{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}supported-calendar-component-set']->getValue();
        Sabre_CalDAV_ICalendarUtil::validateICalendarObject($calendarData, $supportedComponents);

        $this->caldavBackend->createCalendarObject($this->calendarInfo['id'],$name,$calendarData);

    }

    /**
     * Deletes the calendar. 
     * 
     * @return void
     */
    public function delete() {

        if (!$this->hasPrivilege()) throw new Sabre_DAV_Exception_Forbidden('Permission denied to access this calendar');
        $this->caldavBackend->deleteCalendar($this->calendarInfo['id']);

    }

    /**
     * Renames the calendar. Note that most calendars use the 
     * {DAV:}displayname to display a name to display a name. 
     * 
     * @param string $newName 
     * @return void
     */
    public function setName($newName) {

        if (!$this->hasPrivilege()) throw new Sabre_DAV_Exception_Forbidden('Permission denied to access this calendar');
        throw new Sabre_DAV_Exception_MethodNotAllowed('Renaming calendars is not yet supported');

    }

    /**
     * Returns the last modification date as a unix timestamp.
     * 
     * @return void
     */
    public function getLastModified() {

        return null;

    }

    /**
     * Check if user has access.
     *
     * This method does a check if the currently logged in user
     * has permission to access this calendar. There is only read-write
     * access, so you're in or you're out.
     * 
     * @return bool 
     */
    protected function hasPrivilege() {

        if (!$user = $this->authBackend->getCurrentUser()) return false;
        if ($user['uri']!==$this->calendarInfo['principaluri']) return false;
        return true;

    }

}
