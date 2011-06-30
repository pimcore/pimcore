<?php
/**
 * File containing the ezcImageAnalyzerPhpHandler class.
 *
 * @package ImageAnalysis
 * @version 1.1.3
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 */

/**
 * Class to retrieve information about a given image file.
 * This handler implements image analyzation using direct PHP functionality,
 * mainly the {@link getimagesize()} function and, if available, the EXIF
 * extension {@link exif_read_data()}. The driver is capable of determining
 * the type the following file formats:
 *
 * - GIF
 * - JPG
 * - PNG
 * - SWF
 * - SWC
 * - PSD
 * - TIFF
 * - BMP
 * - IFF
 * - JP2
 * - JPX
 * - JB2
 * - JPC
 * - XBM
 * - WBMP
 *
 * The driver determines the MIME type of these images (using the
 * {@link ezcImageAnalyzerPhpHandler::analyzeType()} method). The width,
 * height and size of the given image are always available after analyzing a
 * file, if the file in general can be analyzed
 * {@link ezcImageAnalyzerPhpHandler::canAnalyze()}.
 *
 * For JPEG and TIFF images this driver will try to read in information using
 * the EXIF extension in PHP and fills in the following properties of the
 * {@link ezcImageAnalyzerData} struct, returned by the
 * {@link ezcImageAnalyzerPhpHandler::analyzeImage()} method:
 * - exif
 * - isColor
 * - comment
 * - copyright
 * - date
 * - hasThumbnail
 * - isAnimated
 *
 * For GIF (also animated) it finds information by scanning the file manually
 * and fills in the following properties of the
 * {@link ezcImageAnalyzerData} struct, returned by the
 * {@link ezcImageAnalyzerPhpHandler::analyzeImage()} method:
 * - mode
 * - transparencyType
 * - comment
 * - commentList
 * - colorCount
 * - isAnimated
 *
 * @package ImageAnalysis
 * @version 1.1.3
 */
class ezcImageAnalyzerPhpHandler extends ezcImageAnalyzerHandler
{
    /**
     * Analyzes the image type.
     *
     * This method analyzes image data to determine the MIME type. This method
     * returns the MIME type of the file to analyze in lowercase letters (e.g.
     * "image/jpeg") or false, if the images MIME type could not be determined.
     *
     * For a list of image types this handler will be able to analyze, see
     * {@link ezcImageAnalyzerPhpHandler}.
     *
     * @param string $file The file to analyze.
     * @return string|bool The MIME type if analyzation suceeded or false.
     */
    public function analyzeType( $file )
    {
        $data = getimagesize( $file );
        if ( $data === false )
        {
            return false;
        }
        return image_type_to_mime_type( $data[2] );
    }

    /**
     * Analyze the image for detailed information.
     *
     * This may return various information about the image, depending on it's
     * type. All information is collected in the struct
     * {@link ezcImageAnalyzerData}. At least the
     * {@link ezcImageAnalyzerData::$mime} attribute is always available, if the
     * image type can be analyzed at all. Additionally this handler will always
     * set the {@link ezcImageAnalyzerData::$width},
     * {@link ezcImageAnalyzerData::$height} and
     * {@link ezcImageAnalyzerData::$size} attributes. For detailes information
     * on the additional data returned, see {@link ezcImageAnalyzerPhpHandler}.
     *
     * @throws ezcImageAnalyzerFileNotProcessableException
     *         If image file can not be processed.
     * @param string $file The file to analyze.
     * @return ezcImageAnalyzerData
     */
    public function analyzeImage( $file )
    {
        $data = getimagesize( $file );
        if ( $data === false )
        {
            throw new ezcImageAnalyzerFileNotProcessableException( $file, 'getimagesize() returned false.' );
        }

        $dataStruct = new ezcImageAnalyzerData();
        $dataStruct->width = $data[0];
        $dataStruct->height = $data[1];
        $dataStruct->mime = image_type_to_mime_type( $data[2] );
        $dataStruct->size = filesize( $file );

        if ( ( $dataStruct->mime === 'image/jpeg' || $dataStruct->mime === 'image/tiff' )
             && ezcBaseFeatures::hasFunction( 'exif_read_data')
           )
        {
            $this->analyzeExif( $file, $dataStruct );
        }
        elseif ( $dataStruct->mime === 'image/gif' )
        {
            $this->analyzeGif( $file, $dataStruct );
        }

        return $dataStruct;
    }

    /**
     * Returns if the handler can analyze a given MIME type.
     *
     * This method returns if the driver is capable of analyzing a given MIME
     * type. This method should be called before trying to actually analyze an
     * image using the drivers {@link self::analyzeImage()} method.
     *
     * @param string $mime The MIME type to check for.
     * @return bool True if the handler is able to analyze the MIME type.
     */
    public function canAnalyze( $mime )
    {
        switch ( $mime )
        {
            case 'image/gif':
            case 'image/jpeg':
            case 'image/png':
            case 'image/psd':
            case 'image/bmp':
            case 'image/tiff':
            case 'image/tiff':
            case 'image/jp2':
            case 'application/x-shockwave-flash':
            case 'image/iff':
            case 'image/vnd.wap.wbmp':
            case 'image/xbm':
                return true;
        }
        return false;
    }

