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

namespace AppBundle\Templating\Helper;

use Pimcore\Model\Document;
use Pimcore\Model\Document\Service;
use Pimcore\Tool;
use Symfony\Component\Templating\Helper\Helper;

class LanguageSwitcher extends Helper
{
    /**
     * @var Service|Service\Dao
     */
    private $documentService;

    public function __construct(Service $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'languageSwitcher';
    }

    public function getLocalizedLinks(Document $document): array
    {
        $translations = $this->documentService->getTranslations($document);

        $links = [];
        foreach (Tool::getValidLanguages() as $language) {
            $target = '/' . $language;

            if (isset($translations[$language])) {
                $localizedDocument = Document::getById($translations[$language]);
                if ($localizedDocument) {
                    $target = $localizedDocument->getFullPath();
                }
            }

            $links[$target] = \Locale::getDisplayLanguage($language);
        }

        return $links;
    }
}
