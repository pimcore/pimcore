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
 
class Pimcore_Video_Adapter_Ffmpeg extends Pimcore_Video_Adapter {


    /**
     * @var string
     */
    public $file;

    /**
     * @var string
     */
    protected $processId;

    /**
     * @param string $file
     * @return Pimcore_Video_Adapter
     */
    public function load($file) {
        $this->file = $file;
        $this->setProcessId(uniqid());
    }

    /**
     * @param  $path
     * @return Pimcore_Video_Adapter
     */
    public function save ($path, $format = null) {

        Pimcore_Tool_Console::execInBackground('/usr/local/bin/ffmpeg -i ' . $this->file . ' -vcodec libx264 -acodec libfaac -vb 1500000 -ab 196000 -ar 44000 -f flv -vf "scale=670:trunc(ow/a/vsub)*vsub" ' . $path, $this->getConversionLogFile());
    }

    /**
     *
     */
    public function getConversionStatus() {

    }

    /**
     * @param string $processId
     */
    public function setProcessId($processId)
    {
        $this->processId = $processId;
    }

    /**
     * @return string
     */
    public function getProcessId()
    {
        return $this->processId;
    }

    protected function getConversionLogFile () {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . "/ffmpeg-" . $this->getProcessId() . ".log";
    }
}
