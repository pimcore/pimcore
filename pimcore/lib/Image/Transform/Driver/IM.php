<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * ImageMagick binaries implementation for Image_Transform package
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
 * @author     Peter Bowyer <peter@mapledesign.co.uk>
 * @author     Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright  2002-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: IM.php,v 1.25 2007/04/19 16:36:09 dufuz Exp $
 * @link       http://pear.php.net/package/Image_Transform
 */

require_once 'Image/Transform.php';
require_once 'System.php';

/**
 * ImageMagick binaries implementation for Image_Transform package
 *
 * @category   Image
 * @package    Image_Transform
 * @subpackage Image_Transform_Driver_IM
 * @author     Peter Bowyer <peter@mapledesign.co.uk>
 * @author     Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright  2002-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Image_Transform
 * @link http://www.imagemagick.org/
 **/
class Image_Transform_Driver_IM extends Image_Transform
{
    /**
     * associative array commands to be executed
     * @var array
     * @access private
     */
    var $command;

    /**
     * Class constructor
     */
    function Image_Transform_Driver_IM()
    {
        $this->__construct();
    } // End Image_IM


    /**
     * Class constructor
     */
    function __construct()
    {
        $this->_init();
        if (!defined('IMAGE_TRANSFORM_IM_PATH')) {
            $path = dirname(System::which('convert'))
                    . DIRECTORY_SEPARATOR;
            define('IMAGE_TRANSFORM_IM_PATH', $path);
        }
        if (System::which(IMAGE_TRANSFORM_IM_PATH . 'convert' . ((OS_WINDOWS) ? '.exe' : ''))) {
            include 'Image/Transform/Driver/Imagick/ImageTypes.php';
        } else {
            $this->isError(PEAR::raiseError('Couldn\'t find "convert" binary',
                IMAGE_TRANSFORM_ERROR_UNSUPPORTED));
        }
    } // End Image_IM

    /**
     * Initialize the state of the object
     **/
    function _init()
    {
        $this->command = array();
    }

    /**
     * Load an image.
     *
     * This method doesn't support remote files.
     *
     * @param string filename
     *
     * @return mixed TRUE or a PEAR error object on error
     * @see PEAR::isError()
     */
    function load($image)
    {
        $this->_init();
        if (!file_exists($image)) {
            return PEAR::raiseError('The image file ' . $image
                . ' doesn\'t exist', IMAGE_TRANSFORM_ERROR_IO);
        }
        $this->image = $image;
        $result = $this->_get_image_details($image);
        if (PEAR::isError($result)) {
            return $result;
        }
        return true;

    } // End load

    /**
     * Image_Transform_Driver_IM::_get_image_details()
     *
     * @param string $image the path and name of the image file
     * @return none
     */
    function _get_image_details($image)
    {
        $retval = Image_Transform::_get_image_details($image);
        if (PEAR::isError($retval)) {
            unset($retval);

            if (!System::which(IMAGE_TRANSFORM_IM_PATH . 'identify' . ((OS_WINDOWS) ? '.exe' : ''))) {
                $this->isError(PEAR::raiseError('Couldn\'t find "identify" binary', IMAGE_TRANSFORM_ERROR_UNSUPPORTED));
            }
            $cmd = $this->_prepare_cmd(IMAGE_TRANSFORM_IM_PATH,
                'identify',
                '-format %w:%h:%m ' . escapeshellarg($image));
            exec($cmd, $res, $exit);

            if ($exit == 0) {
                $data  = explode(':', $res[0]);
                $this->img_x = $data[0];
                $this->img_y = $data[1];
                $this->type  = strtolower($data[2]);
                $retval = true;
            } else {
                return PEAR::raiseError("Cannot fetch image or images details.", true);
            }

        }

        return $retval;
    }

    /**
     * Resize the image.
     *
     * @access private
     *
     * @param int   $new_x   New width
     * @param int   $new_y   New height
     * @param mixed $options Optional parameters
     *
     * @return true on success or PEAR Error object on error
     * @see PEAR::isError()
     */
    function _resize($new_x, $new_y, $options = null)
    {
        if (isset($this->command['resize'])) {
            return PEAR::raiseError('You cannot scale or resize an image more than once without calling save() or display()', true);
        }
        $this->command['resize'] = '-geometry '
            . ((int) $new_x) . 'x' . ((int) $new_y) . '!';

        $this->new_x = $new_x;
        $this->new_y = $new_y;

        return true;
    } // End resize

    /**
     * rotate
     *
     * @param   int     angle   rotation angle
     * @param   array   options no option allowed
     * @return mixed TRUE or a PEAR error object on error
     */
    function rotate($angle, $options = null)
    {
        $angle = $this->_rotation_angle($angle);
        if ($angle % 360) {
            $this->command['rotate'] = '-rotate ' . (float) $angle;
        }
        return true;

    } // End rotate

