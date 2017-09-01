<?php

namespace AppBundle\Templating;

use Pimcore\Model\Document;
use Pimcore\Model\Document\Service;
use Pimcore\Tool;

class LanguageSwitcher
{
    /**
     * @var Service
     */
    protected $documentService;

    /**
     * @param Service $documentService
     */
    public function __construct(Service $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * @param Document $document
     *
     * @return array
     */
    public function getLocalizedLinks(Document $document)
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
