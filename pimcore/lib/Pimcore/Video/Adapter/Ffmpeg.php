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
     * @var string
     */
    protected $arguments = array();

    /**
     * @static
     * @return string
     */
    public static function getFfmpegCli () {

        if(Pimcore_Config::getSystemConfig()->assets->ffmpeg) {
            if(is_executable(Pimcore_Config::getSystemConfig()->assets->ffmpeg)) {
                return Pimcore_Config::getSystemConfig()->assets->ffmpeg;
            } else {
                Logger::critical("FFMPEG binary: " . Pimcore_Config::getSystemConfig()->assets->ffmpeg . " is not executable");
            }
        }

        $paths = array("/usr/bin/ffmpeg","/usr/local/bin/ffmpeg", "/bin/ffmpeg");

        foreach ($paths as $path) {
            if(is_executable($path)) {
                return $path;
            }
        }

        throw new Exception("No ffmpeg executable found, please configure the correct path in the system settings");
    }

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
    public function save () {
        if($this->getDestinationFile()) {

            if(is_file($this->getConversionLogFile())) {
                @unlink($this->getConversionLogFile());
            }
            if(is_file($this->getDestinationFile())) {
                @unlink($this->getDestinationFile());
            }

            // get the argument string from the configurations
            $arguments = implode(" ", $this->arguments);

            // add format specific arguments
            /*if($this->getFormat() == "f4v") {
                $arguments = "-f flv -vcodec libx264 -acodec libfaac -ar 44000 -g 100 " . $arguments;
            } else*/
            if($this->getFormat() == "mp4") {
                // `-coder 0 -bf 0 -flags2 -wpred-dct8x8 -wpredp 0´ is the same as to -vpre baseline, using this to avid problems with missing preset files
                // Some flags used were deprecated already
                // todo set the -x264opts flag correctly and get profiles working as they should.
                $arguments = "-strict experimental -f mp4 -vcodec libx264 -acodec aac -g 100 " . $arguments;
            } else if($this->getFormat() == "webm") {
                $arguments = "-f webm -vcodec libvpx -acodec libvorbis -ar 44000 -g 100 " . $arguments;
            } else {
                throw new Exception("Unsupported video output format: " . $this->getFormat());
            }

            // add some global arguments
            $arguments = "-threads 0 " . $arguments;

            $cmd = self::getFfmpegCli() . ' -i ' . realpath($this->file) . ' ' . $arguments . " " . str_replace("/", DIRECTORY_SEPARATOR, $this->getDestinationFile());
            Pimcore_Tool_Console::execInBackground($cmd, $this->getConversionLogFile());
        } else {
            throw new Exception("There is no destination file for video converter");
        }
    }

    /**
     * @static
     * @return bool|string
     */
    public static function getQtfaststartCli () {

        if(Pimcore_Config::getSystemConfig()->assets->qtfaststart) {
            if(is_executable(Pimcore_Config::getSystemConfig()->assets->qtfaststart)) {
                return Pimcore_Config::getSystemConfig()->assets->qtfaststart;
            } else {
                Logger::critical("qtfaststart binary: " . Pimcore_Config::getSystemConfig()->assets->qtfaststart . " is not executable");
            }
        }

        $paths = array("/usr/bin/qtfaststart","/usr/local/bin/qtfaststart", "/bin/qtfaststart");
        foreach ($paths as $path) {
            if(is_executable($path)) {
                return $path;
            }
        }

        return false;
    }

    /**
     * @return mixed
     */
    protected function addMp4MetaData () {

        if(isset($this->__mp4MetaDataAdded)) {
            return;
        }

        if(self::getQtfaststartCli()) {
            $cmd = self::getQtfaststartCli() . " " . $this->getDestinationFile();
            Pimcore_Tool_Console::exec($cmd);
        } else {
            Logger::error("qtfaststart is not installed on your system, unable to move (to the beginning -> pseudo streaming) metadata in the converted mp4 file: " . $this->getDestinationFile());
        }

        $this->__mp4MetaDataAdded = true;
    }

    /**
     * @param null $timeOffset
     */
    public function saveImage($file, $timeOffset = null) {

        if(!$timeOffset) {
            $timeOffset = 5;
        }

        $cmd = self::getFfmpegCli() . " -i " . realpath($this->file) . " -vcodec png -vframes 1 -ss " . $timeOffset . " " . str_replace("/", DIRECTORY_SEPARATOR, $file);
        Pimcore_Tool_Console::exec($cmd);
    }

    /**
     *
     */
    public function destroy() {
        Logger::debug("FFMPEG finished, last message was: \n" . file_get_contents($this->getConversionLogFile()));
        @unlink($this->getConversionLogFile());
    }

    /**
     * @return bool
     */
    public function isFinished() {
        $status = $this->getConversionStatus();
        if($status === "error" || $status > 99) {
            return true;
        }
        return false;
    }

    /**
     *
     */
    public function getConversionStatus() {

        $log = file_get_contents($this->getConversionLogFile());

        // check if the conversion failed
        if(stripos($log, "Invalid data found when processing") !== false
           || stripos($log, "incorrect parameters") !== false
           || stripos($log, "error") !== false) {

            Logger::critical("Problem converting video: " . $this->file . " to format " . $this->getFormat());
            Logger::critical($log);

            return "error";
        }

        // get total video duration
        preg_match("/Duration: ([0-9:\.]+),/", $log, $matches);
        $durationRaw = $matches[1];
        $durationParts = explode(":",$durationRaw);

        // calculate duration in seconds
        $duration = (intval($durationParts[0]) * 3600) + (intval($durationParts[1]) * 60) + floatval($durationParts[2]);

        // get conversion time
        preg_match_all("/time=([0-9:\.]+) bitrate/", $log, $matches);
        $conversionTimeRaw = $matches[1][count($matches[1])-1];
        $conversionTimeParts = explode(":",$conversionTimeRaw);
        // calculate time in seconds
        $conversionTime = (intval($conversionTimeParts[0]) * 3600) + (intval($conversionTimeParts[1]) * 60) + floatval($conversionTimeParts[2]);

        if($duration > 0) {
            $status = $conversionTime / $duration;
        } else {
            $status = 0;
        }

        $percent = round($status * 100);
        // check if the conversion is finished
        clearstatcache(); // clear stat cache otherwise filemtime always returns the same timestamp
        if((time() - filemtime($this->getConversionLogFile())) > 10) {
            $percent = 100;
            @unlink($this->getConversionLogFile());
        }

        if(!$percent) {
            $percent = 1;
        }

        // add flash video metadata when conversion is finished
        if($percent > 99 && $this->getFormat() == "mp4") {
            $this->addMp4MetaData();
        }

        Logger::debug("Video transcoding status of " . $this->getDestinationFile() . ": " . $percent . " - " . $this->getFormat());

        return $percent;
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

    public function addArgument($key, $value) {
        $this->arguments[$key] = $value;
    }

    public function setVideoBitrate($videoBitrate) {
        parent::setVideoBitrate($videoBitrate);

        $this->addArgument("videoBitrate", "-vb " . $videoBitrate . "k");
    }

    public function setAudioBitrate($audioBitrate) {
        parent::setAudioBitrate($audioBitrate);

        $this->addArgument("audioBitrate", "-ab " . $audioBitrate . "k");
    }

    public function resize ($width, $height) {
        // ensure $width & $height are even (mp4 requires this)
        $width = ceil($width/2) * 2;
        $height = ceil($height/2) * 2;
        $this->addArgument("resize", "-s ".$width."x".$height);
    }

    public function scaleByWidth ($width) {
        // ensure $width is even (mp4 requires this)
        $width = ceil($width/2) * 2;
        $this->addArgument("scaleByWidth", '-vf "scale='.$width.':trunc(ow/a/vsub)*vsub"');
    }

    public function scaleByHeight ($height) {
        // ensure $height is even (mp4 requires this)
        $height = ceil($height/2) * 2;
        $this->addArgument("scaleByHeight", '-vf "scale=trunc(oh/(ih/iw)/hsub)*hsub:'.$height.'"');
    }

}
