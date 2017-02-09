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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\PimcoreZendBundle\Templating\Zend\Helper;

use Pimcore\Tool\RequestHelper;
use Zend\View\Helper\AbstractHelper;

class GetParam extends AbstractHelper
{
    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * @param RequestHelper $requestHelper
     */
    public function __construct(RequestHelper $requestHelper)
    {
        $this->requestHelper = $requestHelper;
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    public function __invoke($name, $default = null)
    {
        return $this->requestHelper->getCurrentRequest()->get($name, $default);
    }
}
