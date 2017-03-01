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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\File\Transfer\Adapter;

use Pimcore\File;

class Ftp extends \Zend_File_Transfer_Adapter_Abstract
{
    use \Pimcore\File\Transfer\Adapter\AdapterTrait;

    /**
     * FTP Connection resource
     *
     * @var resource
     */
    protected $connection;

    /**
     * @var bool
     */
    protected $loggedIn = false;

    /**
     * Uploaded files
     *
     * @var array
     */
    protected $uploadedFiles = [];

    /**
     * Downloaded files
     *
     * @var array
     */
    protected $downloadedFiles = [];

    /**
     * Transfer mode for uploads and downloads
     *
     * @var int
     */
    protected $transferMode = FTP_ASCII;

    /**
     * @var bool
     */
    protected $passive = false;

    /**
     * @return boolean
     */
    public function isLoggedIn()
    {
        return $this->loggedIn;
    }

    /**
     * @param boolean $loggedIn
     *
     * @return $this
     */
    public function setLoggedIn($loggedIn)
    {
        $this->loggedIn = $loggedIn;

        return $this;
    }

    /**
     * @return int
     */
    public function getTransferMode()
    {
        return $this->transferMode;
    }

    /**
     * @param int $transferMode
     *
     * @return $this
     */
    public function setTransferMode($transferMode)
    {
        $this->transferMode = $transferMode;

        return $this;
    }

    /**
     * @return resource
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param $connection
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return boolean
     */
    public function isPassive()
    {
        return $this->passive;
    }

    /**
     * @param boolean $passive
     */
    public function setPassive($passive)
    {
        $this->passive = $passive;
    }

    /**
     * Connects to the FTP-Host
     *
     * @return $this
     */
    public function connect()
    {
        if (!$this->getConnection()) {
            $connection = ftp_connect($this->getHost());
            $this->setConnection($connection);
        }

        return $this;
    }

    /**
     * Login with the given credentials
     *
     * @return $this
     */
    public function login()
    {
        if (!$this->getConnection()) {
            $this->connect();
        }
        $this->setLoggedIn(ftp_login($this->getConnection(), $this->getUsername(), $this->getPassword()));

        ftp_pasv($this->getConnection(), $this->isPassive());

        return $this;
    }

    /**
     * Upload file to the FTP-Host
     *
     * @param null $options
     *
     * @return bool|void
     * @throws \Exception
     */
    public function send($options = null)
    {
        if (!$this->isLoggedIn()) {
            $this->login();
        }

        if (!ftp_put($this->getConnection(), $this->getDestinationFile(), $this->getSourceFile(), $this->getTransferMode())) {
            throw new \Exception("Upload of file " . $this->getSourceFile() . ' failed.');
        }
        $this->uploadedFiles[$this->getSourceFile()] = true;

        return true;
    }

    /**
     * Download file from the FTP-Host
     *
     * @param null $options
     *
     * @return bool|void
     * @throws \Exception
     */
    public function receive($options = null)
    {
        if (!ftp_get($this->getConnection(), $this->getDestinationFile(), $this->getSourceFile(), $this->getTransferMode())) {
            throw new \Exception("Download of file " . $this->getSourceFile() . ' failed.');
        }
        $this->downloadedFiles[$this->getSourceFile()] = true;

        return true;
    }

    /**
     * Wrapper for isUploaded()
     *
     * @param  array|string|null $files
     *
     * @return bool
     */
    public function isSent($files = null)
    {
        return $this->isUploaded($files);
    }

    /**
     * Is file received?
     *
     * @param string $files
     *
     * @return bool
     */
    public function isReceived($files = null)
    {
        return $this->downloadedFiles[$files];
    }

    /**
     * Has a file been uploaded ?
     *
     * @param  string $file
     *
     * @return bool
     */
    public function isUploaded($file = null)
    {
        return $this->uploadedFiles[$file];
    }

    /**
     * Has the file been filtered?
     * Not jet implemented!
     *
     * @param array|string|null $files
     *
     * @return bool
     */
    public function isFiltered($files = null)
    {
        return false;
    }
}
