<?php
namespace Pimcore\Image\Adapter;

use Pimcore\File;
use Pimcore\Image\Adapter;
use Pimcore\Logger;
use Pimcore\Tool\Console;
use Symfony\Component\Process\Process;

/**
 *
 *
 * Class ImageMagick
 * @package Pimcore\Image\Adapter
 */
class ImageMagick extends Adapter
{
    /**
     * The base image path
     *
     * @var null|string
     */
    protected $imagePath = null;

    /**
     * @var null|string
     */
    protected $outputPath = null;

    /**
     * Options used by the convert script
     *
     * @var array
     */
    protected $convertCommandOptions = [];

    /**
     * Options used by the composite script
     *
     * @var array
     */
    protected $compositeCommandOptions = [];

    /**
     * @var null|\Imagick
     */
    protected $resource = null;

    /**
     * Array with filters used with options
     *
     * @var array
     */
    protected $convertFilters = [];

    /**
     * @var string
     */
    protected $convertScriptPath = null;

    /**
     * @var string
     */
    protected $compositeScriptPath = null;

    /**
     * @var null
     */
    protected $forceAlpha = false;


    /**
     * loads the image by the specified path
     *
     * @param $imagePath
     * @param array $options
     * @return ImageMagick
     */
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

    /**
     * @return ImageMagick
     */
    protected function initResource()
    {
        if (null === $this->resource) {
            $this->resource = new \Imagick();
        }
        $this->resource->readImage($this->imagePath);
        $this->setWidth($this->resource->getImageWidth())
            ->setHeight($this->resource->getImageHeight());

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
        if (null !== $quality) {
            //set quality of the image
            $this->addConvertOption('quality', $quality);
        }

        $format = (null === $format) || $this->getForceAlpha() || $format == 'png'
            ? 'png32' : null;

        recursiveCopy($this->imagePath, $path);

        if (null !== $format) {
            //set the image format. see: http://www.imagemagick.org/script/formats.php
            $path = strtoupper($format) . ':' . $path;
        }

        $command = $this->getConvertCommand() . $path;
        $this->processCommand($command);
        $this->convertCommandOptions = [];
        $this->convertFilters = [];

        return $this;
    }


    /**
     * @return ImageMagick
     */
    protected function destroy()
    {
        //it deletes tmp files when the process is finished
        foreach ($this->tmpFiles as $tmpFile) {
            unlink($tmpFile);
        }

        return $this;
    }

    /**
     * Resize the image
     *
     * @param $width
     * @param $height
     * @return $this
     */
    public function resize($width, $height)
    {
        $this->addConvertOption('resize', "{$width}x{$height}");
        $this->setWidth($width);
        $this->setHeight($height);

        return $this;
    }

    /**
     * Adds frame which cause that the image gets exactly the entered dimensions by adding borders.
     *
     * @param $width
     * @param $height
     * @param bool $forceResize
     * @return ImageMagick
     */
    public function frame($width, $height, $forceResize = false)
    {
        $this->contain($width, $height, $forceResize);
        $this->saveIfRequired('frame');

        $frameWidth = $width - $this->getWidth() == 0 ? 0 : ($width - $this->getWidth()) / 2;
        $frameHeight = $height - $this->getHeight() == 0 ? 0 : ($height - $this->getHeight()) / 2;
        $this
            ->addConvertOption('bordercolor', 'none')
            ->addConvertOption('mattecolor', 'none')
            ->addConvertOption('frame', "{$frameWidth}x{$frameHeight}")
        ;
        $this->setForceAlpha(true);
        $this->setWidth($width);
        $this->setHeight($height);

        return $this;
    }

    /**
     * Trims edges
     *
     * @param int $tolerance
     * @return ImageMagick
     */
    public function trim($tolerance)
    {
        $this->addConvertOption('trim', $tolerance);

        return $this;
    }

    /**
     * Rotates the image with the given angle.
     *
     * @param $angle
     * @return ImageMagick
     */
    public function rotate($angle)
    {
        $this->setForceAlpha(true);
        $this
            ->addConvertOption('background', 'none')
            ->addConvertOption('alpha', 'set')
            ->addConvertOption('rotate', $angle);

        //an image size has changed after the rotate action, it's required to save it and reinit resource
        $this->saveIfRequired('after_rotate');
        $this->resource = null;
        $this->initResource();

        return $this;
    }

