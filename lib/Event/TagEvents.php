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

final class TagEvents
{
    /**
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    const PRE_ADD_TO_ELEMENT = 'pimcore.tag.preAddToElement';

    /**
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    const POST_ADD_TO_ELEMENT = 'pimcore.tag.postAddToElement';

    /**
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    const PRE_REMOVE_FROM_ELEMENT = 'pimcore.tag.preRemoveFromElement';

    /**
     * @Event("Pimcore\Event\Model\TagEvent")
     *
     * @var string
     */
    const POST_REMOVE_FROM_ELEMENT = 'pimcore.tag.postRemoveFromElement';

}
