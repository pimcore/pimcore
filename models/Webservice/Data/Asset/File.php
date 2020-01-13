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

namespace Pimcore\Model\Webservice\Data\Asset;

use Pimcore\Model;

/**
 * @deprecated
 */
class File extends Model\Webservice\Data\Asset
{
    /**
     * @var string
     */
    public $data;

    /**
     * @param Model\Asset $object
     * @param array|null $options
     */
    public function map($object, $options = null)
    {
        parent::map($object, $options);
        if (is_array($options)) {
            if ($options['LIGHT']) {
                return;
            }
        }
        $this->data = base64_encode($object->getData());
    }

    /**
     * @param Model\Asset $object
     * @param bool $disableMappingExceptions
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     */
    public function reverseMap($object, $disableMappingExceptions = false, $idMapper = null)
    {
        $data = base64_decode($this->data);
        unset($this->data);
        parent::reverseMap($object, $disableMappingExceptions, $idMapper);
        $object->setData($data);
    }
}
