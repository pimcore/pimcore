<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Video\Adapter;

use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Tool\Console;
use Pimcore\Video\Adapter;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
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

            $command = $this->arguments;

            // add format specific arguments
            if ($this->getFormat() == 'mp4') {
                array_push($command, '-strict', 'experimental');
                array_push($command, '-f', 'mp4');
                array_push($command, '-vcodec', 'libx264');
                array_push($command, '-acodec', 'aac');
                array_push($command, '-g', '100');
                array_push($command, '-pix_fmt', 'yuv420p');
                array_push($command, '-movflags', 'faststart');
            } elseif ($this->getFormat() == 'webm') {
                // check for vp9 support
                $webmCodec = 'libvpx';
                $process = new Process([self::getFfmpegCli(), '-codecs']);
                $process->run();
                $codecs = $process->getOutput();
                if (stripos($codecs, 'vp9')) {
                    //$webmCodec = "libvpx-vp9"; // disabled until better support in ffmpeg and browsers
                }

                array_push($command, '-strict', 'experimental');
                array_push($command, '-f', 'webm');
                array_push($command, '-vcodec', $webmCodec);
                array_push($command, '-acodec', 'libvorbis');
                array_push($command, '-ar', '44000');
                array_push($command, '-g', '100');
            } elseif ($this->getFormat() == 'mpd') {
                $medias = $this->getMedias();
                $mediaKeys = array_keys($medias);
                $command = [];

                foreach ($mediaKeys as $mediaKey) {
                    array_push($command, '-map', 'v:0');
                }

                array_push($command, '-c:a', 'libfdk_aac');
                array_push($command, '-vcodec', 'libx264');

                for ($i = 0; $i < count($mediaKeys); $i++) {
                    $bitrate = $mediaKeys[$i];

                    array_push($command, '-b:v:' . $i, $bitrate);
                    array_push($command, '-c:v:' . $i, 'libx264');
                    array_push($command, '-c:v:' . $i, 'libx264');

                    if ($medias[$bitrate]['converter'] instanceof self) {
                        foreach ($medias[$bitrate]['converter']->arguments as $aKey => $argument) {
                            $argument = ($aKey % 2 == 0 ? $argument . ':' . $i : $argument);
                            array_push($command, $argument);
                        }
                    }
                }

                array_push($command, '-use_timeline', '1');
                array_push($command, '-use_template', '1');
                array_push($command, '-window_size', '5');
                array_push($command, '-adaptation_sets', 'id=0,streams=v id=1,streams=a');
                array_push($command, '-single_file', '1');
                array_push($command, '-f', 'dash');
            } else {
                throw new \Exception('Unsupported video output format: ' . $this->getFormat());
            }

            // add some global arguments
            array_push($command, '-threads', '0');
            $command[] = str_replace('/', DIRECTORY_SEPARATOR, $this->getDestinationFile());
            array_unshift($command, 'ffmpeg', '-i', realpath($this->file));

            Console::addLowProcessPriority($command);
            $process = new Process($command);

            Logger::debug('Executing FFMPEG Command: ' . $process->getCommandLine());

            //symfony has a default timeout which is 60 sec. This is not enough for converting big video-files.
            $process->setTimeout(null);
            $process->start();

            $logHandle = fopen($this->getConversionLogFile(), 'a');
            fwrite($logHandle, 'Command: ' . $process->getCommandLine() . "\n\n\n");

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

        $cmd = [
            self::getFfmpegCli(),
            '-ss', $timeOffset, '-i', realpath($this->file),
            '-vcodec', 'png', '-vframes', 1, '-vf', 'scale=iw*sar:ih',
            str_replace('/', DIRECTORY_SEPARATOR, $file), ];
        Console::addLowProcessPriority($cmd);
        $process = new Process($cmd);
        $process->run();
    }

    /**
     * @return string
     *
     * @throws \Exception
     */
    protected function getVideoInfo()
    {
        $tmpFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/video-info-' . uniqid() . '.out';

        $cmd = [self::getFfmpegCli(), '-i', realpath($this->file)];
        Console::addLowProcessPriority($cmd);
        $process = new Process($cmd);
        $process->start();

        $tmpHandle = fopen($tmpFile, 'a');
        $process->wait(function ($type, $buffer) use ($tmpHandle) {
            fwrite($tmpHandle, $buffer);
        });
        fclose($tmpHandle);

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
        $duration = ((int)$durationParts[0] * 3600) + ((int)$durationParts[1] * 60) + (float)$durationParts[2];

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
        array_push($this->arguments, $key, $value);
    }

    /**
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param int $videoBitrate
     *
     * @return $this
     */
    public function setVideoBitrate($videoBitrate)
    {
        $videoBitrate = (int)$videoBitrate;

        $videoBitrate = ceil($videoBitrate / 2) * 2;

        parent::setVideoBitrate($videoBitrate);

        if ($videoBitrate) {
            $this->addArgument('-vb', $videoBitrate . 'k');
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
        $audioBitrate = (int)$audioBitrate;

        $audioBitrate = ceil($audioBitrate / 2) * 2;

        parent::setAudioBitrate($audioBitrate);

        if ($audioBitrate) {
            $this->addArgument('-ab', $audioBitrate . 'k');
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
        $this->addArgument('-s', $width.'x'.$height);
    }

    /**
     * @param int $width
     */
    public function scaleByWidth($width)
    {
        // ensure $width is even (mp4 requires this)
        $width = ceil($width / 2) * 2;
        $this->addArgument('-filter:v', 'scale='.$width.':trunc(ow/a/2)*2');
    }

    /**
     * @param int $height
     */
    public function scaleByHeight($height)
    {
        // ensure $height is even (mp4 requires this)
        $height = ceil($height / 2) * 2;
        $this->addArgument('-filter:v', 'scale=trunc(oh/(ih/iw)/2)*2:'.$height);
    }
}