    /**
     * Checks wether the GD handler is available on the system.
     *
     * Returns if PHP's {@link getimagesize()} function is available.
     *
     * @return bool True is the handler is available.
     */
    public function isAvailable()
    {
        return ezcBaseFeatures::hasFunction( 'getimagesize' );
    }

    /**
     * Analyze EXIF enabled file format for EXIF data entries.
     *
     * The image file is analyzed by calling exif_read_data and placing the
     * result in self::exif. In addition it fills in extra properties from
     * the EXIF data for easy and uniform access.
     *
     * @param string $file                     The file to analyze.
     * @param ezcImageAnalyzerData $dataStruct The data struct to fill.
     * @return ezcImageAnalyzerData The filled data struct.
     */
    private function analyzeExif( $file, ezcImageAnalyzerData $dataStruct )
    {
        $dataStruct->exif = exif_read_data( $file, "COMPUTED,FILE", true, false );

        // Section "COMPUTED"
        if ( isset( $dataStruct->exif['COMPUTED']['Width'] ) && isset( $dataStruct->exif['COMPUTED']['Height'] ) )
        {
            $dataStruct->width = $dataStruct->exif['COMPUTED']['Width'];
            $dataStruct->height = $dataStruct->exif['COMPUTED']['Height'];
        }
        if ( isset( $dataStruct->exif['COMPUTED']['IsColor'] ) )
        {
            $dataStruct->isColor = $dataStruct->exif['COMPUTED']['IsColor'] == 1;
        }
        if ( isset( $dataStruct->exif['COMPUTED']['UserComment'] ) )
        {
            $dataStruct->comment = $dataStruct->exif['COMPUTED']['UserComment'];
            $dataStruct->commentList = array( $dataStruct->comment );
        }
        if ( isset( $dataStruct->exif['COMPUTED']['Copyright'] ) )
        {
            $dataStruct->copyright = $dataStruct->exif['COMPUTED']['Copyright'];
        }

        // Section THUMBNAIL
        $dataStruct->hasThumbnail = isset( $dataStruct->exif['THUMBNAIL'] );

        // Section "FILE"
        if ( isset( $dataStruct->exif['FILE']['FileSize'] ) )
        {
            $dataStruct->size = $dataStruct->exif['FILE']['FileSize'];
        }
        if ( isset( $dataStruct->exif['FILE']['FileDateTime'] ) )
        {
            $dataStruct->date = $dataStruct->exif['FILE']['FileDateTime'];
        }

        // EXIF based image are never animated.
        $dataStruct->isAnimated = false;

        return $dataStruct;
    }

