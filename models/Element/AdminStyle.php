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
 * @category   Pimcore
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element;

use Pimcore\File;
use Pimcore\Model;

class AdminStyle
{
    /**
     * @var string
     */
    protected $elementCssClass = '';

    /**
     * @var string
     */
    protected $elementIcon;

    /**
     * @var string
     */
    protected $elementIconClass;

    /**
     * @var array
     */
    protected $elementQtipConfig;

    /**
     * AdminStyle constructor.
     *
     * @param Model\DataObject\Concrete $element
     */
    public function __construct($element)
    {
        if ($element instanceof Model\DataObject\AbstractObject) {
            if ($element->getType() == 'folder') {
                $this->elementIconClass = 'pimcore_icon_folder';
                $this->elementQtipConfig = [
                    'title' => 'ID: ' . $element->getId(),
                ];
            } else {
                if ($element->getClass()->getIcon()) {
                    $this->elementIcon = $element->getClass()->getIcon();
                } else {
                    $this->elementIconClass = $element->getType() == 'variant' ? 'pimcore_icon_variant' : 'pimcore_icon_object';
                }

                $this->elementQtipConfig = [
                    'title' => 'ID: ' . $element->getId(),
                    'text' => 'Type: ' . $element->getClass()->getName(),
                ];
            }
        } elseif ($element instanceof Model\Asset) {
            $this->elementQtipConfig = [
                'title' => 'ID: ' . $element->getId(),
            ];

            if ($element->getType() == 'folder') {
                $this->elementIconClass = 'pimcore_icon_folder';
            } else {
                $this->elementIconClass = 'pimcore_icon_asset_default';

                $fileExt = File::getFileExtension($element->getFilename());
                if ($fileExt) {
                    $this->elementIconClass = ' pimcore_icon_' . File::getFileExtension($element->getFilename());
                }
            }
        } elseif ($element instanceof Model\Document) {
            $this->elementQtipConfig = [
                'title' => 'ID: ' . $element->getId(),
                'text' => 'Type: ' . $element->getType(),
            ];

            $this->elementIconClass = 'pimcore_icon_' . $element->getType();

            // set type specific settings
            if ($element->getType() == 'page') {
                $site = Model\Site::getByRootId($element->getId());

                if ($site instanceof Model\Site) {
                    $translator = \Pimcore::getContainer()->get('pimcore.translator');
                    $this->elementQtipConfig['text'] .= '<br>' . $translator->trans('site_id', [], 'admin') . ': ' . $site->getId();
                }

                $this->elementIconClass = 'pimcore_icon_page';

                // test for a site
                if ($site = Model\Site::getByRootId($element->getId())) {
                    $this->elementIconClass = 'pimcore_icon_site';
                }
            } elseif ($element->getType() == 'folder' || $element->getType() == 'link' || $element->getType() == 'hardlink') {
                if (!$element->hasChildren() && $element->getType() == 'folder') {
                    $this->elementIconClass = 'pimcore_icon_folder';
                }
            }
        }
    }

    /**
     * @param null|string $elementCssClass
     *
     * @return $this
     */
    public function setElementCssClass($elementCssClass)
    {
        $this->elementCssClass = $elementCssClass;

        return $this;
    }

    /**
     * @param string $elementCssClass
     *
     * @return $this
     */
    public function appendElementCssClass($elementCssClass)
    {
        $this->elementCssClass .= ' ' . $elementCssClass;

        return $this;
    }

    /**
     * @return string
     */
    public function getElementCssClass()
    {
        return $this->elementCssClass;
    }

    /**
     * @param null|string $elementIcon
     *
     * @return $this
     */
    public function setElementIcon($elementIcon)
    {
        $this->elementIcon = $elementIcon;

        return $this;
    }

    /**
     * @return string|bool|null Return false if you don't want to overwrite the default.
     */
    public function getElementIcon()
    {
        return $this->elementIcon;
    }

    /**
     * @param null|string $elementIconClass
     *
     * @return $this
     */
    public function setElementIconClass($elementIconClass)
    {
        $this->elementIconClass = $elementIconClass;

        return $this;
    }

    /**
     * @return string|bool|null Return false if you don't want to overwrite the default.
     */
    public function getElementIconClass()
    {
        return $this->elementIconClass;
    }

    /**
     * @return array|null
     */
    public function getElementQtipConfig()
    {
        return $this->elementQtipConfig;
    }

    /**
     * @param null|array $elementQtipConfig
     */
    public function setElementQtipConfig($elementQtipConfig)
    {
        $this->elementQtipConfig = $elementQtipConfig;
    }
}
