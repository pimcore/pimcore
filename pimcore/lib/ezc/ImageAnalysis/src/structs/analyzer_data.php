<?php
/**
 * File containing the ezcImageAnalyzerData struct.
 *
 * @package ImageAnalysis
 * @version 1.1.3
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 */

/**
 * Struct to store the data retrieved from an image analysis.
 *
 * This class is used as a struct for the data retrieved from 
 * an {@link ezcImageAnalyzerHandler}. It stores various information about
 * an analyzed image and pre-fills it's attributes with sensible default
 * values, to make the usage as easy as possible.
 *
 * Ths struct class should not be accessed directly (except form 
 * {@link ezcImageAnalyzerHandler} classes, where it is created). From a 
 * users view it is transparently accessable through
 * {@link ezcImageAnalyzer::$data}, more specific using
 * <code>
 * $analyzer = new ezcImageAnalyzer( 'myfile.jpg' );
 * echo $analyzer->data->size;
 * </code>
 *
 * @see ezcImageAnalyzer
 * @see ezcImageAnalyzerHandler
 *
 * @package ImageAnalysis
 * @version 1.1.3
 */
class ezcImageAnalyzerData extends ezcBaseStruct
{
    /**
     * Detected MIME type for the image.
     *
     * @var string
     */
    public $mime;

    /**
     * EXIF information retrieved from image.
     *
     * This will only be filled in for images which supports EXIF entries,
     * currently they are:
     * - image/jpeg
     * - image/tiff
     *
     * @link http://php.net/manual/en/function.exif-read-data.php
     *
     * @var array(string=>string)
     */
    public $exif = array();

    /**
     * Width of image in pixels.
     *
     * @var int
     */
    public $width = 0;

    /**
     * Height of image in pixels.
     *
     * @var int
     */
    public $height = 0;

    /**
     * Size of image file in bytes.
     *
     * @var int
     */
    public $size = 0;

    /**
     * The image mode.
     *
     * Can be one of:
     * - ezcImageAnalyzerHandler::MODE_INDEXED   - Image is built with a palette and consists of
     *                          indexed values per pixel.
     * - ezcImageAnalyzerHandler::MODE_TRUECOLOR - Image consists of RGB value per pixel.
     *
     * @var int
     */
    public $mode = ezcImageAnalyzerHandler::MODE_TRUECOLOR;

    /**
     * Type of transparency in image.
     *
     * Can be one of:
     * - ezcImageAnalyzerHandler::TRANSPARENCY_OPAQUE      - No parts of image is transparent.
     * - ezcImageAnalyzerHandler::TRANSPARENCY_TRANSPARENT - Selected palette entries are
     *                                    completely see-through.
     * - ezcImageAnalyzerHandler::TRANSPARENCY_TRANSLUCENT - Transparency determined pixel per
     *                                    pixel with a fuzzy value.
     *
     * @var int
     */
    public $transparencyType;

    /**
     * Does the image have colors?
     *
     * @var bool
     */
    public $isColor = true;

    /**
     * Number of colors in image.
     *
     * @var int
     */
    public $colorCount = 0;

    /**
     * First inline comment for the image.
     *
     * @var string
     */
    public $comment = null;

    /**
     * List of inline comments for the image.
     *
     * @var array(string)
     */
    public $commentList = array();

    /**
     * Copyright text for the image.
     *
     * @var string
     */
    public $copyright = null;

    /**
     * The date when the picture was taken as UNIX timestamp.
     *
     * @var int
     */
    public $date;

    /**
     * Does the image have a thumbnail?
     *
     * @var bool
     */
    public $hasThumbnail = false;

    /**
     * Is the image animated?
     *
     * @var bool
     */
    public $isAnimated = false;

    /**
     * Create a new instance of ezcImageAnalyzerData.
     *
     * Create a new instance of ezcImageAnalyzerData to be used with
     * {@link ezcImageAnalyzer} objects.
     *
     * @see ezcImageAnalyzer::analyzeImage()
     * @see ezcImageAnalyzerHandler::analyzeImage()
     *
     * @param string $mime {@link ezcImageAnalyzerData::$mime}
     * @param array $exif {@link ezcImageAnalyzerData::$exif}
     * @param int $width {@link ezcImageAnalyzerData::$width}
     * @param int $height {@link ezcImageAnalyzerData::$height}
     * @param int $size {@link ezcImageAnalyzerData::$size}
     * @param int $mode {@link ezcImageAnalyzerData::$mode}
     * @param int $transparencyType {@link ezcImageAnalyzerData::$transparencyType}
     * @param bool $isColor {@link ezcImageAnalyzerData::$isColor}
     * @param int $colorCount {@link ezcImageAnalyzerData::$colorCount}
     * @param string $comment {@link ezcImageAnalyzerData::$comment}
     * @param array $commentList {@link ezcImageAnalyzerData::$commentList}
     * @param string $copyright {@link ezcImageAnalyzerData::$copyright}
     * @param int $date {@link ezcImageAnalyzerData::$date}
     * @param bool $hasThumbnail {@link ezcImageAnalyzerData::$hasThumbnail}
     * @param bool $isAnimated {@link ezcImageAnalyzerData::$isAnimated}
     */
    public function __construct(
        $mime = null,
        $exif = array(),
        $width = 0,
        $height = 0,
        $size = 0,
        $mode = ezcImageAnalyzerHandler::MODE_TRUECOLOR,
        $transparencyType = null,
        $isColor = true,
        $colorCount = 0,
        $comment = null,
        $commentList = array(),
        $copyright = null,
        $date = null,
        $hasThumbnail = false,
        $isAnimated = false
    )
    {
        $this->mime = $mime;
        $this->exif = $exif;
        $this->width = $width;
        $this->height = $height;
        $this->size = $size;
        $this->mode = $mode;
        $this->transparencyType = $transparencyType;
        $this->isColor = $isColor;
        $this->colorCount = $colorCount;
        $this->comment = $comment;
        $this->commentList = $commentList;
        $this->copyright = $copyright;
        $this->date = $date;
        $this->hasThumbnail = $hasThumbnail;
        $this->isAnimated = $isAnimated;
    }
}
?>
