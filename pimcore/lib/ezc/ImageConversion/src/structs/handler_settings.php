<?php
/**
 * File containing the ezcImageHandlerSettings struct.
 *
 * @package ImageConversion
 * @version 1.3.8
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @filesource
 */

/**
 * Struct to store the settings for objects of ezcImageHandler.
 *
 * This class is used as a struct for the settings of ezcImageHandler
 * subclasses.
 *
 * @see ezcImageHandler
 *
 * @package ImageConversion
 * @version 1.3.8
 */
class ezcImageHandlerSettings extends ezcBaseStruct
{
    /**
     * The reference name for the handler.
     * This name can be used when referencing the handler in certain operations
     * in the {@link ezcImageConverter converter} class.
     *
     * e.g. 'GD' and 'ImageMagick'.
     *
     * @var string
     */
    public $referenceName;

    /**
     * Name of the class to instantiate as image handler.
     *
     * Note: This class must be a subclass of the {@link ezcImageHandler} class.
     *
     * @var string
     */
    public $className;

    /**
     * Associative array of misc options for the handler.
     * These options will be read by the handler class and varies from handler
     * to handler. Consult the handler class for the available settings.
     *
     * The options array has the following structure:
     * <code>
     * array(
     *     <optionName> => <optionValue>,
     *     [ <optionName> => <optionValue>, ...]
     * )
     * </code>
     *
     * @var array
     */
    public $options = array();

    /**
     * Initialize settings to be used by image handler.
     * The settings passed as parameter will be read by the
     * {@link ezcImageConverter converter} to figure out which image handler to
     * use and then passed to the {@link ezcImageHandler image handler objects}.
     *
     * @see ezcImageHandlerSettings::$referenceName
     * @see ezcImageHandlerSettings::$className
     * @see ezcImageHandlerSettings::$settings
     *
     * @param string $referenceName
     *        The reference name for the handler, e.g. 'GD' or 'ImageMagick'
     * @param string $className
     *        The name of the handler class to instantiate, e.g.
     *        'ezcImageGdHandler' or 'ezcImageImagemagickHandler'
     * @param array  $options
     *        Associative array of settings for the handler.
     */
    public function __construct( $referenceName, $className, array $options = array() )
    {
        $this->referenceName = $referenceName;
        $this->className     = $className;
        $this->options       = $options;
    }
}
?>
