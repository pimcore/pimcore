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
 * Renamed from Zend_Matrixcode to Pimcore_Image_Matrixcode for compatibility reasons
 * @copyright  Copyright (c) 2009-2011 Peter Minne <peter@inthepocket.mobi>
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */


/**
 * Pimcore_Image_Matrixcode_Renderer_Image
 *
 * Renamed from Zend_Matrixcode to Pimcore_Image_Matrixcode for compatibility reasons
 * @copyright  Copyright (c) 2009-2011 Peter Minne <peter@inthepocket.mobi>
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Pimcore_Image_Matrixcode_Renderer_Image extends Pimcore_Image_Matrixcode_Renderer_Abstract
{
	/**
     * List of authorized output format
     * @var array
     */
    protected $_allowedImageType = array(
        'png',
        'jpeg',
        'gif',
    );

    /**
     * Image format
     * @var string
     */
    protected $_imageType = 'png';

    /**
     * In case you want to put a limit to the resulting image size
     * @var unknown_type
     */
    protected $_size_limit = null;
    
    
	/**
     * Set the image type to produce (png, jpeg, gif)
     *
     * @param string $value
     * @return Pimcore_Image_Matrixcode_Renderer_Abstract
     * @throw Pimcore_Image_Matrixcode_Renderer_Exception
     */
    public function setImageType($value)
    {
        if ($value == 'jpg') {
            $value = 'jpeg';
        }

        if (!in_array($value, $this->_allowedImageType)) {
            throw new Pimcore_Image_Matrixcode_Renderer_Exception(sprintf(
                'Invalid type "%s" provided to setImageType()',
                $value
            ));
        }

        $this->_imageType = $value;
        return $this;
    }

    /**
     * Retrieve the image type to produce
     *
     * @return string
     */
    public function getImageType()
    {
        return $this->_imageType;
    }
    
    /**
     * Set a limit to the size
     * @param int $size
     */
    public function setSizeLimit($size) 
    {
    	$this->_size_limit = $size;
    	return $this;
    }
    
    /**
     * Retrieve the size limit
     * @return int
     */
    public function getSizeLimit()
    {
    	return $this->_size_limit;
    }
	
	
	/**
	 * Retrieve the scale of the code
	 * @return int
	 * @throws Pimcore_Image_Matrixcode_Renderer_Exception
	 */
	public function getScale() {
		$module_size = $this->_matrixcode->getModuleSize();
		if($module_size[0] != $module_size[1]) {
            throw new Pimcore_Image_Matrixcode_Renderer_Exception(
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
		
    	// Create empty canvas
    	$canvas = ImageCreate($matrix_dim_with_padding_x, $matrix_dim_with_padding_y);
    	
    	// Set colors/transparency
    	$fore_color = $this->_matrixcode->getForeColor();
    	$back_color = $this->_matrixcode->getBackgroundColor();
   		
    	$symbolcolor = ImageColorAllocate($canvas, ($fore_color & 0xFF0000) >> 16, ($fore_color & 0x00FF00) >> 8, ($fore_color & 0x0000FF));
    	if(!empty($back_color)) {
			$backgroundcolor = ImageColorAllocate($canvas, ($back_color & 0xFF0000) >> 16, ($back_color & 0x00FF00) >> 8, ($back_color & 0x0000FF));
			imagefill($canvas,0,0,$backgroundcolor);
    	}else{
    		$transparent_bg = ImageColorAllocateAlpha($canvas,255,255,255,127);
			imagefill($canvas,0,0,$transparent_bg);
    	}
    	
    	// Convert the matrix into pixels
    	$matrix = $this->_matrixcode->getMatrix();
		for($i=0; $i<$matrix_dimension; $i++) {
		    for($j=0; $j<$matrix_dimension; $j++) {
		    	if( $matrix[$i][$j] ) {
		    		$x = $i + $padding[3];
		    		$y = $j + $padding[2];
		    		ImageSetPixel($canvas,$x,$y,$symbolcolor);
		        }
		    }
		}
		
		// Scaling
		$output_size_width = $matrix_dim_with_padding_x * $this->getScale();
		$output_size_height = $matrix_dim_with_padding_y * $this->getScale();
		
		if (is_numeric($this->_size_limit) && ($output_size_width > $this->_size_limit || $output_size_height > $this->_size_limit)) {
            throw new Pimcore_Image_Matrixcode_Renderer_Exception(
                'Image result too large'
            );
		}
		
		$output_image = ImageCreate($output_size_width, $output_size_height);
		
		ImageCopyResized($output_image, $canvas, 0,0,0,0, $output_size_width, $output_size_height, $matrix_dim_with_padding_x, $matrix_dim_with_padding_y);
		
		@imagedestroy($canvas);
		
		if($this->_send_result) {
			$this->_sendOutput($output_image);
		}else{
			return $output_image;
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
		
		header("Content-Type: image/" . $this->_imageType);
        $functionName = 'image' . $this->_imageType;
	    call_user_func($functionName, $output);
	    @imagedestroy($output);
	    
	    exit();
	}
	
}