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

        $this->setModified(false);

        return $this;
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
        exec((string) $this);

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

    public function resize($width, $height)
    {

    }

    public function frame($width, $height)
    {
    }

    public function trim($tolerance)
    {
    }

    public function rotate($angle)
    {
    }

    public function crop($x, $y, $width, $height)
    {
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
        return "convert {$this->getOptionsAsString($this->outputPath)}";
    }

    /**
     * Returns options parameter for the convert command
     *
     * @return string
     */
    public function getOptionsAsString($path = null)
    {
        $options = '';
        foreach($this->command as $commandKey => $commandValue) {
            $options .= "{$commandKey} {$commandValue} ";
        }
        $options .= ' ' . $path;

        return $options;
    }
}