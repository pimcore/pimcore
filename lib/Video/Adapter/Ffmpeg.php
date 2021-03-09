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

namespace Pimcore\Video\Adapter;

use Pimcore\File;
use Pimcore\Helper\TemporaryFileHelperTrait;
use Pimcore\Logger;
use Pimcore\Tool\Console;
use Pimcore\Video\Adapter;
use Symfony\Component\Process\Process;

class Ffmpeg extends Adapter
{
    use TemporaryFileHelperTrait;

    /**
     * @var string
     */
    public $file;

    /**
     * @var string
     */
    protected $processId;

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @var array
     */
    private $tmpFiles = [];

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
            Logger::warning($e);
        }

        return false;
    }

    /**
     * @return mixed
     *
     * @throws \Exception
     */
    public static function getFfmpegCli()
    {
        return \Pimcore\Tool\Console::getExecutable('ffmpeg', true);
    }

    /**
     * @param string $file
     * @param array $options
     *
     * @return $this
     */
    public function load($file, $options = [])
    {
        $file = $this->getLocalFile($file);

        $this->file = $file;
        $this->setProcessId(uniqid());

        return $this;
    }

    /**
     * @return bool
     *
     * @throws \Exception
     */
    public function save()
    {
        $success = false;

        if ($this->getDestinationFile()) {
            if (is_file($this->getConversionLogFile())) {
                $this->deleteConversionLogFile();
            }
            if (is_file($this->getDestinationFile())) {
                @unlink($this->getDestinationFile());
            }

            // get the argument string from the configurations
            $arguments = implode(' ', $this->arguments);

            // add format specific arguments
            if ($this->getFormat() == 'mp4') {
                $arguments = '-strict experimental -f mp4 -vcodec libx264 -acodec aac -g 100 -pix_fmt yuv420p -movflags faststart ' . $arguments;
            } elseif ($this->getFormat() == 'webm') {
                // check for vp9 support
                $webmCodec = 'libvpx';
                $codecs = Console::exec(self::getFfmpegCli() . ' -codecs');
                if (stripos($codecs, 'vp9')) {
                    //$webmCodec = "libvpx-vp9"; // disabled until better support in ffmpeg and browsers
                }

                $arguments = '-strict experimental -f webm -vcodec ' . $webmCodec . ' -acodec libvorbis -ar 44000 -g 100 ' . $arguments;
            } else {
                throw new \Exception('Unsupported video output format: ' . $this->getFormat());
            }

            // add some global arguments
            $arguments = '-threads 0 ' . $arguments;

            $cmd = self::getFfmpegCli() . ' -i ' . escapeshellarg(realpath($this->file)) . ' ' . $arguments . ' ' . escapeshellarg(str_replace('/', DIRECTORY_SEPARATOR, $this->getDestinationFile()));

            Logger::debug('Executing FFMPEG Command: ' . $cmd);

            $process = new Process($cmd);
            //symfony has a default timeout which is 60 sec. This is not enough for converting big video-files.
            $process->setTimeout(null);
            $process->start();

            $logHandle = fopen($this->getConversionLogFile(), 'a');
            fwrite($logHandle, 'Command: ' . $cmd . "\n\n\n");

            $process->wait(function ($type, $buffer) use ($logHandle) {
                fwrite($logHandle, $buffer);
            });
            fclose($logHandle);

            if ($process->isSuccessful()) {
                // cleanup & status update
                $this->deleteConversionLogFile();
                $success = true;
            } else {
                // create an error log file
                if (file_exists($this->getConversionLogFile()) && filesize($this->getConversionLogFile())) {
                    copy($this->getConversionLogFile(),
                        str_replace('.log', '.error.log', $this->getConversionLogFile()));
                }
            }
        } else {
            throw new \Exception('There is no destination file for video converter');
        }

        return $success;
    }

    /**
     * @param string $file
     * @param int|null $timeOffset
     */
    public function saveImage($file, $timeOffset = null)
    {
        if (!$timeOffset) {
            $timeOffset = 5;
        }

        $realTargetPath = null;
        if (!stream_is_local($file)) {
            $realTargetPath = $file;
            $file = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/ffmpeg-tmp-' . uniqid() . '.' . File::getFileExtension($file);
        }

        $cmd = self::getFfmpegCli() . ' -ss ' . $timeOffset . ' -i ' . escapeshellarg(realpath($this->file)) . ' -vcodec png -vframes 1 -vf scale=iw*sar:ih ' . escapeshellarg(str_replace('/', DIRECTORY_SEPARATOR, $file));
        Console::exec($cmd, null, 60);

        if ($realTargetPath) {
            File::rename($file, $realTargetPath);
        }
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    protected function getVideoInfo()
    {
        $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/video-info-' . uniqid() . '.out';

        $cmd = self::getFfmpegCli() . ' -i ' . escapeshellarg(realpath($this->file));
        Console::exec($cmd, $tmpFile, 60);

        $contents = file_get_contents($tmpFile);
        unlink($tmpFile);

        return $contents;
    }

    /**
     * @return float
     *
     * @throws \Exception
     */
    public function getDuration()
    {
        $output = $this->getVideoInfo();

        // get total video duration
        preg_match("/Duration: ([0-9:\.]+),/", $output, $matches);
        $durationRaw = $matches[1];
        $durationParts = explode(':', $durationRaw);

        // calculate duration in seconds
        $duration = (intval($durationParts[0]) * 3600) + (intval($durationParts[1]) * 60) + floatval($durationParts[2]);

        return $duration;
    }

    /**
     * @return array
     */
    public function getDimensions()
    {
        $output = $this->getVideoInfo();

        preg_match('/ ([0-9]+x[0-9]+)[, ]/', $output, $matches);
        $durationRaw = $matches[1];
        list($width, $height) = explode('x', $durationRaw);

        return ['width' => $width, 'height' => $height];
    }

    public function destroy()
    {
        if (file_exists($this->getConversionLogFile())) {
            Logger::debug("FFMPEG finished, last message was: \n" . file_get_contents($this->getConversionLogFile()));
            $this->deleteConversionLogFile();
        }

        foreach ($this->tmpFiles as $tmpFile) {
            @unlink($tmpFile);
        }
    }

    public function __destruct()
    {
        $this->destroy();
    }

    public function deleteConversionLogFile()
    {
        @unlink($this->getConversionLogFile());
    }

    /**
     * @param string $processId
     *
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
        return PIMCORE_LOG_DIRECTORY . '/ffmpeg-' . $this->getProcessId() . '-' . $this->getFormat() . '.log';
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function addArgument($key, $value)
    {
        $this->arguments[$key] = $value;
    }

    /**
     * @param int $videoBitrate
     *
     * @return $this
     */
    public function setVideoBitrate($videoBitrate)
    {
        $videoBitrate = intval($videoBitrate);

        $videoBitrate = ceil($videoBitrate / 2) * 2;

        parent::setVideoBitrate($videoBitrate);

        if ($videoBitrate) {
            $this->addArgument('videoBitrate', '-vb ' . $videoBitrate . 'k');
        }

        return $this;
    }

    /**
     * @param int $audioBitrate
     *
     * @return $this
     */
    public function setAudioBitrate($audioBitrate)
    {
        $audioBitrate = intval($audioBitrate);

        $audioBitrate = ceil($audioBitrate / 2) * 2;

        parent::setAudioBitrate($audioBitrate);

        if ($audioBitrate) {
            $this->addArgument('audioBitrate', '-ab ' . $audioBitrate . 'k');
        }

        return $this;
    }

    /**
     * @param int $width
     * @param int $height
     */
    public function resize($width, $height)
    {
        // ensure $width & $height are even (mp4 requires this)
        $width = ceil($width / 2) * 2;
        $height = ceil($height / 2) * 2;
        $this->addArgument('resize', '-s '.$width.'x'.$height);
    }

    /**
     * @param int $width
     */
    public function scaleByWidth($width)
    {
        // ensure $width is even (mp4 requires this)
        $width = ceil($width / 2) * 2;
        $this->addArgument('scaleByWidth', '-vf "scale='.$width.':trunc(ow/a/2)*2"');
    }

    /**
     * @param int $height
     */
    public function scaleByHeight($height)
    {
        // ensure $height is even (mp4 requires this)
        $height = ceil($height / 2) * 2;
        $this->addArgument('scaleByHeight', '-vf "scale=trunc(oh/(ih/iw)/2)*2:'.$height.'"');
    }
}
