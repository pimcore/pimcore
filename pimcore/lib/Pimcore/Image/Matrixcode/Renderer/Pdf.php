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
 * Pimcore_Image_Matrixcode_Renderer_Pdf
 *
 * Renamed from Zend_Matrixcode to Pimcore_Image_Matrixcode for compatibility reasons
 * @copyright  Copyright (c) 2009-2011 Peter Minne <peter@inthepocket.mobi>
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Pimcore_Image_Matrixcode_Renderer_Pdf extends Pimcore_Image_Matrixcode_Renderer_Abstract
{
	
	/**
	 * Footnote printed at the bottom of the pdf page
	 * @var string
	 */
	protected $_footnote = '';
	
	
	/**
	 * Setter function
	 * @param string $note
	 */
	public function setFootnote($note)
	{
		$this->_footnote = $note;
	}
	
	/**
	 * Getter function
	 * @return string
	 */
	public function getFootnote()
	{
		return $this->_footnote;
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
		
    	// Scaling
    	$scale = $this->getScale();
		$output_size_width = $matrix_dim_with_padding_x * $scale;
		$output_size_height = $matrix_dim_with_padding_y * $scale;

		// Set colors/transparency
    	$fore_color = $this->_matrixcode->getForeColor();
    	$back_color = $this->_matrixcode->getBackgroundColor();
		
    	
    	
    	$pdf = new Zend_Pdf();
    	$pdf->pages[] = ($page = $pdf->newPage('A4'));
    	
    	// Add credits
    	if(!empty($this->_footnote)) {
	    	$font = Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA);
			$page->setFont($font, 10)
	      		 ->setFillColor(Zend_Pdf_Color_Html::color('#000000'))
	      		 ->drawText($this->_footnote, 20, 20);
    	}
      		 	 
    	$page_width = $page->getWidth();
		$page_height = $page->getHeight();
		// Move center of coordination system (by default the lower left corner)
		$page->translate(floor($page_width - $output_size_width) / 2, ($page_height - $output_size_height) / 2);
		
		if(!empty($back_color)) {
			$back_color = new Zend_Pdf_Color_HTML('#' . $this->_decimalToHTMLColor($back_color));
			$page->setFillColor($back_color);
			$page->drawRectangle(0,0,$output_size_width,$output_size_height,Zend_Pdf_Page::SHAPE_DRAW_FILL);
		}
		
		$page->setFillColor(new Zend_Pdf_Color_HTML('#' . $this->_decimalToHTMLColor($fore_color)));
		
		// Convert the matrix into pixels
    	$matrix = $this->_matrixcode->getMatrix();
		for($i=0; $i<$matrix_dimension; $i++) {
		    for($j=0; $j<$matrix_dimension; $j++) {
		    	if( $matrix[$i][$j] ) {
		    		$x = ($i + $padding[3]) * $scale;
		    		$y = ($matrix_dimension - 1 - $j + $padding[2]) * $scale;
		    		$page->drawRectangle($x, $y, $x + $scale, $y + $scale, Zend_Pdf_Page::SHAPE_DRAW_FILL);
		        }
		    }
		}
		
		
		if($this->_send_result) {
			$this->_sendOutput($pdf->render());
		}else{
			return $pdf;
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
		
		header("Content-Type: application/pdf");
        echo $output;
	    
	    exit();
	}
	
}