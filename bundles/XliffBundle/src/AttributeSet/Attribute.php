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

namespace Pimcore\Bundle\XliffBundle\AttributeSet;

class Attribute
{
    const TYPE_PROPERTY = 'property';

    const TYPE_TAG = 'tag';

    const TYPE_SETTINGS = 'settings';

    const TYPE_LOCALIZED_FIELD = 'localizedfield';

    const TYPE_BRICK_LOCALIZED_FIELD = 'localizedbrick';

    const TYPE_BLOCK = 'block';

    const TYPE_BLOCK_IN_LOCALIZED_FIELD = 'blockinlocalizedfield';

    const TYPE_BLOCK_IN_LOCALIZED_FIELD_COLLECTION = 'blockinlocalizedfieldcollection';

    const TYPE_FIELD_COLLECTION_LOCALIZED_FIELD = 'localizedfieldcollection';

    const TYPE_ELEMENT_KEY = 'key';

    private string $type;

    private string $name;

    private string $content;

    /**
     * @var string[]
     */
    private array $targetContent;

    private bool $isReadonly;

    /**
     * DataExtractorResultAttribute constructor.
     *
     * @param string[] $targetContent
     */
    public function __construct(string $type, string $name, string $content, bool $isReadonly = false, array $targetContent = [])
    {
        $this->type = $type;
        $this->name = $name;
        $this->content = $content;
        $this->isReadonly = $isReadonly;
        $this->targetContent = $targetContent;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return string[]
     */
    public function getTargetContent(): array
    {
        return $this->targetContent;
    }

    /**
     * Readonly attributes should not be translated - relevant for information purposes only.
     *
     */
    public function isReadonly(): bool
    {
        return $this->isReadonly;
    }
}
