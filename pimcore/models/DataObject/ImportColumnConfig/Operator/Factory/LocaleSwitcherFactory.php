<?php

declare(strict_types=1);

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

namespace Pimcore\Model\DataObject\ImportColumnConfig\Operator\Factory;

use Pimcore\Localization\Locale;
use Pimcore\Model\DataObject\ImportColumnConfig\Operator\LocaleSwitcher;
use Pimcore\Model\DataObject\ImportColumnConfig\OperatorInterface;

class LocaleSwitcherFactory implements OperatorFactoryInterface
{
    /**
     * @var Locale
     */
    private $localeService;

    public function __construct(Locale $localeService)
    {
        $this->localeService = $localeService;
    }

    public function build(\stdClass $configElement, $context = null): OperatorInterface
    {
        return new LocaleSwitcher($this->localeService, $configElement, $context);
    }
}
