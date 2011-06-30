<?php
/**
 * File containing the ezcImageAnalyzerImagemagickHandler class.
 *
 * @package ImageAnalysis
 * @version 1.1.3
 * @copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 */

/**
 * Class to retrieve information about a given image file.
 * This is an ezcImageAnalyzerHandler that utilizes ImageMagick to analyze
 * image files.
 *
 * This ezcImageAnalyzerHandler can be configured using the
 * Option 'binary', which must be set to the full path of the ImageMagick
 * "identify" binary. If this option is not submitted to the
 * {@link ezcImageAnalyzerHandler::__construct()} method, the handler will
 * use just the name of the binary ("identify" on Unix, "identify.exe" on
 * Windows).
 *
 * You can provide the options of the ezcImageAnalyzerImagemagickHandler to
 * the {@link ezcImageAnalyzer::setHandlerClasses()}.
 *
 * @package ImageAnalysis
 * @version 1.1.3
 */
class ezcImageAnalyzerImagemagickHandler extends ezcImageAnalyzerHandler
{
    /**
     * The ImageMagick binary to utilize.
     *
     * This variable is set during call to
     * {@link ezcImageAnalyzerImagemagickHandler::checkImagemagick()}.
     *
     * @var string
     */
    protected $binary;

    /**
     * Indicates if this handler is available.
     *
     * The first call to
     * {@link ezcImageAnalyzerImagemagickHandler::isAvailable()}
     * determines this variable, which is then used as a cache for the call to
     * {@link ezcImageAnalyzerImagemagickHandler::checkImagemagick()}.
     *
     * @var bool
     */
    protected $isAvailable;

    /**
     * Mapping between ImageMagick identification strings and MIME types.
     *
     * ImageMagick's "identify" command returns an identification string to
     * indicate the file type examined.
     *
     * This map has been handcrafted, because ImageMagick misses the
     * possibility to determine MIME types. It misses some identification
     * strings (mostly for file types which are absolutely rare in use
     * or which ImageMagick is only capable to read or write, but not both.).
     *
     * @var array(string=>string)
     */
    protected $mimeMap = array(
        'bmp'   => 'image/bmp',
        'bmp2'  => 'image/bmp',
        'bmp3'  => 'image/bmp',
        'cur'   => 'image/x-win-bitmap',
        'dcx'   => 'image/dcx',
        'epdf'  => 'application/pdf',
        'epi'   => 'application/postscript',
        'eps'   => 'application/postscript',
        'eps2'  => 'application/postscript',
        'eps3'  => 'application/postscript',
        'epsf'  => 'application/postscript',
        'epsi'  => 'application/postscript',
        'ept'   => 'application/postscript',
        'ept2'  => 'application/postscript',
        'ept3'  => 'application/postscript',
        'fax'   => 'image/g3fax',
        'fits'  => 'image/x-fits',
        'g3'    => 'image/g3fax',
        'gif'   => 'image/gif',
        'gif87' => 'image/gif',
        'icb'   => 'application/x-icb',
        'ico'   => 'image/x-win-bitmap',
        'icon'  => 'image/x-win-bitmap',
        'jng'   => 'image/jng',
        'jpeg'  => 'image/jpeg',
        'jpg'   => 'image/jpeg',
        'm2v'   => 'video/mpeg2',
        'miff'  => 'application/x-mif',
        'mng'   => 'video/mng',
        'mpeg'  => 'video/mpeg',
        'mpg'   => 'video/mpeg',
        'otb'   => 'image/x-otb',
        'p7'    => 'image/x-xv',
        'palm'  => 'image/x-palm',
        'pbm'   => 'image/pbm',
        'pcd'   => 'image/pcd',
        'pcds'  => 'image/pcd',
        'pcl'   => 'application/pcl',
        'pct'   => 'image/pict',
        'pcx'   => 'image/x-pcx',
        'pdb'   => 'application/vnd.palm',
        'pdf'   => 'application/pdf',
        'pgm'   => 'image/x-pgm',
        'picon' => 'image/xpm',
        'pict'  => 'image/pict',
        'pjpeg' => 'image/pjpeg',
        'png'   => 'image/png',
        'png24' => 'image/png',
        'png32' => 'image/png',
        'png8'  => 'image/png',
        'pnm'   => 'image/pbm',
        'ppm'   => 'image/x-ppm',
        'ps'    => 'application/postscript',
        'psd'   => 'image/x-photoshop',
        'ptif'  => 'image/x-ptiff',
        'ras'   => 'image/ras',
        'sgi'   => 'image/sgi',
        'sun'   => 'image/ras',
        'svg'   => 'image/svg+xml',
        'svgz'  => 'image/svg',
        'text'  => 'text/plain',
        'tga'   => 'image/tga',
        'tif'   => 'image/tiff',
        'tiff'  => 'image/tiff',
        'txt'   => 'text/plain',
        'vda'   => 'image/vda',
        'viff'  => 'image/x-viff',
        'vst'   => 'image/vst',
        'wbmp'  => 'image/vnd.wap.wbmp',
        'xbm'   => 'image/x-xbitmap',
        'xpm'   => 'image/x-xbitmap',
        'xv'    => 'image/x-viff',
        'xwd'   => 'image/xwd',
    );

