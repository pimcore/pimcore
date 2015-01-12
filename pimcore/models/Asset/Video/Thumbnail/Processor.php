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
 * @package    Asset
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Asset\Video\Thumbnail;

use Pimcore\File; 
use Pimcore\Tool\Console;
use Pimcore\Model;
use Pimcore\Model\Tool\TmpStore;

class Processor {


    protected static $argumentMapping = array(
        "resize" => array("width","height"),
        "scaleByWidth" => array("width"),
        "scaleByHeight" => array("height")
    );

    /**
     * @var array
     */
    public $queue = array();

    /**
     * @var string
     */
    public $processId;

    /**
     * @var int
     */
    public $assetId;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var int
     */
    public $status;

    /**
     * @param Model\Asset\Video $asset
     * @param $config
     * @param array $onlyFormats
     * @return Processor
     * @throws \Exception
     */
    public static function process (Model\Asset\Video $asset, $config, $onlyFormats = array()) {

        if(!\Pimcore\Video::isAvailable()) {
            throw new \Exception("No ffmpeg executable found, please configure the correct path in the system settings");
        }

        $instance = new self();
        $formats = empty($onlyFormats) ? array("mp4","webm") : $onlyFormats;
        $instance->setProcessId(uniqid());
        $instance->setAssetId($asset->getId());
        $instance->setConfig($config);

        // check for running or already created thumbnails
        $customSetting = $asset->getCustomSetting("thumbnails");
        $existingFormats = array();
        if(is_array($customSetting) && array_key_exists($config->getName(), $customSetting)) {
            if ($customSetting[$config->getName()]["status"] == "inprogress") {
                if(TmpStore::get($instance->getJobStoreId($customSetting[$config->getName()]["processId"]))) {
                    return;
                }
            } else if($customSetting[$config->getName()]["status"] == "finished") {
                // check if the files are there
                $formatsToConvert = array();
                foreach($formats as $f) {
                    if(!is_file(PIMCORE_DOCUMENT_ROOT . $customSetting[$config->getName()]["formats"][$f])) {
                        $formatsToConvert[] = $f;
                    } else {
                        $existingFormats[$f] = $customSetting[$config->getName()]["formats"][$f];
                    }
                }

                if(!empty($formatsToConvert)) {
                    $formats = $formatsToConvert;
                } else {
                    return;
                }
            } else if($customSetting[$config->getName()]["status"] == "error") {
                throw new \Exception("Unable to convert video, see logs for details.");
            }
        }

        foreach ($formats as $format) {

            $thumbDir = $asset->getVideoThumbnailSavePath() . "/thumb__" . $config->getName();
            $filename = preg_replace("/\." . preg_quote(File::getFileExtension($asset->getFilename())) . "/", "", $asset->getFilename()) . "." . $format;
            $fsPath = $thumbDir . "/" . $filename;

            if(!is_dir(dirname($fsPath))) {
                File::mkdir(dirname($fsPath));
            }

            if(is_file($fsPath)) {
                @unlink($fsPath);
            }

            $converter = \Pimcore\Video::getInstance();
            $converter->load($asset->getFileSystemPath());
            $converter->setAudioBitrate($config->getAudioBitrate());
            $converter->setVideoBitrate($config->getVideoBitrate());
            $converter->setFormat($format);
            $converter->setDestinationFile($fsPath);

            $transformations = $config->getItems();
            if(is_array($transformations) && count($transformations) > 0) {
                foreach ($transformations as $transformation) {
                    if(!empty($transformation)) {
                        $arguments = array();
                        $mapping = self::$argumentMapping[$transformation["method"]];

                        if(is_array($transformation["arguments"])) {
                            foreach ($transformation["arguments"] as $key => $value) {
                                $position = array_search($key, $mapping);
                                if($position !== false) {
                                    $arguments[$position] = $value;
                                }
                            }
                        }

                        ksort($arguments);
                        if(count($mapping) == count($arguments)) {
                            call_user_func_array(array($converter,$transformation["method"]),$arguments);
                        } else {
                            $message = "Video Transform failed: cannot call method `" . $transformation["method"] . "´ with arguments `" . implode(",",$arguments) . "´ because there are too few arguments";
                            \Logger::error($message);
                        }
                    }
                }
            }

            $instance->queue[] = $converter;
        }

        $customSetting = $asset->getCustomSetting("thumbnails");
        $customSetting = is_array($customSetting) ? $customSetting : array();
        $customSetting[$config->getName()] = array(
            "status" => "inprogress",
            "formats" => $existingFormats,
            "processId" => $instance->getProcessId()
        );
        $asset->setCustomSetting("thumbnails", $customSetting);
        $asset->save();

        $instance->convert();

        return $instance;
    }

