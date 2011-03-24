<?php

/**
 * The CalendarObject represents a single VEVENT or VTODO within a Calendar. 
 * 
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_CalendarObject extends Sabre_DAV_File implements Sabre_DAV_IProperties {

    /**
     * Sabre_CalDAV_Backend_Abstract 
     * 
     * @var array 
     */
    private $caldavBackend;

    /**
     * Array with information about this CalendarObject 
     * 
     * @var array 
     */
    private $objectData;

    /**
     * Array with information about the containing calendar
     * 
     * @var array 
     */
    private $calendarInfo;

    /**
     * Constructor 
     * 
     * @param Sabre_CalDAV_Backend_Abstract $caldavBackend 
     * @param array $objectData 
     */
    public function __construct(Sabre_CalDAV_Backend_Abstract $caldavBackend,$calendarInfo,$objectData) {

        $this->caldavBackend = $caldavBackend;
        $this->calendarInfo = $calendarInfo;
        $this->objectData = $objectData;

    }

    /**
     * Returns the uri for this object 
     * 
     * @return string 
     */
    public function getName() {

        return $this->objectData['uri'];

    }

    /**
     * Returns the ICalendar-formatted object 
     * 
     * @return string 
     */
    public function get() {

        return $this->objectData['calendardata'];

    }

    /**
     * Updates the ICalendar-formatted object 
     * 
     * @param string $calendarData 
     * @return void 
     */
    public function put($calendarData) {

        if (is_resource($calendarData))
            $calendarData = stream_get_contents($calendarData);

        $supportedComponents = $this->calendarInfo['{' . Sabre_CalDAV_Plugin::NS_CALDAV . '}supported-calendar-component-set']->getValue();
        Sabre_CalDAV_ICalendarUtil::validateICalendarObject($calendarData, $supportedComponents);

        $this->caldavBackend->updateCalendarObject($this->objectData['calendarid'],$this->objectData['uri'],$calendarData);
        $this->objectData['calendardata'] = $calendarData;

    }

    /**
     * Deletes the calendar object 
     * 
     * @return void
     */
    public function delete() {

        $this->caldavBackend->deleteCalendarObject($this->objectData['calendarid'],$this->objectData['uri']);

    }

    /**
     * Returns the mime content-type 
     * 
     * @return string 
     */
    public function getContentType() {

        return 'text/calendar';

    }

    /**
     * Returns an ETag for this object 
     * 
     * @return string 
     */
    public function getETag() {

        return md5($this->objectData['calendardata']);

    }

    /**
     * Returns the list of properties for this object
     * 
     * @param array $properties 
     * @return array 
     */
    public function getProperties($properties) {

        $response = array();
        if (in_array('{urn:ietf:params:xml:ns:caldav}calendar-data',$properties)) 
            $response['{urn:ietf:params:xml:ns:caldav}calendar-data'] = str_replace("\r","",$this->objectData['calendardata']);
       

        return $response;

    }

    /**
     * Updates properties
     * 
     * @param array $properties
     * @return array 
     */
    public function updateProperties($properties) {

        return false;

    }

    /**
     * Returns the last modification date as a unix timestamp
     * 
     * @return time 
     */
    public function getLastModified() {

        return strtotime($this->objectData['lastmodified']);

    }

    /**
     * Returns the size of this object in bytes 
     * 
     * @return int
     */
    public function getSize() {

        return strlen($this->objectData['calendardata']);

    }
}