    /**
     * MIME types this handler is capable to read.
     *
     * This array holds an extract of the
     * {@link ezcImageAnalyzerHandler::$mimeMap}, listing all MIME types this
     * handler is capable to analyze. The map is indexed by the MIME type,
     * assigned to boolean true, to speed up hash lookups.
     *
     * @var array(string=>bool)
     */
    protected $mimeTypes = array(
        'application/pcl' => true,
        'application/pdf' => true,
        'application/postscript' => true,
        'application/vnd.palm' => true,
        'application/x-icb' => true,
        'application/x-mif' => true,
        'image/dcx' => true,
        'image/g3fax' => true,
        'image/gif' => true,
        'image/jng' => true,
        'image/jpeg' => true,
        'image/pbm' => true,
        'image/pcd' => true,
        'image/pict' => true,
        'image/pjpeg' => true,
        'image/png' => true,
        'image/ras' => true,
        'image/sgi' => true,
        'image/svg' => true,
        'image/tga' => true,
        'image/tiff' => true,
        'image/vda' => true,
        'image/vnd.wap.wbmp' => true,
        'image/vst' => true,
        'image/x-fits' => true,
        'image/x-ms-bmp' => true,
        'image/x-otb' => true,
        'image/x-palm' => true,
        'image/x-pcx' => true,
        'image/x-pgm' => true,
        'image/x-photoshop' => true,
        'image/x-ppm' => true,
        'image/x-ptiff' => true,
        'image/x-viff' => true,
        'image/x-win-bitmap' => true,
        'image/x-xbitmap' => true,
        'image/x-xv' => true,
        'image/xpm' => true,
        'image/xwd' => true,
        'text/plain' => true,
        'video/mng' => true,
        'video/mpeg' => true,
        'video/mpeg2' => true,
    );

