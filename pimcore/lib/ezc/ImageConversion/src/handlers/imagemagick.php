<?php
/**
 * This file contains the ezcImageImagemagickHandler class.
 *
 * @package ImageConversion
 * @version 1.3.8
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 */

/**
 * ezcImageHandler implementation for ImageMagick.
 *
 * @see ezcImageConverter
 * @see ezcImageHandler
 *
 * @package ImageConversion
 * @version 1.3.8
 */
class ezcImageImagemagickHandler extends ezcImageImagemagickBaseHandler
                                 implements ezcImageGeometryFilters,
                                            ezcImageColorspaceFilters,
                                            ezcImageEffectFilters,
                                            ezcImageWatermarkFilters,
                                            ezcImageThumbnailFilters
{

    /**
     * Scale filter.
     * General scale filter. Scales the image to fit into a given box size, 
     * determined by a given width and height value, measured in pixel. This 
     * method maintains the aspect ratio of the given image. Depending on the
     * given direction value, this method performs the following scales:
     *
     * - ezcImageGeometryFilters::SCALE_BOTH:
     *      The image will be scaled to fit exactly into the given box 
     *      dimensions, no matter if it was smaller or larger as the box
     *      before.
     * - ezcImageGeometryFilters::SCALE_DOWN:
     *      The image will be scaled to fit exactly into the given box 
     *      only if it was larger than the given box dimensions before. If it
     *      is smaller, the image will not be scaled at all.
     * - ezcImageGeometryFilters::SCALE_UP:
     *      The image will be scaled to fit exactly into the given box 
     *      only if it was smaller than the given box dimensions before. If it
     *      is larger, the image will not be scaled at all. ATTENTION:
     *      In this case, the image does not necessarily fit into the given box
     *      afterwards.
     *
     * ATTENTION: Using this filter method directly results in the filter being 
     * applied to the image which is internally marked as "active" (most 
     * commonly this is the last recently loaded one). It is highly recommended
     * to apply filters through the {@link ezcImageImagemagickHandler::applyFilter()}
     * method, which enables you to specify the image a filter is applied to.
     *
     * @param int $width     Scale to width
     * @param int $height    Scale to height
     * @param int $direction Scale to which direction.
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         No loaded file could be found or an error destroyed a loaded reference.
     * @throws ezcBaseValueException
     *         If a submitted parameter was out of range or type.
     */
    public function scale( $width, $height, $direction = ezcImageGeometryFilters::SCALE_BOTH )
    {
        if ( !is_int( $width ) || $width < 1 )
        {
            throw new ezcBaseValueException( 'width', $width, 'int > 0' );
        }
        if ( !is_int( $height ) || $height < 1 )
        {
            throw new ezcBaseValueException( 'height', $height, 'int > 0' );
        }
        
        $dirMod = $this->getDirectionModifier( $direction );
        $this->addFilterOption(
            $this->getActiveReference(),
            '-resize',
            $width.$dirMod.'x'.$height.$dirMod
        );
    }

    /**
     * Scale after width filter.
     * Scales the image to a give width, measured in pixel. Scales the height
     * automatically while keeping the ratio. The direction dictates, if an
     * image may only be scaled {@link self::SCALE_UP}, {@link self::SCALE_DOWN}
     * or if the scale may work in {@link self::SCALE_BOTH} directions.
     *
     * ATTENTION: Using this filter method directly results in the filter being 
     * applied to the image which is internally marked as "active" (most 
     * commonly this is the last recently loaded one). It is highly recommended
     * to apply filters through the {@link ezcImageImagemagickHandler::applyFilter()}
     * method, which enables you to specify the image a filter is applied to.
     *
     * @param int $width     Scale to width
     * @param int $direction Scale to which direction
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         No loaded file could be found or an error destroyed a loaded reference
     * @throws ezcBaseValueException
     *         If a submitted parameter was out of range or type.
     */
    public function scaleWidth( $width, $direction )
    {
        if ( !is_int( $width ) || $width < 1 )
        {
            throw new ezcBaseValueException( 'width', $width, 'int > 0' );
        }

        $dirMod = $this->getDirectionModifier( $direction );
        $this->addFilterOption(
            $this->getActiveReference(),
            '-resize ',
            $width.$dirMod
        );
    }

    /**
     * Scale after height filter.
     * Scales the image to a give height, measured in pixel. Scales the width
     * automatically while keeping the ratio. The direction dictates, if an
     * image may only be scaled {@link self::SCALE_UP}, {@link self::SCALE_DOWN}
     * or if the scale may work in {@link self::SCALE_BOTH} directions.
     *
     * ATTENTION: Using this filter method directly results in the filter being 
     * applied to the image which is internally marked as "active" (most 
     * commonly this is the last recently loaded one). It is highly recommended
     * to apply filters through the {@link ezcImageImagemagickHandler::applyFilter()}
     * method, which enables you to specify the image a filter is applied to.
     *
     * @param int $height    Scale to height
     * @param int $direction Scale to which direction
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         No loaded file could be found or an error destroyed a loaded reference
     * @throws ezcBaseValueException
     *         If a submitted parameter was out of range or type.
     */
    public function scaleHeight( $height, $direction )
    {
        if ( !is_int( $height ) || $height < 1 )
        {
            throw new ezcBaseValueException( 'height', $height, 'int > 0' );
        }
        $dirMod = $this->getDirectionModifier( $direction );
        $this->addFilterOption(
            $this->getActiveReference(),
            '-resize ',
            'x'.$height.$dirMod
        );
    }

    /**
     * Scale percent measures filter.
     * Scale an image to a given percentage value size.
     *
     * ATTENTION: Using this filter method directly results in the filter being 
     * applied to the image which is internally marked as "active" (most 
     * commonly this is the last recently loaded one). It is highly recommended
     * to apply filters through the {@link ezcImageImagemagickHandler::applyFilter()}
     * method, which enables you to specify the image a filter is applied to.
     *
     * @param int $width  Scale to width
     * @param int $height Scale to height
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         No loaded file could be found or an error destroyed a loaded reference
     * @throws ezcBaseValueException
     *         If a submitted parameter was out of range or type.
     */
    public function scalePercent( $width, $height )
    {
        if ( !is_int( $height ) || $height < 1 )
        {
            throw new ezcBaseValueException( 'height', $height, 'int > 0' );
        }
        if ( !is_int( $width ) || $width < 1 )
        {
            throw new ezcBaseValueException( 'width', $width, 'int > 0' );
        }
        $this->addFilterOption(
            $this->getActiveReference(),
            '-resize',
            $width.'%x'.$height.'%'
        );
    }

    /**
     * Scale exact filter.
     * Scale the image to a fixed given pixel size, no matter to which
     * direction.
     *
     * ATTENTION: Using this filter method directly results in the filter being 
     * applied to the image which is internally marked as "active" (most 
     * commonly this is the last recently loaded one). It is highly recommended
     * to apply filters through the {@link ezcImageImagemagickHandler::applyFilter()}
     * method, which enables you to specify the image a filter is applied to.
     *
     * @param int $width  Scale to width
     * @param int $height Scale to height
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         No loaded file could be found or an error destroyed a loaded reference.
     * @throws ezcBaseValueException
     *         If a submitted parameter was out of range or type.
     */
    public function scaleExact( $width, $height )
    {
        if ( !is_int( $height ) || $height < 1 )
        {
            throw new ezcBaseValueException( 'height', $height, 'int > 0' );
        }
        if ( !is_int( $width ) || $width < 1 )
        {
            throw new ezcBaseValueException( 'width', $width, 'int > 0' );
        }
        $this->addFilterOption(
            $this->getActiveReference(),
            '-resize',
            $width.'!x'.$height.'!'
        );
    }

    /**
     * Crop filter.
     * Crop an image to a given size. This takes cartesian coordinates of a
     * rect area to crop from the image. The cropped area will replace the old
     * image resource (not the input image immediately, if you use the
     * {@link ezcImageConverter}).  Coordinates are given as integer values and
     * are measured from the top left corner.
     *
     * ATTENTION: Using this filter method directly results in the filter being 
     * applied to the image which is internally marked as "active" (most 
     * commonly this is the last recently loaded one). It is highly recommended
     * to apply filters through the {@link ezcImageImagemagickHandler::applyFilter()}
     * method, which enables you to specify the image a filter is applied to.
     *
     * @param int $x      X offset of the cropping area.
     * @param int $y      Y offset of the cropping area.
     * @param int $width  Width of cropping area.
     * @param int $height Height of cropping area.
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         No loaded file could be found or an error destroyed a loaded reference.
     * @throws ezcBaseValueException
     *         If a submitted parameter was out of range or type.
     */
    public function crop( $x, $y, $width, $height )
    {
        if ( !is_int( $x ) )
        {
            throw new ezcBaseValueException( 'x', $x, 'int' );
        }
        if ( !is_int( $y ) )
        {
            throw new ezcBaseValueException( 'y', $y, 'int' );
        }
        if ( !is_int( $height ) )
        {
            throw new ezcBaseValueException( 'height', $height, 'int' );
        }
        if ( !is_int( $width ) )
        {
            throw new ezcBaseValueException( 'width', $width, 'int' );
        }

        $data = getimagesize( $this->getActiveResource() );
        $x = ( $x >= 0 ) ? $x : $data[0] + $x;
        $y = ( $y >= 0 ) ? $y : $data[1] + $y;

        $xStart = ( $xStart = min( $x, $x + $width ) ) >= 0 ? '+'.$xStart : $xStart;
        $yStart = ( $yStart = min( $y, $y + $height ) ) >= 0 ? '+'.$yStart : $yStart;
        $this->addFilterOption(
            $this->getActiveReference(),
            '-crop ',
            abs( $width ).'x'.abs( $height ).$xStart.$yStart.'!'
        );
    }

    /**
     * Colorspace filter.
     * Transform the color space of the picture. The following color space are
     * supported:
     *
     * - {@link self::COLORSPACE_GREY} - 255 grey colors
     * - {@link self::COLORSPACE_SEPIA} - Sepia colors
     * - {@link self::COLORSPACE_MONOCHROME} - 2 colors black and white
     *
     * ATTENTION: Using this filter method directly results in the filter being 
     * applied to the image which is internally marked as "active" (most 
     * commonly this is the last recently loaded one). It is highly recommended
     * to apply filters through the {@link ezcImageImagemagickHandler::applyFilter()}
     * method, which enables you to specify the image a filter is applied to.
     *
     * @param int $space Colorspace, one of self::COLORSPACE_* constants.
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         No loaded file could be found or an error destroyed a loaded reference
     * @throws ezcBaseValueException
     *         If the parameter submitted as the colorspace was not within the
     *         self::COLORSPACE_* constants.
     */
    public function colorspace( $space )
    {
        switch ( $space )
        {
            case self::COLORSPACE_GREY:
                $this->addFilterOption(
                    $this->getActiveReference(),
                    '-colorspace',
                    'GRAY'
                );
                $this->addFilterOption(
                    $this->getActiveReference(),
                    '-colors',
                    '255'
                );
                break;
            case self::COLORSPACE_MONOCHROME:
                $this->addFilterOption(
                    $this->getActiveReference(),
                    '-monochrome'
                );
                break;
            case self::COLORSPACE_SEPIA:
                $this->addFilterOption(
                    $this->getActiveReference(),
                    '-sepia-tone',
                    '80%'
                );
                break;
            return;
            default:
                throw new ezcBaseValueException( 'space', $space, 'self::COLORSPACE_GREY, self::COLORSPACE_SEPIA, self::COLORSPACE_MONOCHROME' );
                break;
        }
    }

    /**
     * Noise filter.
     * Apply a noise transformation to the image. Valid values are the following
     * strings:
     * - 'Uniform'
     * - 'Gaussian'
     * - 'Multiplicative'
     * - 'Impulse'
     * - 'Laplacian'
     * - 'Poisson'
     *
     * ATTENTION: Using this filter method directly results in the filter being 
     * applied to the image which is internally marked as "active" (most 
     * commonly this is the last recently loaded one). It is highly recommended
     * to apply filters through the {@link ezcImageImagemagickHandler::applyFilter()}
     * method, which enables you to specify the image a filter is applied to.
     *
     * @param strings $value Noise value as described above.
     * @return void
     *
     * @throws ezcBaseValueException
     *         If the noise value is out of range.
     * @throws ezcImageInvalidReferenceException
     *         No loaded file could be found or an error destroyed a loaded reference.
     */
    public function noise( $value )
    {
        $value = ucfirst( strtolower( $value ) );
        $possibleValues = array(
           'Uniform',
           'Gaussian',
           'Multiplicative',
           'Impulse',
           'Laplacian',
           'Poisson',
        );
        if ( !in_array( $value, $possibleValues ) )
        {
            throw new ezcBaseValueException( 'value', $value, 'Uniform, Gaussian, Multiplicative, Impulse, Laplacian, Poisson' );
        }
        $this->addFilterOption(
            $this->getActiveReference(),
            '+noise',
            $value
        );
    }

    /**
     * Swirl filter.
     * Applies a swirl with the given intense to the image.
     *
     * ATTENTION: Using this filter method directly results in the filter being 
     * applied to the image which is internally marked as "active" (most 
     * commonly this is the last recently loaded one). It is highly recommended
     * to apply filters through the {@link ezcImageImagemagickHandler::applyFilter()}
     * method, which enables you to specify the image a filter is applied to.
     *
     * @param int $value Intense of swirl.
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         No loaded file could be found or an error destroyed a loaded reference.
     * @throws ezcBaseValueException
     *         If the swirl value is out of range.
     */
    public function swirl( $value )
    {
        if ( !is_int( $value ) || $value < 0 )
        {
            throw new ezcBaseValueException( 'value', $value, 'int >= 0' );
        }
        $this->addFilterOption(
            $this->getActiveReference(),
            '-swirl',
            $value
        );
    }

    /**
     * Border filter.
     * Adds a border to the image. The width is measured in pixel. The color is
     * defined in an array of hex values:
     *
     * <code>
     * array(
     *      0 => <red value>,
     *      1 => <green value>,
     *      2 => <blue value>,
     * );
     * </code>
     *
     * ATTENTION: Using this filter method directly results in the filter being 
     * applied to the image which is internally marked as "active" (most 
     * commonly this is the last recently loaded one). It is highly recommended
     * to apply filters through the {@link ezcImageImagemagickHandler::applyFilter()}
     * method, which enables you to specify the image a filter is applied to.
     *
     * @param int $width        Width of the border.
     * @param array(int) $color Color.
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         No loaded file could be found or an error destroyed a loaded reference.
     * @throws ezcBaseValueException
     *         If a submitted parameter was out of range or type.
     */
    public function border( $width, array $color )
    {
        if ( !is_int( $width ) )
        {
            throw new ezcBaseValueException( 'width', $width, 'int' );
        }
        $colorString = $this->colorArrayToString( $color );
        $this->addFilterOption(
            $this->getActiveReference(),
            '-bordercolor',
            $colorString
        );
        $this->addFilterOption(
            $this->getActiveReference(),
            '-border',
            $width
        );
    }
    
    /**
     * Watermark filter.
     * Places a watermark on the image. The file to use as the watermark image
     * is given as $image. The $posX, $posY and $size values are given in
     * percent, related to the destination image. A $size value of 10 will make
     * the watermark appear in 10% of the destination image size.
     * $posX = $posY = 10 will make the watermark appear in the top left corner
     * of the destination image, 10% of its size away from its borders. If
     * $size is ommitted, the watermark image will not be resized.
     *
     * @param string $image  The image file to use as the watermark
     * @param int $posX      X position in the destination image in percent.
     * @param int $posY      Y position in the destination image in percent.
     * @param int|bool $size Percentage size of the watermark, false for none.
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         If no valid resource for the active reference could be found.
     * @throws ezcImageFilterFailedException
     *         If the operation performed by the the filter failed.
     * @throws ezcBaseValueException
     *         If a submitted parameter was out of range or type.
     */
    public function watermarkPercent( $image, $posX, $posY, $size = false )
    {
        if ( !is_string( $image ) || !file_exists( $image ) || !is_readable( $image ) ) 
        {
            throw new ezcBaseValueException( 'image', $image, 'string, path to an image file' );
        }
        if ( !is_int( $posX ) || $posX < 0 || $posX > 100 )
        {
            throw new ezcBaseValueException( 'posX', $posX, 'int percentage value' );
        }
        if ( !is_int( $posY ) || $posY < 0 || $posY > 100 )
        {
            throw new ezcBaseValueException( 'posY', $posY, 'int percentage value' );
        }
        if ( !is_bool( $size ) && ( !is_int( $size ) || $size < 0 || $size > 100 ) )
        {
            throw new ezcBaseValueException( 'size', $size, 'int percentage value / bool' );
        }

        $data = getimagesize( $this->getReferenceData( $this->getActiveReference(), "resource" ) );

        $originalWidth = $data[0];
        $originalHeight = $data[1];

        $watermarkWidth = false;
        $watermarkHeight = false;
        
        if ( $size !== false )
        {
            $watermarkWidth = (int) round( $originalWidth * $size / 100 );
            $watermarkHeight = (int) round( $originalHeight * $size / 100 );
        }

        $watermarkPosX = (int) round( $originalWidth * $posX / 100 );
        $watermarkPosY = (int) round( $originalHeight * $posY / 100 );

        $this->watermarkAbsolute( $image, $watermarkPosX, $watermarkPosY, $watermarkWidth, $watermarkHeight );

    }

    /**
     * Watermark filter.
     * Places a watermark on the image. The file to use as the watermark image
     * is given as $image. The $posX, $posY and $size values are given in
     * pixel. The watermark appear at $posX, $posY in the destination image
     * with a size of $size pixel. If $size is ommitted, the watermark image
     * will not be resized.
     *
     * @param string $image    The image file to use as the watermark
     * @param int $posX        X position in the destination image in pixel.
     * @param int $posY        Y position in the destination image in pixel.
     * @param int|bool $width  Pixel size of the watermark, false to keep size.
     * @param int|bool $height Pixel size of the watermark, false to keep size.
     * @return void
     *
     * @throws ezcImageInvalidReferenceException
     *         If no valid resource for the active reference could be found.
     * @throws ezcImageFilterFailedException
     *         If the operation performed by the the filter failed.
     * @throws ezcBaseValueException
     *         If a submitted parameter was out of range or type.
     */
    public function watermarkAbsolute( $image, $posX, $posY, $width = false, $height = false )
    {
        if ( !is_string( $image ) || !file_exists( $image ) || !is_readable( $image ) )
        {
            throw new ezcBaseValueException( 'image', $image, 'string, path to an image file' );
        }
        if ( !is_int( $posX ) )
        {
            throw new ezcBaseValueException( 'posX', $posX, 'int' );
        }
        if ( !is_int( $posY ) )
        {
            throw new ezcBaseValueException( 'posY', $posY, 'int' );
        }
        if ( !is_int( $width ) && !is_bool( $width ) )
        {
            throw new ezcBaseValueException( 'width', $width, 'int/bool' );
        }
        if ( !is_int( $height ) && !is_bool( $height ) )
        {
            throw new ezcBaseValueException( 'height', $height, 'int/bool' );
        }

        $data = getimagesize( $this->getActiveResource() );

        // Negative offsets
        $posX = ( $posX >= 0 ) ? $posX : $data[0] + $posX;
        $posY = ( $posY >= 0 ) ? $posY : $data[1] + $posY;

        $this->addFilterOption(
            $this->getActiveReference(),
            '-composite',
            '' 
        );

        $this->addFilterOption(
            $this->getActiveReference(),
            '-geometry',
            ( $width !== false ? $width : "" ) . ( $height !== false ? "x$height" : "" ) . "+$posX+$posY"
        );

        $this->addCompositeImage( $this->getActiveReference(), $image );
    }
    
    /**
     * Creates a thumbnail, and crops parts of the given image to fit the range best.
     * This filter creates a thumbnail of the given image. The image is scaled
     * down, keeping the original ratio and keeping the image larger as the
     * given range, if necessary. Overhead for the target range is cropped from
     * both sides equally.
     *
     * If you are looking for a filter that just resizes your image to
     * thumbnail size, you should consider the {@link
     * ezcImageImagemagickHandler::scale()} filter.
     * 
     * @param int $width  Width of the thumbnail.
     * @param int $height Height of the thumbnail.
     */
    public function croppedThumbnail( $width, $height )
    {
        if ( !is_int( $width )  || $width < 1 )
        {
            throw new ezcBaseValueException( 'width', $width, 'int > 0' );
        }
        if ( !is_int( $height )  || $height < 1 )
        {
            throw new ezcBaseValueException( 'height', $height, 'int > 0' );
        }
        $data = getimagesize( $this->getReferenceData( $this->getActiveReference(), "resource" ) );
        
        $scaleRatio  = max( $width / $data[0], $height / $data[1] );
        $scaleWidth  = round( $data[0]  * $scaleRatio );
        $scaleHeight = round( $data[1] * $scaleRatio );
        
        $cropOffsetX = ( $scaleWidth > $width )   ? "+" . round( ( $scaleWidth - $width ) / 2 )   : "+0";
        $cropOffsetY = ( $scaleHeight > $height ) ? "+" . round( ( $scaleHeight - $height ) / 2 ) : "+0";

        $this->addFilterOption(
            $this->getActiveReference(),
            '-resize',
            $scaleWidth . "x" . $scaleHeight
        );
        $this->addFilterOption(
            $this->getActiveReference(),
            '-crop',
            $width . "x" . $height . $cropOffsetX . $cropOffsetY . "!"
        );
    }

    /**
     * Creates a thumbnail, and fills up the image to fit the given range.
     * This filter creates a thumbnail of the given image. The image is scaled
     * down, keeping the original ratio and scaling the image smaller as the
     * given range, if necessary. Overhead for the target range is filled with the given
     * color on both sides equally.
     *
     * The color is defined by the following array format (integer values 0-255):
     *
     * <code>
     * array( 
     *      0 => <red value>,
     *      1 => <green value>,
     *      2 => <blue value>,
     * );
     * </code>
     *
     * If you are looking for a filter that just resizes your image to
     * thumbnail size, you should consider the {@link
     * ezcImageImagemagickHandler::scale()} filter.
     * 
     * @param int $width  Width of the thumbnail.
     * @param int $height Height of the thumbnail.
     * @param array $color Fill color.
     * @return void
     */
    public function filledThumbnail( $width, $height, $color = array() )
    {
        if ( !is_int( $width )  || $width < 1 )
        {
            throw new ezcBaseValueException( 'width', $width, 'int > 0' );
        }
        if ( !is_int( $height )  || $height < 1 )
        {
            throw new ezcBaseValueException( 'height', $height, 'int > 0' );
        }
        $data = getimagesize( $this->getReferenceData( $this->getActiveReference(), "resource" ) );
        
        $scaleRatio  = min( $width / $data[0], $height / $data[1] );
        $scaleWidth  = round( $data[0]  * $scaleRatio );
        $scaleHeight = round( $data[1] * $scaleRatio );
        
        $cropOffsetX = ( $scaleWidth < $width )   ? "-" . round( ( $width - $scaleWidth ) / 2 )   : "+0";
        $cropOffsetY = ( $scaleHeight < $height ) ? "-" . round( ( $height - $scaleHeight ) / 2 ) : "+0";

        $colorString = '#';
        $i = 0;
        foreach ( $color as $id => $colorVal )
        {
            if ( $i++ > 2 )
            {
                break;
            }
            if ( !is_int( $colorVal )  || $colorVal < 0 || $colorVal > 255 )
            {
                throw new ezcBaseValueException( "color[$id]", $color[$id], 'int > 0 and < 256' );
            }
            $colorString .= sprintf( '%02x', $colorVal );
        }
        
        $this->addFilterOption(
            $this->getActiveReference(),
            '-resize',
            $width . "x" . $height
        );
        $this->addFilterOption(
            $this->getActiveReference(),
            '-crop',
            $width . "x" . $height . $cropOffsetX . $cropOffsetY . "!"
        );
        $this->addFilterOption(
            $this->getActiveReference(),
            '-background',
            $colorString
        );
        $this->addFilterOption(
            $this->getActiveReference(),
            '-flatten'
        );
    }

    /**
     * Returns the ImageMagick direction modifier for a direction constant.
     * ImageMagick supports the following modifiers to determine if an
     * image should be scaled up only, down only or in both directions:
     *
     * <code>
     *  SCALE_UP:   >
     *  SCALE_DOWN: <
     * </code>
     *
     * This method returns the correct modifier for the internal direction
     * constants.
     *
     * @param int $direction One of ezcImageGeometryFilters::SCALE_*
     * @return string The correct modifier.
     *
     * @throws ezcBaseValueException
     *         If a submitted parameter was out of range or type.
     */
    protected function getDirectionModifier( $direction )
    {
        $dirMod = '';
        switch ( $direction )
        {
            case self::SCALE_DOWN:
                $dirMod = '>';
                break;
            case self::SCALE_UP:
                $dirMod = '<';
                break;
            case self::SCALE_BOTH:
                $dirMod = '';
                break;
            default:
                throw new ezcBaseValueException( 'direction', $direction, 'self::SCALE_BOTH, self::SCALE_UP, self::SCALE_DOWN' );
                break;
        }
        return $dirMod;
    }
}
?>
