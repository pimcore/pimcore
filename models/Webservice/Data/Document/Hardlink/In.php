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

namespace Pimcore\Model\Webservice\Data\Document\Hardlink;

use Pimcore\Model;

/**
 * @deprecated
 */
class In extends Model\Webservice\Data\Document\Link
{
    public $sourceId;

    /**
     * @param Model\Document\Hardlink $object
     * @param bool $disableMappingExceptions
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     */
    public function reverseMap($object, $disableMappingExceptions = false, $idMapper = null)
    {
        $sourceId = $this->sourceId;
        $this->sourceId = null;

        parent::reverseMap($object, $disableMappingExceptions, $idMapper);

        if ($idMapper) {
            $sourceId = $idMapper->getMappedId('document', $sourceId);
        }

        if ($idMapper) {
            $idMapper->recordMappingFailure('object', $object->getId(), 'document', $sourceId);
        }

        $object->setSourceId($sourceId);
    }
}
