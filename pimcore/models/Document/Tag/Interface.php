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

interface Document_Tag_Interface {

    /**
     * Return the data for direct output to the frontend, can also contain HTML code!
     *
     * @return string
     */
    public function frontend();

    /**
     * Return the data for the admin, can also contain HTML code!
     *
     * @return string
     */
    public function admin();

    /**
     * Get the current data stored for the element
     *
     * @return mixed
     */
    public function getData();

    /**
     * Return the type of the element
     *
     * @return string
     */
    public function getType();

    /**
     * Receives the data from the editmode and convert this to the internal data in the object eg. image-id to Asset_Image
     *
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data);

    /**
     * Receives the data from the resource, an convert to the internal data in the object eg. image-id to Asset_Image
     *
     * @param mixed $data
     * @return string
     */
    public function setDataFromResource($data);


    /**
     * Receives data from webservice import and fills the current tag's data
     *
     * @abstract
     * @param  object $wsElement
     * @return void
     */
    public function getFromWebserviceImport($wsElement);


    /**
     * Returns the current tag's data for web service export
     *
     * @abstract
     * @return array
     */
    public function getForWebserviceExport();

}
