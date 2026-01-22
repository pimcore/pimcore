<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\Traits\DataHeightTrait;
use Pimcore\Model\DataObject\Traits\DataWidthTrait;

/**
 * @internal
 */
trait ImageTrait
{
    use DataWidthTrait;
    use DataHeightTrait;

    /**
     * @internal
     */
    public string $uploadPath;

    /**
     * @return $this
     */
    public function setUploadPath(string $uploadPath): static
    {
        $this->uploadPath = $uploadPath;

        return $this;
    }

    public function getUploadPath(): string
    {
        return $this->uploadPath;
    }
}
