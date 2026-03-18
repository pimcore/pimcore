<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\SeoBundle\Event;

final class RedirectEvents
{
    /**
     * @Event("Pimcore\Event\Model\RedirectEvent")
     *
     * @var string
     */
    const PRE_SAVE = 'pimcore.redirect.preSave';

    /**
     * @Event("Pimcore\Event\Model\RedirectEvent")
     *
     * @var string
     */
    const POST_SAVE = 'pimcore.redirect.postSave';

    /**
     * @Event("Pimcore\Event\Model\RedirectEvent")
     *
     * @var string
     */
    const PRE_DELETE = 'pimcore.redirect.preDelete';

    /**
     * @Event("Pimcore\Event\Model\RedirectEvent")
     *
     * @var string
     */
    const POST_DELETE = 'pimcore.redirect.postDelete';

    /**
     * @Event("Pimcore\Event\Model\RedirectEvent")
     *
     * @var string
     */
    const PRE_BUILD = 'pimcore.redirect.preBuild';
}
