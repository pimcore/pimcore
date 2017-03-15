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

namespace Pimcore\Loader\ImplementationLoader;

interface LoaderInterface
{
    /**
     * Checks if implementation is supported
     *
     * @param string $name
     *
     * @return bool
     */
    public function supports($name);

    /**
     * Builds an implementation instance
     *
     * @param string $name
     * @param array  $params
     *
     * @return mixed
     */
    public function build($name, array $params = []);
}
