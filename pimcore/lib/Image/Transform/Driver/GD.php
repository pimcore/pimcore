<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * GD implementation for Image_Transform package
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Image
 * @package    Image_Transform
 * @subpackage Image_Transform_Driver_GD
 * @author     Alan Knowles <alan@akbkhome.com>
 * @author     Peter Bowyer <peter@mapledesign.co.uk>
 * @author     Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright  2002-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: GD.php 287351 2009-08-16 03:28:48Z clockwerx $
 * @link       http://pear.php.net/package/Image_Transform
 */


/**
 * GD implementation for Image_Transform package
 *
 * Usage :
 *    $img    =& Image_Transform::factory('GD');
 *    $angle  = -78;
 *    $img->load('magick.png');
 *
 *    if ($img->rotate($angle, array(
 *               'autoresize' => true,
 *               'color_mask' => array(255, 0, 0)))) {
 *        $img->addText(array(
 *               'text' => 'Rotation ' . $angle,
 *               'x' => 0,
 *               'y' => 100,
 *               'font' => '/usr/share/fonts/default/TrueType/cogb____.ttf'));
 *        $img->display();
 *    } else {
 *        echo "Error";
 *    }
 *    $img->free();
 *
 * @category   Image
 * @package    Image_Transform
 * @subpackage Image_Transform_Driver_GD
 * @author     Alan Knowles <alan@akbkhome.com>
 * @author     Peter Bowyer <peter@mapledesign.co.uk>
 * @author     Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright  2002-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Image_Transform
 * @since      PHP 4.0
 */
class Image_Transform_Driver_GD extends Image_Transform
{
    /**
     * Holds the image resource for manipulation
     *
     * @var resource $imageHandle
     * @access protected
     */
    var $imageHandle = null;

    /**
     * Holds the original image file
     *
     * @var resource $imageHandle
     * @access protected
     */
    var $oldImage = null;

    /**
     * Check settings
     */
    function Image_Transform_Driver_GD()
    {
        $this->__construct();
    } // End function Image

    /**
     * Check settings
     *
     * @since PHP 5
     */
    function __construct()
    {
        if (!PEAR::loadExtension('gd')) {
            $this->isError(PEAR::raiseError("GD library is not available.",
                IMAGE_TRANSFORM_ERROR_UNSUPPORTED));
        } else {
            $types = ImageTypes();
            if ($types & IMG_PNG) {
                $this->_supported_image_types['png'] = 'rw';
            }
            if (($types & IMG_GIF)
                || function_exists('imagegif')) {
                $this->_supported_image_types['gif'] = 'rw';
            } elseif (function_exists('imagecreatefromgif')) {
                $this->_supported_image_types['gif'] = 'r';
            }
            if ($types & IMG_JPG) {
                $this->_supported_image_types['jpeg'] = 'rw';
            }
            if ($types & IMG_WBMP) {
                $this->_supported_image_types['wbmp'] = 'rw';
            }
            if (!$this->_supported_image_types) {
                $this->isError(PEAR::raiseError("No supported image types available", IMAGE_TRANSFORM_ERROR_UNSUPPORTED));
            }
        }

    } // End function Image

    /**
     * Loads an image from file
     *
     * @param string $image filename
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function load($image)
    {
        $this->free();

        $this->image = $image;
        $result = $this->_get_image_details($image);
        if (PEAR::isError($result)) {
            return $result;
        }
        if (!$this->supportsType($this->type, 'r')) {
            return PEAR::raiseError('Image type not supported for input',
                IMAGE_TRANSFORM_ERROR_UNSUPPORTED);
        }

        $functionName = 'ImageCreateFrom' . $this->type;
        $this->imageHandle = $functionName($this->image);
        if (!$this->imageHandle) {
            $this->imageHandle = null;
            return PEAR::raiseError('Error while loading image file.',
                IMAGE_TRANSFORM_ERROR_IO);
        }
        return true;

    } // End load

    /**
     * Returns the GD image handle
     *
     * @return resource
     *
     * @access public
     */
    function getHandle()
    {
        return $this->imageHandle;
    }//function getHandle()