    /**
     * Cuts out a box of the image starting at the given X,Y coordinates and using the width and height.
     *
     * @param $x
     * @param $y
     * @param $width
     * @param $height
     * @return ImageMagick
     */
    public function crop($x, $y, $width, $height)
    {
        $this->addConvertOption('crop', "{$width}x{$height}+{$x}+{$y}");
        $this->setWidth($width)->setHeight($height);

        return $this;
    }

    /**
     * Set the background color of the image.
     *
     * @param $color
     * @return ImageMagick
     */
    public function setBackgroundColor($color)
    {
        //create canvas
        $canvas = $this->generateCanvas($this->getWidth(), $this->getHeight(), $color);

        //merge the background canvas with the main picture
        $this->mergeImage($canvas);

        return $this;
    }

    /**
     * Rounds the corners to the given width/height.
     *
     * @param $width
     * @param $height
     * @return ImageMagick
     */
    public function roundCorners($width, $height)
    {
        $this->saveIfRequired('round_corners_canvas');

        //creates the mask for rounded corners
        $mask = new ImageMagick();
        $mask->addConvertOption('size', "{$this->getWidth()}x{$this->getHeight()}")
            ->addConvertOption('draw', "'roundRectangle 0,0 {$this->getWidth()},{$this->getHeight()} {$width},{$height}'");
        $mask->addFilter('draw', 'xc:none');
        $mask->setWidth($this->getWidth())->setHeight($this->getHeight());
        $this->setTmpPaths($mask, 'mask');
        $mask->save($mask->getOutputPath());
        $mask->imagePath = $mask->getOutputPath();
        $this->tmpFiles[] = $mask->getOutputPath();

        //create the temp file with rounded corners
        $this
            ->addConvertOption('matte', $mask->getOutputPath())
            ->addConvertOption('compose', 'DstIn')
            ->addConvertOption('composite')
            ->addConvertOption('alpha', 'set')
        ;
        $this->setForceAlpha(true);

        //image has to be saved before next actions
        $this->saveIfRequired('round_corners');

        return $this;
    }

    /**
     * Set the image background
     *
     * @param $image
     * @param null $mode
     * @return ImageMagick
     */
    public function setBackgroundImage($image, $mode = null, $relativePath = false)
    {
        $imagePath = $relativePath ?
            PIMCORE_DOCUMENT_ROOT . "/" . ltrim($image, "/")
            : $image;

        if (is_file($imagePath)) {
            //if a specified file as a background exists
            //creates the temp file for the background
            $newImage = $this->createTmpImage($imagePath, 'background');
            if ($mode == "cropTopLeft") {
                //crop the background image
                $newImage->crop(0, 0, $this->getWidth(), $this->getHeight());
            } else {
                // default behavior (fit)
                $newImage->resize($this->getWidth(), $this->getHeight());
            }
            $newImage->save($newImage->getOutputPath());

            //save current state of the thumbnail to the tmp file
            $this->saveIfRequired('gravity');

            //save the current state of the file (with a background)
            $this->compositeCommandOptions = [];
            $this->addCompositeOption('gravity', 'center ' . $this->getOutputPath() . ' ' . $newImage->getOutputPath() . ' ' . $this->getOutputPath());
            $this->processCommand($this->getCompositeCommand());
            $this->imagePath = $this->getOutputPath();
        }


        return $this;
    }

