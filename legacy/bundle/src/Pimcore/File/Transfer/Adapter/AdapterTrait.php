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

trait AdapterTrait
{

    /**
     * @var null | string
     */
    protected $username = '';

    /**
     * @var null | string
     */
    protected $password = '';

    /**
     * @var null | string
     */
    protected $host = '';


    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     *
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @var null
     */
    protected $sourceFile = null;

    /**
     * @var null
     */
    protected $destinationFile = null;

    /**
     * @param $sourceFile
     * @return $this
     */
    public function setSourceFile($sourceFile)
    {
        $this->sourceFile = $sourceFile;

        return $this;
    }

    /**
     * @return null
     */
    public function getSourceFile()
    {
        return $this->sourceFile;
    }

    /**
     * @param $destinationFile
     *
     * @return $this
     */
    public function setDestinationFile($destinationFile)
    {
        $this->destinationFile  = $destinationFile;

        return $this;
    }

    /**
     * @return null
     */
    public function getDestinationFile()
    {
        return $this->destinationFile;
    }
}
