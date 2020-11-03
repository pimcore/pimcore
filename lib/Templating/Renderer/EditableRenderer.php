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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class EditableRenderer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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
        $this->editmodeResolver = $editmodeResolver;
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
     * @param PageSnippet $document
     * @param string $type
     * @param string $inputName
     * @param array $config
     * @param bool|null $editmode
     * @return Editable\EditableInterface
     * @throws \Exception
     */
    public function getEditable(PageSnippet $document, string $type, string $inputName, array $config = [], bool $editmode = null): Editable\EditableInterface
    {
        $type = strtolower($type);

        $name = Editable::buildEditableName($type, $inputName, $document);
        $realName = Editable::buildEditableRealName($inputName, $document);

        if (null === $editmode) {
            $editmode = $this->editmodeResolver->isEditmode();
        }

        $editable = $document->getEditable($name);
        if ($editable instanceof Editable\EditableInterface && $editable->getType() === $type) {
            // call the load() method if it exists to reinitialize the data (eg. from serializing, ...)
            if (method_exists($editable, 'load')) {
                $editable->load();
            }

            $editable->setEditmode($editmode);
            $editable->setConfig($config);
            $editable->setDocument($document);
        } else {
            $editable = Editable::factory($type, $name, $document->getId(), $config, null, null, $editmode);
            $document->setEditable($editable);
        }

        // set the real name of this editable, without the prefixes and suffixes from blocks and areablocks
        $editable->setRealName($realName);

        return $editable;
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
     * @return mixed
     * @throws \Exception
     */
    public function render(PageSnippet $document, $type, $inputName, array $options = [], bool $editmode = null)
    {
        return $this->getEditable($document, $type, $inputName, $options, $editmode);
    }
}
