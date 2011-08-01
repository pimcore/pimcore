<?php
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Peter Bowyer <peter@mapledesign.co.uk>                      |
// |          Alan Knowles <alan@akbkhome.com>                            |
// +----------------------------------------------------------------------+

require_once 'Image/Transform/Driver/GD.php';

/**
 * This driver is for GD1 or the non-bundled version of GD2
 *
 * @package
 * @author NAWAL ASWAN
 * @copyright Copyright (c) 2003
 * @version $Id: GD1.php,v 1.5 2007/04/19 16:36:09 dufuz Exp $
 * @access public
 **/
Class Image_Transform_Driver_GD1 extends Image_Transform_Driver_GD
{
    /**
     * Check settings
     *
     * @return mixed true or  or a PEAR error object on error
     *
     * @see PEAR::isError()
     */
    function Image_Transform_Driver_GD1()
    {
        $this->__construct();
    } // End function Image

    /**
     * Check settings
     *
     * @return mixed true or  or a PEAR error object on error
     *
     * @see PEAR::isError()
     */
    function __construct()
    {
        parent::__construct();
    } // End function Image

   /**
    * Resize Action
    *
    * For GD 2.01+ the new copyresampled function is used
    * It uses a bicubic interpolation algorithm to get far
    * better result.
    *
    * @param $new_x int  new width
    * @param $new_y int  new height
    * @param mixed $options Optional parameters
    *
    * @return true on success or PEAR Error object on error
    * @see PEAR::isError()
    */
    function _resize($new_x, $new_y, $options = null)
    {
        if ($this->resized === true) {
            return PEAR::raiseError('You have already resized the image without saving it.  Your previous resizing will be overwritten', null, PEAR_ERROR_TRIGGER, E_USER_NOTICE);
        }
        $new_img =ImageCreate($new_x,$new_y);
        ImageCopyResized($new_img, $this->imageHandle, 0, 0, 0, 0, $new_x, $new_y, $this->img_x, $this->img_y);
        $this->old_image = $this->imageHandle;
        $this->imageHandle = $new_img;
        $this->resized = true;

        $this->new_x = $new_x;
        $this->new_y = $new_y;
        return true;
    }


    function rotate($angle, $options = null)
    {
        if ($options == null){
            $autoresize = true;
            $color_mask = array(255,255,0);
        } else {
            extract($options);
        }

        while ($angle <= -45) {
            $angle  += 360;
        }
        while ($angle > 270) {
            $angle  -= 360;
        }

        $t = deg2rad($angle);

        if (!is_array($color_mask)) {
            // Not already in numberical format, so we convert it.
            if ($color_mask{0} == '#'){
                $color_mask = $this->colorhex2colorarray($color_mask);
            } else {
                include_once 'Image/Transform/Driver/ColorsDefs.php';
                $color_mask = isset($colornames[$color_mask])?$colornames[$color_mask]:false;
            }
        }

        // Do not round it, too much lost of quality
        $cosT   = cos($t);
        $sinT   = sin($t);

        $img    =& $this->imageHandle;

        $width  = $max_x  = $this->img_x;
        $height = $max_y  = $this->img_y;
        $min_y  = 0;
        $min_x  = 0;

        $x1     = round($max_x/2,0);
        $y1     = round($max_y/2,0);

        if ( $autoresize ){
            $t      = abs($t);
            $a      = round($angle,0);
            switch((int)($angle)){
                case 0:
                        $width2     = $width;
                        $height2    = $height;
                    break;
                case 90:
                        $width2     = $height;
                        $height2    = $width;
                    break;
                case 180:
                        $width2     = $width;
                        $height2    = $height;
                    break;
                case 270:
                        $width2     = $height;
                        $height2    = $width;
                    break;
                default:
                    $width2     = (int)(abs(sin($t) * $height + cos($t) * $width));
                    $height2    = (int)(abs(cos($t) * $height+sin($t) * $width));
            }

            $width2     -= $width2%2;
            $height2    -= $height2%2;

            $d_width    = abs($width - $width2);
            $d_height   = abs($height - $height2);
            $x_offset   = $d_width/2;
            $y_offset   = $d_height/2;
            $min_x2     = -abs($x_offset);
            $min_y2     = -abs($y_offset);
            $max_x2     = $width2;
            $max_y2     = $height2;
        }

        $img2   = @imagecreateTrueColor($width2,$height2);

        if (!is_resource($img2)) {
            return PEAR::raiseError('Cannot create buffer for the rotataion.',
                                null, PEAR_ERROR_TRIGGER, E_USER_NOTICE);
        }

        $this->img_x = $width2;
        $this->img_y = $height2;


        imagepalettecopy($img2,$img);

        $mask   = imagecolorresolve($img2,$color_mask[0],$color_mask[1],$color_mask[2]);

        // use simple lines copy for axes angles
        switch ((int)($angle)) {
            case 0:
                imagefill($img2, 0, 0,$mask);
                for ($y = 0; $y < $max_y; $y++) {
                    for ($x = $min_x; $x < $max_x; $x++){
                        $c  = @imagecolorat ( $img, $x, $y);
                        imagesetpixel($img2,$x+$x_offset,$y+$y_offset,$c);
                    }
                }
                break;
            case 90:
                imagefill ($img2, 0, 0,$mask);
                for ($x = $min_x; $x < $max_x; $x++){
                    for ($y=$min_y; $y < $max_y; $y++) {
                        $c  = imagecolorat ( $img, $x, $y);
                        imagesetpixel($img2,$max_y-$y-1,$x,$c);
                    }
                }
                break;
            case 180:
                imagefill ($img2, 0, 0,$mask);
                for ($y=0; $y < $max_y; $y++) {
                    for ($x = $min_x; $x < $max_x; $x++){
                        $c  = @imagecolorat ( $img, $x, $y);
                        imagesetpixel($img2, $max_x2-$x-1, $max_y2-$y-1, $c);
                    }
                }
                break;
            case 270:
                imagefill ($img2, 0, 0,$mask);
                for ($y=0; $y < $max_y; $y++) {
                    for ($x = $max_x; $x >= $min_x; $x--){
                        $c  = @imagecolorat ( $img, $x, $y);
                        imagesetpixel($img2,$y,$max_x-$x-1,$c);
                    }
                }
                break;
            // simple reverse rotation algo
            default:
                $i=0;
                for ($y = $min_y2; $y < $max_y2; $y++){

                    // Algebra :)
                    $x2 = round((($min_x2-$x1) * $cosT) + (($y-$y1) * $sinT + $x1),0);
                    $y2 = round((($y-$y1) * $cosT - ($min_x2-$x1) * $sinT + $y1),0);

                    for ($x = $min_x2; $x < $max_x2; $x++){

                        // Check if we are out of original bounces, if we are
                        // use the default color mask
                        if ( $x2>=0 && $x2<$max_x && $y2>=0 && $y2<$max_y ){
                            $c  = imagecolorat ( $img, $x2, $y2);
                        } else {
                            $c  = $mask;
                        }
                        imagesetpixel($img2,$x+$x_offset,$y+$y_offset,$c);

                        // round verboten!
                        $x2  += $cosT;
                        $y2  -= $sinT;
                    }
                }
                break;
        }

        $this->imageHandle  =  $img2;
        return true;
    }
} // End class ImageGD