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

interface EditableInterface
{
    /**
     * Renders the editable, calls either frontend() or admin() depending on the context
     *
     */
    public function render(): mixed;

    /**
     * Get the current data stored for the element
     * this is used as general fallback for the methods getDataForResource(), admin(), getValue()
     *
     */
    public function getData(): mixed;

    /**
     * Return the type of the element
     *
     */
    public function getType(): string;

    /**
     * Receives the data from the editmode and convert this to the internal data in the object eg. image-id to Asset\Image
     *
     *
     * @return $this
     */
    public function setDataFromEditmode(mixed $data): static;

    /**
     * Receives the data from the resource, an convert to the internal data in the object eg. image-id to Asset\Image
     *
     *
     * @return $this
     */
    public function setDataFromResource(mixed $data): static;

    public function isEmpty(): bool;
}
