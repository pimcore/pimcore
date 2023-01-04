<?php

namespace Pimcore\Bundle\WordExportBundle\Controller;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Document\Service;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * @Route("/translation")
 *
 */
class TranslationController extends AdminController
{
    /**
     * @Route("/word-export", name="pimcore_bundle_wordexport_translation_wordexport", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function wordExportAction(Request $request): JsonResponse
    {
        ini_set('display_errors', 'off');

        $id = $this->sanitzeExportId((string)$request->get('id'));
        $exportFile = $this->getExportFilePath($id, false);
        $data = $this->decodeJson($request->get('data'));
        $source = $request->get('source');

        if (!is_file($exportFile)) {
            File::put($exportFile, '');
        }

        foreach ($data as $el) {
            try {
                $element = \Pimcore\Model\Element\Service::getElementById($el['type'], $el['id']);
                $output = '';

                // check supported types (subtypes)
                if (!in_array($element->getType(), ['page', 'snippet', 'email', 'object'])) {
                    continue;
                }

                if ($element instanceof ElementInterface) {
                    $output .= '<h1 class="element-headline">' . ucfirst(
                            $element->getType()
                        ) . ' - ' . $element->getRealFullPath() . ' (ID: ' . $element->getId() . ')</h1>';
                }

                if ($element instanceof PageSnippet) {
                    if ($element instanceof Page) {
                        $structuredDataEmpty = true;
                        $structuredData = '
                            <table border="1" cellspacing="0" cellpadding="5">
                                <tr>
                                    <td colspan="2"><span style="color:#cc2929;font-weight: bold;">Structured Data</span></td>
                                </tr>
                        ';

                        if ($element->getTitle()) {
                            $structuredData .= '<tr>
                                    <td><span style="color:#cc2929;">Title</span></td>
                                    <td>' . $element->getTitle() . '&nbsp;</td>
                                </tr>';
                            $structuredDataEmpty = false;
                        }

                        if ($element->getDescription()) {
                            $structuredData .= '<tr>
                                    <td><span style="color:#cc2929;">Description</span></td>
                                    <td>' . $element->getDescription() . '&nbsp;</td>
                                </tr>';
                            $structuredDataEmpty = false;
                        }

                        if ($element->getProperty('navigation_name')) {
                            $structuredData .= '<tr>
                                    <td><span style="color:#cc2929;">Navigation</span></td>
                                    <td>' . $element->getProperty('navigation_name') . '&nbsp;</td>
                                </tr>';
                            $structuredDataEmpty = false;
                        }

                        $structuredData .= '</table>';

                        if (!$structuredDataEmpty) {
                            $output .= $structuredData;
                        }
                    }

                    // we need to set the parameter "pimcore_admin" here to be able to render unpublished documents
                    $html = Service::render($element, [], false, ['pimcore_admin' => true]);

                    $html = preg_replace(
                        '@</?(img|meta|div|section|aside|article|body|bdi|bdo|canvas|embed|footer|head|header|html)([^>]+)?>@',
                        '',
                        $html
                    );
                    $html = preg_replace('/<!--(.*)-->/Uis', '', $html);

                    $dom = new Tool\DomCrawler($html);
                    // remove containers including their contents
                    $elements = $dom->filter('form, script, style, noframes, noscript, object, area, mapm, video, audio, iframe, textarea, input, select, button');
                    foreach ($elements as $element) {
                        $element->parentNode->removeChild($element);
                    }

                    $clearText = function ($string) {
                        $string = str_replace("\r\n", '', $string);
                        $string = str_replace("\n", '', $string);
                        $string = str_replace("\r", '', $string);
                        $string = str_replace("\t", '', $string);
                        $string = preg_replace('/&[a-zA-Z0-9]+;/', '', $string); // remove html entities
                        $string = preg_replace('#[ ]+#', '', $string);

                        return $string;
                    };

                    // remove empty tags (where it matters)
                    // replace links => links get [Linktext]
                    $elements = $dom->filter('a');
                    foreach ($elements as $element) {
                        $string = $clearText($element->textContent);
                        if (!empty($string)) {
                            $newNode = $element->ownerDocument->createTextNode('[' . $element->textContent . ']');

                            $element->parentNode->replaceChild($newNode, $element);
                        } else {
                            $element->ownerDocument->textContent = '';
                        }
                    }

                    if ($dom->count() > 0) {
                        $html = $dom->html();
                    }

                    $dom->clear();
                    unset($dom);

                    // force closing tags
                    $doc = new \DOMDocument();
                    libxml_use_internal_errors(true);
                    $doc->loadHTML('<?xml encoding="UTF-8"><article>' . $html . '</article>');
                    libxml_clear_errors();
                    $html = $doc->saveHTML();

                    $bodyStart = strpos($html, '<body>');
                    $bodyEnd = strpos($html, '</body>');
                    if ($bodyStart && $bodyEnd) {
                        $html = substr($html, $bodyStart + 6, $bodyEnd - $bodyStart);
                    }

                    $output .= $html;
                } elseif ($element instanceof DataObject\Concrete) {
                    $hasContent = false;

                    /** @var DataObject\ClassDefinition\Data\Localizedfields|null $fd */
                    $fd = $element->getClass()->getFieldDefinition('localizedfields');
                    if ($fd) {
                        $definitions = $fd->getFieldDefinitions();

                        $locale = str_replace('-', '_', $source);
                        if (!Tool::isValidLanguage($locale)) {
                            $locale = \Locale::getPrimaryLanguage($locale);
                        }

                        $output .= '
                            <table border="1" cellspacing="0" cellpadding="2">
                                <tr>
                                    <td colspan="2"><span style="color:#cc2929;font-weight: bold;">Localized Data</span></td>
                                </tr>
                        ';

                        foreach ($definitions as $definition) {
                            // check allowed datatypes
                            if (!in_array($definition->getFieldtype(), ['input', 'textarea', 'wysiwyg'])) {
                                continue;
                            }

                            $content = $element->{'get' . ucfirst($definition->getName())}($locale);

                            if (!empty($content)) {
                                $output .= '
                                <tr>
                                    <td><span style="color:#cc2929;">' . $definition->getTitle() . ' (' . $definition->getName() . ')<span></td>
                                    <td>' . $content . '&nbsp;</td>
                                </tr>
                                ';

                                $hasContent = true;
                            }
                        }

                        $output .= '</table>';
                    }

