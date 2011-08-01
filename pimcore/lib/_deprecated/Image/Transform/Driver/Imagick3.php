<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * imagick PECL extension implementation for Image_Transform package
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Image
 * @package    Image_Transform
 * @subpackage Image_Transform_Driver_Imagick3
 * @author     Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright  2007 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: Imagick3.php 287351 2009-08-16 03:28:48Z clockwerx $
 * @link       http://pear.php.net/package/Image_Transform
 */

require_once 'Image/Transform.php';

/**
 * imagick PECL extension implementation for Image_Transform package
 *
 * For use of version 2+ of the extension. For version 0.9.* use Imagick2 driver
 * instead
 *
 * @category   Image
 * @package    Image_Transform
 * @subpackage Image_Transform_Driver_Imagick3
 * @author     Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright  2007 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Image_Transform
 * @since      0.9.2
 * @since      PHP 5.1.3
 * @since      PECL Imagick 2.0.0a1
 */
class Image_Transform_Driver_Imagick3 extends Image_Transform
{
    /**
     * Instance of imagick
     * @var Imagick
     */
    var $imagick = null;

    /**
     * Handler of the image resource before
     * the last transformation
     * @var array
     */
    var $oldImage;

    /**
     * @see __construct()
     */
    function Image_Transform_Driver_Imagick3()
    {
        $this->__construct();
    }

    /**
     * @see http://www.imagemagick.org/www/formats.html
     */
    function __construct()
    {
        if (PEAR::loadExtension('imagick')) {
            include 'Image/Transform/Driver/Imagick/ImageTypes.php';
        } else {
            $this->isError(PEAR::raiseError('Could not find the imagick extension.',
                IMAGE_TRANSFORM_ERROR_UNSUPPORTED));
        }
    }

    /**
     * Loads an image
     *
     * @param string $image filename
     *
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function load($image)
    {
        $this->free();
        $this->imagick = new Imagick();
        try {
            $this->imagick->readImage($image);

        } catch (ImagickException $e) {
            $this->free();
            return $this->raiseError('Could not load image:'.$e->getMessage(),
                IMAGE_TRANSFORM_ERROR_IO);
        }

        $this->image = $image;
        $result = $this->_get_image_details($image);
        if (PEAR::isError($result)) {
            return $result;
        }

        return true;
    }




    /**
     * Sets the image type (in lowercase letters), the image height and width.
     *
     * @return mixed TRUE or PEAR_error
     * @access protected
     * @see PHP_Compat::image_type_to_mime_type()
     * @link http://php.net/getimagesize
     */
    function _get_image_details($image)
    {

        // get width and height
        $dimensions = $this->imagick->getImageGeometry();
        $this->img_x = $this->new_x = $dimensions["width"];
        $this->img_y = $this->new_y = $dimensions["height"];

        $type = $this->imagick->getImageType();

        switch ($type) {
            case IMAGETYPE_GIF:
                $type = 'gif';
                break;
            case IMAGETYPE_JPEG:
                $type = 'jpeg';
                break;
            case IMAGETYPE_PNG:
                $type = 'png';
                break;
            case IMAGETYPE_SWF:
                $type = 'swf';
                break;
            case IMAGETYPE_PSD:
                $type = 'psd';
                break;
            case IMAGETYPE_BMP:
                $type = 'bmp';
                break;
            case IMAGETYPE_TIFF_II:
            case IMAGETYPE_TIFF_MM:
                $type = 'tiff';
                break;
            case IMAGETYPE_JPC:
                $type = 'jpc';
                break;
            case IMAGETYPE_JP2:
                $type = 'jp2';
                break;
            case IMAGETYPE_JPX:
                $type = 'jpx';
                break;
            case IMAGETYPE_JB2:
                $type = 'jb2';
                break;
            case IMAGETYPE_SWC:
                $type = 'swc';
                break;
            case IMAGETYPE_IFF:
                $type = 'iff';
                break;
            case IMAGETYPE_WBMP:
                $type = 'wbmp';
                break;
            case IMAGETYPE_XBM:
                $type = 'xbm';
                break;
            default:
                return PEAR::raiseError("Cannot recognize image format",
                    IMAGE_TRANSFORM_ERROR_UNSUPPORTED);
        }
        $this->type  = $type;

        return true;
    }