    /**
     * Adds a border of constant width around an image
     *
     * @param int $border_width Width of border to add
     * @author Peter Bowyer
     * @return bool TRUE
     * @access public
     */
    function addBorder($border_width, $color = '')
    {
        $this->new_x = $this->img_x + 2 * $border_width;
        $this->new_y = $this->img_y + 2 * $border_width;

        $new_img = $this->_createImage($new_x, $new_y, $this->true_color);

        $options = array('pencilColor', $color);
        $color = $this->_getColor('pencilColor', $options, array(0, 0, 0));
        if ($color) {
            if ($this->true_color) {
                $c = imagecolorresolve($this->imageHandle, $color[0], $color[1], $color[2]);
                imagefill($new_img, 0, 0, $c);
            } else {
                imagecolorset($new_img, imagecolorat($new_img, 0, 0), $color[0], $color[1], $color[2]);
            }
        }
        ImageCopy($new_img, $this->imageHandle, $border_width, $border_width, 0, 0, $this->img_x, $this->img_y);
        $this->imageHandle = $new_img;
        $this->resized = true;

        return true;
    }

    /**
     * addText
     *
     * @param   array   $params     Array contains options
     *                              array(
     *                                  'text'  The string to draw
     *                                  'x'     Horizontal position
     *                                  'y'     Vertical Position
     *                                  'color' Font color
     *                                  'font'  Font to be used
     *                                  'size'  Size of the fonts in pixel
     *                                  'resize_first'  Tell if the image has to be resized
     *                                                  before drawing the text
     *                              )
     *
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     */
    function addText($params)
    {
        $this->oldImage = $this->imageHandle;
        $params = array_merge($this->_get_default_text_params(), $params);
        extract($params);

        $options = array('fontColor' => $color);
        $color = $this->_getColor('fontColor', $options, array(0, 0, 0));

        $c = imagecolorresolve ($this->imageHandle, $color[0], $color[1], $color[2]);

        if ('ttf' == substr($font, -3)) {
            ImageTTFText($this->imageHandle, $size, $angle, $x, $y, $c, $font, $text);
        } else {
            ImagePSText($this->imageHandle, $size, $angle, $x, $y, $c, $font, $text);
        }

        return true;
    } // End addText

    /**
     * Rotates image by the given angle
     *
     * Uses a fast rotation algorythm for custom angles
     * or lines copy for multiple of 90 degrees
     *
     * @param int   $angle   Rotation angle
     * @param array $options array(
     *                             'canvasColor' => array(r ,g, b), named color or #rrggbb
     *                            )
     * @author Pierre-Alain Joye
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function rotate($angle, $options = null)
    {
        if (($angle % 360) == 0) {
            return true;
        }

        $color_mask = $this->_getColor('canvasColor', $options,
                                        array(255, 255, 255));

        $mask   = imagecolorresolve($this->imageHandle, $color_mask[0], $color_mask[1], $color_mask[2]);

        $this->oldImage = $this->imageHandle;

        // Multiply by -1 to change the sign, so the image is rotated clockwise
        $this->imageHandle = ImageRotate($this->imageHandle, $angle * -1, $mask);
        return true;
    }

    /**
     * Horizontal mirroring
     *
     * @return mixed TRUE or PEAR_Error object on error
     * @access public
     * @see flip()
     **/
    function mirror()
    {
        $new_img = $this->_createImage();
        for ($x = 0; $x < $this->new_x; ++$x) {
            imagecopy($new_img, $this->imageHandle, $x, 0,
                $this->new_x - $x - 1, 0, 1, $this->new_y);
        }
        imagedestroy($this->imageHandle);
        $this->imageHandle = $new_img;
        return true;
    }

    /**
     * Vertical mirroring
     *
     * @return TRUE or PEAR Error object on error
     * @access public
     * @see mirror()
     **/
    function flip()
    {
        $new_img = $this->_createImage();
        for ($y = 0; $y < $this->new_y; ++$y) {
            imagecopy($new_img, $this->imageHandle, 0, $y,
                0, $this->new_y - $y - 1, $this->new_x, 1);
        }
        imagedestroy($this->imageHandle);
        $this->imageHandle = $new_img;

        /* for very large images we may want to use the following
           Needs to find out what is the threshhold
        for ($x = 0; $x < $this->new_x; ++$x) {
            for ($y1 = 0; $y1 < $this->new_y / 2; ++$y1) {
                $y2 = $this->new_y - 1 - $y1;
                $color1 = imagecolorat($this->imageHandle, $x, $y1);
                $color2 = imagecolorat($this->imageHandle, $x, $y2);
                imagesetpixel($this->imageHandle, $x, $y1, $color2);
                imagesetpixel($this->imageHandle, $x, $y2, $color1);
            }
        } */
        return true;
    }

