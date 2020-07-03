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

namespace Pimcore\Twig\Extension;

use Pimcore\Templating\Helper\Action;
use Pimcore\Templating\Helper\Inc;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SubrequestExtension extends AbstractExtension
{
    /**
     * @var Inc
     */
    protected $incHelper;

    /**
     * @var Action
     */
    protected $actionHelper;

    /**
     * @param Inc $incHelper
     * @param Action $actionHelper
     */
    public function __construct(Inc $incHelper, Action $actionHelper)
    {
        $this->incHelper = $incHelper;
        $this->actionHelper = $actionHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        // as helpers are invokablem, we can pass them directly as callable
        return [
            new TwigFunction('pimcore_inc', $this->incHelper, [
                'is_safe' => ['html'],
            ]),

            new TwigFunction('pimcore_action', $this->actionHelper, [
                'is_safe' => ['html'],
            ]),
        ];
    }
}
