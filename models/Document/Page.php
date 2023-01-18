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

namespace Pimcore\Model\Document;

use Pimcore\Messenger\GeneratePagePreviewMessage;
use Pimcore\Model\Redirect;
use Pimcore\Model\Tool\Targeting\TargetGroup;

/**
 * @method \Pimcore\Model\Document\Page\Dao getDao()
 */
class Page extends TargetingDocument
{
    /**
     * Contains the title of the page (meta-title)
     *
     * @internal
     *
     * @var string
     */
    protected string $title = '';

    /**
     * Contains the description of the page (meta-description)
     *
     * @internal
     *
     * @var string
     */
    protected string $description = '';

    /**
     * @internal
     *
     * @var array
     */
    protected array $metaData = [];

    /**
     * {@inheritdoc}
     */
    protected string $type = 'page';

    /**
     * @internal
     *
     * @var string|null
     */
    protected ?string $prettyUrl = null;

    /**
     * Comma separated IDs of target groups
     *
     * @internal
     *
     * @var string
     */
    protected string $targetGroupIds = '';

    /**
     * {@inheritdoc}
     */
    protected function doDelete(): void
    {
        // check for redirects pointing to this document, and delete them too
        $redirects = new Redirect\Listing();
        $redirects->setCondition('target = ?', $this->getId());
        $redirects->load();

        foreach ($redirects->getRedirects() as $redirect) {
            $redirect->delete();
        }

        parent::doDelete();
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getTitle(): string
    {
        return \Pimcore\Tool\Text::removeLineBreaks($this->title);
    }

    public function setDescription(string $description): static
    {
        $this->description = str_replace("\n", ' ', $description);

        return $this;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function setMetaData(array $metaData): static
    {
        $this->metaData = $metaData;

        return $this;
    }

    public function getMetaData(): array
    {
        return $this->metaData;
    }

    public function getFullPath(bool $force = false): string
    {
        $path = parent::getFullPath($force);

        // do not use pretty url's when in admin, the current document is wrapped by a hardlink or this document isn't in the current site
        if (!\Pimcore::inAdmin() && !($this instanceof Hardlink\Wrapper\WrapperInterface) && \Pimcore\Tool\Frontend::isDocumentInCurrentSite($this)) {
            // check for a pretty url
            $prettyUrl = $this->getPrettyUrl();
            if (!empty($prettyUrl) && strlen($prettyUrl) > 1) {
                return $prettyUrl;
            }
        }

        return $path;
    }

    public function setPrettyUrl(?string $prettyUrl): static
    {
        if (!$prettyUrl) {
            $this->prettyUrl = null;
        } else {
            $this->prettyUrl = '/' . trim($prettyUrl, ' /');
            if (strlen($this->prettyUrl) < 2) {
                $this->prettyUrl = null;
            }
        }

        return $this;
    }

    public function getPrettyUrl(): ?string
    {
        return $this->prettyUrl;
    }

    /**
     * Set linked Target Groups as set in properties panel as list of IDs
     *
     * @param array|string $targetGroupIds
     */
    public function setTargetGroupIds(array|string $targetGroupIds): void
    {
        if (is_array($targetGroupIds)) {
            $targetGroupIds = implode(',', $targetGroupIds);
        }

        $targetGroupIds = trim($targetGroupIds, ' ,');

        if (!empty($targetGroupIds)) {
            $targetGroupIds = ',' . $targetGroupIds . ',';
        }

        $this->targetGroupIds = $targetGroupIds;
    }

    /**
     * Get serialized list of Target Group IDs
     *
     * @return string
     */
    public function getTargetGroupIds(): string
    {
        return $this->targetGroupIds;
    }

    /**
     * Set assigned target groups
     *
     * @param TargetGroup[]|int[] $targetGroups
     */
    public function setTargetGroups(array $targetGroups): void
    {
        $ids = array_map(function ($targetGroup) {
            if (is_numeric($targetGroup)) {
                return (int)$targetGroup;
            } elseif ($targetGroup instanceof TargetGroup) {
                return $targetGroup->getId();
            }
        }, $targetGroups);

        $ids = array_filter($ids, function ($id) {
            return null !== $id && $id > 0;
        });

        $this->setTargetGroupIds($ids);
    }

    /**
     * Return list of assigned target groups (via properties panel)
     *
     * @return TargetGroup[]
     */
    public function getTargetGroups(): array
    {
        $ids = explode(',', $this->targetGroupIds);

        $targetGroups = array_map(function ($id) {
            $id = trim($id);
            if (!empty($id)) {
                $targetGroup = TargetGroup::getById((int) $id);
                if ($targetGroup) {
                    return $targetGroup;
                }
            }
        }, $ids);

        $targetGroups = array_filter($targetGroups);

        return $targetGroups;
    }

    public function getPreviewImageFilesystemPath(): string
    {
        return PIMCORE_SYSTEM_TEMP_DIRECTORY . '/document-page-previews/document-page-screenshot-' . $this->getId() . '@2x.jpg';
    }

    public function save(array $parameters = []): static
    {
        $page = parent::save($parameters);

        // Dispatch page preview message, if preview is enabled.
        $documentsConfig = \Pimcore\Config::getSystemConfiguration('documents');
        if ($documentsConfig['generate_preview'] ?? false) {
            \Pimcore::getContainer()->get('messenger.bus.pimcore-core')->dispatch(
                new GeneratePagePreviewMessage($this->getId(), \Pimcore\Tool::getHostUrl())
            );
        }

        return $page;
    }
}
