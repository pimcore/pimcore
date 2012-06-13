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
 * Thanks to http://www.swetake.com/qr/qr1_en.html
 *
 * Renamed from Zend_Matrixcode to Pimcore_Image_Matrixcode for compatibility reasons
 * @copyright  Copyright (c) 2009-2011 Peter Minne <peter@inthepocket.mobi>
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */


/**
 * Pimcore_Image_Matrixcode_Qrcode
 *
 * Renamed from Zend_Matrixcode to Pimcore_Image_Matrixcode for compatibility reasons
 * @copyright  Copyright (c) 2009-2011 Peter Minne <peter@inthepocket.mobi>
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Pimcore_Image_Matrixcode_Qrcode extends Pimcore_Image_Matrixcode_Abstract
{
	
	/**
	 * Base path of the qrcode resources (data + image files)
	 * @var string
	 */
	protected $_resourcesBasePath = null;
	
	/**
	 * Folder of the QRcode frame data files
	 * @var string
	 */
	protected $_resourceDataPath = 'data';
	
	/**
	 * ECC level (Reed-Solomon EC)
	 * (L, M, Q or H)
	 * @var string
	 */
	protected $_eccLevel = Pimcore_Image_Matrixcode_Qrcode_Qrspecs::QR_ECC_M;
	
	/**
	 * Version
	 * 0: auto select
	 * 1-40: set size (version 1 is a 21x21 matrix, and matrix grows with 4 per version, so version 40 is 177x177)
	 * Auto select will determine the minimum version needed to hold all the encoded information
	 * @var int
	 */
	protected $_version = 0;
	
	/**
	 * Parity
	 * (0 - 255)
	 * @var int
	 */
	protected $_parity;
	
	/**
	 * Mask information (array with mask number and mask pattern)
	 * @var array
	 */
	protected $_maskData = array();
	
	/**
	 * 2D pattern matrix
	 * @var array
	 */
	protected $_patternMatrix = array();
	
	

	/**
     * Constructor
     * @param array|Zend_Config $options 
     * @return void
     */
    public function __construct ($options = null)
    {
    	// initialize padding with default
    	$this->setPadding(Pimcore_Image_Matrixcode_Qrcode_Qrspecs::DEFAULT_PADDING);
    	
        if (is_array($options)) {
            $this->setOptions($options);
        } elseif ($options instanceof Zend_Config) {
            $this->setConfig($options);
        }
        
        $this->_resourcesBasePath = realpath(dirname(__FILE__) . '/./Qrcode');
    }
	
	
    /**
     * Set ECC level
     * @param string $level
     */
    public function setEccLevel ($level)
    {
    	if($level != '') {
			if(array_key_exists($level, Pimcore_Image_Matrixcode_Qrcode_Qrspecs::$eccLevels)) {
				// level specified in letter format ('L', 'M', ...)
    			$this->_eccLevel = Pimcore_Image_Matrixcode_Qrcode_Qrspecs::$eccLevels[strtoupper($level)];
			}else if(in_array($level, Pimcore_Image_Matrixcode_Qrcode_Qrspecs::$eccLevels)) {
				// level specified in numeric format (0,1,...)
				$this->_eccLevel = $level;
			}
    	}else{
            throw new Pimcore_Image_Matrixcode_Qrcode_Exception(
                'Invalid value for the ECC level'
            );
    	}
    }
    
    /**
     * Retrieve ECC level
     * @return string
     */
    public function getEccLevel ()
    {
    	return $this->_eccLevel;
    }
   
    
    /**
     * Set version
     * @param int $version
     * @throws Pimcore_Image_Matrixcode_Qrcode_Exception
     */
    public function setVersion ($version)
    {
    	if(is_int($version) && $version <= Pimcore_Image_Matrixcode_Qrcode_Qrspecs::QR_VERSION_MAX) {
    		$this->_version = $version;
    	}else{
            throw new Pimcore_Image_Matrixcode_Qrcode_Exception(
                'The version of a QR code should be between 0 and '.Pimcore_Image_Matrixcode_Qrcode_Qrspecs::QR_VERSION_MAX
            );
    	}
    }
    
    /**
     * Retrieve version
     * @return int
     */
    public function getVersion ()
    {
    	return $this->_version;
    }
    
    
    /**
     * Set parity
     * @param int $parity
     */
    public function setParity ($parity) 
    {
    	if(is_int($parity) && $parity <= 255) {
    		$this->_parity = $parity;
    	}else{
            throw new Pimcore_Image_Matrixcode_Qrcode_Exception(
                'The parity of a QR code should be between 0 and 255'
            );
    	}
    }
    
    /**
     * Get parity
     * @return int
     */
    public function getParity ()
    {
    	return $this->_parity;
    }
    
    
    /**
     * Set path of the resources (data and pattern files)
     * @param string $path
     */
    public function setResourcesBasePath ($path)
    {
    	if(is_string($path)) {
    		$this->_resourcesBasePath = $path;
    	}
    }
    
    /**
     * Retrieve resources base path
     * @return $string
     */
    public function getResourcesBasePath ()
    {
    	return $this->_resourcesBasePath;
    }
    
    
    /**
     * Function that identifies the QR mode
     * @return string
     */
    protected function _identifyMode ()
    {
    	if ( preg_match('/[^0-9]/', $this->getText()) ) { // if contains non-numerical characters
		    if( preg_match('/[^0-9A-Z \$\*\%\+\-\.\/\:]/',$this->getText()) ) {
		     	return Pimcore_Image_Matrixcode_Qrcode_Qrspecs::QR_MODE_8;
			} else {
		    	return Pimcore_Image_Matrixcode_Qrcode_Qrspecs::QR_MODE_AN;
			}
		} else {
			return Pimcore_Image_Matrixcode_Qrcode_Qrspecs::QR_MODE_NUM;
		}
    }
    
    
    /**
     * Calculate the matrix dimension for the version
     * @param int $version
     */
    protected function _getMatrixDimension($version = null) 
    {
    	if(is_null($version)) {
    		$version = $this->_version;
    	}
    	return 17 + ($version << 2);
    }

    
    /**
     * Retrieve the maximum code words for the code version
     * RS blocks? (http://www.swetake.com/qr/qr_table2.html)
     *
     * @return int
     */
    protected function _getMaximumCodeWords()
    {
    	return Pimcore_Image_Matrixcode_Qrcode_Qrspecs::getMatrixCapacityWords($this->_version);
    }
    
    /**
     * Mask selection (http://www.swetake.com/qr/qr5_en.html)
     * If the density of one color is too high or a pattern similar to "finder patterns" 
	 * appears, the decoder application will have trouble decoding.
	 * To prevent this, we select a mask from 8 different patterns (000 ... 111).
     * 
     * Of the 8 masks, select the one that (probably) gives the best result (lowest weight).
     * For each mask, 4 aspects are measured and evaluated:
     *   CHARACTERISTICS										CONDITION							WEIGHT
     * - concatenation of same color in a line or a column		count of modules=(5+i)				3+i
	 * - module block of same color								block size 2*2						3
	 * - 1:1:3:1:1(dark:bright:dark:bright:dark)pattern in a line or a column						40
	 * - percentage of dark modules								from 50�(5+k)% to 50�(5+(k+1))%		10*k
	 *
     * @param array $matrix_content
     * @return array Array countaining mask number and mask pattern
     */
    protected function _selectBestMaskPattern($matrix_content) 
    {
    	$matrix_dimension = $this->_getMatrixDimension();
    	$min_demerit_score = 0;
		$hor_master = "";
		$ver_master = "";
		
		for($k=0; $k<$matrix_dimension; $k++) {
			for($l=0; $l<$matrix_dimension; $l++) {
	            $hor_master = $hor_master.chr($matrix_content[$l][$k]);
	            $ver_master = $ver_master.chr($matrix_content[$k][$l]);
	        }
	    }
		
		$all_matrix = pow($matrix_dimension,2);
		
		for($i=0; $i<8; $i++) {
			$demerit_n1 = 0;
		    $ptn_temp = array();
		    $bit = 1 << $i;
		    $bit_r = (~$bit) & 255;
		    $bit_mask = str_repeat(chr($bit),$all_matrix);
		    $hor = $hor_master & $bit_mask;
		    $ver = $ver_master & $bit_mask;
		
		    $ver_shift1 = $ver.str_repeat(chr(170),$matrix_dimension);
		    $ver_shift2 = str_repeat(chr(170),$matrix_dimension).$ver;
		    $ver_shift1_0 = $ver.str_repeat(chr(0),$matrix_dimension);
		    $ver_shift2_0 = str_repeat(chr(0),$matrix_dimension).$ver;
		    $ver_or = chunk_split(~($ver_shift1 | $ver_shift2),$matrix_dimension,chr(170));
		    $ver_and = chunk_split(~($ver_shift1_0 & $ver_shift2_0),$matrix_dimension,chr(170));
		
		    $hor = chunk_split(~$hor,$matrix_dimension,chr(170));
		    $ver = chunk_split(~$ver,$matrix_dimension,chr(170));
		    $hor = $hor.chr(170).$ver;
		
		    $n1_search = "/".str_repeat(chr(255),5)."+|".str_repeat(chr($bit_r),5)."+/";
		    $n3_search = chr($bit_r).chr(255).chr($bit_r).chr($bit_r).chr($bit_r).chr(255).chr($bit_r);
		
		   	$demerit_n3 = substr_count($hor,$n3_search)*40;
		   	$total_bits = Pimcore_Image_Matrixcode_Qrcode_Qrspecs::getMatrixCapacityRemainder($this->_version) + ($this->_getMaximumCodeWords() << 3);
		   	$demerit_n4 = floor(abs(( (100* (substr_count($ver,chr($bit_r))/($total_bits)) )-50)/5))*10;
		
		   	$n2_search1 = "/".chr($bit_r).chr($bit_r)."+/";
		   	$n2_search2 = "/".chr(255).chr(255)."+/";
		   	$demerit_n2 = 0;
		   	preg_match_all($n2_search1,$ver_and,$ptn_temp);
		   	foreach($ptn_temp[0] as $str_temp){
		       	$demerit_n2 += (strlen($str_temp)-1);
		   	}
		   	$ptn_temp = array();
		   	preg_match_all($n2_search2,$ver_or,$ptn_temp);
		   	foreach($ptn_temp[0] as $str_temp){
		       	$demerit_n2 += (strlen($str_temp)-1);
		   	}
		   	$demerit_n2 *= 3;
		  
		  	$ptn_temp = array();
		
		  	preg_match_all($n1_search,$hor,$ptn_temp);
		   	foreach($ptn_temp[0] as $str_temp){
		       	$demerit_n1 += (strlen($str_temp)-2);
		   	}
		
		   	$demerit_score = $demerit_n1 + $demerit_n2 + $demerit_n3 + $demerit_n4;
			// mask with lower score is better
		   	if ($demerit_score <= $min_demerit_score || $i==0){
		        $mask_number = $i;
		        $min_demerit_score = $demerit_score;
		   }
		
		}
		
		return array('number' => $mask_number,
					 'pattern' => 1 << $mask_number);
		
    }
    
    
    
    /**
     * Create the QRcode matrix
     *
     * @param string $original_data
     */
    protected function _prepareDataMatrix ($original_data = null)
    {
    	
    	$data_counter = 0;
    	
		
		// Determine encode mode
		$mode = $this->_identifyMode();
		
		
		//
		// 1. Set first 4 bits = mode (f.i. 0010 for alfa-numeric data)
		//
		$data_bits[$data_counter] = 4;
		$data_value[$data_counter] = $mode;
		$data_counter++;
		
		//
		// 2. Set next 8/9/10 bits = character count indicator (f.i. alfa-numeric data is 6 characters long => 0 0000 0110)
		// 3. Encoding data in binary representation
		//
		$data_value[$data_counter] = $data_length = strlen($this->getText());
		
		
		switch ($mode) {
			
			case Pimcore_Image_Matrixcode_Qrcode_Qrspecs::QR_MODE_8:
				
				$data_bits[$data_counter] = 8;   	// #version 1-9
				
				$codeword_num_plus = array(0,  // not used
										   0,0,0,0,0,0,0,0,0,
										   8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,8,
										   8,8,8,8,8,8,8,8,8,8,8,8,8,8);
		
		        $codeword_num_counter_value = $data_counter++;
				
				/*
				Next: encode source data to binary representation
				In 8bit byte mode, each value is directly encoded in 8bit long binary representation.
				*/
		        for($i=0; $i<$data_length; $i++) {
		            $data_value[$data_counter] = ord(substr($this->getText(), $i, 1));
		            $data_bits[$data_counter] = 8;
		            $data_counter++;
		        }
				break;
				
				
			case Pimcore_Image_Matrixcode_Qrcode_Qrspecs::QR_MODE_AN:
				
				$data_bits[$data_counter] = 9;   	// #version 1-9
				
				$codeword_num_plus = array(0,  // not used
										   0,0,0,0,0,0,0,0,0,
										   2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,
										   4,4,4,4,4,4,4,4,4,4,4,4,4,4);
		        $codeword_num_counter_value = $data_counter++;
				/*
				Next: encode source data to binary representation.	
				In alphanumeric mode, each character is converted into a value according the hash matrix below.
				Next we consider delimited data by 2 characters. First value is multiplied by 45, and second value is added to it. 
				Result value is encoded in 11 bit long binary representation.
				When length of delimited data is 1, 6 bit long is used.
				*/
		        $alphanum_char_hash = array("0"=>0,"1"=>1,"2"=>2,"3"=>3,"4"=>4,"5"=>5,"6"=>6,"7"=>7,"8"=>8,"9"=>9,
		        							"A"=>10,"B"=>11,"C"=>12,"D"=>13,"E"=>14,"F"=>15,"G"=>16,"H"=>17,"I"=>18,
		        							"J"=>19,"K"=>20,"L"=>21,"M"=>22,"N"=>23,"O"=>24,"P"=>25,"Q"=>26,"R"=>27,
		        							"S"=>28,"T"=>29,"U"=>30,"V"=>31,"W"=>32,"X"=>33,"Y"=>34,"Z"=>35,
		        							" "=>36,"$"=>37,"%"=>38,"*"=>39,"+"=>40,"-"=>41,"."=>42,"/"=>43,":"=>44);
		        for($i=0; $i<$data_length; $i++) {
		            if (($i %2)==0) {
		                $data_value[$data_counter] = $alphanum_char_hash[substr($this->getText(), $i, 1)];
		                $data_bits[$data_counter] = 6;
		            } else {
		                $data_value[$data_counter] = $data_value[$data_counter]*45 + $alphanum_char_hash[substr($this->getText(), $i, 1)];
		                $data_bits[$data_counter] = 11;
		                $data_counter++;
		            }
		        }
				break;
				
				
			case Pimcore_Image_Matrixcode_Qrcode_Qrspecs::QR_MODE_NUM:
				
				$data_bits[$data_counter] = 10;  	// #version 1-9
				
				$codeword_num_plus = array(0, // not used
										   0,0,0,0,0,0,0,0,0,
										   2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,
										   4,4,4,4,4,4,4,4,4,4,4,4,4,4);
			    $codeword_num_counter_value = $data_counter++;
				/*
				Next: encode source data to binary representation.
				In numeric mode, data is delimited by 3 digits.
				For example, "123456" is delimited "123" and "456", and first data is "123", second data is "456".
				Each data block is encoded in 10bit long binary representation.
				
				When length of delimited data is 1 or 2, 4bit long or 7bit long are used in each case.
				For example,"9876" is delimited "987" in 10 bit long and "6" in 4 bit long.
				Its result is "1111011011 0110"
				*/
			    for($i=0; $i<$data_length; $i++) {
			        if (($i % 3) == 0){
			            $data_value[$data_counter] = substr($this->getText(), $i, 1);
			            $data_bits[$data_counter] = 4;
			        } else {
			             $data_value[$data_counter] = $data_value[$data_counter]*10+substr($this->getText(), $i, 1);
				         if (($i % 3) == 1){
				             $data_bits[$data_counter]=7;
				         } else {
				             $data_bits[$data_counter]=10;
				             $data_counter++;
				         }
			        }
			    }
				break;
				
			default: break;
		}
		
		if (@$data_bits[$data_counter] > 0) {
		    $data_counter++;
		}
		
			
		
		// Total number of bits set so far
		$total_data_bits = array_sum($data_bits);
		
		// Retrieve the maximum bitcapacity based on the ECC level and the version (better ECC = lower capacity, higher version = higher capacity)
		if (!$this->_version) {	// auto-select version
	    	$this->_version = 1;
			for($k=0; $k<40; $k++) {
			    if ( Pimcore_Image_Matrixcode_Qrcode_Qrspecs::$maxDataBits[$this->_eccLevel][$k] >= $total_data_bits+$codeword_num_plus[$this->_version] ) {
			        break;
			    }
			    $this->_version++;
			}
		}
		$max_data_bits = Pimcore_Image_Matrixcode_Qrcode_Qrspecs::$maxDataBits[$this->_eccLevel][($this->_version-1)];
		
		$total_data_bits += $codeword_num_plus[$this->_version];
		$data_bits[$codeword_num_counter_value] += $codeword_num_plus[$this->_version];
		
		
		//
		// 4. Add a 0000 terminator when the amount of used bits is less than the maximum
		// When used bits == maximum bits => no terminator needed
		//
		if ($total_data_bits <= $max_data_bits - 4){
		    $data_value[$data_counter] = 0;
		    $data_bits[$data_counter] = 4;
		} else {
		    if ($total_data_bits < $max_data_bits){
				$data_value[$data_counter] = 0;
		        $data_bits[$data_counter] = $max_data_bits - $total_data_bits;
		    } else if ($total_data_bits > $max_data_bits) {
            	throw new Pimcore_Image_Matrixcode_Qrcode_Exception(
                	'QR code overflow error. Version cannot hold all the encoded data.'
            	);
		    }
		}
		
	
		$max_codewords = $this->_getMaximumCodeWords();
		$matrix_dimension = $this->_getMatrixDimension();
		$max_data_codewords = ($max_data_bits >> 3);
		
		
		//
		// 5. Divide data in 8 bit chuncks
		//
		$codewords = array();
		$full_bit_string = '';
		for($i=0; $i<$data_counter; $i++) {
			$full_bit_string .= str_pad(decbin($data_value[$i]), $data_bits[$i], '0', STR_PAD_LEFT);
		}
		// padd the data with 0 until the length is a multiple of 8
		$full_bit_string = str_pad($full_bit_string, ceil(strlen($full_bit_string) / 8) * 8, '0', STR_PAD_RIGHT);
		
		for($i=0; $i<(strlen($full_bit_string)/8); $i++) {
			$chunk = substr($full_bit_string,$i*8,8);
			$codewords[] = bindec($chunk);
		}
		$codewords_counter = count($codewords);
		
		
		// If count of code words is less than the capacity, 
		// padd alternately with "11101100" (236) and "00010001" (17) until full capacity
		$terminators = array(236, 17);
		$k = 0;
	    while ($codewords_counter < $max_data_codewords){
            $codewords[] = $terminators[$k%2];
	        $codewords_counter++;
	        $k++;
		}
		
		
		// Read QR version data file
		$byte_num = Pimcore_Image_Matrixcode_Qrcode_Qrspecs::getMatrixCapacityRemainder($this->_version) + ($max_codewords << 3);
		$filename = $this->_resourcesBasePath . DIRECTORY_SEPARATOR . $this->_resourceDataPath . DIRECTORY_SEPARATOR . "qrv".$this->_version."_".$this->_eccLevel.".dat";
		if($fp = fopen ($filename, "rb")) {
		    $matx	= fread($fp,$byte_num);
		    $maty	= fread($fp,$byte_num);
		    $masks	= fread($fp,$byte_num);
		    $fi_x	= fread($fp,15);
		    $fi_y	= fread($fp,15);
		    $rs_ecc_codewords = ord(fread($fp,1));
		    $rso = fread($fp,128);
			fclose($fp);
		}else{
            throw new Pimcore_Image_Matrixcode_Qrcode_Exception(
                $filename. 'could not be opened'
            );
		}
		
		
		$matrix_x_array = unpack("C*", $matx);
		$matrix_y_array = unpack("C*", $maty);
		$mask_array = unpack("C*", $masks);
		$rs_block_order = unpack("C*", $rso);
		
		$format_information_x2 = unpack("C*",$fi_x);
		$format_information_y2 = unpack("C*",$fi_y);
		
		$format_information_x1 = array(0,1,2,3,4,5,7,8,8,8,8,8,8,8,8);
		$format_information_y1 = array(8,8,8,8,8,8,8,8,7,5,4,3,2,1,0);
		
		
		// Read RSC data file
		$filename = $this->_resourcesBasePath . DIRECTORY_SEPARATOR . $this->_resourceDataPath . DIRECTORY_SEPARATOR . "rsc".$rs_ecc_codewords.".dat";
		$fp = fopen ($filename, "rb");
		for($i=0; $i<256; $i++) {
		    $rs_cal_table_array[$i]=fread ($fp,$rs_ecc_codewords);
		}
		fclose ($fp);
		
		
		
		// RS-ECC preparation
		$i = $j = 0;
		$rs_block_number = 0;
		$rs_temp[0] = "";
		
		while($i < $max_data_codewords){
		
		    $rs_temp[$rs_block_number] .= chr($codewords[$i]);
		    $j++;
		
		    if ($j >= $rs_block_order[$rs_block_number+1] - $rs_ecc_codewords){
		        $j = 0;
		        $rs_block_number++;
		        $rs_temp[$rs_block_number] = "";
		    }
		    $i++;
		}
		
		//
		// 6. RS-ECC main section
		// Reed-Solomon error correction
		$rs_block_number = 0;
		$rs_block_order_num = count($rs_block_order);
		
		while ($rs_block_number < $rs_block_order_num){
		
		    $rs_codewords = $rs_block_order[$rs_block_number+1];
		    $rs_data_codewords = $rs_codewords - $rs_ecc_codewords;
		
		    $rstemp = $rs_temp[$rs_block_number].str_repeat(chr(0),$rs_ecc_codewords);
		    $padding_data = str_repeat(chr(0), $rs_data_codewords);
		
		    $j = $rs_data_codewords;
		    while($j > 0){
		        $first = ord(substr($rstemp,0,1));
		
		        if ($first){
		            $left_chr = substr($rstemp,1);
		            $cal = $rs_cal_table_array[$first].$padding_data;
		            $rstemp = $left_chr ^ $cal;
		        } else {
		            $rstemp = substr($rstemp,1);
		        }
		        $j--;
		    }
		
		    $codewords = array_merge($codewords,unpack("C*",$rstemp));
		    $rs_block_number++;
		}
		
		
		//
		// 7. Create the matrix
		//
		
		// Initialize matrix
		for($i=0; $i<$matrix_dimension; $i++) {
			for($j=0; $j<$matrix_dimension; $j++) {
				$matrix_content[$i][$j] = 0;
			}
		}

		// Attach actual data
		for($i=0; $i<$max_codewords; $i++) {
		    $cw = $codewords[$i];
		    for($j=8; $j>0; $j--) {
		        $bit_index = ($i << 3) + $j;
		        $matrix_content[ $matrix_x_array[$bit_index] ][ $matrix_y_array[$bit_index] ] = ((255*($cw & 1)) ^ $mask_array[$bit_index] ); 
		        $cw = $cw >> 1;
		    }
		}
		
		$matrix_remain = Pimcore_Image_Matrixcode_Qrcode_Qrspecs::getMatrixCapacityRemainder($this->_version);
		while ($matrix_remain){
		    $remain_bit_temp = $matrix_remain + ($max_codewords << 3);
		    $matrix_content[ $matrix_x_array[$remain_bit_temp] ][ $matrix_y_array[$remain_bit_temp] ]  =  ( 255 ^ $mask_array[$remain_bit_temp] );
		    $matrix_remain--;
		}
		
		$this->_maskData = $this->_selectBestMaskPattern($matrix_content);

		//
		// 8. Add information data
		//
		$format_information = Pimcore_Image_Matrixcode_Qrcode_Qrspecs::$formatInformation;
		$symbol_format_info = str_pad(decbin(hexdec($format_information[$this->_eccLevel][$this->_maskData['number']])),15,'0',STR_PAD_LEFT);
		for($i=0; $i<15; $i++) {
		    $content = substr( $symbol_format_info, $i, 1);
		    $matrix_content[$format_information_x1[$i]][$format_information_y1[$i]] = $content * 255;
		    $matrix_content[$format_information_x2[$i+1]][$format_information_y2[$i+1]] = $content * 255;
		}
		
		return $matrix_content;
    }
    
    
    /**
     * Creates a matrix with all patterns
     * @return array
     */
    protected function _preparePatternMatrix()
    {
    	$this->_setFinderPatterns();
    	$this->_setTimingPatterns();
    	$this->_setAlignmentPatterns();
    	$this->_setVersionPatterns();

		return $this->_patternMatrix;
    }
    
    
    /**
     * Set the 3 finder patterns in the corners
     */
    protected function _setFinderPatterns ()
    {
    	$matrix_dimension = $this->_getMatrixDimension();
    	
    	$patternRelativeOffsets = array(
    								array('x' => 0, 'y' => 0),
    								array('x' => $matrix_dimension - 7, 'y' => 0),
    								array('x' => 0, 'y' => $matrix_dimension - 7)
    					  		  );
    	
    	foreach($patternRelativeOffsets as $pattern) {
    		// 7x7 border
    		for($i=0; $i<7; $i++) {
    			$this->_patternMatrix[$pattern['x'] + $i][$pattern['y']] = 1;
    			$this->_patternMatrix[$pattern['x'] + 6][$pattern['y'] + $i] = 1;
    			$this->_patternMatrix[$pattern['x'] + $i][$pattern['y'] + 6] = 1;
    			$this->_patternMatrix[$pattern['x']][$pattern['y'] + $i] = 1;
    		}
    		// filled 3x3 square
    		for($j=0; $j<9; $j++)  {
    			$x = $pattern['x'] + 2 + ($j % 3);
    			$y = $pattern['y'] + 2 + floor($j / 3);
    			$this->_patternMatrix[$x][$y] = 1;
    		}
    	}
    }
    
    /**
     * Set the timing patterns (dotted lines between the finder patterns)
     */
    protected function _setTimingPatterns ()
    {
    	$matrix_dimension = $this->_getMatrixDimension();
    	
    	for($i=0; $i<$matrix_dimension; $i+=2) {
    		$this->_patternMatrix[$i][6] = 1;
    		$this->_patternMatrix[6][$i] = 1;
    	}
    	// the lonely pixel
    	$this->_patternMatrix[8][$matrix_dimension-8] = 1;
    }
    
    /**
     * Set the alignment patterns (5x5 squares with a dot in the center)
     */
    protected function _setAlignmentPatterns ()
    {
    	if($this->_version < 2)
    		return;
    	
    	$matrix_dimension = $this->_getMatrixDimension();
    	$aPattern = Pimcore_Image_Matrixcode_Qrcode_Qrspecs::getAlignmentPattern($this->_version);
    	
    	$d = $aPattern[1] - $aPattern[0];
		if($d < 0) {
			$w = 2;
		} else {
			$w = floor(($matrix_dimension - $aPattern[0]) / $d) + 2;
		}

		if($w * $w - 3 == 1) {
			$x = $y = $aPattern[0];
			$this->_setSingleAlignmentPattern($x, $y);
			return;
		}
	
		$cx = $aPattern[0];
		for($x=1; $x < $w-1; $x++) {
			$this->_setSingleAlignmentPattern(6, $cx);
			$this->_setSingleAlignmentPattern($cx, 6);
			$cx += $d;
		}

		$cy = $aPattern[0];
		for($y=0; $y < $w-1; $y++) {
			$cx = $aPattern[0];
			for($x=0; $x < $w-1; $x++) {
				$this->_setSingleAlignmentPattern($cx, $cy);
				$cx += $d;
			}
			$cy += $d;
		}
    }
    
    /**
     * Set a single alignment pattern
     * @param int $x
     * @param int $y
     */
    protected function _setSingleAlignmentPattern ($x, $y)
    {
    	$this->_patternMatrix[$x][$y] = 1;
    	for($i=0; $i<5; $i++) {
    		$this->_patternMatrix[$x - 2 + $i][$y - 2] = 1;
    		$this->_patternMatrix[$x + 2][$y - 2 + $i] = 1;
    		$this->_patternMatrix[$x -2 + $i][$y + 2] = 1;
			$this->_patternMatrix[$x - 2][$y - 2 + $i] = 1;
		}
    }
    
    /**
     * Set version info pattern (only for version >= 7), 
     * above the lower finder pattern and on the left of the top right finder pattern
     */
    protected function _setVersionPatterns ()
    {
    	if($this->_version < 7) {
    		return;
    	}
    	
    	$matrix_dimension = $this->_getMatrixDimension();
    	$vPattern = Pimcore_Image_Matrixcode_Qrcode_Qrspecs::getVersionPattern($this->_version);
    	for($x=0; $x<6; $x++) {
    		for($y=$matrix_dimension-11; $y<=$matrix_dimension-9; $y++) {
    			if($vPattern & 1 == 1) {
    				$this->_patternMatrix[$x][$y] = 1;
    				$this->_patternMatrix[$y][$x] = 1;
    			}
    			$vPattern = $vPattern >> 1;
    		}
    	}
    }
    
    
    
    /**
     * @see Pimcore_Image_Matrixcode_Abstract::_checkParams()
     */
    protected function _checkParams() {}
    
    
    /**
     * @see Pimcore_Image_Matrixcode_Abstract::_prepareMatrixcode()
     */
    protected function _prepareMatrixcode()
    {
    	$data_matrix = $this->_prepareDataMatrix();
    	$pattern_matrix = $this->_preparePatternMatrix();
    	$mask_pattern = $this->_maskData['pattern'];
		
    	// Merge the matrixes
    	$matrix_table = array();
    	$matrix_dimension = $this->_getMatrixDimension();
    	for($i=0; $i < $matrix_dimension; $i++) {
		    for($j=0; $j < $matrix_dimension; $j++) {
		    	$matrix_table[$i][$j] = 0;
		    	if( (isset($pattern_matrix[$i][$j]) && $pattern_matrix[$i][$j]) || ($data_matrix[$i][$j] & $mask_pattern) ) {
		    		$matrix_table[$i][$j] = 1;
		        }
		    }
		}
		return $matrix_table;
    }
    
}