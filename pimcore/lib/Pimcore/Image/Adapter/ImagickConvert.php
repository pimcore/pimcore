<?php
namespace Pimcore\Image\Adapter;

use Pimcore\File;
use Pimcore\Image\Adapter;
use Pimcore\Logger;

class ImagickConvert extends Adapter
{
    protected $imagePath = "";

    /**
     * Command used by the CLI script
     *
     * @var string
     */
    protected $command = [];


    protected $outputPath = null;
    /**
     * available options in the convert tool
     *
     * @var null|array
     */
    protected $availableOptions = null;

    /**
     * @var null|\Imagick
     */
    protected $resource = null;

    public function load($imagePath, $options = [])
    {
        // support image URLs
        if (preg_match("@^https?://@", $imagePath)) {
            $tmpFilename = "imagick_auto_download_" . md5($imagePath) . "." . File::getFileExtension($imagePath);
            $tmpFilePath = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $tmpFilename;

            $this->tmpFiles[] = $tmpFilePath;

            File::put($tmpFilePath, \Pimcore\Tool::getHttpData($imagePath));
            $imagePath = $tmpFilePath;
        }

        if (!stream_is_local($imagePath)) {
            // imagick is only able to deal with local files
            // if your're using custom stream wrappers this wouldn't work, so we create a temp. local copy
            $tmpFilePath = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/imagick-tmp-" . uniqid() . "." . File::getFileExtension($imagePath);
            copy($imagePath, $tmpFilePath);
            $imagePath = $tmpFilePath;
            $this->tmpFiles[] = $imagePath;
        }

        $this->imagePath = $imagePath;

        $this->initResource();

        $this->setModified(false);

        return $this;
    }

    protected function initResource()
    {
        if (null === $this->resource) {
            $this->resource = new \Imagick();
        }
        $this->resource->readImage($this->imagePath);
        $this->setWidth($this->resource->getImageWidth())
            ->setHeight($this->resource->getImageHeight());
    }

    /**
     * Save the modified image output in the specified path as the first argument.
     *
     * @param $path
     * @param null $format
     * @param null $quality
     * @return $this
     */
    public function save($path, $format = null, $quality = null)
    {
        $command = ((string) $this) . $path;
        recursiveCopy($this->imagePath, $path);
        exec($command);

        return $this;
    }

    /**
     * @return ImagickConvert
     */
    protected function destroy()
    {
        foreach($this->tmpFiles as $tmpFile) {
            unlink($tmpFile);
        }

        return $this;
    }

    /**
     * @param $width
     * @param $height
     * @return $this
     */
    public function resize($width, $height)
    {
        $this->preModify();
        $this->addOption('resize', "{$width}x{$height}");
        $this->setWidth($width);
        $this->setHeight($height);
        $this->postModify();
        return $this;
    }

    /**
     * @param $width
     * @param $height
     * @return ImagickConvert
     */
    public function frame($width, $height)
    {
        $this->preModify();

        $this->contain($width, $height);
        $frameWidth = $width - $this->getWidth() == 0 ? 0 : ($width - $this->getWidth()) / 2;
        $frameHeight = $height - $this->getHeight() == 0 ? 0 : ($height - $this->getHeight()) / 2;
        $this->addOption('frame', "{$frameWidth}x{$frameHeight}")
            ->addOption('alpha', 'Set');

        $this->postModify();

        return $this;
    }

    /**
     * @param int $tolerance
     * @return ImagickConvert
     */
    public function trim($tolerance)
    {
        $this->preModify();
        $this->addOption('trim', $tolerance);
        $this->postModify();

        return $this;
    }

    /**
     * @param $angle
     * @return ImagickConvert
     */
    public function rotate($angle)
    {
        $this->preModify();
        $this->addOption('rotate', $angle)->addOption('alpha', 'Set');
        $this->postModify();
        return $this;
    }

    /**
     * @param $x
     * @param $y
     * @param $width
     * @param $height
     * @return ImagickConvert
     */
    public function crop($x, $y, $width, $height)
    {
        $this->addOption('crop', "{$width}x{$height}+{$x}+{$y}");

        return $this;
    }

    public function setBackgroundColor($color)
    {
    }

    public function roundCorners($width, $height)
    {
    }

    public function setBackgroundImage($image, $mode = null)
    {
    }

    public function addOverlay($image, $x = 0, $y = 0, $alpha = 100, $composite = "COMPOSITE_DEFAULT", $origin = 'top-left')
    {
    }

    public function addOverlayFit($image, $composite = "COMPOSITE_DEFAULT")
    {
    }

    /**
     * @param $image
     * @return ImagickConvert
     */
    public function applyMask($image)
    {
        $this->preModify();
        $this->addOption('write-mask', $image);
        $this->postModify();

        return $this;
    }

    public function cropPercent($x, $y, $width, $height)
    {
    }

    /**
     * @return ImagickConvert
     */
    public function grayscale($method = "Rec709Luminance")
    {
        $this->preModify();
        $this->addOption('grayscale', $method);
        $this->postModify();

        return $this;
    }

    public function sepia()
    {
    }

    public function sharpen($radius = 0, $sigma = 1.0, $amount = 1.0, $threshold = 0.05)
    {
    }

    public function gaussianBlur($radius = 0, $sigma = 1.0)
    {
    }

    public function brightnessSaturation($brightness = 100, $saturation = 100, $hue = 100)
    {
    }

    public function mirror($mode)
    {
    }

    protected function addOption($name, $value = null)
    {
        $this->command[$name] = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "convert {$this->getOptionsAsString()}";
    }

    /**
     * Returns options parameter for the convert command
     *
     * @return string
     */
    public function getOptionsAsString()
    {
        $options = $this->imagePath . ' ';
        foreach($this->command as $commandKey => $commandValue) {
            $options .= "-{$commandKey} {$commandValue} ";
        }

        return $options;
    }

    /**
     * @TODO - change to CLI
     *
     * @param $width
     * @param $height
     * @param string $color
     * @return \Imagick
     */
    protected function createImage($width, $height, $color = "transparent")
    {
        $newImage = new \Imagick();
        $newImage->newimage($width, $height, $color);
        $newImage->setImageFormat($this->resource->getImageFormat());

        return $newImage;
    }
}