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
 
abstract class Pimcore_Video_Adapter {


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
     * @param int $audioBitrate
     */
    public function setAudioBitrate($audioBitrate)
    {
        $this->audioBitrate = $audioBitrate;
    }

    /**
     * @return int
     */
    public function getAudioBitrate()
    {
        return $this->audioBitrate;
    }

    /**
     * @param int $videoBitrate
     */
    public function setVideoBitrate($videoBitrate)
    {
        $this->videoBitrate = $videoBitrate;
    }

    /**
     * @return int
     */
    public function getVideoBitrate()
    {
        return $this->videoBitrate;
    }

    /**
     * @param string $file
     * @return Pimcore_Video_Adapter
     */
    public abstract function load($file);

    /**
     * @abstract
     * @return Pimcore_Video_Adapter
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
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param string $destinationFile
     */
    public function setDestinationFile($destinationFile)
    {
        $this->destinationFile = $destinationFile;
    }

    /**
     * @return string
     */
    public function getDestinationFile()
    {
        return $this->destinationFile;
    }

    /**
     * @param int $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }


}
