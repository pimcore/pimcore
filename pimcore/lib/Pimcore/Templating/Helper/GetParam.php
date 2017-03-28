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

namespace Pimcore\Templating\Helper;

use Pimcore\Http\RequestHelper;
use Symfony\Component\Templating\Helper\Helper;

class GetParam extends Helper
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
     * @inheritDoc
     */
    public function getName()
    {
        return 'getParam';
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
