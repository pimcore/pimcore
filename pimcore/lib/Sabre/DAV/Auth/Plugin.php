<?php

/**
 * This plugin provides Authentication for a WebDAV server.
 * 
 * It relies on a Backend object, which provides user information.
 *
 * Additionally, it provides support for:
 *  * {DAV:}current-user-principal property from RFC5397
 *  * {DAV:}principal-collection-set property from RFC3744
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Auth_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * Reference to main server object 
     * 
     * @var Sabre_DAV_Server 
     */
    private $server;

    /**
     * Authentication backend
     * 
     * @var Sabre_DAV_Auth_Backend_Abstract 
     */
    private $authBackend;

    /**
     * The authentication realm. 
     * 
     * @var string 
     */
    private $realm;

    /**
     * __construct 
     * 
     * @param Sabre_DAV_Auth_Backend_Abstract $authBackend 
     * @param string $realm 
     * @return void
     */
    public function __construct(Sabre_DAV_Auth_Backend_Abstract $authBackend, $realm) {

        $this->authBackend = $authBackend;
        $this->realm = $realm;

    }

    /**
     * Initializes the plugin. This function is automatically called by the server  
     * 
     * @param Sabre_DAV_Server $server 
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $this->server->subscribeEvent('beforeMethod',array($this,'beforeMethod'),10);
        $this->server->subscribeEvent('afterGetProperties',array($this,'afterGetProperties'));
        $this->server->subscribeEvent('report',array($this,'report'));

    }

    /**
     * This method intercepts calls to PROPFIND and similar lookups 
     * 
     * This is done to inject the current-user-principal if this is requested.
     *
     * @todo support for 'unauthenticated'
     * @return void  
     */
    public function afterGetProperties($href, &$properties) {

        if (array_key_exists('{DAV:}current-user-principal', $properties[404])) {
            if ($ui = $this->authBackend->getCurrentUser()) {
                $properties[200]['{DAV:}current-user-principal'] = new Sabre_DAV_Property_Principal(Sabre_DAV_Property_Principal::HREF, $ui['uri']);
            } else {
                $properties[200]['{DAV:}current-user-principal'] = new Sabre_DAV_Property_Principal(Sabre_DAV_Property_Principal::UNAUTHENTICATED);
            }
            unset($properties[404]['{DAV:}current-user-principal']);
        }
        if (array_key_exists('{DAV:}principal-collection-set', $properties[404])) {
            $properties[200]['{DAV:}principal-collection-set'] = new Sabre_DAV_Property_Href('principals');
            unset($properties[404]['{DAV:}principal-collection-set']);
        }
        if (array_key_exists('{DAV:}supported-report-set', $properties[200])) {
            $properties[200]['{DAV:}supported-report-set']->addReport(array(
                '{DAV:}expand-property',
            ));
        }


    }

    /**
     * This method is called before any HTTP method and forces users to be authenticated
     * 
     * @param string $method
     * @throws Sabre_DAV_Exception_NotAuthenticated
     * @return bool 
     */
    public function beforeMethod($method) {

        $this->authBackend->authenticate($this->server,$this->realm);

    }

    /**
     * This functions handles REPORT requests 
     * 
     * @param string $reportName 
     * @param DOMNode $dom 
     * @return bool|null 
     */
    public function report($reportName,$dom) {

        switch($reportName) { 
            case '{DAV:}expand-property' :
                $this->expandPropertyReport($dom);
                return false;
            case '{DAV:}principal-property-search' :
                if ($this->server->getRequestUri()==='principals') {
                    $this->principalPropertySearchReport($dom);
                    return false;
                }
                break;
            case '{DAV:}principal-search-property-set' :
                if ($this->server->getRequestUri()==='principals') {
                    $this->principalSearchPropertySetReport($dom);
                    return false;
                }
                break;
                 
        }
    
    }

    /**
     * The expand-property report is defined in RFC3253 section 3-8. 
     *
     * This report is very similar to a standard PROPFIND. The difference is
     * that it has the additional ability to look at properties containing a
     * {DAV:}href element, follow that property and grab additional elements
     * there.
     *
     * Other rfc's, such as ACL rely on this report, so it made sense to put
     * it in this plugin.
     *
     * @param DOMElement $dom 
     * @return void
     */
    protected function expandPropertyReport($dom) {

        $requestedProperties = $this->parseExpandPropertyReportRequest($dom->firstChild->firstChild);
        $depth = $this->server->getHTTPDepth(0);
        $requestUri = $this->server->getRequestUri();

        $result = $this->expandProperties($requestUri,$requestedProperties,$depth);

        $dom = new DOMDocument('1.0','utf-8');
        $dom->formatOutput = true;
        $multiStatus = $dom->createElement('d:multistatus');
        $dom->appendChild($multiStatus);

        // Adding in default namespaces
        foreach($this->server->xmlNamespaces as $namespace=>$prefix) {

            $multiStatus->setAttribute('xmlns:' . $prefix,$namespace);

        }

        foreach($result as $entry) {

            $entry->serialize($this->server,$multiStatus);

        }

        $xml = $dom->saveXML();
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->sendBody($xml);

        // Make sure the event chain is broken
        return false;

    }

    /**
     * This method is used by expandPropertyReport to parse
     * out the entire HTTP request.
     * 
     * @param DOMElement $node 
     * @return array 
     */
    protected function parseExpandPropertyReportRequest($node) {

        $requestedProperties = array();
        do {

            if (Sabre_DAV_XMLUtil::toClarkNotation($node)!=='{DAV:}property') continue;
                
            if ($node->firstChild) {
                
                $children = $this->parseExpandPropertyReportRequest($node->firstChild);

            } else {

                $children = array();

            }

            $namespace = $node->getAttribute('namespace');
            if (!$namespace) $namespace = 'DAV:';

            $propName = '{'.$namespace.'}' . $node->getAttribute('name');
            $requestedProperties[$propName] = $children; 

        } while ($node = $node->nextSibling);

        return $requestedProperties;

    }

    /**
     * This method expands all the properties and returns
     * a list with property values
     *
     * @param array $path
     * @param array $requestedProperties the list of required properties
     * @param array $depth
     */
    protected function expandProperties($path,array $requestedProperties,$depth) { 

        $foundProperties = $this->server->getPropertiesForPath($path,array_keys($requestedProperties),$depth);

        $result = array();

        foreach($foundProperties as $node) {

            foreach($requestedProperties as $propertyName=>$childRequestedProperties) {

                // We're only traversing if sub-properties were requested
                if(count($childRequestedProperties)===0) continue;
                
                // We only have to do the expansion if the property was found
                // and it contains an href element.
                if (!array_key_exists($propertyName,$node[200])) continue;
                if (!($node[200][$propertyName] instanceof Sabre_DAV_Property_IHref)) continue;

                $href = $node[200][$propertyName]->getHref();
                list($node[200][$propertyName]) = $this->expandProperties($href,$childRequestedProperties,0);

            }
            $result[] = new Sabre_DAV_Property_Response($path, $node);

        }

        return $result;

    }

    protected function principalSearchPropertySetReport(DOMDocument $dom) {

        $searchProperties = array(
            '{DAV:}displayname' => 'display name'

        );

        $httpDepth = $this->server->getHTTPDepth(0);
        if ($httpDepth!==0) {
            throw new Sabre_DAV_Exception_BadRequest('This report is only defined when Depth: 0');
        }
        
        if ($dom->firstChild->hasChildNodes()) 
            throw new Sabre_DAV_Exception_BadRequest('The principal-search-property-set report element is not allowed to have child elements'); 

        $dom = new DOMDocument('1.0','utf-8');
        $dom->formatOutput = true;
        $root = $dom->createElement('d:principal-search-property-set');
        $dom->appendChild($root);
        // Adding in default namespaces
        foreach($this->server->xmlNamespaces as $namespace=>$prefix) {

            $root->setAttribute('xmlns:' . $prefix,$namespace);

        }

        $nsList = $this->server->xmlNamespaces; 

        foreach($searchProperties as $propertyName=>$description) {

            $psp = $dom->createElement('d:principal-search-property');
            $root->appendChild($psp);

            $prop = $dom->createElement('d:prop');
            $psp->appendChild($prop);
  
            $propName = null;
            preg_match('/^{([^}]*)}(.*)$/',$propertyName,$propName);

            //if (!isset($nsList[$propName[1]])) {
            //    $nsList[$propName[1]] = 'x' . count($nsList);
            //}

            // If the namespace was defined in the top-level xml namespaces, it means 
            // there was already a namespace declaration, and we don't have to worry about it.
            //if (isset($server->xmlNamespaces[$propName[1]])) {
                $currentProperty = $dom->createElement($nsList[$propName[1]] . ':' . $propName[2]);
            //} else {
            //    $currentProperty = $dom->createElementNS($propName[1],$nsList[$propName[1]].':' . $propName[2]);
            //}
            $prop->appendChild($currentProperty);

            $descriptionElem = $dom->createElement('d:description');
            $descriptionElem->setAttribute('xml:lang','en');
            $descriptionElem->appendChild($dom->createTextNode($description));
            $psp->appendChild($descriptionElem);


        }

        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendStatus(200);
        $this->server->httpResponse->sendBody($dom->saveXML());

    }

    protected function principalPropertySearchReport($dom) {

        $searchableProperties = array(
            '{DAV:}displayname' => 'display name'

        );

        list($searchProperties, $requestedProperties) = $this->parsePrincipalPropertySearchReportRequest($dom);

        $uri = $this->server->getRequestUri();
        
        $result = array();

        $lookupResults = $this->server->getPropertiesForPath($uri, array_keys($searchProperties), 1);

        // The first item in the results is the parent, so we get rid of it.
        array_shift($lookupResults);

        $matches = array();

        foreach($lookupResults as $lookupResult) {

            foreach($searchProperties as $searchProperty=>$searchValue) {
                if (!isset($searchableProperties[$searchProperty])) {
                    throw new Sabre_DAV_Exception_BadRequest('Searching for ' . $searchProperty . ' is not supported');
                }
                
                if (isset($lookupResult[200][$searchProperty]) &&
                    mb_stripos($lookupResult[200][$searchProperty], $searchValue, 0, 'UTF-8')!==false) {
                        $matches[] = $lookupResult['href'];
                }

            }

        }

        $matchProperties = array();

        foreach($matches as $match) {
            
           list($result) = $this->server->getPropertiesForPath($match, $requestedProperties, 0);
           $matchProperties[] = $result;

        }

        $xml = $this->server->generateMultiStatus($matchProperties);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->sendBody($xml);

    }

    protected function parsePrincipalPropertySearchReportRequest($dom) {

        $httpDepth = $this->server->getHTTPDepth(0);
        if ($httpDepth!==0) {
            throw new Sabre_DAV_Exception_BadRequest('This report is only defined when Depth: 0');
        }

        $searchProperties = array();

        // Parsing the search request
        foreach($dom->firstChild->childNodes as $searchNode) {

            if (Sabre_DAV_XMLUtil::toClarkNotation($searchNode)!=='{DAV:}property-search')
                continue;

            $propertyName = null;
            $propertyValue = null;

            foreach($searchNode->childNodes as $childNode) {

                switch(Sabre_DAV_XMLUtil::toClarkNotation($childNode)) {

                    case '{DAV:}prop' :
                        $property = Sabre_DAV_XMLUtil::parseProperties($searchNode);
                        reset($property); 
                        $propertyName = key($property);
                        break;

                    case '{DAV:}match' :
                        $propertyValue = $childNode->textContent;
                        break;

                }


            }

            if (is_null($propertyName) || is_null($propertyValue))
                throw new Sabre_DAV_Exception_BadRequest('Invalid search request. propertyname: ' . $propertyName . '. propertvvalue: ' . $propertyValue);

            $searchProperties[$propertyName] = $propertyValue;

        }

        return array($searchProperties, array_keys(Sabre_DAV_XMLUtil::parseProperties($dom->firstChild)));

    }

}
