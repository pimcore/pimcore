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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Twig\Extension;

use Pimcore\Model\Document;
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
            new TwigTest('pimcore_document', static function ($object) {
                return $object instanceof Document;
            }),
            new TwigTest('pimcore_document_email', static function ($object) {
                return $object instanceof Document\Email;
            }),
            new TwigTest('pimcore_document_folder', static function ($object) {
                return $object instanceof Document\Folder;
            }),
            new TwigTest('pimcore_document_hardlink', static function ($object) {
                return $object instanceof Document\Hardlink;
            }),
            new TwigTest('pimcore_document_newsletter', static function ($object) {
                return $object instanceof Document\Newsletter;
            }),
            new TwigTest('pimcore_document_page', static function ($object) {
                return $object instanceof Document\Page;
            }),
            new TwigTest('pimcore_document_link', static function ($object) {
                return $object instanceof Document\Link;
            }),
            new TwigTest('pimcore_document_page_snippet', static function ($object) {
                return $object instanceof Document\PageSnippet;
            }),
            new TwigTest('pimcore_document_print', static function ($object) {
                return $object instanceof Document\PrintAbstract;
            }),
            new TwigTest('pimcore_document_print_container', static function ($object) {
                return $object instanceof Document\Printcontainer;
            }),
            new TwigTest('pimcore_document_print_page', static function ($object) {
                return $object instanceof Document\Printpage;
            }),
            new TwigTest('pimcore_document_snippet', static function ($object) {
                return $object instanceof Document\Snippet;
            }),
        ];
    }
}
