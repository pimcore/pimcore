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

namespace Pimcore\Translation\AttributeSet;

class Attribute
{
    const TYPE_PROPERTY = 'property';
    const TYPE_TAG = 'tag';
    const TYPE_SETTINGS = 'settings';
    const TYPE_LOCALIZED_FIELD = 'localizedfield';
    const TYPE_BRICK_LOCALIZED_FIELD = 'localizedbrick';
    const TYPE_BLOCK = 'block';

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $content;

    /**
     * @var bool
     */
    private $isReadonly;

    /**
     * DataExtractorResultAttribute constructor.
     *
     * @param string $type
     * @param string $name
     * @param string $content
     * @param bool $isReadonly
     */
    public function __construct(string $type, string $name, string $content, bool $isReadonly = false)
    {
        $this->type = $type;
        $this->name = $name;
        $this->content = $content;
        $this->isReadonly = $isReadonly;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Readonly attributes should not be translated - relevant for information purposes only.
     *
     * @return bool
     */
    public function isReadonly(): bool
    {
        return $this->isReadonly;
    }
}