    /**
     * Analyze GIF files for detailed information.
     *
     * The GIF file is analyzed by scanning for frame entries, if more than one
     * is found it is assumed to be animated.
     * It also extracts other information such as image width and height, color
     * count, image mode, transparency type and comments.
     *
     * @throws ezcBaseFileIoException
     *         If image file could not be read.
     * @throws ezcImageAnalyzerFileNotProcessableException
     *         If image file can not be processed.
     * @param string $file                     The file to analyze.
     * @param ezcImageAnalyzerData $dataStruct The data struct to fill.
     * @return ezcImageAnalyzerData The filled data struct.
     */
    private function analyzeGif( $file, ezcImageAnalyzerData $dataStruct )
    {
        if ( ( $fp = fopen( $file, 'rb' ) ) === false )
        {
            throw new ezcBaseFileIoException( $file, ezcBaseFileException::READ );
        }

        // Read GIF header
        $magic = fread( $fp, 6 );
        $offset = 6;
        if ( $magic != 'GIF87a' &&
             $magic != 'GIF89a' )
        {
            throw new ezcImageAnalyzerFileNotProcessableException( $file, 'Not a valid GIF image file' );
        }

        $info = array();

        $version = substr( $magic, 3 );
        $frames = 0;
        // Gifs are always indexed
        $dataStruct->mode             = self::MODE_INDEXED;
        $dataStruct->commentList      = array();
        $dataStruct->transparencyType = self::TRANSPARENCY_OPAQUE;

        // Read Logical Screen Descriptor
        $data = unpack( "v1width/v1height/C1bitfield/C1index/C1ration", fread( $fp, 7 ) );
        $offset += 7;

        $lsdFields            = $data['bitfield'];
        $globalColorCount     = 0;
        $globalColorTableSize = 0;
        if ( $lsdFields >> 7 )
        {
            // Extract 3 bits for color count
            $globalColorCount     = ( 1 << ( ( $lsdFields & 0x07 ) + 1) );
            // Each color entry is RGB ie. 3 bytes
            $globalColorTableSize = $globalColorCount * 3;
        }

        $dataStruct->colorCount = $globalColorCount;
        $dataStruct->width      = $data['width'];
        $dataStruct->height     = $data['height'];

        if ( $globalColorTableSize )
        {
            // Skip the color table, we don't need the data
            fseek( $fp, $globalColorTableSize, SEEK_CUR );
            $offset += $globalColorTableSize;
        }

        $done = false;
        // Iterate over all blocks and extract information
        while ( !$done )
        {
            $data      = fread( $fp, 1 );
            $offset   += 1;
            $blockType = ord( $data[0] );

            if ( $blockType == 0x21 ) // Extension Introducer
            {
                $data          .= fread( $fp, 1 );
                $offset        += 1;
                $extensionLabel = ord( $data[1] );

                if ( $extensionLabel == 0xf9 ) // Graphical Control Extension
                {
                    $data     = unpack( "C1blocksize/C1bitfield/v1delay/C1index/C1term", fread( $fp, 5 + 1 ) );
                    $gceFlags = $data['bitfield'];//ord( $data[1] );
                    // $animationTimer is currently not in use.
                    /* $animationTimer = $data['delay']; */

                    // Check bit 0
                    if ( $gceFlags & 0x01 )
                    {
                        $dataStruct->transparencyType = self::TRANSPARENCY_TRANSPARENT;
                    }
                    $offset += 5 + 1;
                }
                else if ( $extensionLabel == 0xff ) // Application Extension
                {
                    $data    = fread( $fp, 12 );
                    $offset += 12;

                    $dataBlockDone = false;
                    while ( !$dataBlockDone )
                    {
                        $data       = unpack( "C1blocksize", fread( $fp, 1 ) );
                        $offset    += 1;
                        $blockBytes = $data['blocksize'];

                        if ( $blockBytes )
                        {
                            // Skip application data, we don't need the data
                            fseek( $fp, $blockBytes, SEEK_CUR );
                            $offset += $blockBytes;
                        }
                        else
                        {
                            $dataBlockDone = true;
                        }
                    }
                }
                else if ( $extensionLabel == 0xfe ) // Comment Extension
                {
                    $commentBlockDone = false;
                    $comment          = false;

                    while ( !$commentBlockDone )
                    {
                        $data       = unpack( "C1blocksize", fread( $fp, 1 ) );
                        $offset    += 1;
                        $blockBytes = $data['blocksize'];

                        if ( $blockBytes )
                        {
                            // Append current block to comment
                            $data     = fread( $fp, $blockBytes );
                            $comment .= $data;
                            $offset  += $blockBytes;
                        }
                        else
                        {
                            $commentBlockDone = true;
                        }
                    }
                    if ( $comment )
                    {
                        if ( $dataStruct->comment === null )
                        {
                            $dataStruct->comment = $comment;
                        }
                        $dataStruct->commentList[] = $comment;
                    }
                }
                else
                {
                    throw new ezcImageAnalyzerFileNotProcessableException( $file, "Invalid extension label 0x" . hexdec( $extensionLabel ) . " in GIF image." );
                }
            }
            else if ( $blockType == 0x2c ) // Image Descriptor
            {
                ++$frames;
                $data               .= fread( $fp, 9 );
                $data                = unpack( "C1separator/v1leftpos/v1toppos/v1width/v1height/C1bitfield", $data );
                $localColorTableSize = 0;
                $localColorCount     = 0;
                $idFields            = $data['bitfield'];
                if ( $idFields >> 7 ) // Local Color Table
                {
                    // Extract 3 bits for color count
                    $localColorCount     = ( 1 << ( ( $idFields & 0x07 ) + 1) );
                    // Each color entry is RGB ie. 3 bytes
                    $localColorTableSize = $localColorCount * 3;
                }
                if ( $localColorCount > $globalColorCount )
                {
                    $dataStruct->colorCount = $localColorCount;
                }

                if ( $localColorTableSize )
                {
                    // Skip the color table, we don't need the data
                    fseek( $fp, $localColorTableSize, SEEK_CUR );
                    $offset += $localColorTableSize;
                }

                $lzwCodeSize = fread( $fp, 1 ); // LZW Minimum Code Size, currently unused
                $offset     += 1;

                $dataBlockDone = false;
                while ( !$dataBlockDone )
                {
                    $data       = unpack( "C1blocksize", fread( $fp, 1 ) );
                    $offset    += 1;
                    $blockBytes = $data['blocksize'];

                    if ( $blockBytes )
                    {
                        // Skip image data, we don't need the data
                        fseek( $fp, $blockBytes, SEEK_CUR );
                        $offset += $blockBytes;
                    }
                    else
                    {
                        $dataBlockDone = true;
                    }
                }
            }
            else if ( $blockType == 0x3b ) // Trailer, end of stream
            {
                $done = true;
            }
            else
            {
                throw new ezcImageAnalyzerFileNotProcessableException( $file, "Invalid block type 0x" . hexdec( $blockType ) . " in GIF image." );
            }
            if ( feof( $fp ) )
            {
                break;
            }
        }
        $dataStruct->isAnimated = $frames > 1;

        return $dataStruct;
    }
}
?>