    /**
     * Resizes the image
     *
     * @param integer $new_x   New width
     * @param integer $new_y   New height
     * @param mixed $options Optional parameters
     * <ul>
     *  <li>'scaleMethod': "pixel" or "smooth"</li>
     * </ul>
     *
     * @return bool|PEAR_Error TRUE or PEAR_Error object on error
     * @access protected
     */
    function _resize($new_x, $new_y, $options = null)
    {
        try {
            $scaleMethod = $this->_getOption('scaleMethod', $options, 'smooth');
            $blur = ($scaleMethod == 'pixel') ? 0 : 1;
            $this->imagick->resizeImage($new_x, $new_y,
                                        imagick::FILTER_UNDEFINED, $blur);

        } catch (ImagickException $e) {
            return $this->raiseError('Could not resize image.',
                IMAGE_TRANSFORM_ERROR_FAILED);
        }

        $this->new_x = $new_x;
        $this->new_y = $new_y;
        return true;

    } // End resize

    /**
     * Rotates the current image
     *
     * @param float $angle Rotation angle in degree
     * @param array $options Supported options:
     * <ul>
     *  <li>'canvasColor' : array(r ,g, b), named color or #rrggbb</li>
     * </ul>
     *
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function rotate($angle, $options = null)
    {
        if (($angle % 360) == 0) {
            return true;
        }
        $color = $this->_getColor('canvasColor', $options, array(255, 255, 255));
        if (is_array($color)) {
            $color = $this->colorarray2colorhex($color);
        }
        $pixel = new ImagickPixel($color);
        try {
            $this->imagick->rotateImage($pixel, $angle);

        } catch (ImagickException $e) {
            return $this->raiseError('Cannot create a new imagick image for the rotation: '.$e->getMessage(),
                IMAGE_TRANSFORM_ERROR_FAILED);
        }
        $info = $this->imagick->getImageGeometry();
        $this->new_x = $info['width'];
        $this->new_y = $info['height'];
        return true;

    } // End rotate

    /**
     * Adds text to the image
     *
     * @param   array   $params Array contains options:
     * <ul>
     *  <li>'text' (string) The string to draw</li>
     *  <li>'x'    (integer) Horizontal position</li>
     *  <li>'y'    (integer) Vertical Position</li>
     *  <li>'Color' (mixed) Font color</li>
     *  <li>'font' (string) Font to be used</li>
     *  <li>'size' (integer) Size of the fonts in pixel</li>
     * </ul>
     *
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function addText($params)
    {
        $this->oldImage = clone $this->imagick;
        $params = array_merge($this->_get_default_text_params(), $params);

        if (is_array($params['color'])) {
            $params['color'] = $this->colorarray2colorhex($params['color']);
        } else {
            $params['color'] = strtolower($params['color']);
        }

        static $cmds = array(
            'setFillColor' => 'color',
            'setFontSize'  => 'size',
            'setFontFace'  => 'font'
        );
        $this->imagick->beginDraw();

        foreach ($cmds as $cmd => $v) {
            if (!$this->imagick->$cmd($params[$v])) {
                return $this->raiseError("Problem with adding Text::{$v} = {$params[$v]}",
                    IMAGE_TRANSFORM_ERROR_FAILED);
            }
        }
        if (!$this->imagick->drawAnnotation($params['x'], $params['y'], $params['text'])) {
            return $this->raiseError('Problem with adding Text',
                IMAGE_TRANSFORM_ERROR_FAILED);
        }

        return true;

    } // End addText


    /**
     * Saves the image to a file
     *
     * @param $filename string the name of the file to write to
     *
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function save($filename, $type = '', $quality = null)
    {
        $options = (is_array($quality)) ? $quality : array();
        if (is_numeric($quality)) {
            $options['quality'] = $quality;
        }
        $quality = $this->_getOption('quality', $options, 75);
        
        // PIMCORE_MODIFICATION
        $this->imagick->setCompressionQuality($quality);
        $this->imagick->setImageCompressionQuality($quality);

        if ($type && strcasecmp($type, $this->type)) {
            try {
                $this->imagick->setImageFormat($type);

            } catch (ImagickException $e) {
                return $this->raiseError('Could not save image to file (conversion failed).',
                IMAGE_TRANSFORM_ERROR_FAILED);
            }
        }

        try {
            $this->imagick->writeImage($filename);

        } catch (ImagickException $e) {
            return $this->raiseError('Could not save image to file: '.$e->getMessage(),
                IMAGE_TRANSFORM_ERROR_IO);
        }

        if (!$this->keep_settings_on_save) {
            $this->free();
        }

        return true;

    } // End save

    /**
     * Displays image without saving and lose changes
     *
     * This method adds the Content-type HTTP header
     *
     * @param string type (JPG,PNG...);
     * @param int quality 75
     *
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function display($type = '', $quality = null)
    {
        $options = (is_array($quality)) ? $quality : array();
        if (is_numeric($quality)) {
            $options['quality'] = $quality;
        }
        $quality = $this->_getOption('quality', $options, 75);
        $this->imagick->setImageCompression($quality);

        if ($type && strcasecmp($type, $this->type)) {
            try {
                $this->imagick->setImageFormat($type);

            } catch (ImagickException $e) {
                return $this->raiseError('Could not save image to file (conversion failed).',
                IMAGE_TRANSFORM_ERROR_FAILED);
            }
        }
        try {
            $image = $this->imagick->getImageBlob();

        } catch (ImagickException $e) {
            return $this->raiseError('Could not display image.',
                IMAGE_TRANSFORM_ERROR_IO);
        }
        header('Content-type: ' . $this->getMimeType($type));
        echo $image;
        $this->free();
        return true;
    }

    /**
     * Adjusts the image gamma
     *
     * @param float $outputgamma
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function gamma($outputgamma = 1.0) {
        if ($outputgamma != 1.0) {
            $this->imagick->setImageGamma($outputgamma);
        }
        return true;
    }

    /**
     * Crops the image
     *
     * @param integer $width Cropped image width
     * @param integer $height Cropped image height
     * @param integer $x X-coordinate to crop at
     * @param integer $y Y-coordinate to crop at
     *
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function crop($width, $height, $x = 0, $y = 0)
    {
        // Sanity check
        if (!$this->intersects($width, $height, $x, $y)) {
            return PEAR::raiseError('Nothing to crop', IMAGE_TRANSFORM_ERROR_OUTOFBOUND);
        }
        try {
            $this->imagick->cropImage($width, $height, $x, $y);

        } catch (ImagickException $e) {
            return $this->raiseError('Could not crop image',
                IMAGE_TRANSFORM_ERROR_FAILED);
        }

        // I think that setting img_x/y is wrong, but scaleByLength() & friends
        // mess up the aspect after a crop otherwise.
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
        $this->imagick->setImageType(Imagick::IMGTYPE_GRAYSCALE);
        /*$this->imagick->setImageColorSpace(Imagick::COLORSPACE_GRAY);
        $this->imagick->setImageDepth(8);
        $this->imagick->separateImageChannel(Imagick::CHANNEL_GRAY);
        $this->imagick->setImageChannelDepth(Imagick::CHANNEL_GRAY, 8);*/
        return true;
    }

    /**
     * Horizontal mirroring
     *
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function mirror()
    {
        try {
            $this->imagick->flopImage();
        } catch (ImagickException $e) {
            return $this->raiseError('Could not mirror the image.',
                IMAGE_TRANSFORM_ERROR_FAILED);
        }
        return true;
    }

    /**
     * Vertical mirroring
     *
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function flip()
    {
        try {
            $this->imagick->flipImage();

        } catch (ImagickException $e) {
            return $this->raiseError('Could not flip the image.',
                IMAGE_TRANSFORM_ERROR_FAILED);
        }
        return true;
    }

    /**
     * Destroy image handle
     *
     * @access public
     */
    function free()
    {
        if (isset($this->imagick)) {
            $this->imagick->destroy();
            $this->imagick = null;
        }
    }

    /**
     * RaiseError Method - shows imagick Raw errors.
     *
     * @param string $message message = prefixed message..
     * @param int    $code error code
     * @return PEAR error object
     * @access protected
     */
    function raiseError($message, $code = 0)
    {
        return PEAR::raiseError($message, $code);
    }
}