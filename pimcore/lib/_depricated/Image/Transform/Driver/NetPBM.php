<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * NetPBM implementation for Image_Transform package
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
 * @version    CVS: $Id: NetPBM.php,v 1.21 2007/04/19 16:36:09 dufuz Exp $
 * @link       http://pear.php.net/package/Image_Transform
 */

require_once 'Image/Transform.php';
require_once 'System.php';

/**
 * NetPBM implementation for Image_Transform package
 *
 * @category   Image
 * @package    Image_Transform
 * @subpackage Image_Transform_Driver_NetPBM
 * @author     Peter Bowyer <peter@mapledesign.co.uk>
 * @author     Philippe Jausions <Philippe.Jausions@11abacus.com>
 * @copyright  2002-2005 The PHP Group
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/Image_Transform
 * @link       http://netpbm.sourceforge.net/
 */
class Image_Transform_Driver_NetPBM extends Image_Transform
{
    /**
     * associative array commands to be executed
     * @var array
     */
    var $command = array();

    /**
     * Class Constructor
     */
    function Image_Transform_Driver_NetPBM()
    {
        $this->__construct();

    } // End function Image_NetPBM

    /**
     * Class Constructor
     */
    function __construct()
    {
        if (!defined('IMAGE_TRANSFORM_NETPBM_PATH')) {
            $path = dirname(System::which('pnmscale'))
                    . DIRECTORY_SEPARATOR;
            define('IMAGE_TRANSFORM_NETPBM_PATH', $path);
        }
        if (!System::which(IMAGE_TRANSFORM_NETPBM_PATH . 'pnmscale'
                             . ((OS_WINDOWS) ? '.exe' : ''))) {
            $this->isError(PEAR::raiseError('Couldn\'t find "pnmscale" binary',
                IMAGE_TRANSFORM_ERROR_UNSUPPORTED));
        }
    } // End function Image_NetPBM

    /**
     * Load image
     *
     * @param string filename
     * @return bool|PEAR_Error TRUE or a PEAR_Error object on error
     * @access public
     */
    function load($image)
    {
        $this->image = $image;
        $result = $this->_get_image_details($image);
        if (PEAR::isError($result)) {
            return $result;
        }
        return true;

    } // End load

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
        // there's no technical reason why resize can't be called multiple
        // times...it's just silly to do so
        $scaleMethod = $this->_getOption('scaleMethod', $options, 'smooth');
        switch ($scaleMethod) {
            case 'pixel':
                $scale_x = $new_x / $this->img_x;
                if ($scale_x == $new_y / $this->img_x
                    && $scale_x > 1
                    && floor($scale_x) == $scale_x) {
                    if (System::which(IMAGE_TRANSFORM_NETPBM_PATH .
                                           'pnmenlarge'
                                           . ((OS_WINDOWS) ? '.exe' : ''))) {
                        $this->command[] = $this->_prepare_cmd(
                            IMAGE_TRANSFORM_NETPBM_PATH,
                            'pnmenlarge',
                            $scale_x);
                    } else {
                        return PEAR::raiseError('Couldn\'t find "pnmenlarge" binary',
                            IMAGE_TRANSFORM_ERROR_UNSUPPORTED);
                    }
                } else {
                    $this->command[] = $this->_prepare_cmd(
                        IMAGE_TRANSFORM_NETPBM_PATH,
                        'pnmscale',
                        '-nomix -width ' . ((int) $new_x)
                            . ' -height ' . ((int) $new_y));
                }
                break;

            case 'smooth':
            default:
                $this->command[] = $this->_prepare_cmd(
                    IMAGE_TRANSFORM_NETPBM_PATH,
                    'pnmscale',
                    '-width ' . ((int) $new_x) . ' -height '
                        . ((int) $new_y));
                // Smooth things if scaling by a factor more than 3
                // (see pnmscale man page)
                if ($new_x / $this->img_x > 3
                    || $new_y / $this->img_y > 3) {
                    if (System::which(IMAGE_TRANSFORM_NETPBM_PATH .
                                           'pnmsmooth' . ((OS_WINDOWS) ? '.exe' : ''))) {
                        $this->command[] = $this->_prepare_cmd(
                            IMAGE_TRANSFORM_NETPBM_PATH,
                            'pnmsmooth');
                    } else {
                        return PEAR::raiseError('Couldn\'t find "pnmsmooth" binary',
                            IMAGE_TRANSFORM_ERROR_UNSUPPORTED);
                    }
                }
        } // End [SWITCH]