    /**
     * Analyzes the image type.
     * This method analyzes image data to determine the MIME type. This method
     * returns the MIME type of the file to analyze in lowercase letters (e.g.
     * "image/jpeg") or false, if the images MIME type could not be determined.
     *
     * For a list of image types this handler will be able to analyze, see
     * {@link ezcImageAnalyzerImagemagickHandler}.
     *
     * @param string $file The file to analyze.
     * @return string|bool The MIME type if analyzation suceeded or false.
     */
    public function analyzeType( $file )
    {
        $parameters = '-format ' . escapeshellarg( '%m|' ) . ' ' . escapeshellarg( $file );
        $res = ezcImageAnalyzerImagemagickHandler::runCommand( $parameters, $outputString, $errorString );
        if ( $res !== 0 || $errorString !== '' )
        {
            return false;
        }
        $identifiers = explode( '|', strtolower( $outputString ), 2 );
        if ( !isset( $this->mimeMap[$identifiers[0]] ) )
        {
            return false;
        }
        return $this->mimeMap[$identifiers[0]];
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
     * on the additional data returned, see {@link ezcImageAnalyzerImagemagickHandler}.
     *
     * @todo Why does ImageMagick return the wrong file size on TIFF with comments?
     * @todo Check for translucent transparency.
     *
     * @throws ezcImageAnalyzerFileNotProcessableException 
     *         If image file can not be processed.
     * @param string $file The file to analyze.
     * @return ezcImageAnalyzerData
     */
    public function analyzeImage( $file )
    {
        // Example strings returned here:
        // JPEG (Exif without comment):
        // string(45) "[JPEG|76383|399|600|8|59428|DirectClassRGB|]*"
        // --------------------------------
        // TIFF (Exif with comment):
        // string(79) "[TIFF|108125|399|600|8|113524|DirectClassRGB|A simple comment in a TIFF file.]*"
        // --------------------------------
        // PNG:
        // string(46) "[PNG|5420|160|120|8|254|DirectClassRGBMatte|]*"
        // --------------------------------
        // GIF (Animated):
        // string(168) "[GIF|4100|80|50|8|38|PseudoClassRGB|]*[GIF|4100|80|50|8|21|PseudoClassRGB|]*[GIF|4100|80|50|8|17|PseudoClassRGB|Copyright 1996 DeMorgan Industries Corp.
        // 
        // Animated Cog]*"
        // --------------------------------

        $command = '-format ' . escapeshellarg( '[%m|%b|%w|%h|%k|%r|%c]*' ) . ' ' . escapeshellarg( $file );

        // Execute ImageMagick
        $return = $this->runCommand( $command, $outputString, $errorString );
        if ( $return !== 0 || $errorString !== '' )
        {
            throw new ezcImageAnalyzerFileNotProcessableException( $file, "ImageMagick error: '{$errorString}'." );
        }

        $dataStruct = new ezcImageAnalyzerData();

        $rawDataArr = explode( '*', $outputString );
        if ( sizeof( $rawDataArr ) === 1 )
        {
            throw new ezcImageAnalyzerFileNotProcessableException( $file, "ImageMagick did not return correct formated string." );
        }

        // Unset last (empty) element
        unset( $rawDataArr[count( $rawDataArr ) - 1] );

        if ( sizeof( $rawDataArr ) > 1 )
        {
            $dataStruct->isAnimated = true;
        }
        foreach ( $rawDataArr as $id => $rawData )
        {
            $parsedData = explode( '|', substr( $rawData, 1, -1 ) );
            $dataStruct->mime     = $this->mimeMap[strtolower( $parsedData[0] )];

            $dataStruct->size     = filesize( $file );

            $dataStruct->width    = max( (int) $parsedData[2], $dataStruct->width );
            $dataStruct->height   = max( (int) $parsedData[3], $dataStruct->height );

            $dataStruct->isColor  = $parsedData[4] > 2 ? true : false;

            $dataStruct->transparencyType = self::TRANSPARENCY_OPAQUE;
            if ( strpos( $parsedData[5], 'RGBMatte' ) !== FALSE )
            {
                $dataStruct->transparencyType = self::TRANSPARENCY_TRANSPARENT;
            }

            if ( $parsedData[6] !== '' )
            {
                if ( $dataStruct->isAnimated && $id > 0 )
                {
                    $dataStruct->commentList[] = $parsedData[6];
                }
                else
                {
                    $dataStruct->comment = $parsedData[6];
                    $dataStruct->commentList = array( $parsedData[6] );
                }
            }

            if ( $dataStruct->mime === 'image/jpeg' || $dataStruct->mime === 'image/tiff' )
            {
                $this->analyzeExif( $dataStruct, $file );
            }
        }
        return $dataStruct;
    }

    /**
     * Analyze Exif data contained in JPEG and TIFF images.
     *
     * This method analyzes the Exif data contained in JPEG and TIFF images,
     * using ImageMagick's "identify" binary.
     *
     * This method tries to provide the EXIF data in a format as close as
     * possible to the format returned by ext/EXIF {@link http://php.net/exif}.
     *
     * @param ezcImageAnalyzerData $data The data object to fill.
     * @param string $file               The file to analyze.
     */
    protected function analyzeExif( ezcImageAnalyzerData $data, $file )
    {
        $tagMap = array(
            "IFD0" => array(
                "ImageDescription",
                "Make",
                "Model",
                "Orientation",
                "XResolution",
                "YResolution",
                "ResolutionUnit",
                "Software",
                "DateTime",
                "YCbCrPositioning",
                "Exif_IFD_Pointer",
                "Copyright",
                "UserComment",
            ),

            "EXIF" => array(
                "ExposureTime",
                "FNumber",
                "ExposureProgram",
                "ISOSpeedRatings",
                "ExifVersion",
                "DateTimeOriginal",
                "DateTimeDigitized",
                "ComponentsConfiguration",
                "BrightnessValue",
                "ExposureBiasValue",
                "MaxApertureValue",
                "MeteringMode",
                "LightSource",
                "Flash",
                "FocalLength",
            // ImageMagick does not grab this correct, therefore not supported
            //  "SubjectLocation",
                "MakerNote",
                "UserComment",
                "FlashPixVersion",
                "ColorSpace",
                "ExifImageWidth",
                "ExifImageLength",
                "InteroperabilityOffset",
                "FileSource",
                "SceneType",
                "CustomRendered",
                "ExposureMode",
                "WhiteBalance",
                "DigitalZoomRatio",
                "FocalLengthIn35mmFilm",
                "SceneCaptureType",
                "GainControl",
                "Contrast",
                "Saturation",
                "Sharpness",
                "SubjectDistanceRange",
            ),
            "INTEROP" => array(
                "InterOperabilityIndex",
                "InterOperabilityVersion"
            )
        );

        // Retreive exif data
        $command = '-format ' . escapeshellarg( "%[EXIF:*]"  ) . ' ' . escapeshellarg( $file );
        $return = $this->runCommand( $command, $outputString, $errorString, false );
        if ( $return !== 0 || $errorString !== '' )
        {
            throw new ezcImageAnalyzerFileNotProcessableException( $file, "ImageMagick error: '{$errorString}'." );
        }

        // The following is done in 2 steps to ensure the same array order as ext/exif provides.

        // Pre-process data
        $rawData = explode( "\n", $outputString );
        $dataArr = array();
        foreach ( $rawData as $dataString )
        {
            $dataParts = explode( "=", $dataString, 2 );
            if ( sizeof( $dataParts ) === 2 )
            {
                $dataArr[$dataParts[0]] = substr( $dataParts[1], -1, 1 ) === "." ? substr( $dataParts[1], 0, -1 ) : $dataParts[1];
            }
        }
        // Some post-processing is needed because ext/exif has some different tag names
        if ( isset( $dataArr["ExifOffset"] ) )
        {
            $dataArr["Exif_IFD_Pointer"] =  $dataArr["ExifOffset"];
        }
        if ( isset( $dataArr["InteroperabilityIndex"] ) )
        {
            $dataArr["InterOperabilityIndex"] = $dataArr["InteroperabilityIndex"];
        }
        if ( isset( $dataArr["InteroperabilityVersion"] ) )
        {
            $dataArr["InterOperabilityVersion"] = $dataArr["InteroperabilityVersion"];
        }
        if ( isset( $dataArr["Artist"] ) )
        {
            $dataArr["Author"] = $dataArr["Artist"];
        }

        // Assign data to tags
        $exifArr = array();
        foreach ( $tagMap as $section => $tags )
        {
            foreach ( $tags as $tag )
            {
                if ( isset( $dataArr[$tag] ) )
                {
                    // Correct types
                    switch ( true )
                    {
                        case ( ctype_digit( $dataArr[$tag] ) && stripos( $tag, "version" ) === false ):
                            $exifArr[$section][$tag] = (int)$dataArr[$tag];
                            break;
                        case ( is_numeric( $dataArr[$tag] ) && stripos( $tag, "version" ) === false ):
                            $exifArr[$section][$tag] = (float)$dataArr[$tag];
                            break;
                        default:
                            $exifArr[$section][$tag] = $dataArr[$tag];
                            break;
                    }
                }
            }
        }

        // Retreive additional data for computation
        $imageData = getimagesize( $file );

        $colorCount = 0;
        $command = '-format ' . escapeshellarg( '%k' ) . ' ' . escapeshellarg( $file );
        $return = $this->runCommand( $command, $colorCount, $errorString );
        if ( $return !== 0 || $errorString !== '' )
        {
            throw new ezcImageAnalyzerFileNotProcessableException( $file, "ImageMagick error: '{$errorString}'." );
        }

        // Compute additional section ext/EXIF provides
        $additionsArr = array();
        $addtionsArr["FILE"]["FileName"]               =  basename( $file );
        $addtionsArr["FILE"]["FileDateTime"]           =  filemtime( $file );
        $addtionsArr["FILE"]["FileSize"]               =  filesize( $file );
        $addtionsArr["FILE"]["FileType"]               =  $imageData[2];
        $addtionsArr["FILE"]["MimeType"]               =  $data->mime;
        $addtionsArr["FILE"]["SectionsFound"]          =
                ( isset( $exifArr["EXIF"] ) || isset( $exifArr["IFD0"] ) ? "ANY_TAG, " : "" )
                . implode( ", ", array_keys( $exifArr ) );

        $addtionsArr["COMPUTED"]["html"]               =  "width=\"{$data->width}\" height=\"{$data->height}\"";
        $addtionsArr["COMPUTED"]["Height"]             =  $data->height;
        $addtionsArr["COMPUTED"]["Width"]              =  $data->width;
        $addtionsArr["COMPUTED"]["IsColor"]            =  ( $colorCount < 3 ) ? 0 : 1;

        // @todo Implement if possible!
        // $addtionsArr["COMPUTED"]["ByteOrderMotorola"]  =  null;

        $fNumberParts = isset( $exifArr["EXIF"]["FNumber"] ) ? explode( "/", $exifArr["EXIF"]["FNumber"] ) : null;
        if ( sizeof( $fNumberParts ) === 2 )
        {
            $addtionsArr["COMPUTED"]["ApertureFNumber"] = sprintf( "f/%.1f", $fNumberParts[0] / $fNumberParts[1] );
        }
        // ImageMagick resturns "..." for not set comments
        if ( isset( $exifArr["EXIF"]["UserComment"] ) )
        {
            $addtionsArr["COMPUTED"]["UserComment"]    =  preg_match( "/^\.*$/", $exifArr["EXIF"]["UserComment"] ) === false ? $exifArr["EXIF"]["UserComment"] : null;
            // @todo Maybe we can determine that somehow?
            // $addtionsArr["COMPUTED"]["UserCommentEncoding"] =  "UNDEFINED";
        }

        // Not available through ImageMagick
        // $addtionsArr["COMPUTED"]["Thumbnail.FileType"]  =  null
        // $addtionsArr["COMPUTED"]["Thumbnail.MimeType"]  =  null

        // Merge arrays (done here, to have consistent key order)
        $data->exif = array_merge( $addtionsArr, $exifArr );
    }

    /**
     * Returns if the handler can analyze a given MIME type.
     *
     * This method returns if the driver is capable of analyzing a given MIME
     * type. This method should be called before trying to actually analyze an
     * image using the drivers {@link ezcImageAnalyzerHandler::analyzeImage()}
     * method.
     *
     * @param string $mime The MIME type to check for.
     * @return bool True if the handler is able to analyze the MIME type.
     */
    public function canAnalyze( $mime )
    {
        return isset( $this->mimeTypes[strtolower( $mime )] );
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
        if ( !isset( $this->isAvailable ) )
        {
            $this->isAvailable = $this->checkImagemagick();
        }
        return $this->isAvailable;
    }

    /**
     * Checks the availability of ImageMagick on the system.
     * 
     * @return bool
     */
    protected function checkImagemagick()
    {
        if ( !isset( $this->options['binary'] ) )
        {
            switch ( PHP_OS )
            {
                case 'Linux':
                case 'Unix':
                case 'FreeBSD':
                case 'MacOS':
                case 'Darwin':
                case 'SunOS':
                    $this->binary = 'identify';
                    break;
                case 'Windows':
                case 'WINNT':
                case 'WIN32':
                    $this->binary = 'identify.exe';
                    break;
                default:
                    throw new ezcImageAnalyzerInvalidHandlerException( 'ezcImageAnalyzerImagemagickHandler' );
                    break;
            }
        }
        else
        {
            $this->binary = $this->options['binary'];
        }

        return ezcBaseFeatures::hasImageIdentify();
    }

    /**
     * Run the binary registered in ezcImageAnalyzerImagemagickHandler::$binary.
     *
     * This method executes the ImageMagick binary using the applied parameter
     * string. It returns the return value of the command. The output printed
     * to STDOUT and ERROUT is available through the $stdOut and $errOut
     * parameters.
     *
     * @param string $parameters    The parameters for the binary to execute.
     * @param string $stdOut        The standard output.
     * @param string $errOut        The error output.
     * @param bool   $stripNewlines Wether to strip the newlines from STDOUT.
     * @return int The return value of the command (0 on success).
     */
    protected function runCommand( $parameters, &$stdOut, &$errOut, $stripNewlines = true )
    {
        $command = escapeshellcmd( $this->binary ) . ( $parameters !== '' ?  ' ' . $parameters : '' );
        // Prepare to run ImageMagick command
        $descriptors = array(
            array( 'pipe', 'r' ),
            array( 'pipe', 'w' ),
            array( 'pipe', 'w' ),
        );

        // Open ImageMagick process
        $process = proc_open( $command, $descriptors, $pipes );

        // Close STDIN pipe
        fclose( $pipes[0] );

        // Read STDOUT
        $stdOut = '';
        do
        {
            $stdOut .= ( $stripNewlines === true ) ? rtrim( fgets( $pipes[1], 1024), "\n" ) : fgets( $pipes[1], 1024 );
        } while ( !feof( $pipes[1] ) );

        // Read STDERR
        $errOut = '';
        do
        {
            $errOut .= rtrim( fgets( $pipes[2], 1024), "\n" );
        } while ( !feof( $pipes[2] ) );

        // Wait for process to terminate and store return value
        return proc_close( $process );
    }
}
?>