                    if (!$hasContent) {
                        $output = ''; // there's no content in the object, so reset all contents and do not inclide it in the export
                    }
                }

                // append contents
                if (!empty($output)) {
                    $f = fopen($exportFile, 'a+');
                    fwrite($f, $output);
                    fclose($f);
                }
            } catch (\Exception $e) {
                Logger::error('Word Export: ' . $e->getMessage());
                Logger::error((string) $e);

                throw $e;
            }
        }

        return $this->adminJson(
            [
                'success' => true,
            ]
        );
    }

    /**
     * @Route("/word-export-download", name="pimcore_bundle_wordexport_translation_wordexportdownload", methods={"GET"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function wordExportDownloadAction(Request $request): Response
    {
        $id = $this->sanitzeExportId((string)$request->get('id'));
        $exportFile = $this->getExportFilePath($id, true);

        // no conversion, output html file, works fine with MS Word and LibreOffice
        $content = file_get_contents($exportFile);
        @unlink($exportFile);

        // replace <script> and <link>
        $content = preg_replace('/<link[^>]+>/im', '$1', $content);
        $content = preg_replace("/<script[^>]+>(.*)?<\/script>/im", '$1', $content);

        $content =
            "<html>\n" .
            "<head>\n" .
            '<style type="text/css">' . "\n" .
            file_get_contents(PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/css/word-export.css') .
            "</style>\n" .
            "</head>\n\n" .
            "<body>\n" .
            $content .
            "\n\n</body>\n" .
            "</html>\n";

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/html');
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="word-export-' . date('Ymd') . '_' . uniqid() . '.htm"'
        );

        return $response;
    }

    /**
     * @Route("/content-export-jobs", name="pimcore_bundle_wordexport_translation_contentexportjobs", methods={"POST"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function contentExportJobsAction(Request $request, RouterInterface $router): JsonResponse
    {
        $data = $this->decodeJson($request->get('data'));
        $elements = [];
        $jobs = [];
        $exportId = uniqid();
        $source = $request->get('source', '');
        $target = $request->get('target', '');

        $source = str_replace('_', '-', $source);
        $target = str_replace('_', '-', $target);

        if ($data && is_array($data)) {
            foreach ($data as $element) {
                $elements[$element['type'] . '_' . $element['id']] = [
                    'id' => $element['id'],
                    'type' => $element['type'],
                ];

                $el = null;

                if ($element['children']) {
                    $el = \Pimcore\Model\Element\Service::getElementById($element['type'], $element['id']);
                    $baseClass = \Pimcore\Model\Element\Service::getBaseClassNameForElement($element['type']);
                    $listClass = '\\Pimcore\\Model\\' . $baseClass . '\\Listing';
                    $list = new $listClass();
                    $list->setUnpublished(true);
                    if ($el instanceof DataObject\AbstractObject) {
                        // inlcude variants
                        $list->setObjectTypes(
                            [DataObject::OBJECT_TYPE_VARIANT,
                                DataObject::OBJECT_TYPE_OBJECT,
                                DataObject::OBJECT_TYPE_FOLDER, ]
                        );
                    }
                    $list->setCondition(
                        'path LIKE ?',
                        [$list->escapeLike($el->getRealFullPath() . ($el->getRealFullPath() != '/' ? '/' : '')) . '%']
                    );
                    $children = $list->load();

                    foreach ($children as $child) {
                        $childId = $child->getId();
                        $elements[$element['type'] . '_' . $childId] = [
                            'id' => $childId,
                            'type' => $element['type'],
                        ];

                        if (isset($element['relations']) && $element['relations']) {
                            $childDependencies = $child->getDependencies()->getRequires();
                            foreach ($childDependencies as $cd) {
                                if ($cd['type'] == 'object' || $cd['type'] == 'document') {
                                    $elements[$cd['type'] . '_' . $cd['id']] = $cd;
                                }
                            }
                        }
                    }
                }

                if (isset($element['relations']) && $element['relations']) {
                    if (!$el instanceof ElementInterface) {
                        $el = \Pimcore\Model\Element\Service::getElementById($element['type'], $element['id']);
                    }

                    $dependencies = $el->getDependencies()->getRequires();
                    foreach ($dependencies as $dependency) {
                        if ($dependency['type'] == 'object' || $dependency['type'] == 'document') {
                            $elements[$dependency['type'] . '_' . $dependency['id']] = $dependency;
                        }
                    }
                }
            }
        }

        $elements = array_values($elements);

        // the word export can only handle one document per request
        // the problem is Document\Service::render(), ... in the action can be a $this->redirect() or exit;
        // nobody knows what's happening in an action ;-) So we need to isolate them in isolated processes
        // so that the export doesn't stop completely after a "redirect" or any other unexpected behavior of an action
        $elementsPerJob = 1;

        // one job = X elements
        $elements = array_chunk($elements, $elementsPerJob);
        foreach ($elements as $chunk) {
            $jobs[] = [[
                'url' => $router->generate('pimcore_wordexport_translation_wordexport'),
                'method' => 'POST',
                'params' => [
                    'id' => $exportId,
                    'source' => $source,
                    'target' => $target,
                    'data' => $this->encodeJson($chunk),
                ],
            ]];
        }

        return $this->adminJson(
            [
                'success' => true,
                'jobs' => $jobs,
                'id' => $exportId,
            ]
        );
    }

    private function sanitzeExportId(string $id): string
    {
        if (empty($id) || !preg_match('/^[a-z0-9]+$/', $id)) {
            throw new BadRequestHttpException('Invalid export ID format');
        }

        return $id;
    }

    private function getExportFilePath(string $id, bool $checkExistence = true): string
    {
        // no need to check for path traversals here as sanitizeExportId restricted the ID parameter
        $exportFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . DIRECTORY_SEPARATOR . $id . '.html';

        if ($checkExistence && !file_exists($exportFile)) {
            throw $this->createNotFoundException(sprintf('Export file does not exist at path %s', $exportFile));
        }

        return $exportFile;
    }
}
