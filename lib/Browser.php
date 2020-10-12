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

namespace Pimcore;

@trigger_error('The "\Pimcore\Browser" library will be removed in Pimcore 7.0.0. use \Browser directly', E_USER_DEPRECATED);

/**
 * @deprecated since 6.8.0, use \Browser directly, will be removed in Pimcore 7.0.0
 */
class Browser extends \Browser
{
}
