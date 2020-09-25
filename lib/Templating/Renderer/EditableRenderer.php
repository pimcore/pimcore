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

namespace Pimcore\Templating\Renderer;

use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Model\Document\Editable;
use Pimcore\Model\Document\Editable\Loader\EditableLoaderInterface;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Templating\Model\ViewModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class EditableRenderer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var EditableLoaderInterface
     *
     * @deprecated since v6.8 and will be removed in 7. Use $editableLoader instead.
     */
    protected $tagLoader;

    /**
     * @var Editable\Loader\EditableLoader
     */
    protected $editableLoader;

    /**
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    /**
     * @param EditableLoaderInterface $editableLoader
     * @param EditmodeResolver $editmodeResolver
     */
    public function __construct(EditableLoaderInterface $editableLoader, EditmodeResolver $editmodeResolver)
    {
        $this->editableLoader = $editableLoader;
        $this->tagLoader = & $this->editableLoader;
        $this->editmodeResolver = $editmodeResolver;
    }

    /**
     * @param string $type
     *
     * @return bool
     *
     * @deprecated since v6.8 and will be removed in 7. Use editableExists() instead.
     */
    public function tagExists($type)
    {
        return $this->editableExists($type);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function editableExists($type)
    {
        return $this->editableLoader->supports($type);
    }

    /**
     * Loads a document tag
     *
     * @param PageSnippet $document
     * @param string $type
     * @param string $inputName
     * @param array $options
     * @param bool|null $editmode
     *
     * @return Editable|null
     *
     * @deprecated since v6.8 and will be removed in 7. Use editableExists() instead.
     */
    public function getTag(PageSnippet $document, $type, $inputName, array $options = [], bool $editmode = null)
    {
        return $this->getEditable($document, $type, $inputName, $options, $editmode);
    }

    /**
     * Loads a document tag
     *
     * @param PageSnippet $document
     * @param string $type
     * @param string $inputName
     * @param array $config
     * @param bool|null $editmode
     *
     * @throws \Exception
     *
     * @return Editable|null
     */
    public function getEditable(PageSnippet $document, $type, $inputName, array $config = [], bool $editmode = null)
    {
        $type = strtolower($type);

        $name = Editable::buildEditableName($type, $inputName, $document);
        $realName = Editable::buildEditableRealName($inputName, $document);

        if (null === $editmode) {
            $editmode = $this->editmodeResolver->isEditmode();
        }

        try {
            $editable = null;

            if ($document instanceof PageSnippet) {
                $view = new ViewModel([
                    'editmode' => $editmode,
                    'document' => $document,
                ]);

                $editable = $document->getEditable($name);

                // @TODO: BC layer, to be removed in v7.0
                $aliases = [
                    'href' => 'relation',
                    'multihref' => 'relations',
                ];
                if (isset($aliases[$type])) {
                    $type = $aliases[$type];
                }

                if ($editable instanceof Editable && $editable->getType() === $type) {
                    // call the load() method if it exists to reinitialize the data (eg. from serializing, ...)
                    if (method_exists($editable, 'load')) {
                        $editable->load();
                    }

                    $editable->setView($view);
                    $editable->setEditmode($editmode);
                    $editable->setConfig($config);
                    $editable->setDocument($document);
                } else {
                    $editable = Editable::factory($type, $name, $document->getId(), $config, null, $view, $editmode);
                    $document->setEditable($name, $editable);
                }

                // set the real name of this editable, without the prefixes and suffixes from blocks and areablocks
                $editable->setRealName($realName);
            }

            return $editable;
        } catch (\Exception $e) {
            $this->logger->warning($e);

            return null;
        }
    }

    /**
     * Renders an editable
     *
     * @param PageSnippet $document
     * @param string $type
     * @param string $inputName
     * @param array $options
     * @param bool|null $editmode
     *
     * @return Editable|string
     */
    public function render(PageSnippet $document, $type, $inputName, array $options = [], bool $editmode = null)
    {
        $editable = $this->getEditable($document, $type, $inputName, $options, $editmode);

        if ($editable) {
            return $editable;
        }

        return '';
    }
}

class_alias(EditableRenderer::class, 'Pimcore\Templating\Renderer\TagRenderer');
