<?php

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to version 1.0 of the Zend Framework
 * license, that is bundled with this package in the file LICENSE.txt, and
 * is available through the world-wide-web at the following URL:
 * http://framework.zend.com/license/new-bsd. If you did not receive
 * a copy of the Zend Framework license and are unable to obtain it
 * through the world-wide-web, please send a note to license@zend.com
 * so we can mail you a copy immediately.
 *
 * Renamed from \Zend_Matrixcode to Pimcore_Image_Matrixcode for compatibility reasons
 * @copyright  Copyright (c) 2009-2011 Peter Minne <peter@inthepocket.mobi>
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */


/**
 * Pimcore_Image_Matrixcode_Renderer_Svg
 *
 * Renamed from \Zend_Matrixcode to Pimcore_Image_Matrixcode for compatibility reasons
 * @copyright  Copyright (c) 2009-2011 Peter Minne <peter@inthepocket.mobi>
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Pimcore\Image\Matrixcode\Renderer;

use Pimcore\Image\Matrixcode\Qrcode\Exception;

class Svg extends AbstractRenderer
{
	
	/**
	 * Retrieve the scale of the code
	 * @return int
	 * @throws Pimcore_Image_Matrixcode_Renderer_Exception
	 */
	public function getScale() {
		$module_size = $this->_matrixcode->getModuleSize();
		if($module_size[0] != $module_size[1]) {
            throw new Exception(
                'So far only square modules are supported. The current module size settings of '.$module_size[0].'x'.$module_size[1].' indicate a different rectangular shape.'
            );
		}
		return $module_size[0];
	}
	
	
	/**
	 * @see Pimcore_Image_Matrixcode_Renderer_Abstract::_checkParams()
	 */
	protected function _checkParams() {}

	
	/**
	 * @see Pimcore_Image_Matrixcode_Renderer_Abstract::_renderMatrixcode()
	 */
	protected function _renderMatrixcode()
	{
		$padding = $this->_matrixcode->getPadding();
		
		$this->_matrixcode->draw();
		$matrix_dimension = count($this->_matrixcode->getMatrix());
    	
		$matrix_dim_with_padding_x = $matrix_dimension + $padding[1] + $padding[3];
		$matrix_dim_with_padding_y = $matrix_dimension + $padding[0] + $padding[2];
		
    	// Scaling
    	$scale = $this->getScale();
		$output_size_width = $matrix_dim_with_padding_x * $scale;
		$output_size_height = $matrix_dim_with_padding_y * $scale;

		// Set colors/transparency
    	$fore_color = $this->_matrixcode->getForeColor();
    	$back_color = $this->_matrixcode->getBackgroundColor();
		
    	$output = 
    	'<?xml version="1.0" encoding="utf-8"?>'."\n".
		'<svg version="1.1" baseProfile="full"  width="'.$output_size_width.'" height="'.$output_size_height.'" viewBox="0 0 '.$output_size_width.' '.$output_size_height.'"
		 xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:ev="http://www.w3.org/2001/xml-events">'."\n".
  		'<desc></desc>'."\n";
  		
    	if(!empty($back_color)) {
			$backgroundcolor = $this->_decimalToHTMLColor($back_color);
  			$output .= '<rect width="'.$output_size_width.'" height="'.$output_size_height.'" fill="#'.$backgroundcolor.'" cx="0" cy="0" />'."\n";
  		}
  		
  		$output .= 
  		'<defs>'."\n".
    	'<rect id="p" width="'.$scale.'" height="'.$scale.'" />'."\n".
  		'</defs>'."\n".
  		'<g fill="#'.$this->_decimalToHTMLColor($fore_color).'">'."\n";
  		
  		
		// Convert the matrix into pixels
    	$matrix = $this->_matrixcode->getMatrix();
		for($i=0; $i<$matrix_dimension; $i++) {
		    for($j=0; $j<$matrix_dimension; $j++) {
		    	if( $matrix[$i][$j] ) {
		    		$x = ($i + $padding[3]) * $scale;
		    		$y = ($j + $padding[2]) * $scale;
		    		$output .= '<use x="'.$x.'" y="'.$y.'" xlink:href="#p" />'."\n";
		        }
		    }
		}
		
		$output .= 
		'</g>'."\n".
    	'</svg>';
		
		
		if($this->_send_result) {
			$this->_sendOutput($output);
		}else{
			return $output;
		}
		
		return;
	}
	
	
	
	protected function _sendOutput($output)
	{
		if(is_array($this->_send_result)) {
			foreach($this->_send_result as $header) {
				header($header);
			}
		}
		
		header("Content-Type: image/svg+xml");
        echo $output;
	    
	    exit();
	}
	
}
