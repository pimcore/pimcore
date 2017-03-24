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

namespace Pimcore\Extension\Document\Areabrick;

use Pimcore\Extension\Document\Areabrick\Exception\ConfigurationException;
use Pimcore\Model\Document\Tag\Area\Info;

abstract class AbstractAreabrick implements AreabrickInterface, TemplateAreabrickInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @inheritDoc
     */
    public function setId($id)
    {
        // make sure ID is only set once
        if (null !== $this->id) {
            throw new ConfigurationException(sprintf(
                'Brick ID is immutable (trying to set ID %s for brick %s)',
                $id,
                $this->id
            ));
        }

        $this->id = $id;
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->id ? ucfirst($this->id) : '';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getVersion()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function hasViewTemplate()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function hasEditTemplate()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getEditTemplate()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function action(Info $info)
    {
        // noop - implement as needed
    }

    /**
     * {@inheritdoc}
     */
    public function postRenderAction(Info $info)
    {
        // noop - implement as needed
    }

    /**
     * {@inheritdoc}
     */
    public function getHtmlTagOpen(Info $info)
    {
        return '<div class="pimcore_area_' . $info->getId() . ' pimcore_area_content">';
    }

    /**
     * {@inheritdoc}
     */
    public function getHtmlTagClose(Info $info)
    {
        return '</div>';
    }
}
