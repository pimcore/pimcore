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

namespace Pimcore\Video\Adapter;

use Pimcore\Video\Adapter;
use Pimcore\Tool\Console;
use Pimcore\File;

class Ffmpeg extends Adapter
{


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
     * @return bool
     */
    public function isAvailable()
    {
        try {
            $ffmpeg = self::getFfmpegCli();
            $phpCli = Console::getPhpCli();
            if ($ffmpeg && $phpCli) {
                return true;
            }
        } catch (\Exception $e) {
            \Logger::warning($e);
        }

        return false;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public static function getFfmpegCli()
    {
        return \Pimcore\Tool\Console::getExecutable("ffmpeg", true);
    }

    /**
     * @param $file
     * @return $this|mixed
     */
    public function load($file)
    {
        $this->file = $file;
        $this->setProcessId(uniqid());

        return $this;
    }

    /**
     * @return mixed|void
     * @throws \Exception
     */
    public function save()
    {
        if ($this->getDestinationFile()) {
            if (is_file($this->getConversionLogFile())) {
                $this->deleteConversionLogFile();
            }
            if (is_file($this->getDestinationFile())) {
                @unlink($this->getDestinationFile());
            }

            // get the argument string from the configurations
            $arguments = implode(" ", $this->arguments);

            // add format specific arguments
            /*if($this->getFormat() == "f4v") {
                $arguments = "-f flv -vcodec libx264 -acodec libfaac -ar 44000 -g 100 " . $arguments;
            } else*/
            if ($this->getFormat() == "mp4") {
                // `-coder 0 -bf 0 -flags2 -wpred-dct8x8 -wpredp 0´ is the same as to -vpre baseline, using this to avid problems with missing preset files
                // Some flags used were deprecated already
                // todo set the -x264opts flag correctly and get profiles working as they should.
                $arguments = "-strict experimental -f mp4 -vcodec libx264 -acodec aac -g 100 -pix_fmt yuv420p -movflags faststart " . $arguments;
            } elseif ($this->getFormat() == "webm") {
                // check for vp9 support
                $webmCodec = "libvpx";
                $codecs = Console::exec(self::getFfmpegCli() . " -codecs");
                if (stripos($codecs, "vp9")) {
                    //$webmCodec = "libvpx-vp9"; // disabled until better support in ffmpeg and browsers
                }

                $arguments = "-strict experimental -f webm -vcodec " . $webmCodec . " -acodec libvorbis -ar 44000 -g 100 " . $arguments;
            } else {
                throw new \Exception("Unsupported video output format: " . $this->getFormat());
            }

            // add some global arguments
            $arguments = "-threads 0 " . $arguments;

            $cmd = self::getFfmpegCli() . ' -i ' . realpath($this->file) . ' ' . $arguments . " " . str_replace("/", DIRECTORY_SEPARATOR, $this->getDestinationFile());
            Console::execInBackground($cmd, $this->getConversionLogFile());
        } else {
            throw new \Exception("There is no destination file for video converter");
        }
    }

    /**
     * @param null $timeOffset
     */
    public function saveImage($file, $timeOffset = null)
    {
        if (!$timeOffset) {
            $timeOffset = 5;
        }

        $realTargetPath = null;
        if(!stream_is_local($file)) {
            $realTargetPath = $file;
            $file = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/ghostscript-tmp-" . uniqid() . "." . File::getFileExtension($file);
        }

        $cmd = self::getFfmpegCli() . " -i " . realpath($this->file) . " -vcodec png -vframes 1 -vf scale=iw*sar:ih -ss " . $timeOffset . " " . str_replace("/", DIRECTORY_SEPARATOR, $file);
        Console::exec($cmd, null, 60);

        if($realTargetPath) {
            File::rename($file, $realTargetPath);
        }
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getDuration()
    {
        $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/video-info-" . uniqid() . ".out";

        $cmd = self::getFfmpegCli() . " -i " . realpath($this->file);
        Console::exec($cmd, $tmpFile, null, 60);

        $contents = file_get_contents($tmpFile);
        unlink($tmpFile);

        return $this->extractDuration($contents);
    }

    /**
     *
     */
    public function destroy()
    {
        \Logger::debug("FFMPEG finished, last message was: \n" . file_get_contents($this->getConversionLogFile()));
        $this->deleteConversionLogFile();
    }

    /**
     *
     */
    public function deleteConversionLogFile()
    {
        @unlink($this->getConversionLogFile());
    }

    /**
     * @return bool
     */
    public function isFinished()
    {
        $status = $this->getConversionStatus();
        if ($status === "error" || $status > 99) {
            return true;
        }
        return false;
    }

    /**
     * @param $output
     * @return int
     */
    protected function extractDuration($output)
    {
        // get total video duration
        preg_match("/Duration: ([0-9:\.]+),/", $output, $matches);
        $durationRaw = $matches[1];
        $durationParts = explode(":", $durationRaw);

        // calculate duration in seconds
        $duration = (intval($durationParts[0]) * 3600) + (intval($durationParts[1]) * 60) + floatval($durationParts[2]);

        return $duration;
    }

    /**
     *
     */
    public function getConversionStatus()
    {
        $log = file_get_contents($this->getConversionLogFile());

        // check if the conversion failed
        if (stripos($log, "Invalid data found when processing") !== false
           || stripos($log, "incorrect parameters") !== false
           || stripos($log, "error") !== false
           || stripos($log, "unable") !== false) {
            \Logger::critical("Problem converting video: " . $this->file . " to format " . $this->getFormat());
            \Logger::critical($log);

            // create a copy of the conversion log, so that it will persist
            copy($this->getConversionLogFile(), str_replace(".log", ".error.log", $this->getConversionLogFile()));

            return "error";
        }

        $duration = $this->extractDuration($log);

        // get conversion time
        preg_match_all("/time=([0-9:\.]+) bitrate/", $log, $matches);
        $conversionTimeRaw = $matches[1][count($matches[1])-1];
        $conversionTimeParts = explode(":", $conversionTimeRaw);
        // calculate time in seconds
        $conversionTime = (intval($conversionTimeParts[0]) * 3600) + (intval($conversionTimeParts[1]) * 60) + floatval($conversionTimeParts[2]);

        if ($duration > 0) {
            $status = $conversionTime / $duration;
        } else {
            $status = 0;
        }

        $percent = round($status * 100);
        // check if the conversion is finished
        clearstatcache(); // clear stat cache otherwise filemtime always returns the same timestamp
        if ((time() - filemtime($this->getConversionLogFile())) > 10) {
            $percent = 100;
            $this->deleteConversionLogFile();
        }

        if (!$percent) {
            $percent = 1;
        }

        \Logger::debug("Video transcoding status of " . $this->getDestinationFile() . ": " . $percent . " - " . $this->getFormat());

        return $percent;
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
     * @return string
     */
    protected function getConversionLogFile()
    {
        return PIMCORE_LOG_DIRECTORY . "/ffmpeg-" . $this->getProcessId() . "-" . $this->getFormat() . ".log";
    }

    /**
     * @param $key
     * @param $value
     */
    public function addArgument($key, $value)
    {
        $this->arguments[$key] = $value;
    }

    /**
     * @param $videoBitrate
     * @return $this
     */
    public function setVideoBitrate($videoBitrate)
    {
        $videoBitrate = intval($videoBitrate);

        $videoBitrate = ceil($videoBitrate/2) * 2;

        parent::setVideoBitrate($videoBitrate);

        if ($videoBitrate) {
            $this->addArgument("videoBitrate", "-vb " . $videoBitrate . "k");
        }
        return $this;
    }

    /**
     * @param $audioBitrate
     * @return $this
     */
    public function setAudioBitrate($audioBitrate)
    {
        $audioBitrate = intval($audioBitrate);

        $audioBitrate = ceil($audioBitrate/2) * 2;

        parent::setAudioBitrate($audioBitrate);

        if ($audioBitrate) {
            $this->addArgument("audioBitrate", "-ab " . $audioBitrate . "k");
        }
        return $this;
    }

    /**
     * @param $width
     * @param $height
     */
    public function resize($width, $height)
    {
        // ensure $width & $height are even (mp4 requires this)
        $width = ceil($width/2) * 2;
        $height = ceil($height/2) * 2;
        $this->addArgument("resize", "-s ".$width."x".$height);
    }

    /**
     * @param $width
     */
    public function scaleByWidth($width)
    {
        // ensure $width is even (mp4 requires this)
        $width = ceil($width/2) * 2;
        $this->addArgument("scaleByWidth", '-vf "scale='.$width.':trunc(ow/a/vsub)*vsub"');
    }

    /**
     * @param $height
     */
    public function scaleByHeight($height)
    {
        // ensure $height is even (mp4 requires this)
        $height = ceil($height/2) * 2;
        $this->addArgument("scaleByHeight", '-vf "scale=trunc(oh/(ih/iw)/hsub)*hsub:'.$height.'"');
    }
}