    /**
     * Crops image by size and start coordinates
     *
     * @param int width Cropped image width
     * @param int height Cropped image height
     * @param int x X-coordinate to crop at
     * @param int y Y-coordinate to crop at
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function crop($width, $height, $x = 0, $y = 0)
    {
        // Sanity check
        if (!$this->intersects($width, $height, $x, $y)) {
            return PEAR::raiseError('Nothing to crop', IMAGE_TRANSFORM_ERROR_OUTOFBOUND);
        }
        $x = min($this->new_x, max(0, $x));
        $y = min($this->new_y, max(0, $y));
        $width   = min($width,  $this->new_x - $x);
        $height  = min($height, $this->new_y - $y);
        $new_img = $this->_createImage($width, $height);

        if (!imagecopy($new_img, $this->imageHandle, 0, 0, $x, $y, $width, $height)) {
            imagedestroy($new_img);
            return PEAR::raiseError('Failed transformation: crop()',
                IMAGE_TRANSFORM_ERROR_FAILED);
        }

        $this->oldImage = $this->imageHandle;
        $this->imageHandle = $new_img;
        $this->resized = true;

        $this->new_x = $width;
        $this->new_y = $height;
        return true;
    }

    /**
     * Converts the image to greyscale
     *
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function greyscale() {
        imagecopymergegray($this->imageHandle, $this->imageHandle, 0, 0, 0, 0, $this->new_x, $this->new_y, 0);
        return true;
    }

   /**
    * Resize Action
    *
    * For GD 2.01+ the new copyresampled function is used
    * It uses a bicubic interpolation algorithm to get far
    * better result.
    *
    * @param int   $new_x   New width
    * @param int   $new_y   New height
     * @param array $options Optional parameters
     * <ul>
     *  <li>'scaleMethod': "pixel" or "smooth"</li>
     * </ul>
    *
    * @return bool|PEAR_Error TRUE on success or PEAR_Error object on error
    * @access protected
    */
    function _resize($new_x, $new_y, $options = null)
    {
        if ($this->resized === true) {
            return PEAR::raiseError('You have already resized the image without saving it.  Your previous resizing will be overwritten', null, PEAR_ERROR_TRIGGER, E_USER_NOTICE);
        }

        if ($this->new_x == $new_x && $this->new_y == $new_y) {
            return true;
        }

        $scaleMethod = $this->_getOption('scaleMethod', $options, 'smooth');

        // Make sure to get a true color image if doing resampled resizing
        // otherwise get the same type of image
        $trueColor = ($scaleMethod == 'pixel') ? null : true;
        $new_img = $this->_createImage($new_x, $new_y, $trueColor);

        $icr_res = null;
        if ($scaleMethod != 'pixel' && function_exists('ImageCopyResampled')) {
            $icr_res = ImageCopyResampled($new_img, $this->imageHandle, 0, 0, 0, 0, $new_x, $new_y, $this->img_x, $this->img_y);
        }
        if (!$icr_res) {
            ImageCopyResized($new_img, $this->imageHandle, 0, 0, 0, 0, $new_x, $new_y, $this->img_x, $this->img_y);
        }
        $this->oldImage = $this->imageHandle;
        $this->imageHandle = $new_img;
        $this->resized = true;

        $this->new_x = $new_x;
        $this->new_y = $new_y;
        return true;
    }

