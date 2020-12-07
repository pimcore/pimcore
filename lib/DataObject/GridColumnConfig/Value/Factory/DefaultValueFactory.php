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

namespace Pimcore\DataObject\GridColumnConfig\Value\Factory;

use Pimcore\DataObject\GridColumnConfig\Value\ValueInterface;
use Pimcore\Localization\LocaleServiceInterface;

class DefaultValueFactory implements ValueFactoryInterface
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var LocaleServiceInterface
     */
    private $localeService;

    public function __construct(string $className, LocaleServiceInterface $localeService)
    {
        $this->className = $className;
        $this->localeService = $localeService;
    }

    public function build(\stdClass $configElement, $context = null): ValueInterface
    {
        return new $this->className($configElement, $context, $this->localeService);
    }
}
