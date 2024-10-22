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

namespace Pimcore\Twig\Extension;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Element\Tag;
use Pimcore\Model\Site;
use Pimcore\Model\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal
 */
class PimcoreObjectExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        // simple object access functions in case documents/assets/objects need to be loaded directly in the template
        return [
            new TwigFunction('pimcore_document', [Document::class, 'getById']),
            new TwigFunction('pimcore_document_by_path', [Document::class, 'getByPath']),
            new TwigFunction('pimcore_site', [Site::class, 'getById']),
            new TwigFunction('pimcore_site_by_root_id', [Site::class, 'getByRootId']),
            new TwigFunction('pimcore_site_by_domain', [Site::class, 'getByDomain']),
            new TwigFunction('pimcore_site_is_request', [Site::class, 'isSiteRequest']),
            new TwigFunction('pimcore_site_current', [Site::class, 'getCurrentSite']),
            new TwigFunction('pimcore_asset', [Asset::class, 'getById']),
            new TwigFunction('pimcore_asset_by_path', [Asset::class, 'getByPath']),
            new TwigFunction('pimcore_object', [DataObject::class, 'getById']),
            new TwigFunction('pimcore_object_by_path', [DataObject::class, 'getByPath']),
            new TwigFunction('pimcore_document_wrap_hardlink', [Document\Hardlink\Service::class, 'wrap']),
            new TwigFunction('pimcore_user', [User::class, 'getById']),
            new TwigFunction('pimcore_object_classificationstore_group', [DataObject\Classificationstore\GroupConfig::class, 'getById']),
            new TwigFunction('pimcore_object_classificationstore_get_field_definition_from_json', [$this, 'getFieldDefinitionFromJson']),
            new TwigFunction('pimcore_object_brick_definition_key', [DataObject\Objectbrick\Definition::class, 'getByKey']),
            new TwigFunction('pimcore_element_tags', [$this, 'getPimcoreElementTags']),
        ];
    }

    public function getFieldDefinitionFromJson(array|string $definition, string $type): ?DataObject\ClassDefinition\Data
    {
        if (is_json($definition)) {
            $definition = json_decode($definition, true);
        }

        return DataObject\Classificationstore\Service::getFieldDefinitionFromJson($definition, $type);
    }

    public function getPimcoreElementTags(?AbstractElement $element, bool $asNameList): array
    {
        if (!$element) {
            return [];
        }

        if ($asNameList) {
            return array_map(function ($tag) {
                return $tag->getName();
            }, Tag::getTagsForElement($element->getType(), $element->getId()));
        }

        return Tag::getTagsForElement($element->getType(), $element->getId());
    }
}
