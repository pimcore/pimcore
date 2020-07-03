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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Event;

final class DataObjectImportEvents
{
    /**
     * @Event("Pimcore\Event\Model\DataObject\DataObjectImportEvent")
     *
     * fired before the first data set is imported
     *
     * @var string
     */
    const BEFORE_START = 'pimcore.dataobject.import.beforestart';

    /**
     * @Event("Pimcore\Event\Model\DataObject\DataObjectImportEvent")
     *
     * fired before an object (row) gets saved
     *
     * @var string
     */
    const PRE_SAVE = 'pimcore.dataobject.import.preSave';

    /**
     * @Event("Pimcore\Event\Model\DataObject\DataObjectImportEvent")
     *
     * fired befere the preview data gets prepared
     *
     * @var string
     */
    const PREVIEW = 'pimcore.dataobject.import.preview';

    /**
     * @Event("Pimcore\Event\Model\DataObject\DataObjectImportEvent")
     *
     * fired after an object (row) gets saved
     *
     * @var string
     */
    const POST_SAVE = 'pimcore.dataobject.import.postSave';

    /**
     * @Event("Pimcore\Event\Model\DataObject\DataObjectImportEvent")
     *
     * fired after the entire import is done
     *
     * @var string
     */
    const DONE = 'pimcore.dataobject.import.done';
}
