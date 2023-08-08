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

namespace Pimcore\Extension\Document\Areabrick;

use Pimcore\Model\Document\Editable\Area\Info;
use Symfony\Component\HttpFoundation\Response;

interface AreabrickInterface
{
    /**
     * The brick ID as registered on AreabrickManager
     *
     */
    public function setId(string $id): void;

    /**
     * Brick ID - needs to be unique throughout the system.
     *
     */
    public function getId(): string;

    /**
     * A descriptive name as shown in extension manager and edit mode.
     *
     */
    public function getName(): string;

    /**
     * Area description as shown in extension manager.
     *
     */
    public function getDescription(): string;

    /**
     * Area version as shown in extension manager.
     *
     */
    public function getVersion(): string;

    /**
     * Icon as absolute path, e.g. /bundles/websitedemo/img/areas/foo/icon.png
     *
     */
    public function getIcon(): ?string;

    /**
     * Determines if the brick has a view template
     *
     */
    public function hasTemplate(): bool;

    /**
     * Get view template
     *
     */
    public function getTemplate(): ?string;

    /**
     * Will be called before the view is rendered. Acts as extension point for custom area logic.
     *
     * If this method returns a Response object, it will be pushed onto the response stack and returned to the client.
     *
     *
     */
    public function action(Info $info): ?Response;

    /**
     * Will be called after rendering.
     *
     * If this method returns a Response object, it will be pushed onto the response stack and returned to the client.
     *
     *
     */
    public function postRenderAction(Info $info): ?Response;

    /**
     * Returns the brick HTML open tag.
     *
     *
     */
    public function getHtmlTagOpen(Info $info): string;

    /**
     * Returns the brick HTML close tag.
     *
     *
     */
    public function getHtmlTagClose(Info $info): string;

    /**
     * Whether the UI needs a reload after this brick was added or removed
     *
     */
    public function needsReload(): bool;
}
