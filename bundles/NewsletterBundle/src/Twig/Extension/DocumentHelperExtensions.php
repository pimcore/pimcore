<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\NewsletterBundle\Twig\Extension;

use Pimcore\Bundle\NewsletterBundle\Model\Document\Newsletter;
use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

/**
 * @internal
 */
class DocumentHelperExtensions extends AbstractExtension
{
    public function getTests(): array
    {
        return [
            new TwigTest('pimcore_document_newsletter', static function ($object) {
                return $object instanceof Newsletter;
            }),
        ];
    }
}