    /**
     * Crop image
     *
     * @author Ian Eure <ieure@websprockets.com>
     * @since 0.8
     *
     * @param int width Cropped image width
     * @param int height Cropped image height
     * @param int x X-coordinate to crop at
     * @param int y Y-coordinate to crop at
     *
     * @return mixed TRUE or a PEAR error object on error
     */
    function crop($width, $height, $x = 0, $y = 0) {
        // Do we want a safety check - i.e. if $width+$x > $this->img_x then we
        // raise a warning? [and obviously same for $height+$y]
        $this->command['crop'] = '-crop '
            . ((int) $width)  . 'x' . ((int) $height)
            . '+' . ((int) $x) . '+' . ((int) $y);

        // I think that setting img_x/y is wrong, but scaleByLength() & friends
        // mess up the aspect after a crop otherwise.
        $this->new_x = $this->img_x = $width - $x;
        $this->new_y = $this->img_y = $height - $y;

        return true;
    }

    /**
     * addText
     *
     * @param   array   options     Array contains options
     *                              array(
     *                                  'text'  The string to draw
     *                                  'x'     Horizontal position
     *                                  'y'     Vertical Position
     *                                  'Color' Font color
     *                                  'font'  Font to be used
     *                                  'size'  Size of the fonts in pixel
     *                                  'resize_first'  Tell if the image has to be resized
     *                                                  before drawing the text
     *                              )
     *
     * @return mixed TRUE or a PEAR error object on error
     * @see PEAR::isError()
     */
    function addText($params)
    {
         $params = array_merge($this->_get_default_text_params(), $params);
         extract($params);
         if (true === $resize_first) {
             // Set the key so that this will be the last item in the array
            $key = 'ztext';
         } else {
            $key = 'text';
         }
         $this->command[$key] = '-font ' . escapeshellarg($font)
            . ' -fill ' . escapeshellarg($color)
            . ' -draw \'text ' . escapeshellarg($x . ',' . $y)
            . ' "' . escapeshellarg($text) . '"\'';
         // Producing error: gs: not found gs: not found convert: Postscript delegate failed [No such file or directory].
        return true;

    } // End addText

    /**
     * Adjust the image gamma
     *
     * @access public
     * @param float $outputgamma
     * @return mixed TRUE or a PEAR error object on error
     */
    function gamma($outputgamma = 1.0) {
        if ($outputgamme != 1.0) {
            $this->command['gamma'] = '-gamma ' . (float) $outputgamma;
        }
        return true;
    }

    /**
     * Convert the image to greyscale
     *
     * @access public
     * @return mixed TRUE or a PEAR error object on error
     */
    function greyscale() {
        $this->command['type'] = '-type Grayscale';
        return true;
    }

    /**
     * Horizontal mirroring
     *
     * @access public
     * @return TRUE or PEAR Error object on error
     */
    function mirror() {
        // We can only apply "flop" once
        if (isset($this->command['flop'])) {
            unset($this->command['flop']);
        } else {
            $this->command['flop'] = '-flop';
        }
        return true;
    }

    /**
     * Vertical mirroring
     *
     * @access public
     * @return TRUE or PEAR Error object on error
     */
    function flip() {
        // We can only apply "flip" once
        if (isset($this->command['flip'])) {
            unset($this->command['flip']);
        } else {
            $this->command['flip'] = '-flip';
        }
        return true;
    }

    /**
     * Save the image file
     *
     * @access public
     *
     * @param $filename string  the name of the file to write to
     * @param $quality  quality image dpi, default=75
     * @param $type     string  (JPEG, PNG...)
     *
     * @return mixed TRUE or a PEAR error object on error
     */
    function save($filename, $type = '', $quality = null)
    {
        $type = strtoupper(($type == '') ? $this->type : $type);
        switch ($type) {
            case 'JPEG':
                $type = 'JPG';
                break;
        }
        $options = array();
        if (!is_null($quality)) {
            $options['quality'] = $quality;
        }
        $quality = $this->_getOption('quality', $options, 75);

        $cmd = $this->_prepare_cmd(
            IMAGE_TRANSFORM_IM_PATH,
            'convert',
            implode(' ', $this->command)
               . ' -quality ' . ((int) $quality) . ' '
               . escapeshellarg($this->image) . ' ' . $type . ':'
               . escapeshellarg($filename) . ' 2>&1');
        exec($cmd, $res, $exit);

        return ($exit == 0) ? true : PEAR::raiseError(implode('. ', $res),
            IMAGE_TRANSFORM_ERROR_IO);
    } // End save

    /**
     * Display image without saving and lose changes
     *
     * This method adds the Content-type HTTP header
     *
     * @access public
     *
     * @param string type (JPEG,PNG...);
     * @param int quality 75
     *
     * @return mixed TRUE or a PEAR error object on error
     */
    function display($type = '', $quality = null)
    {
        $type    = strtoupper(($type == '') ? $this->type : $type);
        switch ($type) {
            case 'JPEG':
                $type = 'JPG';
                break;
        }
        $options = array();
        if (!is_null($quality)) {
            $options['quality'] = $quality;
        }
        $quality = $this->_getOption('quality', $options, 75);

        $this->_send_display_headers($type);

        $cmd = $this->_prepare_cmd(
            IMAGE_TRANSFORM_IM_PATH,
            'convert',
            implode(' ', $this->command) . " -quality $quality "  .
                   $this->image . ' ' . $type . ":-");
        passthru($cmd);

        if (!$this->keep_settings_on_save) {
            $this->free();
        }
        return true;
    }

    /**
     * Destroy image handle
     *
     * @return void
     */
    function free()
    {
        $this->command = array();
        $this->image = '';
        $this->type = '';
    }

} // End class ImageIM