    /**
     * @param string $image
     * @param int $x
     * @param int $y
     * @param int $alpha
     * @param string $composite
     * @param string $origin
     * @return ImageMagick
     */
    public function addOverlay($image, $x = 0, $y = 0, $alpha = 100, $composite = "COMPOSITE_DEFAULT", $origin = 'top-left')
    {
        $this->saveIfRequired('overlay_first_step');

        $image = PIMCORE_DOCUMENT_ROOT . "/" . ltrim($image, "/");
        if (is_file($image)) {
            //if a specified file as a overlay exists
            $overlayImage = $this->createTmpImage($image, 'overlay');
            $overlayImage->setForceAlpha(true)->addConvertOption('evaluate', "set {$alpha}%");

            //defines the position in order to the origin value
            switch ($origin) {
                case "top-right":
                    $x =  $this->getWidth() - $overlayImage->getWidth() - $x;
                    break;
                case "bottom-left":
                    $y =  $this->getHeight() - $overlayImage->getHeight() - $y;
                    break;
                case "bottom-right":
                    $x = $this->getWidth() - $overlayImage->getWidth()  - $x;
                    $y = $this->getHeight() - $overlayImage->getHeight() - $y;
                    break;
                case "center":
                    $x = round($this->getWidth() / 2)  - round($overlayImage->getWidth() / 2) + $x;
                    $y = round($this->getHeight() / 2) - round($overlayImage->getHeight() / 2) + $y;
                    break;
            }
            //top the overlay image
            $overlayImage->save($overlayImage->getOutputPath());

            $this->processOverlay($overlayImage, $composite, $x, $y, $alpha);
        }

        return $this;
    }

    /**
     * @param $image
     * @param string $composite
     * @return ImageMagick
     */
    public function addOverlayFit($image, $composite = "COMPOSITE_DEFAULT")
    {
        $image = PIMCORE_DOCUMENT_ROOT . "/" . ltrim($image, "/");
        if (is_file($image)) {
            //if a specified file as a overlay exists
            $overlayImage = $this->createTmpImage($image, 'overlay');
            $overlayImage->resize($this->getWidth(), $this->getHeight())->save($overlayImage->getOutputPath());

            $this->processOverlay($overlayImage, $composite);
        }

        return $this;
    }

    /**
     * @param ImageMagick $overlayImage
     * @param string $composite
     * @return ImageMagick
     */
    protected function processOverlay(ImageMagick $overlayImage, $composite = "COMPOSITE_DEFAULT", $x = 0, $y = 0, $overlayOpacity = 100)
    {
        //sets a value used by the compose option
        $allowedComposeOptions = [
            'hardlight', 'exclusion'
        ];
        $composite = strtolower(substr(strrchr($composite, "_"), 1));
        $composeVal = in_array($composite, $allowedComposeOptions) ? $composite : 'dissolve';

        //save current state of the thumbnail to the tmp file
        $this->saveIfRequired('compose');

        $this->setForceAlpha(true);
        $this->addConvertOption('compose', $composeVal)
            ->addConvertOption('geometry', "{$overlayImage->getWidth()}x{$overlayImage->getHeight()}+{$x}+{$y}")
            ->addConvertOption('define', "compose:args={$overlayOpacity},100")
            ->addConvertOption('composite')
            ->addFilter('compose', $overlayImage->imagePath);

        $this->save($this->getOutputPath());

        return $this;
    }

    /**
     * Add mask to the image
     *
     * @param $image
     * @return ImageMagick
     */
    public function applyMask($image)
    {
        $this->setForceAlpha(true)->saveIfRequired('mask');

        $image = PIMCORE_DOCUMENT_ROOT . "/" . ltrim($image, "/");

        $this->addConvertOption('alpha', 'off')->addConvertOption('compose', 'CopyOpacity')
            ->addConvertOption('composite')
            ->addFilter('alpha', $image);


        return $this;
    }

    /**
     * Cuts out a box of the image starting at the given X,Y coordinates and using percentage values of width and height.
     *
     * @param $x
     * @param $y
     * @param $width
     * @param $height
     * @return $this
     */
    public function cropPercent($x, $y, $width, $height)
    {
        $this->addConvertOption('crop-percent', "{$width}%x{$height}%+{$x}+{$y}");

        return $this;
    }

    /**
     * Converts the image into a linear-grayscale image.
     *
     * @return ImageMagick
     */
    public function grayscale($method = "Rec601Luma")
    {
        $this->addConvertOption('grayscale', $method);

        return $this;
    }

    /**
     * Applies the sepia effect into the image.
     *
     * @return ImageMagick
     */
    public function sepia()
    {
        $this->addConvertOption('sepia-tone', "85%");

        return $this;
    }

