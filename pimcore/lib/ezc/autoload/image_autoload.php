<?php
/**
 * Autoloader definition for the ImageConversion component.
 *
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version 1.3.8
 * @filesource
 * @package ImageConversion
 */

return array(
    'ezcImageException'                            => 'ImageConversion/exceptions/exception.php',
    'ezcImageFileNameInvalidException'             => 'ImageConversion/exceptions/file_name_invalid.php',
    'ezcImageFileNotProcessableException'          => 'ImageConversion/exceptions/file_not_processable.php',
    'ezcImageFilterFailedException'                => 'ImageConversion/exceptions/filter_failed.php',
    'ezcImageFilterNotAvailableException'          => 'ImageConversion/exceptions/filter_not_available.php',
    'ezcImageHandlerNotAvailableException'         => 'ImageConversion/exceptions/handler_not_available.php',
    'ezcImageHandlerSettingsInvalidException'      => 'ImageConversion/exceptions/handler_settings_invalid.php',
    'ezcImageInvalidFilterParameterException'      => 'ImageConversion/exceptions/invalid_filter_parameter.php',
    'ezcImageInvalidReferenceException'            => 'ImageConversion/exceptions/invalid_reference.php',
    'ezcImageMimeTypeUnsupportedException'         => 'ImageConversion/exceptions/mime_type_unsupported.php',
    'ezcImageMissingFilterParameterException'      => 'ImageConversion/exceptions/missing_filter_parameter.php',
    'ezcImageTransformationAlreadyExistsException' => 'ImageConversion/exceptions/transformation_already_exists.php',
    'ezcImageTransformationException'              => 'ImageConversion/exceptions/transformation.php',
    'ezcImageTransformationNotAvailableException'  => 'ImageConversion/exceptions/transformation_not_available.php',
    'ezcImageHandler'                              => 'ImageConversion/interfaces/handler.php',
    'ezcImageMethodcallHandler'                    => 'ImageConversion/interfaces/methodcall_handler.php',
    'ezcImageColorspaceFilters'                    => 'ImageConversion/interfaces/colorspace.php',
    'ezcImageEffectFilters'                        => 'ImageConversion/interfaces/effect.php',
    'ezcImageGdBaseHandler'                        => 'ImageConversion/handlers/gd_base.php',
    'ezcImageGeometryFilters'                      => 'ImageConversion/interfaces/geometry.php',
    'ezcImageImagemagickBaseHandler'               => 'ImageConversion/handlers/imagemagick_base.php',
    'ezcImageThumbnailFilters'                     => 'ImageConversion/interfaces/thumbnail.php',
    'ezcImageWatermarkFilters'                     => 'ImageConversion/interfaces/watermark.php',
    'ezcImageConverter'                            => 'ImageConversion/converter.php',
    'ezcImageConverterSettings'                    => 'ImageConversion/structs/converter_settings.php',
    'ezcImageFilter'                               => 'ImageConversion/structs/filter.php',
    'ezcImageGdHandler'                            => 'ImageConversion/handlers/gd.php',
    'ezcImageHandlerSettings'                      => 'ImageConversion/structs/handler_settings.php',
    'ezcImageImagemagickHandler'                   => 'ImageConversion/handlers/imagemagick.php',
    'ezcImageSaveOptions'                          => 'ImageConversion/options/save_options.php',
    'ezcImageTransformation'                       => 'ImageConversion/transformation.php',
);
?>
