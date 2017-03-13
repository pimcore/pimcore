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

namespace Pimcore\Bundle\PimcoreBundle\Templating\Helper;

use Pimcore\Http\RequestHelper;
use Symfony\Component\Templating\Helper\Helper;

class GetAllParams extends Helper
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
        return 'GetAllParams';
    }

    /**
     * @return array
     */
    public function __invoke()
    {
        $request = $this->requestHelper->getCurrentRequest();
        return array_merge($request->request->all(), $request->query->all());
    }
}
