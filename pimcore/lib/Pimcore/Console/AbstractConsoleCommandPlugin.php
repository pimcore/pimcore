<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
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
