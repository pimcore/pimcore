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

namespace Pimcore\Model\Document\Editable;

use Exception;
use Pimcore;
use Pimcore\Document\Editable\Block\BlockName;
use Pimcore\Document\Editable\EditableHandler;
use Pimcore\Extension\Document\Areabrick\AreabrickManagerInterface;
use Pimcore\Extension\Document\Areabrick\EditableDialogBoxInterface;
use Pimcore\Model;
use Pimcore\Templating\Renderer\EditableRenderer;
use Pimcore\Tool\HtmlUtils;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Area extends Model\Document\Editable
{
    /**
     * The Type configured for the area
     *
     * @internal
     *
     */
    protected ?string $type = null;

    public function getBrickType(): ?string
    {
        return $this->type;
    }

    public function getType(): string
    {
        return 'area';
    }

    public function getData(): mixed
    {
        return [
            'type' => $this->type,
        ];
    }

    public function getDataForResource(): array
    {
        return [
            'type' => $this->type,
        ];
    }

    public function getDataEditmode(): array
    {
        return [
            'type' => $this->type,
        ];
    }

    public function admin(): void
    {
        $attributes = $this->getEditmodeElementAttributes();
        $attributeString = HtmlUtils::assembleAttributeString($attributes);
        $this->outputEditmode('<div ' . $attributeString . '>');
        $this->frontend();

        $this->outputEditmode('</div>');
    }

    private function renderDialogBoxEditables(array|Model\Document\Editable $config, EditableRenderer $editableRenderer, string $dialogId): array
    {
        if ($config instanceof BlockInterface || $config instanceof Area) {
            // Unsupported element was passed (e.g., Block, Areablock, ...)
            // or an Areas was passed, which is not supported to avoid too long editable names
            throw new Exception(sprintf('Using editables of type "%s" for the editable dialog "%s" is not supported.', get_debug_type($config), $dialogId));
        } elseif ($config instanceof Model\Document\Editable) {
            // Map editable to array config
            $config = [
                'type' => $config->getType(),
                'name' => $config->getName(),
                'label' => $config->getLabel(),
                'config' => $config->getConfig(),
                'description' => $config->getDialogDescription(),
            ];
        }

        if (isset($config['items']) && is_array($config['items'])) {
            // layout component
            foreach ($config['items'] as $index => $child) {
                $config['items'][$index] = $this->renderDialogBoxEditables($child, $editableRenderer, $dialogId);
            }
        } elseif (isset($config['name']) && isset($config['type'])) {
            $editable = $editableRenderer->getEditable($this->getDocument(), $config['type'], $config['name'], $config['config'] ?? []);
            if (!$editable instanceof Model\Document\Editable) {
                throw new Exception(sprintf('Invalid editable type "%s" configured for Dialog Box', $config['type']));
            }

            $editable->setInDialogBox($dialogId);
            $editable->addConfig('dialogBoxConfig', $config);
            $this->outputEditmode($editable->render());
        } else {
            foreach ($config as $index => $item) {
                $config['items'][$index] = $this->renderDialogBoxEditables($item, $editableRenderer, $dialogId);
            }
        }

        return $config;
    }

    private function buildInfoObject(): Area\Info
    {
        $config = $this->getConfig();
        // create info object and assign it to the view
        $info = new Area\Info();
        $info->setId($config['type']);
        $info->setEditable($this);
        $info->setIndex(0);

        $params = [];
        if (is_array($config['params'][$config['type']] ?? null)) {
            $params = $config['params'][$config['type']];
        }

        if (is_array($config['globalParams'] ?? null)) {
            $params = array_merge($config['globalParams'], $params);
        }

        $info->setParams($params);

        return $info;
    }

    public function frontend(): void
    {
        $config = $this->getConfig();

        // TODO inject area handler via DI when editables are built by container
        $editableHandler = Pimcore::getContainer()->get(EditableHandler::class);

        // push current block name
        $blockState = $this->getBlockState();
        $blockState->pushBlock(BlockName::createFromEditable($this));

        // create info object and assign it to the view
        $info = $this->buildInfoObject();

        // start at first index
        $blockState->pushIndex(1);

        $areabrickManager = Pimcore::getContainer()->get(AreabrickManagerInterface::class);

        $dialogConfig = null;
        $brick = $areabrickManager->getBrick($this->config['type']);
        $info = $this->buildInfoObject();
        if ($this->getEditmode() && $brick instanceof EditableDialogBoxInterface) {
            $dialogConfig = $brick->getEditableDialogBoxConfiguration($this, $info);
            if ($dialogConfig->getItems()) {
                $dialogConfig->setId('dialogBox-' . $this->getName());
            } else {
                $dialogConfig = null;
            }
        }

        if ($dialogConfig) {
            $attributes = $this->getEditmodeElementAttributes();
            $dialogAttributes = [
                'data-dialog-id' => $dialogConfig->getId(),
            ];

            $dialogAttributes = HtmlUtils::assembleAttributeString($dialogAttributes);
            $this->outputEditmode('<div class="pimcore_area_dialog" data-name="' . $attributes['data-name'] . '" data-real-name="' . $attributes['data-real-name'] . '" ' . $dialogAttributes . '></div>');
        }

        $params = [];
        if (isset($config['params']) && is_array($config['params']) && array_key_exists($config['type'], $config['params'])) {
            if (is_array($config['params'][$config['type']])) {
                $params = $config['params'][$config['type']];
            }
        }

        $info->setParams($params);

        if ($dialogConfig) {
            $editableRenderer = Pimcore::getContainer()->get(EditableRenderer::class);
            $items = $this->renderDialogBoxEditables($dialogConfig->getItems(), $editableRenderer, $dialogConfig->getId());
            $dialogConfig->setItems($items);
            $this->outputEditmode('<template id="dialogBoxConfig-' . $dialogConfig->getId() . '">' . json_encode($dialogConfig) . '</template>');
        }

        echo $editableHandler->renderAreaFrontend($info);

        // remove current block and index from stack
        $blockState->popIndex();
        $blockState->popBlock();
    }

    public function setDataFromResource(mixed $data): static
    {
        $unserializedData = $this->getUnserializedData($data) ?? [];
        $this->type = $unserializedData['type'] ?? null;

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
    {
        if (is_array($data)) {
            $this->type = $data['type'] ?? null;
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return false;
    }

    /**
     * Gets an element from the referenced brick. E.g. if you have an area "myArea" which defines "gallery-single-images"
     * as used areabrick and this areabrick defines a block "gallery", you can use $area->getElement('gallery') to get
     * an instance of the block element.
     *
     *
     */
    public function getElement(string $name): ?Model\Document\Editable
    {
        $document = $this->getDocument();
        $parentBlockNames = $this->getParentBlockNames();
        $parentBlockNames[] = $this->getName();

        $id = Model\Document\Editable::buildChildEditableName($name, 'area', $parentBlockNames, 1);
        $editable = $document->getEditable($id);

        if ($editable) {
            $editable->setParentBlockNames($parentBlockNames);
        }

        return $editable;
    }
}
