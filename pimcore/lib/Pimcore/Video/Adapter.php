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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Video;

abstract class Adapter
{

    /**
     * @var int
     */
    public $videoBitrate;

    /**
     * @var int
     */
    public $audioBitrate;

    /**
     * @var string
     */
    public $format;

    /**
     * @var string
     */
    public $destinationFile;

    /**
     * @var string
     */
    public $storageFile;

    /**
     * length in seconds
     * @var int
     */
    public $length;


    /**
     * @param $audioBitrate
     * @return $this
     */
    public function setAudioBitrate($audioBitrate)
    {
        $this->audioBitrate = $audioBitrate;

        return $this;
    }

    /**
     * @return int
     */
    public function getAudioBitrate()
    {
        return $this->audioBitrate;
    }

    /**
     * @param $videoBitrate
     * @return $this
     */
    public function setVideoBitrate($videoBitrate)
    {
        $this->videoBitrate = $videoBitrate;

        return $this;
    }

    /**
     * @return int
     */
    public function getVideoBitrate()
    {
        return $this->videoBitrate;
    }

    /**
     * @param $file
     * @return mixed
     */
    abstract public function load($file);

    /**
     * @return mixed
     */
    abstract public function save();

    /**
     * @abstract
     * @param $file
     * @param $timeOffset
     */
    abstract public function saveImage($file, $timeOffset = null);

    /**
     * @abstract
     */
    abstract public function destroy();

    /**
     * @param $format
     * @return $this
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param $destinationFile
     * @return $this
     */
    public function setDestinationFile($destinationFile)
    {
        $this->destinationFile = $destinationFile;

        return $this;
    }

    /**
     * @return string
     */
    public function getDestinationFile()
    {
        return $this->destinationFile;
    }

    /**
     * @param $length
     * @return $this
     */
    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @return string
     */
    public function getStorageFile()
    {
        return $this->storageFile;
    }

    /**
     * @param string $storageFile
     */
    public function setStorageFile($storageFile)
    {
        $this->storageFile = $storageFile;
    }
}
