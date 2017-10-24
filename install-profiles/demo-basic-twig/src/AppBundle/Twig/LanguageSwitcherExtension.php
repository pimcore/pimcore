<?php

declare(strict_types=1);

namespace AppBundle\Twig;

use Pimcore\Model\Document;
use Pimcore\Model\Document\Service;
use Pimcore\Tool;

class LanguageSwitcherExtension extends \Twig_Extension
{
    /**
     * @var Service|Service\Dao
     */
    private $documentService;

    public function __construct(Service $documentService)
    {
        $this->documentService = $documentService;
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

    /**
     * @inheritDoc
     */
    public function getFunctions(): array
    {
        return [
            new \Twig_Function('get_localized_links', [$this, 'getLocalizedLinks'])
        ];
    }
}
