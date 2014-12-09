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
 * Renamed from \Zend_Matrixcode to Pimcore_Image_Matrixcode for compatibility reasons
 * @copyright  Copyright (c) 2009-2011 Peter Minne <peter@inthepocket.mobi>
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */


/**
 * Pimcore_Image_Matrixcode_Qrcode_Qrspecs
 *
 * Renamed from \Zend_Matrixcode to Pimcore_Image_Matrixcode for compatibility reasons
 * @copyright  Copyright (c) 2009-2011 Peter Minne <peter@inthepocket.mobi>
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Pimcore\Image\Matrixcode\Qrcode;

class Qrspecs
{
	
	/*
	 * Error Correcting Code levels (Reed Salomon)
	 * L...H -> 0...3 would probably make more sense but the ecc data files (qrv_x.dat) 
	 * are named according the constants below
	 */
	const QR_ECC_L = 1;	// 7% of codewords can be restored
	const QR_ECC_M = 0; // 15% of codewords can be restored
	const QR_ECC_Q = 3; // 25% of codewords can be restored
	const QR_ECC_H = 2; // 30% of codewords can be restored
	
	/**
	 * ECC levels
	 * @var array
	 */
	public static $eccLevels = array('L' => self::QR_ECC_L ,
									 'M' => self::QR_ECC_M ,
									 'Q' => self::QR_ECC_Q ,
									 'H' => self::QR_ECC_H );
	
	/**
	 * Maximum version (size) of QR-symbol
	 */
	const QR_VERSION_MAX = 40;
	
	/*
	 * QR mode
	 */
	const QR_MODE_NUM 	= 1;	// numeric mode (0-9),  characters are encoded to 10bit length (max 7089 characters)
	const QR_MODE_AN 	= 2;	// alpha-numeric mode (0-9A-Z $%*+-./:), 2 characters are encoded to 11 bit length (max 4296 characters)
	const QR_MODE_8 	= 4;	// 8-bit data mode (max 2953 characters)
	
	/*
	 * Default padding around symbol
	 */
	const DEFAULT_PADDING = 4;
	
	
	/**
     * Table with the capacity of symbols
     * See Table 1 (pp.13) and Table 12-16 (pp.30-36), JIS X0510:2004.
     * Extracted from libqrencode 3.0.3 (license LGPL 2.1) by Kentaro Fukuchi <fukuchi@megaui.net>
     * 
     * width: width of matrix code (modules)
     *
     * @var array
     */
    private static $_matrixCapacity = array(
        1  => array( 'width' =>  21, 'words' =>    26, 'remainder' =>  0, 'ec' => array(   7,   10,   13,   17) ), // version 1
        2  => array( 'width' =>  25, 'words' =>    44, 'remainder' =>  7, 'ec' => array(  10,   16,   22,   28) ),
        3  => array( 'width' =>  29, 'words' =>    70, 'remainder' =>  7, 'ec' => array(  15,   26,   36,   44) ),
        4  => array( 'width' =>  33, 'words' =>   100, 'remainder' =>  7, 'ec' => array(  20,   36,   52,   64) ),
        5  => array( 'width' =>  37, 'words' =>   134, 'remainder' =>  7, 'ec' => array(  26,   48,   72,   88) ), // 5
        6  => array( 'width' =>  41, 'words' =>   172, 'remainder' =>  7, 'ec' => array(  36,   64,   96,  112) ),
        7  => array( 'width' =>  45, 'words' =>   196, 'remainder' =>  0, 'ec' => array(  40,   72,  108,  130) ),
        8  => array( 'width' =>  49, 'words' =>   242, 'remainder' =>  0, 'ec' => array(  48,   88,  132,  156) ),
        9  => array( 'width' =>  53, 'words' =>   292, 'remainder' =>  0, 'ec' => array(  60,  110,  160,  192) ),
        10 => array( 'width' =>  57, 'words' =>   346, 'remainder' =>  0, 'ec' => array(  72,  130,  192,  224) ), //10
        11 => array( 'width' =>  61, 'words' =>   404, 'remainder' =>  0, 'ec' => array(  80,  150,  224,  264) ),
        12 => array( 'width' =>  65, 'words' =>   466, 'remainder' =>  0, 'ec' => array(  96,  176,  260,  308) ),
        13 => array( 'width' =>  69, 'words' =>   532, 'remainder' =>  0, 'ec' => array( 104,  198,  288,  352) ),
        14 => array( 'width' =>  73, 'words' =>   581, 'remainder' =>  3, 'ec' => array( 120,  216,  320,  384) ),
        15 => array( 'width' =>  77, 'words' =>   655, 'remainder' =>  3, 'ec' => array( 132,  240,  360,  432) ), //15
        16 => array( 'width' =>  81, 'words' =>   733, 'remainder' =>  3, 'ec' => array( 144,  280,  408,  480) ),
        17 => array( 'width' =>  85, 'words' =>   815, 'remainder' =>  3, 'ec' => array( 168,  308,  448,  532) ),
        18 => array( 'width' =>  89, 'words' =>   901, 'remainder' =>  3, 'ec' => array( 180,  338,  504,  588) ),
        19 => array( 'width' =>  93, 'words' =>   991, 'remainder' =>  3, 'ec' => array( 196,  364,  546,  650) ),
        20 => array( 'width' =>  97, 'words' =>  1085, 'remainder' =>  3, 'ec' => array( 224,  416,  600,  700) ), //20
        21 => array( 'width' => 101, 'words' =>  1156, 'remainder' =>  4, 'ec' => array( 224,  442,  644,  750) ),
        22 => array( 'width' => 105, 'words' =>  1258, 'remainder' =>  4, 'ec' => array( 252,  476,  690,  816) ),
        23 => array( 'width' => 109, 'words' =>  1364, 'remainder' =>  4, 'ec' => array( 270,  504,  750,  900) ),
        24 => array( 'width' => 113, 'words' =>  1474, 'remainder' =>  4, 'ec' => array( 300,  560,  810,  960) ),
        25 => array( 'width' => 117, 'words' =>  1588, 'remainder' =>  4, 'ec' => array( 312,  588,  870, 1050) ), //25
        26 => array( 'width' => 121, 'words' =>  1706, 'remainder' =>  4, 'ec' => array( 336,  644,  952, 1110) ),
        27 => array( 'width' => 125, 'words' =>  1828, 'remainder' =>  4, 'ec' => array( 360,  700, 1020, 1200) ),
        28 => array( 'width' => 129, 'words' =>  1921, 'remainder' =>  3, 'ec' => array( 390,  728, 1050, 1260) ),
        29 => array( 'width' => 133, 'words' =>  2051, 'remainder' =>  3, 'ec' => array( 420,  784, 1140, 1350) ),
        30 => array( 'width' => 137, 'words' =>  2185, 'remainder' =>  3, 'ec' => array( 450,  812, 1200, 1440) ), //30
        31 => array( 'width' => 141, 'words' =>  2323, 'remainder' =>  3, 'ec' => array( 480,  868, 1290, 1530) ),
        32 => array( 'width' => 145, 'words' =>  2465, 'remainder' =>  3, 'ec' => array( 510,  924, 1350, 1620) ),
        33 => array( 'width' => 149, 'words' =>  2611, 'remainder' =>  3, 'ec' => array( 540,  980, 1440, 1710) ),
        34 => array( 'width' => 153, 'words' =>  2761, 'remainder' =>  3, 'ec' => array( 570, 1036, 1530, 1800) ),
        35 => array( 'width' => 157, 'words' =>  2876, 'remainder' =>  0, 'ec' => array( 570, 1064, 1590, 1890) ), //35
        36 => array( 'width' => 161, 'words' =>  3034, 'remainder' =>  0, 'ec' => array( 600, 1120, 1680, 1980) ),
        37 => array( 'width' => 165, 'words' =>  3196, 'remainder' =>  0, 'ec' => array( 630, 1204, 1770, 2100) ),
        38 => array( 'width' => 169, 'words' =>  3362, 'remainder' =>  0, 'ec' => array( 660, 1260, 1860, 2220) ),
        39 => array( 'width' => 173, 'words' =>  3532, 'remainder' =>  0, 'ec' => array( 720, 1316, 1950, 2310) ),
        40 => array( 'width' => 177, 'words' =>  3706, 'remainder' =>  0, 'ec' => array( 750, 1372, 2040, 2430) ) //40
    );
    
    /**
     * Positions of alignment patterns.
     * This array includes only the second and the third position of the alignment
     * patterns. Rest of them can be calculated from the distance between them.
     *
     * See Table 1 in Appendix E (pp.71) of JIS X0510:2004.
     * Extracted from libqrencode 3.0.3 (license LGPL 2.1) by Kentaro Fukuchi <fukuchi@megaui.net>
     * 
     * @var array
     */
    private static $_alignmentPatterns = array(
        array(  0,  0 ),	// not used
        array(  0,  0 ), array( 18,  0 ), array( 22,  0 ), array( 26,  0 ), array( 30,  0 ), // 1- 5
        array( 34,  0 ), array( 22, 38 ), array( 24, 42 ), array( 26, 46 ), array( 28, 50 ), // 6-10
        array( 30, 54 ), array( 32, 58 ), array( 34, 62 ), array( 26, 46 ), array( 26, 48 ), //11-15
        array( 26, 50 ), array( 30, 54 ), array( 30, 56 ), array( 30, 58 ), array( 34, 62 ), //16-20
        array( 28, 50 ), array( 26, 50 ), array( 30, 54 ), array( 28, 54 ), array( 32, 58 ), //21-25
        array( 30, 58 ), array( 34, 62 ), array( 26, 50 ), array( 30, 54 ), array( 26, 52 ), //26-30
        array( 30, 56 ), array( 34, 60 ), array( 30, 58 ), array( 34, 62 ), array( 30, 54 ), //31-35
        array( 24, 50 ), array( 28, 54 ), array( 32, 58 ), array( 26, 54 ), array( 30, 58 ), //35-40
    );
    
    
    /**
 	 * Version information pattern (BCH coded).
 	 * See Table 1 in Appendix D (pp.68) of JIS X0510:2004.
 	 * @var array
 	 */
	private static $_versionPatterns = array(
		7  => '0x07c94', 8 =>  '0x085bc', 9  => '0x09a99', 10 => '0x0a4d3', 11 => '0x0bbf6', 12 => '0x0c762', 13 => '0x0d847', 14 => '0x0e60d',
		15 => '0x0f928', 16 => '0x10b78', 17 => '0x1145d', 18 => '0x12a17', 19 => '0x13532', 20 => '0x149a6', 21 => '0x15683', 22 => '0x168c9',
		23 => '0x177ec', 24 => '0x18ec4', 25 => '0x191e1', 26 => '0x1afab', 27 => '0x1b08e', 28 => '0x1cc1a', 29 => '0x1d33f', 30 => '0x1ed75',
		31 => '0x1f250', 32 => '0x209d5', 33 => '0x216f0', 34 => '0x228ba', 35 => '0x2379f', 36 => '0x24b0b', 37 => '0x2542e', 38 => '0x26a64',
		39 => '0x27541', 40 => '0x28c69'
	);
    
    
    /**
     * Symbol's word/bit capacity (depending on ECC-level and version)
     * http://www.swetake.com/qr/qr_table0.html (multiply "data code words" by 8 to get table below)
     * @var array
     */
    public static $maxDataBits = array(
    	self::QR_ECC_L => array(
			152,272,440,640,864,1088,1248,1552,1856,2192,				// ECC L
			2592,2960,3424,3688,4184,4712,5176,5768,6360,6888,
			7456,8048,8752,9392,10208,10960,11744,12248,13048,13880,
			14744,15640,16568,17528,18448,19472,20528,21616,22496,23648),
		self::QR_ECC_M => array(
			128,224,352,512,688,864,992,1232,1456,1728,					// ECC M
			2032,2320,2672,2920,3320,3624,4056,4504,5016,5352,
			5712,6256,6880,7312,8000,8496,9024,9544,10136,10984,
		11640,12328,13048,13800,14496,15312,15936,16816,17728,18672),
		self::QR_ECC_Q => array(
			104,176,272,384,496,608,704,880,1056,1232,					// ECC Q
			1440,1648,1952,2088,2360,2600,2936,3176,3560,3880,
			4096,4544,4912,5312,5744,6032,6464,6968,7288,7880,
			8264,8920,9368,9848,10288,10832,11408,12016,12656,13328),
		self::QR_ECC_H => array(
			72,128,208,288,368,480,528,688,800,976,						// ECC H
			1120,1264,1440,1576,1784,2024,2264,2504,2728,3080,
			3248,3536,3712,4112,4304,4768,5024,5288,5608,5960,
			6344,6760,7208,7688,7888,8432,8768,9136,9776,10208)
	);
		
		
	/**
	 * Format information includes EC level and mask pattern indicator in a 15 bit long.
	 * - first 2 bits are EC level
	 * - next 3 bits define the mask pattern
	 * - last 10 bits is error correcting data which is Bose-Chaudhuri-Hocquenghem(BCH)(15,5)
	 * example: 0x77c4 = 111011111000100
	 * @var array
	 */
	public static $formatInformation = array(
		self::QR_ECC_L => array('0x77c4', '0x72f3', '0x7daa', '0x789d', '0x662f', '0x6318', '0x6c41', '0x6976'),
		self::QR_ECC_M => array('0x5412', '0x5125', '0x5e7c', '0x5b4b', '0x45f9', '0x40ce', '0x4f97', '0x4aa0'),
		self::QR_ECC_Q => array('0x355f', '0x3068', '0x3f31', '0x3a06', '0x24b4', '0x2183', '0x2eda', '0x2bed'),
		self::QR_ECC_H => array('0x1689', '0x13be', '0x1ce7', '0x19d0', '0x0762', '0x0255', '0x0d0c', '0x083b')
	);
	
	
	/**
	 * Getter function for the version pattern data
	 * @param int $version
	 * @param bool $binary
	 */
	public static function getVersionPattern ($version, $binary = false)
	{
		$hexPattern = self::$_versionPatterns[$version];
		if($binary) {
			return decbin(hexdec($hexPattern));
		}else{
			return hexdec($hexPattern);
		}
	}
	
	
	/**
	 * Getter function for the alignment pattern data
	 * @param int $version
	 */
	public static function getAlignmentPattern ($version)
	{
		return self::$_alignmentPatterns[$version];
	}
	
	
	/**
	 * Retrieve matrix capacity remainder
	 * @param int $version
	 */
	public static function getMatrixCapacityRemainder($version)
	{
		return self::$_matrixCapacity[$version]['remainder'];
	}
	
	
	/**
	 * Retrieve matrix capacity words
	 * @param int $version
	 */
	public static function getMatrixCapacityWords($version)
	{
		return self::$_matrixCapacity[$version]['words'];
	}
	
}