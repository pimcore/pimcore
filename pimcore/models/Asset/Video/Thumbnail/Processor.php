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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */
 
class Asset_Video_Thumbnail_Processor {


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
     * @var Asset_Video_Thumbnail_Config
     */
    public $config;

    /**
     * @var int
     */
    public $status;

    /**
     * @static
     * @param Asset_Video $asset
     * @param Asset_Video_Thumbnail_Config $config
     */
    public static function process (Asset_Video $asset, $config, $onlyFormats = array()) {

        if(!Pimcore_Video::isAvailable()) {
            throw new Exception("No ffmpeg executable found, please configure the correct path in the system settings");
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
                if(is_file($instance->getJobFile($customSetting[$config->getName()]["processId"]))) {
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
                throw new Exception("Unable to convert video, see logs for details.");
            }
        }

        foreach ($formats as $format) {

            $thumbDir = $asset->getVideoThumbnailSavePath() . "/thumb__" . $config->getName();
            $filename = preg_replace("/\." . preg_quote(Pimcore_File::getFileExtension($asset->getFilename())) . "/", "", $asset->getFilename()) . "." . $format;
            $fsPath = $thumbDir . "/" . $filename;

            if(!is_dir(dirname($fsPath))) {
                Pimcore_File::mkdir(dirname($fsPath));
            }

            if(is_file($fsPath)) {
                @unlink($fsPath);
            }

            $converter = Pimcore_Video::getInstance();
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
                            Logger::error($message);
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

    public static function execute ($processId) {
        $instance = new self();
        $instance->setProcessId($processId);
        $instance = Pimcore_Tool_Serialize::unserialize(file_get_contents($instance->getJobFile()));
        $formats = array();
        $overallStatus = array();
        $conversionStatus = "finished";

        // set overall status for all formats to 0
        foreach ($instance->queue as $converter) {
            $overallStatus[$converter->getFormat()] = 0;
        }

        // check if there is already a transcoding process running, wait if so ...
        Tool_Lock::acquire("video-transcoding", 7200, 10); // expires after 2 hrs, refreshes every 10 secs

        // start converting
        foreach ($instance->queue as $converter) {
            try {
                Logger::info("start video " . $converter->getFormat() . " to " . $converter->getDestinationFile());
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
                Logger::info("finished video " . $converter->getFormat() . " to " . $converter->getDestinationFile());

                if($converter->getConversionStatus() !== "error") {
                    $formats[$converter->getFormat()] = str_replace(PIMCORE_DOCUMENT_ROOT, "", $converter->getDestinationFile());
                } else {
                    $conversionStatus = "error";
                }

                $converter->destroy();
            } catch (Exception $e) {
                Logger::error($e);
            }
        }

        Tool_Lock::release("video-transcoding");

        $asset = Asset::getById($instance->getAssetId());
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

        @unlink($instance->getJobFile());
    }

    /**
     * @static
     * @param $processId
     * @return int
     */
    public static function getProgress($processId) {
        $instance = new self();
        $instance->setProcessId($processId);

        if(is_file($instance->getJobFile())) {
            $i = Pimcore_Tool_Serialize::unserialize(file_get_contents($instance->getJobFile()));
            if($i instanceof Asset_Video_Thumbnail_Processor) {
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
        $cmd = Pimcore_Tool_Console::getPhpCli() . " " . realpath(PIMCORE_PATH . DIRECTORY_SEPARATOR . "cli" . DIRECTORY_SEPARATOR . "video-converter.php"). " " . $this->getProcessId();
        Pimcore_Tool_Console::execInBackground($cmd);
    }

    /**
     * @return bool
     */
    public function save() {
        Pimcore_File::put($this->getJobFile(), Pimcore_Tool_Serialize::serialize($this));
        return true;
    }

    /**
     * @return string
     */
    protected function getJobFile ($processId = null) {
        if(!$processId) {
            $processId = $this->getProcessId();
        }
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . "/video-job-" . $processId . ".psf";
    }

    /**
     * @param string $processId
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
     * @param int $assetId
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
     * @param \Asset_Video_Thumbnail_Config $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @return \Asset_Video_Thumbnail_Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $queue
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
     * @param int $status
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
