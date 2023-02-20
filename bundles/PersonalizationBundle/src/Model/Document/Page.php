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

namespace Pimcore\Bundle\PersonalizationBundle\Model\Document;

use Pimcore\Bundle\PersonalizationBundle\Model\Document\Targeting\TargetingDocumentInterface;
use Pimcore\Bundle\PersonalizationBundle\Model\Document\Traits\TargetDocumentTrait;
use Pimcore\Bundle\PersonalizationBundle\Model\Tool\Targeting\TargetGroup;

/**
 * @method \Pimcore\Bundle\PersonalizationBundle\Model\Document\Page\Dao getDao()
 */
class Page extends \Pimcore\Model\Document\Page implements TargetingDocumentInterface
{
    use TargetDocumentTrait;

    protected string $type = 'page';

    /**
     * Comma separated IDs of target groups
     *
     * @internal
     *
     * @var string
     */
    protected string $targetGroupIds = '';

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
                $targetGroup = TargetGroup::getById((int)$id);
                if ($targetGroup) {
                    return $targetGroup;
                }
            }
        }, $ids);

        $targetGroups = array_filter($targetGroups);

        return $targetGroups;
    }
}