    /**
     * Adjusts the image gamma
     *
     * @param float $outputgamma
     *
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function gamma($outputgamma = 1.0)
    {
        if ($outputgamma != 1.0) {
            ImageGammaCorrect($this->imageHandle, 1.0, $outputgamma);
        }
        return true;
    }

    /**
     * Helper method to save to a file or output the image
     *
     * @param string $filename the name of the file to write to (blank to output)
     * @param string $types    define the output format, default
     *                          is the current used format
     * @param int    $quality  output DPI, default is 75
     *
     * @return bool|PEAR_Error TRUE on success or PEAR_Error object on error
     * @access protected
     */
    function _generate($filename, $type = '', $quality = null)
    {
        $type = strtolower(($type == '') ? $this->type : $type);
        $options = (is_array($quality)) ? $quality : array();
        switch ($type) {
            case 'jpg':
                $type = 'jpeg';
            case 'jpeg':
                if (is_numeric($quality)) {
                    $options['quality'] = $quality;
                }
                $quality = $this->_getOption('quality', $options, 75);
                break;
        }
        if (!$this->supportsType($type, 'w')) {
            return PEAR::raiseError('Image type not supported for output',
                IMAGE_TRANSFORM_ERROR_UNSUPPORTED);
        }

        if ($filename == '') {
            header('Content-type: ' . $this->getMimeType($type));
            $action = 'output image';
        } else {
            $action = 'save image to file';
        }

        $functionName = 'image' . $type;
        switch ($type) {
            case 'jpeg':
                $result = $functionName($this->imageHandle, $filename, $quality);
                break;
            default:
                if ($filename == '') {
                    $result = $functionName($this->imageHandle);
                } else {
                    $result = $functionName($this->imageHandle, $filename);
                }
        }
        if (!$result) {
            return PEAR::raiseError('Couldn\'t ' . $action,
                IMAGE_TRANSFORM_ERROR_IO);
        }
        $this->imageHandle = $this->oldImage;
        if (!$this->keep_settings_on_save) {
            $this->free();
        }
        return true;

    } // End save

    /**
     * Displays image without saving and lose changes.
     *
     * This method adds the Content-type HTTP header
     *
     * @param string $type (JPEG, PNG...);
     * @param int    $quality 75
     *
     * @return bool|PEAR_Error TRUE or PEAR_Error object on error
     * @access public
     */
    function display($type = '', $quality = null)
    {
        return $this->_generate('', $type, $quality);
    }

    /**
     * Saves the image to a file
     *
     * @param string $filename the name of the file to write to
     * @param string $type     the output format, default
     *                          is the current used format
     * @param int    $quality  default is 75
     *
     * @return bool|PEAR_Error TRUE on success or PEAR_Error object on error
     * @access public
     */
    function save($filename, $type = '', $quality = null)
    {
        if (!trim($filename)) {
            return PEAR::raiseError('Filename missing',
                IMAGE_TRANSFORM_ERROR_ARGUMENT);
        }
        return $this->_generate($filename, $type, $quality);
    }

    /**
     * Destroys image handle
     *
     * @access public
     */
    function free()
    {
        $this->resized = false;
        if (is_resource($this->imageHandle)) {
            ImageDestroy($this->imageHandle);
        }
        $this->imageHandle = null;
        if (is_resource($this->oldImage)){
            ImageDestroy($this->oldImage);
        }
        $this->oldImage = null;
    }

    /**
     * Returns a new image for temporary processing
     *
     * @param int $width width of the new image
     * @param int $height height of the new image
     * @param bool $trueColor force which type of image to create
     * @return resource a GD image resource
     * @access protected
     */
    function _createImage($width = -1, $height = -1, $trueColor = null)
    {
        if ($width == -1) {
            $width = $this->new_x;
        }
        if ($height == -1) {
            $height = $this->new_y;
        }

        $new_img = null;
        if (is_null($trueColor)) {
            if (function_exists('imageistruecolor')) {
                $createtruecolor = imageistruecolor($this->imageHandle);
            } else {
                $createtruecolor = true;
            }
        } else {
            $createtruecolor = $trueColor;
        }
        if ($createtruecolor
            && function_exists('ImageCreateTrueColor')) {
            $new_img = @ImageCreateTrueColor($width, $height);
            imagealphablending($new_img, false);
            imagesavealpha($new_img, true);
        }
        if (!$new_img) {
            $new_img = ImageCreate($width, $height);
            imagepalettecopy($new_img, $this->imageHandle);
            $color = imagecolortransparent($this->imageHandle);
            if ($color != -1) {
                imagecolortransparent($new_img, $color);
                imagefill($new_img, 0, 0, $color);
            }
        }
        return $new_img;
    }
}