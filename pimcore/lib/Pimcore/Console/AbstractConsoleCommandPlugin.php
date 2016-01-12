<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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
