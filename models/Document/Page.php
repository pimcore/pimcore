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

use Pimcore;
use Pimcore\Messenger\GeneratePagePreviewMessage;

/**
 * @method \Pimcore\Model\Document\Page\Dao getDao()
 */
class Page extends PageSnippet
{
    /**
     * Contains the title of the page (meta-title)
     *
     * @internal
     *
     */
    protected string $title = '';

    /**
     * Contains the description of the page (meta-description)
     *
     * @internal
     *
     */
    protected string $description = '';

    protected string $type = 'page';

    /**
     * @internal
     *
     */
    protected ?string $prettyUrl = null;

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

    public function getFullPath(bool $force = false): string
    {
        $path = parent::getFullPath($force);

        // do not use pretty url's when in admin, the current document is wrapped by a hardlink or this document isn't in the current site
        if (!Pimcore::inAdmin() && !($this instanceof Hardlink\Wrapper\WrapperInterface) && \Pimcore\Tool\Frontend::isDocumentInCurrentSite($this)) {
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
            Pimcore::getContainer()->get('messenger.bus.pimcore-core')->dispatch(
                new GeneratePagePreviewMessage($this->getId(), \Pimcore\Tool::getHostUrl())
            );
        }

        return $page;
    }
}