    /**
     * Sharpen the image.
     *
     * @param int $radius
     * @param float $sigma
     * @param float $amount
     * @param float $threshold
     * @return ImageMagick
     */
    public function sharpen($radius = 0, $sigma = 1.0, $amount = 1.0, $threshold = 0.05)
    {
        $this->addConvertOption('sharpen', "'{$radius}x{$sigma}+$amount+$threshold'");

        return $this;
    }

    /**
     * Blur the image.
     *
     * @param int $radius
     * @param float $sigma
     * @return $this
     */
    public function gaussianBlur($radius = 0, $sigma = 1.0)
    {
        $this->addConvertOption('gaussian-blur', "{$radius}x{$sigma}");

        return $this;
    }

    /**
     * Brightness, saturation and hue setting of the image.
     *
     * @param int $brightness
     * @param int $saturation
     * @param int $hue
     * @return ImageMagick
     */
    public function brightnessSaturation($brightness = 100, $saturation = 100, $hue = 100)
    {
        $this->addConvertOption('modulate', "{$brightness},{$saturation},{$hue}");

        return $this;
    }

    /**
     * Creates vertical or horizontal mirror of the image.
     *
     * @param $mode
     * @return ImageMagick
     */
    public function mirror($mode)
    {
        if ($mode == "vertical") {
            $this->addConvertOption('flip');
        } elseif ($mode == "horizontal") {
            $this->addConvertOption('flop');
        }

        return $this;
    }

    /**
     * Add option to the command
     *
     * @param $name
     * @param null $value
     * @return ImageMagick
     */
    public function addConvertOption($name, $value = null)
    {
        $this->convertCommandOptions[$name] = $value;

        return $this;
    }

    /**
     * Add option available in the composite tool
     *
     * @param $name
     * @param null $value
     * @return ImageMagick
     */
    public function addCompositeOption($name, $value = null)
    {
        $this->compositeCommandOptions[$name] = $value;

        return $this;
    }

    /**
     * Add a filter to the convert command
     *
     * @param $optionName
     * @param $filterValue
     * @return $this
     */
    public function addFilter($optionName, $filterValue)
    {
        if (! isset($this->convertFilters[$optionName])) {
            $this->convertFilters[$optionName] = [];
        }

        $this->convertFilters[$optionName][] = $filterValue;

        return $this;
    }

    /**
     * Returns the filters array
     *
     * @param $optionName
     * @return array
     */
    public function getConvertFilters($optionName)
    {
        return isset($this->convertFilters[$optionName]) ? $this->convertFilters[$optionName] : [];
    }

    /**
     * Return the command without an output file path
     *
     * @return string
     */
    public function getConvertCommand()
    {
        return "{$this->getConvertScriptPath()} {$this->getConvertOptionsAsString()}";
    }

    /**
     * Return the composite command as a string
     *
     * @return string
     */
    public function getCompositeCommand()
    {
        return "{$this->getCompositeScriptPath()} {$this->getCompositeOptionsAsString()}";
    }

    /**
     * Returns options parameter for the convert command
     *
     * @return string
     */
    public function getConvertOptionsAsString()
    {
        $options = $this->imagePath . ' ';
        foreach ($this->convertCommandOptions as $commandKey => $commandValue) {
            $options .= implode(' ', $this->getConvertFilters($commandKey)) . ' ';
            $options .= "-{$commandKey} {$commandValue} ";
        }

        return $options;
    }

    /**
     * Returns the composite options as a string
     *
     * @return string
     */
    public function getCompositeOptionsAsString()
    {
        $options = '';
        foreach ($this->compositeCommandOptions as $commandKey => $commandValue) {
            $options .= "-{$commandKey} {$commandValue} ";
        }

        return $options;
    }

    /**
     * Returns the convert cli script path.
     *
     * @return string
     */
    public function getConvertScriptPath()
    {
        if (null === $this->convertScriptPath) {
            $this->convertScriptPath = Console::getExecutable('convert');
        }

        return $this->convertScriptPath;
    }

    /**
     * Convert script path, as a default the adapter is just using 'convert'.
     *
     * @param $convertScriptPath
     * @return ImageMagick
     */
    public function setConvertScriptPath($convertScriptPath)
    {
        $this->convertScriptPath = $convertScriptPath;

        return $this;
    }

