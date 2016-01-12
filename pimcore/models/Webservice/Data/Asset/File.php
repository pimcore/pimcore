<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Webservice
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Webservice\Data\Asset;

use Pimcore\Model;

class File extends Model\Webservice\Data\Asset {
    
    /**
     * @var string
     */
    public $data;
    
    
    public function map ($object, $options = null) {
        parent::map($object, $options);
        if (is_array($options)) {
            if ($options["LIGHT"]) {
                return;
            }
        }
        $this->data = base64_encode($object->getData());
    }


    public function reverseMap($object, $disableMappingExceptions = false, $idMapper = null) {

        $data = base64_decode($this->data);
        unset($this->data);
        parent::reverseMap($object, $disableMappingExceptions, $idMapper);
        $object->setData($data);

    }
   
}
