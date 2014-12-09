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

namespace Pimcore\Video;

abstract class Adapter {

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
    public abstract function load($file);

    /**
     * @return mixed
     */
    public abstract function save ();

    /**
     * @abstract
     * @param $timeOffset
     */
    public abstract function saveImage($file, $timeOffset = null);

    /**
     * @abstract
     */
    public abstract function getConversionStatus();

    /**
     * @abstract
     */
    public abstract function destroy();

    /**
     * @abstract
     * @return bool
     */
    public abstract function isFinished();

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
}
