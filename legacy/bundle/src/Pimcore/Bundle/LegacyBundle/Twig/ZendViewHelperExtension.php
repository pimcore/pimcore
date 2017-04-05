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

namespace Pimcore\Bundle\LegacyBundle\Twig;

use Pimcore\Bundle\LegacyBundle\Zend\View\ViewHelperBridge;

class ZendViewHelperExtension extends \Twig_Extension
{
    /**
     * @var ViewHelperBridge
     */
    protected $viewHelperBridge;

    /**
     * @param ViewHelperBridge $zendViewHelperBridge
     */
    public function __construct(ViewHelperBridge $zendViewHelperBridge)
    {
        $this->viewHelperBridge = $zendViewHelperBridge;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('zend_*', [$this, 'zendViewHelper'], [
                'is_safe'     => ['html'],
                'is_variadic' => true
            ])
        ];
    }

    // HACK HACK the ignore filter is just a hack until i found out how to register the function
    // above so that it can be called with {% %} instead of {{ }}
    public function getFilters()
    {
        return [
            // ignore the output
            new \Twig_Filter('ignore', function($input) {
                return '';
            })
        ];
    }

    /**
     * @param $name
     * @param array $arguments
     * @return string
     */
    public function zendViewHelper($name, array $arguments = [])
    {
        return $this->viewHelperBridge->execute($name, $arguments);
    }
}
