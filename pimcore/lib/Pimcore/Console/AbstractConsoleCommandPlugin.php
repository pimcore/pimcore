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

namespace Pimcore\Console;

use Pimcore\API\Plugin\AbstractPlugin;
use Pimcore\API\Plugin\PluginInterface;

/**
 * Base class for plugins providing CLI commands.
 */
abstract class AbstractConsoleCommandPlugin extends AbstractPlugin implements PluginInterface
{
    use ConsoleCommandPluginTrait;

    public function init()
    {
        parent::init();
        $this->initConsoleCommands();
    }
}
