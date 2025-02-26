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

namespace Pimcore\Model\Element;

use Pimcore;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Symfony\Contracts\Translation\TranslatorInterface;

class AdminStyle
{
    protected string|bool|null $elementCssClass = '';

    protected string|bool|null $elementIcon = null;

    protected string|bool|null $elementIconClass = null;

    protected ?array $elementQtipConfig = null;

    protected ?string $elementText = null;

    public function __construct(ElementInterface $element)
    {
        $this->setElementText($element->getKey());
        if ($element instanceof AbstractObject) {
            if ($element instanceof Folder) {
                $this->elementIconClass = 'pimcore_icon_folder';
                $this->elementQtipConfig = [
                    'title' => 'ID: ' . $element->getId(),
                ];
            } elseif ($element instanceof Concrete) {
                if ($element->getClass()->getIcon()) {
                    $this->elementIcon = $element->getClass()->getIcon();
                } else {
                    $this->elementIconClass = $element->getType() === 'variant' ? 'pimcore_icon_variant' : 'pimcore_icon_object';
                }

                $this->elementQtipConfig = [
                    'title' => 'ID: ' . $element->getId(),
                    'text' => 'Type: ' . $element->getClass()->getName(),
                ];
            }
        } elseif ($element instanceof Asset) {
            $this->elementQtipConfig = [
                'title' => 'ID: ' . $element->getId(),
            ];

            if ($element->getType() === 'folder') {
                $this->elementIconClass = 'pimcore_icon_folder';
            } else {
                $this->elementIconClass = 'pimcore_icon_asset_default';

                $fileExt = pathinfo($element->getFilename(), PATHINFO_EXTENSION);
                if ($fileExt) {
                    $this->elementIconClass .= ' pimcore_icon_' . strtolower(pathinfo($element->getFilename(), PATHINFO_EXTENSION));
                }
            }
        } elseif ($element instanceof Document) {
            $this->elementQtipConfig = [
                'title' => 'ID: ' . $element->getId(),
                'text' => 'Type: ' . $element->getType(),
            ];

            $this->elementIconClass = 'pimcore_icon_' . $element->getType();

            // set type specific settings
            if ($element->getType() === 'page') {
                $site = Site::getByRootId($element->getId());

                if ($site instanceof Site) {
                    $translator = Pimcore::getContainer()->get(TranslatorInterface::class);
                    $this->elementQtipConfig['text'] .= '<br>' . $translator->trans('site_id', [], 'admin') . ': ' . $site->getId();
                }

                $this->elementIconClass = 'pimcore_icon_page';

                if ($element instanceof Document\Page && $element->getStaticGeneratorEnabled()) {
                    $this->elementIconClass = 'pimcore_icon_page_static';
                }

                // test for a site
                if ($site = Site::getByRootId($element->getId())) {
                    $this->elementIconClass = 'pimcore_icon_site';
                }
            } elseif ($element->getType() === 'folder' || $element->getType() === 'link' || $element->getType() === 'hardlink') {
                if (!$element->hasChildren() && $element->getType() == 'folder') {
                    $this->elementIconClass = 'pimcore_icon_folder';
                }
            }
        }
    }

    public function setElementCssClass(bool|string|null $elementCssClass): static
    {
        $this->elementCssClass = $elementCssClass;

        return $this;
    }

    public function appendElementCssClass(string $elementCssClass): static
    {
        $this->elementCssClass .= ' ' . $elementCssClass;

        return $this;
    }

    public function getElementCssClass(): bool|string|null
    {
        return $this->elementCssClass;
    }

    public function setElementIcon(bool|string|null $elementIcon): static
    {
        $this->elementIcon = $elementIcon;

        return $this;
    }

    /**
     * @return string|bool|null Return false if you don't want to overwrite the default.
     */
    public function getElementIcon(): bool|string|null
    {
        return $this->elementIcon;
    }

    public function setElementIconClass(bool|string|null $elementIconClass): static
    {
        $this->elementIconClass = $elementIconClass;

        return $this;
    }

    /**
     * @return string|bool|null Return false if you don't want to overwrite the default.
     */
    public function getElementIconClass(): bool|string|null
    {
        return $this->elementIconClass;
    }

    public function getElementQtipConfig(): ?array
    {
        return $this->elementQtipConfig;
    }

    public function setElementQtipConfig(?array $elementQtipConfig): void
    {
        $this->elementQtipConfig = $elementQtipConfig;
    }

    public function getElementText(): ?string
    {
        return $this->elementText;
    }

    public function setElementText(?string $elementText): void
    {
        $this->elementText = $elementText;
    }
}
