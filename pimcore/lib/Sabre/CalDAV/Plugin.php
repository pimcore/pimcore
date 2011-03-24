<?php

/**
 * CalDAV plugin
 *
 * This plugin provides functionality added by CalDAV (RFC 4791)
 * It implements new reports, and the MKCALENDAR method.
 *
 * @package Sabre
 * @subpackage CalDAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CalDAV_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * This is the official CalDAV namespace
     */
    const NS_CALDAV = 'urn:ietf:params:xml:ns:caldav';
    
    /**
     * This is the namespace for the proprietary calendarserver extensions
     */
    const NS_CALENDARSERVER = 'http://calendarserver.org/ns/';

    /**
     * The following constants are used to differentiate
     * the various filters for the calendar-query report
     */
    const FILTER_COMPFILTER   = 1;
    const FILTER_TIMERANGE    = 3;
    const FILTER_PROPFILTER   = 4;
    const FILTER_PARAMFILTER  = 5;
    const FILTER_TEXTMATCH    = 6;

    /**
     * The hardcoded root for calendar objects. It is unfortunate
     * that we're stuck with it, but it will have to do for now
     */
    const CALENDAR_ROOT = 'calendars';

    /**
     * Reference to server object 
     * 
     * @var Sabre_DAV_Server 
     */
    private $server;

    /**
     * Use this method to tell the server this plugin defines additional
     * HTTP methods.
     *
     * This method is passed a uri. It should only return HTTP methods that are 
     * available for the specified uri.
     *
     * @param string $uri
     * @return array 
     */
    public function getHTTPMethods($uri) {

        // The MKCALENDAR is only available on unmapped uri's, whose
        // parents extend IExtendedCollection
        list($parent, $name) = Sabre_DAV_URLUtil::splitPath($uri);

        $node = $this->server->tree->getNodeForPath($parent);

        if ($node instanceof Sabre_DAV_IExtendedCollection) {
            try {
                $node->getChild($name);
            } catch (Sabre_DAV_Exception_FileNotFound $e) {
                return array('MKCALENDAR');
            }
        }
        return array();

    }

    /**
     * Returns a list of features for the DAV: HTTP header. 
     * 
     * @return array 
     */
    public function getFeatures() {

        return array('calendar-access');

    }

    /**
     * Initializes the plugin 
     * 
     * @param Sabre_DAV_Server $server 
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $server->subscribeEvent('unknownMethod',array($this,'unknownMethod'));
        //$server->subscribeEvent('unknownMethod',array($this,'unknownMethod2'),1000);
        $server->subscribeEvent('report',array($this,'report'));
        $server->subscribeEvent('afterGetProperties',array($this,'afterGetProperties'));

        $server->xmlNamespaces[self::NS_CALDAV] = 'cal';
        $server->xmlNamespaces[self::NS_CALENDARSERVER] = 'cs';

        $server->propertyMap['{' . self::NS_CALDAV . '}supported-calendar-component-set'] = 'Sabre_CalDAV_Property_SupportedCalendarComponentSet';

        array_push($server->protectedProperties,

            '{' . self::NS_CALDAV . '}supported-calendar-component-set',
            '{' . self::NS_CALDAV . '}supported-calendar-data',
            '{' . self::NS_CALDAV . '}max-resource-size',
            '{' . self::NS_CALDAV . '}min-date-time',
            '{' . self::NS_CALDAV . '}max-date-time',
            '{' . self::NS_CALDAV . '}max-instances',
            '{' . self::NS_CALDAV . '}max-attendees-per-instance',
            '{' . self::NS_CALDAV . '}calendar-home-set',
            '{' . self::NS_CALDAV . '}supported-collation-set',

            // scheduling extension
            '{' . self::NS_CALDAV . '}calendar-user-address-set'

        );
    }

    /**
     * This function handles support for the MKCALENDAR method
     * 
     * @param string $method 
     * @return bool 
     */
    public function unknownMethod($method) {

        if ($method!=='MKCALENDAR') return;

        $this->httpMkCalendar();
        // false is returned to stop the unknownMethod event
        return false;

    }

    /**
     * This functions handles REPORT requests specific to CalDAV 
     * 
     * @param string $reportName 
     * @param DOMNode $dom 
     * @return bool 
     */
    public function report($reportName,$dom) {

        switch($reportName) { 
            case '{'.self::NS_CALDAV.'}calendar-multiget' :
                $this->calendarMultiGetReport($dom);
                return false;
            case '{'.self::NS_CALDAV.'}calendar-query' :
                $this->calendarQueryReport($dom);
                return false;
            default :
                return true;

        }


    }

    /**
     * This function handles the MKCALENDAR HTTP method, which creates
     * a new calendar.
     * 
     * @return void 
     */
    public function httpMkCalendar() {

        // Due to unforgivable bugs in iCal, we're completely disabling MKCALENDAR support
        // for clients matching iCal in the user agent
        //$ua = $this->server->httpRequest->getHeader('User-Agent');
        //if (strpos($ua,'iCal/')!==false) {
        //    throw new Sabre_DAV_Exception_Forbidden('iCal has major bugs in it\'s RFC3744 support. Therefore we are left with no other choice but disabling this feature.');
        //}

        $body = $this->server->httpRequest->getBody(true);
        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($body);

        $properties = array();
        foreach($dom->firstChild->childNodes as $child) {

            if (Sabre_DAV_XMLUtil::toClarkNotation($child)!=='{DAV:}set') continue;
            foreach(Sabre_DAV_XMLUtil::parseProperties($child,$this->server->propertyMap) as $k=>$prop) {
                $properties[$k] = $prop;
            }
        
        }

        $requestUri = $this->server->getRequestUri();
        $resourceType = array('{DAV:}collection','{urn:ietf:params:xml:ns:caldav}calendar');

        $this->server->createCollection($requestUri,$resourceType,$properties);

        $this->server->httpResponse->sendStatus(201);
        $this->server->httpResponse->setHeader('Content-Length',0);
    }

    /**
     * afterGetProperties
     *
     * This method handler is invoked after properties for a specific resource
     * are received. This allows us to add any properties that might have been
     * missing.
     * 
     * @param string $path
     * @param array $properties 
     * @return void
     */
    public function afterGetProperties($path, &$properties) {

        // Find out if we are currently looking at a principal resource
        $currentNode = $this->server->tree->getNodeForPath($path);
        if ($currentNode instanceof Sabre_DAV_Auth_Principal) {

            // calendar-home-set property
            $calHome = '{' . self::NS_CALDAV . '}calendar-home-set';
            if (array_key_exists($calHome,$properties[404])) {
                $principalId = $currentNode->getName(); 
                $calendarHomePath = self::CALENDAR_ROOT . '/' . $principalId . '/';
                unset($properties[404][$calHome]);
                $properties[200][$calHome] = new Sabre_DAV_Property_Href($calendarHomePath);
            }

            // calendar-user-address-set property
            $calProp = '{' . self::NS_CALDAV . '}calendar-user-address-set';
            if (array_key_exists($calProp,$properties[404])) {

                // Do we have an email address?
                $props = $currentNode->getProperties(array('{http://sabredav.org/ns}email-address'));
                if (isset($props['{http://sabredav.org/ns}email-address'])) {
                    $email = $props['{http://sabredav.org/ns}email-address'];
                } else {
                    // We're going to make up an emailaddress
                    $email = $currentNode->getName() . '.sabredav@' . $this->server->httpRequest->getHeader('host');
                }
                $properties[200][$calProp] = new Sabre_DAV_Property_Href('mailto:' . $email, false);
                unset($properties[404][$calProp]);

            }


        }

        if ($currentNode instanceof Sabre_CalDAV_Calendar || $currentNode instanceof Sabre_CalDAV_CalendarObject) {
            if (array_key_exists('{DAV:}supported-report-set', $properties[200])) {
                $properties[200]['{DAV:}supported-report-set']->addReport(array(
                     '{' . self::NS_CALDAV . '}calendar-multiget',
                     '{' . self::NS_CALDAV . '}calendar-query',
                //     '{' . self::NS_CALDAV . '}supported-collation-set',
                //     '{' . self::NS_CALDAV . '}free-busy-query',
                ));
            }
        }

        
    }

    /**
     * This function handles the calendar-multiget REPORT.
     *
     * This report is used by the client to fetch the content of a series
     * of urls. Effectively avoiding a lot of redundant requests.
     * 
     * @param DOMNode $dom 
     * @return void
     */
    public function calendarMultiGetReport($dom) {

        $properties = array_keys(Sabre_DAV_XMLUtil::parseProperties($dom->firstChild));

        $hrefElems = $dom->getElementsByTagNameNS('urn:DAV','href');
        foreach($hrefElems as $elem) {
            $uri = $this->server->calculateUri($elem->nodeValue);
            list($objProps) = $this->server->getPropertiesForPath($uri,$properties);
            $propertyList[]=$objProps;

        }

        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendBody($this->server->generateMultiStatus($propertyList));

    }

    /**
     * This function handles the calendar-query REPORT
     *
     * This report is used by clients to request calendar objects based on
     * complex conditions.
     * 
     * @param DOMNode $dom 
     * @return void
     */
    public function calendarQueryReport($dom) {

        $requestedProperties = array_keys(Sabre_DAV_XMLUtil::parseProperties($dom->firstChild));

        $filterNode = $dom->getElementsByTagNameNS('urn:ietf:params:xml:ns:caldav','filter');
        if ($filterNode->length!==1) {
            throw new Sabre_DAV_Exception_BadRequest('The calendar-query report must have a filter element');
        }
        $filters = $this->parseCalendarQueryFilters($filterNode->item(0));

        $requestedCalendarData = true;

        if (!in_array('{urn:ietf:params:xml:ns:caldav}calendar-data', $requestedProperties)) {
            // We always retrieve calendar-data, as we need it for filtering.
            $requestedProperties[] = '{urn:ietf:params:xml:ns:caldav}calendar-data';

            // If calendar-data wasn't explicitly requested, we need to remove 
            // it after processing.
            $requestedCalendarData = false;
        }

        // These are the list of nodes that potentially match the requirement
        $candidateNodes = $this->server->getPropertiesForPath($this->server->getRequestUri(),$requestedProperties,$this->server->getHTTPDepth(0));

        $verifiedNodes = array();

        foreach($candidateNodes as $node) {

            // If the node didn't have a calendar-data property, it must not be a calendar object
            if (!isset($node[200]['{urn:ietf:params:xml:ns:caldav}calendar-data'])) continue;

            if ($this->validateFilters($node[200]['{urn:ietf:params:xml:ns:caldav}calendar-data'],$filters)) {
                
                if (!$requestedCalendarData) {
                    unset($node[200]['{urn:ietf:params:xml:ns:caldav}calendar-data']);
                }
                $verifiedNodes[] = $node;
            } 

        }

        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendBody($this->server->generateMultiStatus($verifiedNodes));

    }


    /**
     * This function parses the calendar-query report request body
     *
     * The body is quite complicated, so we're turning it into a PHP
     * array.
     * 
     * @param DOMNode $domNode 
     * @return array 
     */
    public function parseCalendarQueryFilters($domNode,$basePath = '/c:iCalendar', &$filters = array()) {

        foreach($domNode->childNodes as $child) {

            switch(Sabre_DAV_XMLUtil::toClarkNotation($child)) {

                case '{urn:ietf:params:xml:ns:caldav}comp-filter' :
                case '{urn:ietf:params:xml:ns:caldav}prop-filter' :
                   
                    $filterName = $basePath . '/' . 'c:' . strtolower($child->getAttribute('name'));
                    $filters[$filterName] = array(); 

                    $this->parseCalendarQueryFilters($child, $filterName,$filters);
                    break;

                case '{urn:ietf:params:xml:ns:caldav}time-range' :
               
                    if ($start = $child->getAttribute('start')) {
                        $start = $this->parseICalendarDateTime($start);
                    } else {
                        $start = null;
                    }
                    if ($end = $child->getAttribute('end')) {
                        $end = $this->parseICalendarDateTime($end);
                    } else {
                        $end = null;
                    }

                    if (!is_null($start) && !is_null($end) && $end <= $start) {
                        throw new Sabre_DAV_Exception_BadRequest('The end-date must be larger than the start-date in the time-range filter');
                    }

                    $filters[$basePath]['time-range'] = array(
                        'start' => $start,
                        'end'   => $end
                    );
                    break;

                case '{urn:ietf:params:xml:ns:caldav}is-not-defined' :
                    $filters[$basePath]['is-not-defined'] = true;
                    break;

                case '{urn:ietf:params:xml:ns:caldav}param-filter' :
               
                    $filterName = $basePath . '/@' . strtolower($child->getAttribute('name'));
                    $filters[$filterName] = array();
                    $this->parseCalendarQueryFilters($child, $filterName, $filters);
                    break;

                case '{urn:ietf:params:xml:ns:caldav}text-match' :
               
                    $collation = $child->getAttribute('collation');
                    if (!$collation) $collation = 'i;ascii-casemap';

                    $filters[$basePath]['text-match'] = array(
                        'collation' => $collation,
                        'negate-condition' => $child->getAttribute('negate-condition')==='yes',
                        'value' => $child->nodeValue,
                    );
                    break;

            }

        }

        return $filters;

    }

    /**
     * Verify if a list of filters applies to the calendar data object 
     *
     * The calendarData object must be a valid iCalendar blob. The list of 
     * filters must be formatted as parsed by Sabre_CalDAV_Plugin::parseCalendarQueryFilters
     *
     * @param string $calendarData 
     * @param array $filters 
     * @return bool 
     */
    public function validateFilters($calendarData,$filters) {

        // We are converting the calendar object to an XML structure
        // This makes it far easier to parse
        $xCalendarData = Sabre_CalDAV_ICalendarUtil::toXCal($calendarData);
        $xml = simplexml_load_string($xCalendarData);
        $xml->registerXPathNamespace('c','urn:ietf:params:xml:ns:xcal');

        foreach($filters as $xpath=>$filter) {

            // if-not-defined comes first
            if (isset($filter['is-not-defined'])) {
                if (!$xml->xpath($xpath))
                    continue;
                else
                    return false;
                
            }

            $elem = $xml->xpath($xpath);
            
            if (!$elem) return false;
            $elem = $elem[0];

            if (isset($filter['time-range'])) {

                switch($elem->getName()) {
                    case 'vevent' :
                        $result = $this->validateTimeRangeFilterForEvent($xml,$xpath,$filter);
                        if ($result===false) return false;
                        break;
                    case 'vtodo' :
                        $result = $this->validateTimeRangeFilterForTodo($xml,$xpath,$filter);
                        if ($result===false) return false;
                        break;
                    case 'vjournal' :
                        // TODO: not implemented
                        break;
                        $result = $this->validateTimeRangeFilterForJournal($xml,$xpath,$filter);
                        if ($result===false) return false;
                        break;
                    case 'vfreebusy' :
                        // TODO: not implemented
                        break;
                        $result = $this->validateTimeRangeFilterForFreeBusy($xml,$xpath,$filter);
                        if ($result===false) return false;
                        break;
                    case 'valarm' :
                        // TODO: not implemented
                        break;
                        $result = $this->validateTimeRangeFilterForAlarm($xml,$xpath,$filter);
                        if ($result===false) return false;
                        break;

                }

            } 

            if (isset($filter['text-match'])) {
                $currentString = (string)$elem;

                $isMatching = $this->substringMatch($currentString, $filter['text-match']['value'], $filter['text-match']['collation']);
                if ($filter['text-match']['negate-condition'] && $isMatching) return false;
                if (!$filter['text-match']['negate-condition'] && !$isMatching) return false;
                
            }

        }
        return true;
        
    }

    private function validateTimeRangeFilterForEvent(SimpleXMLElement $xml,$currentXPath,array $currentFilter) {

        // Grabbing the DTSTART property
        $xdtstart = $xml->xpath($currentXPath.'/c:dtstart');
        if (!count($xdtstart)) {
            throw new Sabre_DAV_Exception_BadRequest('DTSTART property missing from calendar object');
        }

        // The dtstart can be both a date, or datetime property
        if ((string)$xdtstart[0]['value']==='DATE') {
            $isDateTime = false;
        } else {
            $isDateTime = true;
        }

        // Determining the timezone
        if ($tzid = (string)$xdtstart[0]['tzid']) {
            $tz = new DateTimeZone($tzid);
        } else {
            $tz = null;
        }
        if ($isDateTime) {
            $dtstart = $this->parseICalendarDateTime((string)$xdtstart[0],$tz);
        } else {
            $dtstart = $this->parseICalendarDate((string)$xdtstart[0]);
        }


        // Grabbing the DTEND property
        $xdtend = $xml->xpath($currentXPath.'/c:dtend');
        $dtend = null;

        if (count($xdtend)) {
            // Determining the timezone
            if ($tzid = (string)$xdtend[0]['tzid']) {
                $tz = new DateTimeZone($tzid);
            } else {
                $tz = null;
            }

            // Since the VALUE parameter of both DTSTART and DTEND must be the same
            // we can assume we don't need to check the VALUE paramter of DTEND.
            if ($isDateTime) {
                $dtend = $this->parseICalendarDateTime((string)$xdtend[0],$tz);
            } else {
                $dtend = $this->parseICalendarDate((string)$xdtend[0],$tz);
            }

        } 
        
        if (is_null($dtend)) {
            // The DTEND property was not found. We will first see if the event has a duration
            // property

            $xduration = $xml->xpath($currentXPath.'/c:duration');
            if (count($xduration)) {
                $duration = $this->parseICalendarDuration((string)$xduration[0]);

                // Making sure that the duration is bigger than 0 seconds.
                $tempDT = clone $dtstart;
                $tempDT->modify($duration);
                if ($tempDT > $dtstart) {

                    // use DTEND = DTSTART + DURATION 
                    $dtend = $tempDT;
                } else {
                    // use DTEND = DTSTART
                    $dtend = $dtstart;
                }

            }
        }

        if (is_null($dtend)) {
            if ($isDateTime) {
                // DTEND = DTSTART
                $dtend = $dtstart;
            } else {
                // DTEND = DTSTART + 1 DAY
                $dtend = clone $dtstart;
                $dtend->modify('+1 day');
            }
        }
       
        if (!is_null($currentFilter['time-range']['start']) && $currentFilter['time-range']['start'] >= $dtend)  return false;
        if (!is_null($currentFilter['time-range']['end'])   && $currentFilter['time-range']['end']   <= $dtstart) return false;
        return true;
    
    }

    private function validateTimeRangeFilterForTodo(SimpleXMLElement $xml,$currentXPath,array $filter) {

        // Gathering all relevant elements

        $dtStart = null;
        $duration = null;
        $due = null;
        $completed = null;
        $created = null;

        $xdt = $xml->xpath($currentXPath.'/c:dtstart');
        if (count($xdt)) {
            // The dtstart can be both a date, or datetime property
            if ((string)$xdt[0]['value']==='DATE') {
                $isDateTime = false;
            } else {
                $isDateTime = true;
            }

            // Determining the timezone
            if ($tzid = (string)$xdt[0]['tzid']) {
                $tz = new DateTimeZone($tzid);
            } else {
                $tz = null;
            }
            if ($isDateTime) {
                $dtStart = $this->parseICalendarDateTime((string)$xdt[0],$tz);
            } else {
                $dtStart = $this->parseICalendarDate((string)$xdt[0]);
            }
        }

        // Only need to grab duration if dtStart is set
        if (!is_null($dtStart)) {

            $xduration = $xml->xpath($currentXPath.'/c:duration');
            if (count($xduration)) {
                $duration = $this->parseICalendarDuration((string)$xduration[0]);
            }

        }

        if (!is_null($dtStart) && !is_null($duration)) {

            // Comparision from RFC 4791:
            // (start <= DTSTART+DURATION) AND ((end > DTSTART) OR (end >= DTSTART+DURATION))

            $end = clone $dtStart;
            $end->modify($duration);

            if( (is_null($filter['time-range']['start']) || $filter['time-range']['start'] <= $end) &&
                (is_null($filter['time-range']['end']) || $filter['time-range']['end'] > $dtStart || $filter['time-range']['end'] >= $end) ) {
                return true;
            } else {
                return false;
            }

        }

        // Need to grab the DUE property
        $xdt = $xml->xpath($currentXPath.'/c:due');
        if (count($xdt)) {
            // The due property can be both a date, or datetime property
            if ((string)$xdt[0]['value']==='DATE') {
                $isDateTime = false;
            } else {
                $isDateTime = true;
            }
            // Determining the timezone
            if ($tzid = (string)$xdt[0]['tzid']) {
                $tz = new DateTimeZone($tzid);
            } else {
                $tz = null;
            }
            if ($isDateTime) {
                $due = $this->parseICalendarDateTime((string)$xdt[0],$tz);
            } else {
                $due = $this->parseICalendarDate((string)$xdt[0]);
            }
        }

        if (!is_null($dtStart) && !is_null($due)) {

            // Comparision from RFC 4791:
            // ((start < DUE) OR (start <= DTSTART)) AND ((end > DTSTART) OR (end >= DUE))
            
            if( (is_null($filter['time-range']['start']) || $filter['time-range']['start'] < $due || $filter['time-range']['start'] < $dtstart) &&
                (is_null($filter['time-range']['end'])   || $filter['time-range']['end'] >= $due) ) {
                return true;
            } else {
                return false;
            }

        }

        if (!is_null($dtStart)) {
            
            // Comparision from RFC 4791
            // (start <= DTSTART)  AND (end > DTSTART)
            if ( (is_null($filter['time-range']['start']) || $filter['time-range']['start'] <= $dtStart) &&
                 (is_null($filter['time-range']['end'])   || $filter['time-range']['end'] > $dtStart) ) {
                 return true;
            } else {
                return false;
            }

        }

        if (!is_null($due)) {
            
            // Comparison from RFC 4791
            // (start < DUE) AND (end >= DUE)
            if ( (is_null($filter['time-range']['start']) || $filter['time-range']['start'] < $due) &&
                 (is_null($filter['time-range']['end'])   || $filter['time-range']['end'] >= $due) ) {
                 return true;
            } else {
                return false;
            }

        }
        // Need to grab the COMPLETED property
        $xdt = $xml->xpath($currentXPath.'/c:completed');
        if (count($xdt)) {
            $completed = $this->parseICalendarDateTime((string)$xdt[0]);
        }
        // Need to grab the CREATED property
        $xdt = $xml->xpath($currentXPath.'/c:created');
        if (count($xdt)) {
            $created = $this->parseICalendarDateTime((string)$xdt[0]);
        }

        if (!is_null($completed) && !is_null($created)) {
            // Comparison from RFC 4791
            // ((start <= CREATED) OR (start <= COMPLETED)) AND ((end >= CREATED) OR (end >= COMPLETED))
            if( (is_null($filter['time-range']['start']) || $filter['time-range']['start'] <= $created || $filter['time-range']['start'] <= $completed) &&
                (is_null($filter['time-range']['end'])   || $filter['time-range']['end'] >= $created   || $filter['time-range']['end'] >= $completed)) {
                return true;
            } else {
                return false;
            }
        }

        if (!is_null($completed)) {
            // Comparison from RFC 4791
            // (start <= COMPLETED) AND (end  >= COMPLETED)
            if( (is_null($filter['time-range']['start']) || $filter['time-range']['start'] <= $completed) &&
                (is_null($filter['time-range']['end'])   || $filter['time-range']['end'] >= $completed)) {
                return true;
            } else {
                return false;
            }
        }

        if (!is_null($created)) {
            // Comparison from RFC 4791
            // (end > CREATED)
            if( (is_null($filter['time-range']['end']) || $filter['time-range']['end'] > $created) ) {
                return true;
            } else {
                return false;
            }
        }

        // Everything else is TRUE
        return true;

    }

    public function substringMatch($haystack, $needle, $collation) {

        switch($collation) {
            case 'i;ascii-casemap' :
                // default strtolower takes locale into consideration
                // we don't want this.
                $haystack = str_replace(range('a','z'), range('A','Z'), $haystack);
                $needle = str_replace(range('a','z'), range('A','Z'), $needle);
                return strpos($haystack, $needle)!==false;

            case 'i;octet' :
                return strpos($haystack, $needle)!==false;
            
            default:
                throw new Sabre_DAV_Exception_BadRequest('Unknown collation: ' . $collation);
        }                

    }

    /**
     * Parses an iCalendar (rfc5545) formatted datetime and returns a DateTime object
     *
     * Specifying a reference timezone is optional. It will only be used
     * if the non-UTC format is used. The argument is used as a reference, the 
     * returned DateTime object will still be in the UTC timezone.
     *
     * @param string $dt 
     * @param DateTimeZone $tz 
     * @return DateTime 
     */
    public function parseICalendarDateTime($dt,DateTimeZone $tz = null) {

        // Format is YYYYMMDD + "T" + hhmmss 
        $result = preg_match('/^([1-3][0-9]{3})([0-1][0-9])([0-3][0-9])T([0-2][0-9])([0-5][0-9])([0-5][0-9])([Z]?)$/',$dt,$matches);

        if (!$result) {
            throw new Sabre_DAV_Exception_BadRequest('The supplied iCalendar datetime value is incorrect: ' . $dt);
        }

        if ($matches[7]==='Z' || is_null($tz)) {
            $tz = new DateTimeZone('UTC');
        } 
        $date = new DateTime($matches[1] . '-' . $matches[2] . '-' . $matches[3] . ' ' . $matches[4] . ':' . $matches[5] .':' . $matches[6], $tz);

        // Still resetting the timezone, to normalize everything to UTC
        $date->setTimeZone(new DateTimeZone('UTC'));
        return $date;

    }

    /**
     * Parses an iCalendar (rfc5545) formatted datetime and returns a DateTime object
     *
     * @param string $date 
     * @param DateTimeZone $tz 
     * @return DateTime 
     */
    public function parseICalendarDate($date) {

        // Format is YYYYMMDD
        $result = preg_match('/^([1-3][0-9]{3})([0-1][0-9])([0-3][0-9])$/',$date,$matches);

        if (!$result) {
            throw new Sabre_DAV_Exception_BadRequest('The supplied iCalendar date value is incorrect: ' . $date);
        }

        $date = new DateTime($matches[1] . '-' . $matches[2] . '-' . $matches[3], new DateTimeZone('UTC'));
        return $date;

    }
   
    /**
     * Parses an iCalendar (RFC5545) formatted duration and returns a string suitable
     * for strtotime or DateTime::modify.
     *
     * 
     * NOTE: When we require PHP 5.3 this can be replaced by the DateTimeInterval object, which
     * supports ISO 8601 Intervals, which is a superset of ICalendar durations.
     *
     * For now though, we're just gonna live with this messy system
     *
     * @param string $duration
     * @return string
     */
    public function parseICalendarDuration($duration) {

        $result = preg_match('/^(?P<plusminus>\+|-)?P((?P<week>\d+)W)?((?P<day>\d+)D)?(T((?P<hour>\d+)H)?((?P<minute>\d+)M)?((?P<second>\d+)S)?)?$/', $duration, $matches);
        if (!$result) {
            throw new Sabre_DAV_Exception_BadRequest('The supplied iCalendar duration value is incorrect: ' . $duration);
        }
       
        $parts = array(
            'week',
            'day',
            'hour',
            'minute',
            'second',
        );

        $newDur = '';
        foreach($parts as $part) {
            if (isset($matches[$part]) && $matches[$part]) {
                $newDur.=' '.$matches[$part] . ' ' . $part . 's';
            }
        }

        $newDur = ($matches['plusminus']==='-'?'-':'+') . trim($newDur);
        return $newDur;

    }

}
