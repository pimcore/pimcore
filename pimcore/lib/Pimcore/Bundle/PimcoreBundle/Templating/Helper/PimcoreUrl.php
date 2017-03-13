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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Templating\Helper\Helper;

class PimcoreUrl extends Helper
{
    /**
     * @var UrlGeneratorInterface
     */
    protected $generator;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * @param UrlGeneratorInterface $generator
     */
    public function __construct(UrlGeneratorInterface $generator, RequestHelper $requestHelper)
    {
        $this->generator     = $generator;
        $this->requestHelper = $requestHelper;
    }

    /**
     * @param array $urlOptions
     * @param null $name
     * @param bool $reset
     * @param bool $encode
     * @param bool $relative
     * @return string
     */
    public function __invoke(array $urlOptions = [], $name = null, $reset = false, $encode = true, $relative = false)
    {

        // merge all parameters from request to parameters
        if (!$reset) {
            $urlOptions = array_replace($_GET, $urlOptions);
        }

        return $this->generateUrl($name, $urlOptions, $relative ? UrlGeneratorInterface::RELATIVE_PATH : UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    /**
     * Generate URL with support to only pass parameters ZF1 style (defaults to current route).
     *
     * @param null $name
     * @param array $parameters
     * @param int $referenceType
     * @return string
     */
    protected function generateUrl($name = null, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        // if name is an array, treat it as parameters
        if (is_array($name)) {
            if (is_array($parameters)) {
                $parameters = array_merge($name, $parameters);
            } else {
                $parameters = $name;
            }

            $name = null;
        }

        // get name from current route
        if ($name === null) {
            $name = $this->requestHelper->getCurrentRequest()->attributes->get('_route');
        }

        return $this->generator->generate($name, $parameters, $referenceType);
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName()
    {
        return "pimcoreUrl";
    }
}