    /**
     * @param $processId
     */
    public static function execute ($processId) {
        $instance = new self();
        $instance->setProcessId($processId);

        $instanceItem = TmpStore::get($instance->getJobStoreId($processId));
        $instance = $instanceItem->getData();

        $formats = array();
        $overallStatus = array();
        $conversionStatus = "finished";

        // set overall status for all formats to 0
        foreach ($instance->queue as $converter) {
            $overallStatus[$converter->getFormat()] = 0;
        }

        // check if there is already a transcoding process running, wait if so ...
        Model\Tool\Lock::acquire("video-transcoding", 7200, 10); // expires after 2 hrs, refreshes every 10 secs

        // start converting
        foreach ($instance->queue as $converter) {
            try {
                \Logger::info("start video " . $converter->getFormat() . " to " . $converter->getDestinationFile());
                $converter->save();
                while (!$converter->isFinished()) {
                    sleep(5);
                    $overallStatus[$converter->getFormat()] = $converter->getConversionStatus();

                    $a = 0;
                    foreach ($overallStatus as $f => $s) {
                        $a += $s;
                    }
                    $a = $a / count($overallStatus);

                    $instance->setStatus($a);
                    $instance->save();
                }
                \Logger::info("finished video " . $converter->getFormat() . " to " . $converter->getDestinationFile());

                // set proper permissions
                @chmod($converter->getDestinationFile(), File::getDefaultMode());

                if($converter->getConversionStatus() !== "error") {
                    $formats[$converter->getFormat()] = str_replace(PIMCORE_DOCUMENT_ROOT, "", $converter->getDestinationFile());
                } else {
                    $conversionStatus = "error";
                }

                $converter->destroy();
            } catch (\Exception $e) {
                \Logger::error($e);
            }
        }

        Model\Tool\Lock::release("video-transcoding");

        $asset = Model\Asset::getById($instance->getAssetId());
        if($asset) {
            $customSetting = $asset->getCustomSetting("thumbnails");
            $customSetting = is_array($customSetting) ? $customSetting : array();

            if(array_key_exists($instance->getConfig()->getName(), $customSetting)
                && array_key_exists("formats", $customSetting[$instance->getConfig()->getName()])
                && is_array($customSetting[$instance->getConfig()->getName()]["formats"]) ) {

                $formats = array_merge($customSetting[$instance->getConfig()->getName()]["formats"], $formats);
            }

            $customSetting[$instance->getConfig()->getName()] = array(
                "status" => $conversionStatus,
                "formats" => $formats
            );
            $asset->setCustomSetting("thumbnails", $customSetting);
            $asset->save();
        }

        TmpStore::delete($instance->getJobStoreId());
    }

    /**
     * @static
     * @param $processId
     * @return int
     */
    public static function getProgress($processId) {
        $instance = new self();
        $instance->setProcessId($processId);

        $instanceItem = TmpStore::get($instance->getJobStoreId());

        if($instanceItem) {
            $i = $instanceItem->getData();
            if($i instanceof Processor) {
                $instance = $i;
            }
        }

        return $instance->getStatus();
    }

    /**
     *
     */
    public function convert() {
        $this->save();
        $cmd = Console::getPhpCli() . " " . realpath(PIMCORE_PATH . DIRECTORY_SEPARATOR . "cli" . DIRECTORY_SEPARATOR . "video-converter.php"). " " . $this->getProcessId();
        Console::execInBackground($cmd);
    }

    /**
     * @return bool
     */
    public function save() {
        TmpStore::add($this->getJobStoreId(), $this, "video-job");
        return true;
    }

    /**
     * @param $processId
     * @return string
     */
    protected function getJobStoreId($processId = null) {
        if(!$processId) {
            $processId = $this->getProcessId();
        }
        return "video-job-" . $processId;
    }

    /**
     * @param $processId
     * @return $this
     */
    public function setProcessId($processId)
    {
        $this->processId = $processId;
        return $this;
    }

    /**
     * @return string
     */
    public function getProcessId()
    {
        return $this->processId;
    }

    /**
     * @param $assetId
     * @return $this
     */
    public function setAssetId($assetId)
    {
        $this->assetId = $assetId;
        return $this;
    }

    /**
     * @return int
     */
    public function getAssetId()
    {
        return $this->assetId;
    }

    /**
     * @param $config
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param $queue
     * @return $this
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * @return array
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }
}
