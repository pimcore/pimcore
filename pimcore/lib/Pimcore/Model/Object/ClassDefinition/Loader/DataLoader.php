<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition\Loader;

use Pimcore\Loader\ImplementationLoader\ImplementationLoader;
use Pimcore\Model\Object\ClassDefinition\Data;

class DataLoader extends ImplementationLoader implements DataLoaderInterface
{
    /**
     * @inheritDoc
     */
    protected function init()
    {
        $this->initClassMap();

        $normalizer = function ($name) {
            return ucfirst($name);
        };

        $this->prefixLoader->addPrefix('\\Pimcore\\Model\\Object\\ClassDefinition\\Data\\', $normalizer);
        $this->prefixLoader->addPrefix('\\Object_Class_Data', $normalizer);
    }

    /**
     * Init static class map for performance reasons.
     */
    protected function initClassMap()
    {
        $classMap = [
            'block'               => 'Pimcore\Model\Object\ClassDefinition\Data\Block',
            'calculatedValue'     => 'Pimcore\Model\Object\ClassDefinition\Data\CalculatedValue',
            'checkbox'            => 'Pimcore\Model\Object\ClassDefinition\Data\Checkbox',
            'classificationstore' => 'Pimcore\Model\Object\ClassDefinition\Data\Classificationstore',
            'country'             => 'Pimcore\Model\Object\ClassDefinition\Data\Country',
            'countrymultiselect'  => 'Pimcore\Model\Object\ClassDefinition\Data\Countrymultiselect',
            'date'                => 'Pimcore\Model\Object\ClassDefinition\Data\Date',
            'datetime'            => 'Pimcore\Model\Object\ClassDefinition\Data\Datetime',
            'email'               => 'Pimcore\Model\Object\ClassDefinition\Data\Email',
            'externalImage'       => 'Pimcore\Model\Object\ClassDefinition\Data\ExternalImage',
            'fieldcollections'    => 'Pimcore\Model\Object\ClassDefinition\Data\Fieldcollections',
            'firstname'           => 'Pimcore\Model\Object\ClassDefinition\Data\Firstname',
            'gender'              => 'Pimcore\Model\Object\ClassDefinition\Data\Gender',
            'geobounds'           => 'Pimcore\Model\Object\ClassDefinition\Data\Geobounds',
            'geopoint'            => 'Pimcore\Model\Object\ClassDefinition\Data\Geopoint',
            'geopolygon'          => 'Pimcore\Model\Object\ClassDefinition\Data\Geopolygon',
            'hotspotimage'        => 'Pimcore\Model\Object\ClassDefinition\Data\Hotspotimage',
            'href'                => 'Pimcore\Model\Object\ClassDefinition\Data\Href',
            'image'               => 'Pimcore\Model\Object\ClassDefinition\Data\Image',
            'input'               => 'Pimcore\Model\Object\ClassDefinition\Data\Input',
            'language'            => 'Pimcore\Model\Object\ClassDefinition\Data\Language',
            'languagemultiselect' => 'Pimcore\Model\Object\ClassDefinition\Data\Languagemultiselect',
            'lastname'            => 'Pimcore\Model\Object\ClassDefinition\Data\Lastname',
            'link'                => 'Pimcore\Model\Object\ClassDefinition\Data\Link',
            'localizedfields'     => 'Pimcore\Model\Object\ClassDefinition\Data\Localizedfields',
            'multihref'           => 'Pimcore\Model\Object\ClassDefinition\Data\Multihref',
            'multihrefMetadata'   => 'Pimcore\Model\Object\ClassDefinition\Data\MultihrefMetadata',
            'multiselect'         => 'Pimcore\Model\Object\ClassDefinition\Data\Multiselect',
            'newsletterActive'    => 'Pimcore\Model\Object\ClassDefinition\Data\NewsletterActive',
            'nonownerobjects'     => 'Pimcore\Model\Object\ClassDefinition\Data\Nonownerobjects',
            'numeric'             => 'Pimcore\Model\Object\ClassDefinition\Data\Numeric',
            'objectbricks'        => 'Pimcore\Model\Object\ClassDefinition\Data\Objectbricks',
            'objects'             => 'Pimcore\Model\Object\ClassDefinition\Data\Objects',
            'objectsMetadata'     => 'Pimcore\Model\Object\ClassDefinition\Data\ObjectsMetadata',
            'password'            => 'Pimcore\Model\Object\ClassDefinition\Data\Password',
            'persona'             => 'Pimcore\Model\Object\ClassDefinition\Data\Persona',
            'personamultiselect'  => 'Pimcore\Model\Object\ClassDefinition\Data\Personamultiselect',
            'quantityValue'       => 'Pimcore\Model\Object\ClassDefinition\Data\QuantityValue',
            'select'              => 'Pimcore\Model\Object\ClassDefinition\Data\Select',
            'slider'              => 'Pimcore\Model\Object\ClassDefinition\Data\Slider',
            'structuredTable'     => 'Pimcore\Model\Object\ClassDefinition\Data\StructuredTable',
            'table'               => 'Pimcore\Model\Object\ClassDefinition\Data\Table',
            'textarea'            => 'Pimcore\Model\Object\ClassDefinition\Data\Textarea',
            'time'                => 'Pimcore\Model\Object\ClassDefinition\Data\Time',
            'user'                => 'Pimcore\Model\Object\ClassDefinition\Data\User',
            'video'               => 'Pimcore\Model\Object\ClassDefinition\Data\Video',
            'wysiwyg'             => 'Pimcore\Model\Object\ClassDefinition\Data\Wysiwyg',
        ];

        $this->classMapLoader->setClassMap(array_merge($this->classMapLoader->getClassMap(), $classMap));
    }

    /**
     * @inheritDoc
     */
    public function build(string $name, array $params = []) : Data
    {
        return parent::build($name, $params);
    }
}
