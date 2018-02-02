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

final class BundleManagerEvents
{
    /**
     * The CSS_PATHS event is triggered for paths to CSS files which are about to be loaded for the admin interface.
     *
     * @Event("Pimcore\Event\BundleManager\PathsEvent")
     *
     * @var string
     */
    const CSS_PATHS = 'pimcore.bundle_manager.paths.css';

    /**
     * The JS_PATHS event is triggered for paths to JS files which are about to be loaded for the admin interface.
     *
     * @Event("Pimcore\Event\BundleManager\PathsEvent")
     *
     * @var string
     */
    const JS_PATHS = 'pimcore.bundle_manager.paths.js';

    /**
     * The EDITMODE_CSS_PATHS event is triggered for paths to CSS files which are about to be loaded in editmode.
     *
     * @Event("Pimcore\Event\BundleManager\PathsEvent")
     *
     * @var string
     */
    const EDITMODE_CSS_PATHS = 'pimcore.bundle_manager.paths.editmode_css';

    /**
     * The EDITMODE_JS_PATHS event is triggered for paths to JS files which are about to be loaded in editmode.
     *
     * @Event("Pimcore\Event\BundleManager\PathsEvent")
     *
     * @var string
     */
    const EDITMODE_JS_PATHS = 'pimcore.bundle_manager.paths.editmode_js';
}
