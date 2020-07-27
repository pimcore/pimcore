<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Webservice
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Webservice\Data;

use Pimcore\Model;

/**
 * @deprecated
 */
class ClassDefinition extends Model\Webservice\Data
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    /**
     * @var int
     */
    public $creationDate;

    /**
     * @var int
     */
    public $modificationDate;

    /**
     * @var int
     */
    public $userOwner;

    /**
     * @var int
     */
    public $userModification;

    /**
     * Name of the parent class if set
     *
     * @var string
     */
    public $parentClass;

    /**
     * Name of the parent listing class if set
     *
     * @var string
     */
    public $listingParentClass;

    /**
     * Name of the traits to use if set
     *
     * @var string
     */
    public $useTraits;

    /**
     * Name of the listing traits to use if set
     *
     * @var string
     */
    public $listingUseTraits;

    /**
     * @var bool
     */
    public $allowInherit = false;

    /**
     * @var bool
     */
    public $allowVariants = false;

    /**
     * @var bool
     */
    public $showVariants = false;

    /**
     * @var bool
     */
    public $generateTypeDeclarations = false;

    /**
     * @var string
     */
    public $implementsInterfaces;

    /**
     * @var Model\DataObject\ClassDefinition\Data[]
     */
    public $fieldDefinitions = [];

    /**
     * @var Model\DataObject\ClassDefinition\Layout|null
     */
    public $layoutDefinitions;

    /**
     * @var string
     */
    public $icon;

    /**
     * @var string
     */
    public $previewUrl;

    /**
     * @var string
     */
    public $group;

    /**
     * @var bool
     */
    public $showAppLoggerTab = false;

    /**
     * @var string
     */
    public $linkGeneratorReference;

    /**
     * @var array
     */
    public $compositeIndices;

    /**
     * @var bool
     */
    public $showFieldLookup = false;
}
