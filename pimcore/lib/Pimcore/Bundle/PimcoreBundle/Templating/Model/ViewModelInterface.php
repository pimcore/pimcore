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

namespace Pimcore\Bundle\PimcoreBundle\Templating\Model;

use Symfony\Component\HttpFoundation\ParameterBag;

interface ViewModelInterface extends \Countable, \IteratorAggregate, \ArrayAccess, \JsonSerializable
{
    /**
     * @return ParameterBag
     */
    public function getParameters();

    /**
     * Get parameter value
     *
     * @param string $key
     * @param mixed|null $default
     * @return bool
     */
    public function get($key, $default = null);

    /**
     * Check if parameter is set
     *
     * @param string $key
     * @return bool
     */
    public function has($key);
}