        $this->_set_new_x($new_x);
        $this->_set_new_y($new_y);
        return true;

    } // End resize

    /**
     * Rotates the image
     *
     * @param int $angle The angle to rotate the image through
     * @param array $options
     * @return bool|PEAR_Error TRUE on success, PEAR_Error object on error
     */
    function rotate($angle, $options = null)
    {
        if (!($angle == $this->_rotation_angle($angle))) {
            // No rotation needed
            return true;
        }

        // For pnmrotate, we want to limit rotations from -45 to +45 degrees
        // even if acceptable range is -90 to +90 (see pnmrotate man page)
        // Bring image to that range by using pamflip
        if ($angle > 45 && $angle < 315) {
            if (!System::which(IMAGE_TRANSFORM_NETPBM_PATH .
                                   'pamflip' . ((OS_WINDOWS) ? '.exe' : ''))) {
                return PEAR::raiseError('Couldn\'t find "pamflip" binary',
                    IMAGE_TRANSFORM_ERROR_UNSUPPORTED);
            }

            $quarters = floor(ceil($angle / 45) / 2);
            $this->command[] = $this->_prepare_cmd(
                IMAGE_TRANSFORM_NETPBM_PATH,
                'pamflip',
                '-rotate' . (360 - $quarters * 90));
            $angle -= $quarters * 90;
        }

        if ($angle != 0) {
            if ($angle > 45) {
                $angle -= 360;
            }

            if (!System::which(IMAGE_TRANSFORM_NETPBM_PATH .
                                   'pnmrotate' . ((OS_WINDOWS) ? '.exe' : ''))) {
                return PEAR::raiseError('Couldn\'t find "pnmrotate" binary',
                    IMAGE_TRANSFORM_ERROR_UNSUPPORTED);
            }

            $bgcolor = $this->_getColor('canvasColor', $options,
                                            array(255, 255, 255));
            $bgcolor = $this->colorarray2colorhex($bgcolor);

            $scaleMethod = $this->_getOption('scaleMethod', $options, 'smooth');
            if ($scaleMethod != 'pixel') {
                $this->command[] = $this->_prepare_cmd(
                    IMAGE_TRANSFORM_NETPBM_PATH,
                    'pnmrotate',
                    '-background=' . $bgcolor . ' -' . (float) $angle);
            } else {
                $this->command[] = $this->_prepare_cmd(
                    IMAGE_TRANSFORM_NETPBM_PATH,
                    'pnmrotate',
                    '-background=' . $bgcolor . ' -noantialias -' . (float) $angle);
            }
        }
        return true;
    } // End rotate

    /**
     * Crop an image
     *
     * @param int $width Cropped image width
     * @param int $height Cropped image height
     * @param int $x positive X-coordinate to crop at
     * @param int $y positive Y-coordinate to crop at
     *
     * @return mixed TRUE or a PEAR error object on error
     * @todo keep track of the new cropped size
     **/
    function crop($width, $height, $x = 0, $y = 0)
    {
        // Sanity check
        if (!$this->intersects($width, $height, $x, $y)) {
            return PEAR::raiseError('Nothing to crop', IMAGE_TRANSFORM_ERROR_OUTOFBOUND);
        }
        if ($x != 0 || $y != 0
            || $width != $this->img_x
            || $height != $this->img_y) {
            if (!System::which(IMAGE_TRANSFORM_NETPBM_PATH .
                                   'pnmcut' . ((OS_WINDOWS) ? '.exe' : ''))) {
                return PEAR::raiseError('Couldn\'t find "pnmcut" binary',
                    IMAGE_TRANSFORM_ERROR_UNSUPPORTED);
            }

            $this->command[] = $this->_prepare_cmd(
                IMAGE_TRANSFORM_NETPBM_PATH,
                'pnmcut',
                '-left ' . ((int) $x)
                    . ' -top ' . ((int) $y)
                    . ' -width ' . ((int) $width)
                    . ' -height ' . ((int) $height));
        }
        return true;
    } // End crop

    /**
     * Adjust the image gamma
     *
     * @param float $outputgamma
     *
     * @return mixed TRUE or a PEAR error object on error
     */
    function gamma($outputgamma = 1.0) {
        if ($outputgamme != 1.0) {
            if (!System::which(IMAGE_TRANSFORM_NETPBM_PATH .
                                   'pnmgamma' . ((OS_WINDOWS) ? '.exe' : ''))) {
                return PEAR::raiseError('Couldn\'t find "pnmgamma" binary',
                    IMAGE_TRANSFORM_ERROR_UNSUPPORTED);
            }
            $this->command[] = $this->_prepare_cmd(
                IMAGE_TRANSFORM_NETPBM_PATH,
                'pnmgamma',
                (float) $outputgamma);
        }
        return true;
    }

    /**
     * Vertical mirroring
     *
     * @see mirror()
     * @return TRUE or PEAR Error object on error
     **/
    function flip()
    {
        if (!System::which(IMAGE_TRANSFORM_NETPBM_PATH .
                               'pamflip' . ((OS_WINDOWS) ? '.exe' : ''))) {
            return PEAR::raiseError('Couldn\'t find "pamflip" binary',
                IMAGE_TRANSFORM_ERROR_UNSUPPORTED);
        }
        $this->command[] = $this->_prepare_cmd(
            IMAGE_TRANSFORM_NETPBM_PATH,
            'pamflip',
            '-topbottom');
        return true;
    }

    /**
     * Horizontal mirroring
     *
     * @see flip()
     * @return TRUE or PEAR Error object on error
     **/
    function mirror()
    {
        if (!System::which(IMAGE_TRANSFORM_NETPBM_PATH .
                               'pamflip' . ((OS_WINDOWS) ? '.exe' : ''))) {
            return PEAR::raiseError('Couldn\'t find "pamflip" binary',
                IMAGE_TRANSFORM_ERROR_UNSUPPORTED);
        }
        $this->command[] = $this->_prepare_cmd(
            IMAGE_TRANSFORM_NETPBM_PATH,
            'pamflip',
            '-leftright');
        return true;
    }

    /**
     * Converts an image into greyscale colors
     *
     * @access public
     * @return mixed TRUE or a PEAR error object on error
     **/
    function greyscale()
    {
        if (!System::which(IMAGE_TRANSFORM_NETPBM_PATH .
                               'ppmtopgm' . ((OS_WINDOWS) ? '.exe' : ''))) {
            return PEAR::raiseError('Couldn\'t find "ppmtopgm" binary',
                IMAGE_TRANSFORM_ERROR_UNSUPPORTED);
        }
        $this->command[] = $this->_prepare_cmd(
            IMAGE_TRANSFORM_NETPBM_PATH,
            'ppmtopgm');
        return true;
    }

    /**
     * adds text to an image
     *
     * @param   array   options     Array contains options
     *             array(
     *                  'text'          // The string to draw
     *                  'x'             // Horizontal position
     *                  'y'             // Vertical Position
     *                  'color'         // Font color
     *                  'font'          // Font to be used
     *                  'size'          // Size of the fonts in pixel
     *                  'resize_first'  // Tell if the image has to be resized
     *                                  // before drawing the text
     *                   )
     *
     * @return void
     */
    function addText($params)
    {
        if (!System::which(IMAGE_TRANSFORM_NETPBM_PATH .
                               'ppmlabel' . ((OS_WINDOWS) ? '.exe' : ''))) {
            return PEAR::raiseError('Couldn\'t find "ppmlabel" binary',
                IMAGE_TRANSFORM_ERROR_UNSUPPORTED);
        }

        // we ignore 'resize_first' since the more logical approach would be
        // for the user to just call $this->_resize() _first_ ;)
        extract(array_merge($this->_get_default_text_params(), $params));

        $options = array('colorFont' => $color);
        $color = $this->_getColor('colorFont', $options, array(0, 0, 0));
        $color = $this->colorarray2colorhex($color);

        $this->command[] = $this->_prepare_cmd(
            IMAGE_TRANSFORM_NETPBM_PATH,
            'ppmlabel',
            '-angle ' . ((int) $angle)
                . ' -colour ' . escapeshellarg($color)
                . ' -size ' . ((float) $size)
                . ' -x ' . ((int) $x)
                . ' -y ' . ((int) ($y + $size))
                . ' -text ' . escapeshellarg($text));

    } // End addText

    /**
     * Image_Transform_Driver_NetPBM::_postProcess()
     *
     * @param $type
     * @param $quality
     * @return string A chain of shell command
     * @link http://netpbm.sourceforge.net/doc/directory.html
     */
    function _postProcess($type, $quality)
    {
        array_unshift($this->command, $this->_prepare_cmd(
            IMAGE_TRANSFORM_NETPBM_PATH,
            strtolower($this->type) . 'topnm',
            escapeshellarg($this->image)));
        $arg = '';
        $type = strtolower($type);
        $program = '';
        switch ($type) {
            // ppmto* converters
            case 'gif':
                if (!System::which(IMAGE_TRANSFORM_NETPBM_PATH . 'ppmquant'
                                    . ((OS_WINDOWS) ? '.exe' : ''))) {
                    return PEAR::raiseError('Couldn\'t find "ppmquant" binary',
                        IMAGE_TRANSFORM_ERROR_UNSUPPORTED);
                }
                $this->command[] = $this->_prepare_cmd(
                    IMAGE_TRANSFORM_NETPBM_PATH,
                    'ppmquant',
                    256);
            case 'acad':
            case 'bmp':
            case 'eyuv':
            case 'ilbm':
            case 'leaf':
            case 'lj':
            case 'mitsu':
            case 'mpeg':
            case 'neo':
            case 'pcx':
            case 'pi1':
            case 'pict':
            case 'pj':
            case 'pjxl':
            case 'puzz':
            case 'sixel':
            case 'tga':
            case 'uil':
            case 'xpm':
            case 'yuv':
                $program = 'ppmto' . $type;
                break;

            // Windows icon
            case 'winicon':
            case 'ico':
                $type = 'winicon';
                $program = 'ppmto' . $type;
                break;

            // pbmto* converters
            case 'ascii':
            case 'text':
            case 'txt':
                $type = 'ascii';
            case 'atk':
            case 'bbubg':
            case 'epsi':
            case 'epson':
            case 'escp2':
            case 'icon':    // Sun icon
            case 'gem':
            case 'go':
            case 'lj':
            case 'ln03':
            case 'lps':
            case 'macp':
            case 'mda':
            case 'mgr':
            case 'pi3':
            case 'pk':
            case 'plot':
            case 'ptx':
            case 'wbp':
            case 'xbm':
            case 'x10bm':
            case 'ybm':
            case 'zinc':
            case '10x':
                $program = 'pbmto' . $type;
                break;

            // pamto* converters
            case 'jpc':
                $type = 'jpeg2k';
            case 'html':
            case 'pfm':
            case 'tga':
                $program = 'pamto' . $type;
                break;

            // pnmto* converters
            case 'jpc':
                $type = 'jpeg2k';
                break;
            case 'wfa':
                $type = 'fiasco';
                break;
            case 'jpg':
                $type = 'jpeg';
            case 'jpeg':
                $arg = '--quality=' . $quality;
            case 'jbig':
            case 'fits':
            case 'palm':
            case 'pclxl':
            case 'png':
            case 'ps':
            case 'rast':
            case 'rle':
            case 'sgi':
            case 'sir':
            case 'tiff':
            case 'xwd':
                $program = 'pnmto' . $type;
                break;

        } // switch

        if ($program == '') {
            $program = 'pnmto' . $type;
        }

        if (!System::which(IMAGE_TRANSFORM_NETPBM_PATH . $program
                            . ((OS_WINDOWS) ? '.exe' : ''))) {
            return PEAR::raiseError("Couldn't find \"$program\" binary",
                IMAGE_TRANSFORM_ERROR_UNSUPPORTED);
        }
        $this->command[] = $this->_prepare_cmd(
            IMAGE_TRANSFORM_NETPBM_PATH,
            $program);
        return implode('|', $this->command);
    }

    /**
     * Save the image file
     *
     * @param $filename string the name of the file to write to
     * @param string $type (jpeg,png...);
     * @param int $quality 75
     * @return TRUE or PEAR Error object on error
     */
    function save($filename, $type = null, $quality = 75)
    {
        $type    = (is_null($type)) ? $this->type : $type;
        $options = array();
        if (!is_null($quality)) {
            $options['quality'] = $quality;
        }
        $quality = $this->_getOption('quality', $options, $quality);

        $nullDevice = (OS_WINDOWS) ? 'nul' : '/dev/null';

        $cmd = $this->_postProcess($type, $quality) . '> "' . $filename . '"';
        exec($cmd . '2> ' . $nullDevice, $res, $exit);
        if (!$this->keep_settings_on_save) {
            $this->free();
        }

        return ($exit == 0) ? true : PEAR::raiseError(implode('. ', $res),
            IMAGE_TRANSFORM_ERROR_IO);
    } // End save

    /**
     * Display image without saving and lose changes
     *
     * @param string $type (jpeg,png...);
     * @param int $quality 75
     * @return TRUE or PEAR Error object on error
     */
    function display($type = null, $quality = null)
    {
        $type    = (is_null($type)) ? $this->type : $type;
        $options = array();
        if (!is_null($quality)) {
            $options['quality'] = $quality;
        }
        $quality = $this->_getOption('quality', $options, 75);

        header('Content-type: ' . $this->getMimeType($type));
        $cmd = $this->_postProcess($type, $quality);
        passthru($cmd . ' 2>&1');
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
    }


} // End class ImageIM