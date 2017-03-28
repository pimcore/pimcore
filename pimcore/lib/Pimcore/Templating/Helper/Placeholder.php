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

/**
 * ----------------------------------------------------------------------------------
 * based on @author ZF1 Zend_View_Helper_Placeholder
 * ----------------------------------------------------------------------------------
 */

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Pimcore\Templating\Helper;

use Pimcore\Templating\Helper\Placeholder\AbstractHelper;
use Pimcore\Templating\Helper\Placeholder\Container;
use Pimcore\Templating\Helper\Placeholder\ContainerService;

/**
 * Helper for passing data between otherwise segregated Views. It's called
 * Placeholder to make its typical usage obvious, but can be used just as easily
 * for non-Placeholder things. That said, the support for this is only
 * guaranteed to effect subsequently rendered templates, and of course Layouts.
 */
class Placeholder extends AbstractHelper
{
    /**
     * Registry key under which container registers itself
     * @var string
     */
    protected $_regKey = 'Placeholder';

    /**
     * @var ContainerService
     */
    protected $containerService;

    /**
     * @var Container[]
     */
    protected $containers = [];


    public function getName()
    {
        return 'placeholder';
    }

    /**
     * AbstractHelper constructor.
     * @param ContainerService $containerService
     * @internal param Container $container
     */
    public function __construct(ContainerService $containerService)
    {
        $this->containerService = $containerService;
    }

    /**
     * Retrieve object instance; optionally add meta tag
     *
     * @param  string $content
     * @param  string $keyValue
     * @param  string $keyType
     * @param  array $modifiers
     * @param  string $placement
     * @return Container
     */
    public function __invoke($containerName = null)
    {
        $containerName = (string) $containerName;
        if (empty($this->containers[$containerName])) {
            $this->containers[$containerName] = $this->containerService->getContainer($this->_regKey . "_" . $containerName);
        }

        return $this->containers[$containerName];
    }
}
