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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Tool;

class XmlWriter extends \Zend_Config_Writer_Xml {

    /**
     * name of the root element
     *
     * @var string
     */
    protected $rootElementName = 'data';

    /**
     * Attributes for the root element
     *
     * @var array
     */
    protected $rootElementAttributes = array();

    /**
     * @var string
     */
    protected $encoding = 'utf-8';

    /**
     * array of options to set
     *
     * @param array $options
     */
    public function __construct($options = array()){
        foreach($options as $key => $value){
            $setter = "set" . ucfirst($key);
            if(method_exists($this,$setter)){
                $this->$setter($value);
            }
        }
    }

    /**
     * @return array
     */
    public function getRootElementAttributes()
    {
        return $this->rootElementAttributes;
    }

    /**
     * @param array $rootElementAttributes
     *
     * @return $this
     */
    public function setRootElementAttributes($rootElementAttributes)
    {
        $this->rootElementAttributes = $rootElementAttributes;
        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setRootElementName($name){
        $this->rootElementName = $name;
        return $this;
    }

    /**
     * @param \Zend_Config $config
     *
     * @return $this|\Zend_Config_Writer
     */
    public function setConfig($config)
    {
        if(is_array($config)){
            $config = new \Zend_Config($config);
        }
        $this->_config = $config;

        return $this;
    }

    /**
     * @return string
     */
    public function getRootElementName(){
        return $this->rootElementName;
    }

    /**
     * @param $encoding
     *
     * @return $this
     */
    public function setEncoding($encoding){
        $this->encoding  = $encoding;
        return $this;
    }

    /**
     * @return string
     */
    public function getEncoding(){
        return $this->encoding;
    }

    /**
     * returns the XML string
     *
     * @return string
     * @throws \Zend_Config_Exception
     */
    public function render()
    {

        $xml         = new \SimpleXMLElement('<'.$this->getRootElementName().' encoding="' . $this->getEncoding().'"/>');
        if($this->_config){
            $extends     = $this->_config->getExtends();
            $sectionName = $this->_config->getSectionName();
            if (is_string($sectionName)) {
                $child = $xml->addChild($sectionName);

                $this->_addBranch($this->_config, $child, $xml);
            } else {
                foreach ($this->_config as $sectionName => $data) {
                    if (!($data instanceof \Zend_Config)) {
                        $xml->addChild($sectionName, (string) $data);
                    } else {
                        $child = $xml->addChild($sectionName);

                        if (isset($extends[$sectionName])) {
                            $child->addAttribute('zf:extends', $extends[$sectionName], \Zend_Config_Xml::XML_NAMESPACE);
                        }

                        $this->_addBranch($data, $child, $xml);
                    }
                }
            }
        }

        $dom = dom_import_simplexml($xml)->ownerDocument;
        $dom->formatOutput = true;

        $xmlString = $dom->saveXML();

        return $xmlString;
    }

    /**
     *  displays the rendered XML
     */
    public function displayXML(){
        header("Content-Type: application/xml");
        die($this->render());
    }
}