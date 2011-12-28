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
 
class Asset_Video_Thumbnail_Processor {

    /**
     * @var array
     */
    public $queue = array();

    /**
     * @var string
     */
    public $processId;

    /**
     * @static
     * @param Asset_Video $asset
     * @param $config
     */
    public static function process (Asset_Video $asset, $config) {

        $instance = new self();
        $formats = array("f4v","mp4","webm");
        $instance->setProcessId(uniqid());

        foreach ($formats as $format) {

            $filename = "video_" . $asset->getId() . "__" . $config->getName() . "." . $format;
            $fsPath = PIMCORE_TEMPORARY_DIRECTORY . "/" . $filename;
            $path = str_replace(PIMCORE_DOCUMENT_ROOT, "", $fsPath);

            $converter = Pimcore_Video::getInstance();
            $converter->load($asset->getFileSystemPath());
            $converter->setAudioBitrate(196000);
            $converter->setVideoBitrate(1500000);
            $converter->setHeight(640);
            $converter->setWidth(480);
            $converter->save($fsPath, $format);

            $instance->queue[] = $converter;
        }


        return $instance;
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


}
