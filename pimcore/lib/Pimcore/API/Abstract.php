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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_API_Abstract
{


    /**
     * Hook called before pimcore starts dispatchloop
     */
    public function preDispatch()
    {

    }


    /**
     *
     * Hook called before an asset was added
     *
     * @param Asset $asset
     */
    public function preAddAsset(Asset $asset)
    {

    }

    /**
     *
     * Hook called after an asset was added
     *
     * @param Asset $asset
     */
    public function postAddAsset(Asset $asset)
    {

    }

    /**
     * Hook called before an asset is deleted
     *
     * @param Asset $asset
     */
    public function preDeleteAsset(Asset $asset)
    {

    }

    /**
     * Hook called after an asset is deleted
     *
     * @param Asset $asset
     */
    public function postDeleteAsset(Asset $asset)
    {

    }

    /**
     * Hook called before an asset is updated
     *
     * @param Asset $asset
     */
    public function preUpdateAsset(Asset $asset)
    {

    }

    /**
     * Hook called after an asset is updated
     *
     * @param Asset $asset
     */
    public function postUpdateAsset(Asset $asset)
    {

    }


    /**
     *
     * Hook called before a document was added
     *
     * @param Document $document
     */
    public function preAddDocument(Document $document)
    {

    }

    /**
     *
     * Hook called after a document was added
     *
     * @param Document $document
     */
    public function postAddDocument(Document $document)
    {

    }

    /**
     * Hook called before a document is deleted
     *
     * @param Document $document
     */
    public function preDeleteDocument(Document $document)
    {

    }

    /**
     * Hook called after a document is deleted
     *
     * @param Document $document
     */
    public function postDeleteDocument(Document $document)
    {

    }

    /**
     * Hook called before a document is updated
     *
     * @param Document $document
     */
    public function preUpdateDocument(Document $document)
    {

    }

    /**
     * Hook called after  a document is updated
     *
     * @param Document $document
     */
    public function postUpdateDocument(Document $document)
    {

    }


    /**
     * Hook before an object was is added
     *
     * @param Object_Abstract $object
     */
    public function preAddObject(Object_Abstract $object)
    {

    }

    /**
     * Hook after an object was is added
     *
     * @param Object_Abstract $object
     */
    public function postAddObject(Object_Abstract $object)
    {

    }

    /**
     * Hook called before an object is deleted
     *
     * @param Object_Abstract $object
     */
    public function preDeleteObject(Object_Abstract $object)
    {

    }

    /**
     * Hook called after an object is deleted
     *
     * @param Object_Abstract $object
     */
    public function postDeleteObject(Object_Abstract $object)
    {

    }


    /**
     * Hook called before an object was updated
     *
     * @param Object_Abstract $object
     */
    public function preUpdateObject(Object_Abstract $object)
    {

    }

    /**
     * Hook called after an object was updated
     *
     * @param Object_Abstract $object
     */
    public function postUpdateObject(Object_Abstract $object)
    {

    }


    /**
     * Hook called when login in pimcore is about to fail. Must return
     * a valid pimcore User for successful authentication or null for failure.
     *
     * @param string $username username provided in login credentials
     * @param string $pasword password provided in login credentials
     * @return User authenticated user or null if login shall fail
     */
    public function authenticateUser($username, $password)
    {
    }

    /**
     * Hook called when the user logs out
     *
     * @param User $user
     */
    public function preLogoutUser(User $user)
    {
    }

    /**
     * Hook called when maintenance script is called
     */
    public function maintenance()
    {
    }

}