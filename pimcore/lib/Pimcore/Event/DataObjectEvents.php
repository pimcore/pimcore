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
     *
     * @Event("Pimcore\Event\Model\DataObjectEvent")
     *
     * @var string
     */
    const POST_UPDATE = 'pimcore.dataobject.postUpdate';

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
     *  - base_element | Pimcore\Model\Document | contains the base document used in copying process
     *
     * @Event("Pimcore\Event\Model\DataObjectEvent")
     *
     * @var string
     */
    const POST_COPY = 'pimcore.dataobject.postCopy';
}
