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

namespace Pimcore\Model\Webservice\Data\Document\Hardlink;

use Pimcore\Model;

class In extends Model\Webservice\Data\Document\Link {

    public function reverseMap($object, $disableMappingExceptions = false, $idMapper = null) {

        $sourceId = $this->sourceId;
        $this->sourceId = null;

        parent::reverseMap($object, $disableMappingExceptions, $idMapper);


        if ($idMapper) {
            $sourceId = $idMapper->getMappedId("document", $sourceId);
        }

        if ($idMapper) {
            $idMapper->recordMappingFailure("object", $object->getId(), "document", $sourceId);
        }

        $object->setSourceId = $sourceId;
    }


}
