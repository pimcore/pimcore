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
     *
     * @var int
     */
    public $length;

    /**
     * @param int $audioBitrate
     *
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
     * @param int $videoBitrate
     *
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
     * @param string $file
     * @param array $options
     *
     * @return $this
     */
    abstract public function load($file, $options = []);

    /**
     * @return bool
     */
    abstract public function save();

    /**
     * @abstract
     *
     * @param string $file
     * @param int|null $timeOffset
     */
    abstract public function saveImage($file, $timeOffset = null);

    /**
     * @abstract
     */
    abstract public function destroy();

    /**
     * @param string $format
     *
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
     * @param string $destinationFile
     *
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
     * @param int $length
     *
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

    /**
     * @return float
     *
     * @throws \Exception
     */
    abstract public function getDuration();

    /**
     * @return array
     */
    abstract public function getDimensions();
}
