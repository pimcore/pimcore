<?php
declare(strict_types=1);

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Video\Adapter;

use Exception;
use Pimcore\Logger;
use Pimcore\Tool\Console;
use Pimcore\Video\Adapter;
use Symfony\Component\Process\Process;

/**
 * @internal
 */
class Ffmpeg extends Adapter
{
    public string $file;

    protected string $processId;

    protected array $arguments = [];

    protected array $videoFilter = [];

    protected ?float $inputSeeking = null;

    public function isAvailable(): bool
    {
        try {
            $ffmpeg = self::getFfmpegCli();
            $phpCli = Console::getPhpCli();
            if ($ffmpeg && $phpCli) {
                return true;
            }
        } catch (Exception $e) {
            Logger::warning((string) $e);
        }

        return false;
    }

    /**
     *
     * @throws Exception
     */
    public static function getFfmpegCli(): false|string
    {
        return \Pimcore\Tool\Console::getExecutable('ffmpeg', true);
    }

    public function load(string $file, array $options = []): static
    {
        $this->file = $file;
        $this->setProcessId(uniqid());

        return $this;
    }

    /**
     *
     * @throws Exception
     */
    public function save(): bool
    {
        $success = false;

        if ($this->getDestinationFile()) {
            if (is_file($this->getConversionLogFile())) {
                $this->deleteConversionLogFile();
            }
            if (is_file($this->getDestinationFile())) {
                @unlink($this->getDestinationFile());
            }

            if (count($this->videoFilter) > 0) {
                $this->addArgument('-vf', implode(',', $this->videoFilter));
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
            } elseif ($this->getFormat() === 'mpg') {
                array_push($command, '-c:v', 'mpeg2video');
                array_push($command, '-c:a', 'mp2');
                array_push($command, '-f', 'vob');
            } else {
                throw new Exception('Unsupported video output format: ' . $this->getFormat());
            }

            // add some global arguments
            array_push($command, '-threads', '0');
            $command[] = str_replace('/', DIRECTORY_SEPARATOR, $this->getDestinationFile());
            array_unshift($command, '-i', realpath($this->file));
            // prepend seeking before input file to use input seeking method
            if (isset($this->inputSeeking)) {
                $sourceDuration = $this->getDuration() * 100;
                if ($this->inputSeeking >= $sourceDuration) {
                    $this->inputSeeking = 0;
                }
                array_unshift($command, '-ss', $this->inputSeeking);
            }
            array_unshift($command, self::getFfmpegCli());

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
                    copy(
                        $this->getConversionLogFile(),
                        str_replace('.log', '.error.log', $this->getConversionLogFile())
                    );
                }
            }
        } else {
            throw new Exception('There is no destination file for video converter');
        }

        return $success;
    }

    public function saveImage(string $file, int $timeOffset = null): void
    {
        if (!is_numeric($timeOffset)) {
            $timeOffset = 5;
        }

        $cmd = [
            self::getFfmpegCli(),
            '-ss', $timeOffset, '-i', realpath($this->file),
            '-vcodec', 'png', '-vframes', 1, '-vf', 'scale=iw*sar:ih',
            str_replace('/', DIRECTORY_SEPARATOR, $file),
        ];
        Console::addLowProcessPriority($cmd);
        $process = new Process($cmd);
        $process->run();
    }

    /**
     *
     * @throws Exception
     */
    protected function getVideoInfo(): string
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

    public function getDuration(): ?float
    {
        try {
            $output = $this->getVideoInfo();

            // get total video duration
            $result = preg_match('/Duration: (\d\d):(\d\d):(\d\d\.\d+),/', $output, $matches);

            if ($result) {
                // calculate duration in seconds
                $duration = ((int)$matches[1] * 3600) + ((int)$matches[2] * 60) + (float)$matches[3];

                return $duration;
            }

            throw new Exception(
                'Could not read duration with FFMPEG Adapter. File: ' . $this->file . '. Output: ' . $output
            );
        } catch (Exception $e) {
            Logger::error($e->getMessage());
        }

        return null;
    }

    public function getDimensions(): ?array
    {
        try {
            $output = $this->getVideoInfo();

            if (preg_match('/ ([0-9]+x[0-9]+)[, ]/', $output, $matches)) {
                $dimensionRaw = $matches[1];
                [$width, $height] = explode('x', $dimensionRaw);

                return ['width' => $width, 'height' => $height];
            }

            throw new Exception(
                'Could not read dimensions with FFMPEG Adapter. File: ' . $this->file . '. Output: ' . $output
            );
        } catch (Exception $e) {
            Logger::error($e->getMessage());
        }

        return null;
    }

    public function destroy(): void
    {
        if (file_exists($this->getConversionLogFile())) {
            Logger::debug("FFMPEG finished, last message was:\n" . file_get_contents($this->getConversionLogFile()));
            $this->deleteConversionLogFile();
        }
    }

    private function deleteConversionLogFile(): void
    {
        @unlink($this->getConversionLogFile());
    }

    public function setProcessId(string $processId): static
    {
        $this->processId = $processId;

        return $this;
    }

    public function getProcessId(): string
    {
        return $this->processId;
    }

    protected function getConversionLogFile(): string
    {
        return PIMCORE_LOG_DIRECTORY . '/ffmpeg-' . $this->getProcessId() . '-' . $this->getFormat() . '.log';
    }

    public function addArgument(string $key, string $value): void
    {
        array_push($this->arguments, $key, $value);
    }

    public function addFlag(string $flag): void
    {
        array_push($this->arguments, $flag);
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setVideoBitrate(int $videoBitrate): static
    {
        $videoBitrate = (int) ceil($videoBitrate / 2) * 2;

        parent::setVideoBitrate($videoBitrate);

        if ($videoBitrate) {
            $this->addArgument('-vb', $videoBitrate . 'k');
        }

        return $this;
    }

    public function setAudioBitrate(int $audioBitrate): static
    {
        $audioBitrate = (int) ceil($audioBitrate / 2) * 2;

        parent::setAudioBitrate($audioBitrate);

        if ($audioBitrate) {
            $this->addArgument('-ab', $audioBitrate . 'k');
        }

        return $this;
    }

    public function resize(int $width, int $height): void
    {
        // ensure $width & $height are even (mp4 requires this)
        $width = ceil($width / 2) * 2;
        $height = ceil($height / 2) * 2;
        $this->addArgument('-s', $width.'x'.$height);
    }

    public function scaleByWidth(int $width): void
    {
        // ensure $width is even (mp4 requires this)
        $width = ceil($width / 2) * 2;
        $this->videoFilter[] = 'scale='.$width.':trunc(ow/a/2)*2';
    }

    public function scaleByHeight(int $height): void
    {
        // ensure $height is even (mp4 requires this)
        $height = ceil($height / 2) * 2;
        $this->videoFilter[] = 'scale=trunc(oh/(ih/iw)/2)*2:'.$height;
    }

    public function cut(?string $inputSeeking = null, ?string $targetDuration = null): void
    {
        if (!empty($inputSeeking)) {
            $result = preg_match("/^(\d\d):(\d\d):(\d\d\.?\d*)$/", $inputSeeking, $matches);

            if ($result) {
                $this->inputSeeking = ((int)$matches[1] * 3600) + ((int)$matches[2] * 60) + (float)$matches[3];
            }
        }
        if (!empty($targetDuration)) {
            $this->addArgument('-t', $targetDuration);
        }
    }

    public function setFramerate(int $fps): void
    {
        $this->videoFilter[] = 'fps='.$fps;
    }

    public function mute(): void
    {
        $this->addFlag('-an');
    }

    public function colorChannelMixer(?string $effect = null): void
    {
        if (!empty($effect)) {
            $this->videoFilter[] = 'colorchannelmixer='.$effect;
        }
    }
}
