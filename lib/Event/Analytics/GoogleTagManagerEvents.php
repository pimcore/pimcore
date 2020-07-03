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

namespace Pimcore\Event\Analytics;

final class GoogleTagManagerEvents
{
    /**
     * Triggered before the tag manager head code block is rendered. Can be used to add additional code
     * snippets to the head code.
     *
     * @Event("Pimcore\Event\Analytics\Google\TagManager\CodeEvent")
     *
     * @var string
     */
    const CODE_HEAD = 'pimcore.analytics.google.tag_manager.code_head';

    /**
     * Triggered before the tag manager body code is rendered. Can be used to add additional code
     * snippets to the body code.
     *
     * @Event("Pimcore\Event\Analytics\Google\TagManager\CodeEvent")
     *
     * @var string
     */
    const CODE_BODY = 'pimcore.analytics.google.tag_manager.code_body';
}