    /**
     * Returns the composite cli script path.
     *
     * @return string
     */
    public function getCompositeScriptPath()
    {
        if (null === $this->compositeScriptPath) {
            $this->compositeScriptPath = Console::getExecutable('composite');
        }

        return $this->compositeScriptPath;
    }

    /**
     * Composite script path, as a default the adapter is just using 'composite'.
     *
     * @param $convertScriptPath
     * @return ImageMagick
     */
    public function setCompositeScriptPath($compositeScriptPath)
    {
        $this->compositeScriptPath = $compositeScriptPath;

        return $this;
    }

    /**
     * Creates the tmp image, that image will be automatically deleted when the process finishes.
     *
     * @param $imagePath
     * @param $suffix
     * @return ImageMagick
     */
    protected function createTmpImage($imagePath, $suffix)
    {
        //if a specified file as a overlay exists
        $tmpImage = new ImageMagick();
        $tmpImage->load($imagePath);
        //creates the temp file for the background
        $this->setTmpPaths($tmpImage, $suffix);
        $this->tmpFiles[] = $tmpImage->getOutputPath();

        return $tmpImage;
    }

    /**
     * @param ImageMagick $image
     * @param $suffix
     * @return $this
     */
    protected function setTmpPaths(ImageMagick $image, $suffix)
    {
        $tmpFilename = "imagemagick_{$suffix}_" . md5($this->imagePath) . '.png';
        $tmpFilepath = PIMCORE_SYSTEM_TEMP_DIRECTORY . "/" . $tmpFilename;
        $image->setOutputPath($tmpFilepath);

        return $this;
    }

    /**
     *
     * @return null|string
     */
    public function getOutputPath()
    {
        return $this->outputPath;
    }

    /**
     * @param $path
     * @return ImageMagick
     */
    public function setOutputPath($path)
    {
        $this->outputPath = $path;

        return $this;
    }

    /**
     * It generates a basic canvas file with specified size and color
     *
     * @param $width
     * @param $height
     * @param $color
     * @return ImageMagick
     */
    public function generateCanvas($width, $height, $color)
    {
        $canvas = new ImageMagick();
        $canvas->addConvertOption('size', "{$width}x{$height}")
            ->addConvertOption('fill', "\"$color\"")
            ->addFilter('fill', "canvas:{$color}");

        $this->setTmpPaths($canvas, 'canvas');
        $canvas->save($canvas->getOutputPath());
        $canvas->imagePath = $canvas->getOutputPath();
        $this->tmpFiles[] = $canvas->getOutputPath();

        return $canvas;
    }

    /**
     * Merges the image specified as the argument into the main picture.
     *
     * @param ImageMagick $backgroundImage
     * @return ImageMagick
     */
    public function mergeImage(ImageMagick $backgroundImage)
    {
        $this->setTmpPaths($this, 'merge');
        $this->save($this->getOutputPath());
        //save the current state of the file (with a background)
        $this->compositeCommandOptions = [];
        $this->addCompositeOption('gravity', 'center ' . $this->getOutputPath() . ' ' . $backgroundImage->getOutputPath() . ' ' . $this->getOutputPath());
        $this->processCommand($this->getCompositeCommand());
        $this->imagePath = $this->getOutputPath();
        $this->tmpFiles[] = $this->getOutputPath();

        return $this;
    }

    /**
     * @param $command
     * @return int
     */
    protected function processCommand($command)
    {
        $process = new Process($command);

        return $process->run();
    }

    /**
     * @return bool
     */
    public function getForceAlpha()
    {
        return (bool) $this->forceAlpha;
    }

    /**
     * @param bool $forceAlpha
     * @return ImageMagick
     */
    public function setForceAlpha($forceAlpha)
    {
        $this->forceAlpha = (bool) $forceAlpha;

        return $this;
    }


    /**
     * @param string $suffix a thumbnail identifier
     * @return $this
     */
    public function saveIfRequired($suffix)
    {
        //saves previuos changes if there are any commands specified
        if (count($this->convertCommandOptions)) {
            $this->setTmpPaths($this, $suffix);
            $this->save($this->getOutputPath());
            $this->imagePath = $this->getOutputPath();
            $this->tmpFiles[] = $this->getOutputPath();
        }
        
        return $this;
    }
}
