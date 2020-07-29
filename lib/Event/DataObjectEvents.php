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

final class DataObjectEvents
{
    /**
     * @Event("Pimcore\Event\Model\DataObjectEvent")
     *
     * @var string
     */
    const PRE_ADD = 'pimcore.dataobject.preAdd';

    /**
     * @Event("Pimcore\Event\Model\DataObjectEvent")
     *
     * @var string
     */
    const POST_ADD = 'pimcore.dataobject.postAdd';

    /**
     * Arguments:
     *  - exception | exception object
     *
     * @Event("Pimcore\Event\Model\DataObjectEvent")
     *
     * @var string
     */
    const POST_ADD_FAILURE = 'pimcore.dataobject.postAddFailure';

    /**
     * Arguments:
     *  - saveVersionOnly | is set if method saveVersion() was called instead of save()
     *
     * @Event("Pimcore\Event\Model\DataObjectEvent")
     *
     * @var string
     */
    const PRE_UPDATE = 'pimcore.dataobject.preUpdate';

    /**
     * Arguments:
     *  - saveVersionOnly | is set if method saveVersion() was called instead of save()
     *  - oldPath | the old full path in case the path has changed
     *
     * @Event("Pimcore\Event\Model\DataObjectEvent")
     *
     * @var string
     */
    const POST_UPDATE = 'pimcore.dataobject.postUpdate';

    /**
     * Arguments:
     *  - saveVersionOnly | is set if method saveVersion() was called instead of save()
     *  - exception | exception object
     *
     * @Event("Pimcore\Event\Model\DataObjectEvent")
     *
     * @var string
     */
    const POST_UPDATE_FAILURE = 'pimcore.dataobject.postUpdateFailure';

    /**
     * @Event("Pimcore\Event\Model\DataObjectDeleteInfoEvent")
     *
     * @var string
     */
    const DELETE_INFO = 'pimcore.dataobject.deleteInfo';

    /**
     * @Event("Pimcore\Event\Model\DataObjectEvent")
     *
     * @var string
     */
    const PRE_DELETE = 'pimcore.dataobject.preDelete';

    /**
     * @Event("Pimcore\Event\Model\DataObjectEvent")
     *
     * @var string
     */
    const POST_DELETE = 'pimcore.dataobject.postDelete';

    /**
     * Arguments:
     *  - exception | exception object
     *
     * @Event("Pimcore\Event\Model\DataObjectEvent")
     *
     * @var string
     */
    const POST_DELETE_FAILURE = 'pimcore.dataobject.postDeleteFailure';

    /**
     * Arguments:
     *  - base_element | Pimcore\Model\Document | contains the base document used in copying process
     *
     * @Event("Pimcore\Event\Model\DataObjectEvent")
     *
     * @var string
     */
    const POST_COPY = 'pimcore.dataobject.postCopy';

    /**
     * Arguments:
     *  - objectData | array | contains the export data of the object
     *  - context | array | context information - default ['source' => 'pimcore-export']
     *  - requestedLanguage | string | requested language
     *  - helperDefinitions | array | containing the column definition from the grid view
     *  - localeService | \Pimcore\Localization\LocaleService
     *  - returnMappedFieldNames | bool | if "true" the objectData is an associative array, otherwise it is an indexed array
     *
     * @Event("Pimcore\Event\Model\DataObjectEvent")
     *
     * @var string
     */
    const POST_CSV_ITEM_EXPORT = 'pimcore.dataobject.postCsvItemExport';
}
