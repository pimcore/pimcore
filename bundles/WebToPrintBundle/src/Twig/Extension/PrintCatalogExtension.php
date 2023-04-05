<?php

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

namespace Pimcore\Bundle\WebToPrintBundle\Twig\Extension;

use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\ClassDefinition\Data\Hotspotimage;
use Pimcore\Model\DataObject\ClassDefinition\Data\ImageGallery;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToOneRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Translation\Translator;
use Pimcore\Twig\Extension\Templating\Placeholder;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PrintCatalogExtension extends AbstractExtension
{
    protected Translator $translator;

    protected Placeholder $placeholderHelper;

    /**
     * PrintCatalogExtension constructor.
     *
     * @param Translator $translator
     * @param Placeholder $placeholderHelper
     */
    public function __construct(Translator $translator, Placeholder $placeholderHelper)
    {
        $this->translator = $translator;
        $this->placeholderHelper = $placeholderHelper;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('app_print_output_spec_value', [$this, 'getSpecValue']),
            new TwigFunction('app_print_create_register', [$this, 'createRegisterTitleStyling']),
            new TwigFunction('app_print_get_register_name', [$this, 'getRegisterName']),
        ];
    }

    public function getSpecValue(\stdClass $outputElement, string $thumbnailName = null): string
    {
        if ($outputElement->value instanceof Image) {
            return $this->printImage($outputElement->value, $thumbnailName);
        } elseif (($outputElement->def ?? null) instanceof ImageGallery) {
            return $this->printImageGallery($outputElement->value, $thumbnailName);
        } elseif (($outputElement->def ?? null) instanceof Hotspotimage) {
            return $this->printHotspotImage($outputElement->value, $thumbnailName);
        } elseif (($outputElement->def ?? null) instanceof Select) {
            return $this->printSelectValue($outputElement->value);
        } elseif (($outputElement->def ?? null) instanceof Multiselect) {
            return $this->printMultiSelectValue($outputElement->value);
        } elseif (($outputElement->def ?? null) instanceof ManyToOneRelation) {
            return $this->printManyToOne($outputElement->value);
        } elseif (($outputElement->def ?? null) instanceof ManyToManyObjectRelation) {
            return $this->printManyToManyObjects($outputElement->value);
        } else {
            return $outputElement->value;
        }
    }

    protected function printImage(Image $image, string $thumbnailName): string
    {
        $src = $thumbnailName ? $image->getThumbnail($thumbnailName) : $image->getFullPath();

        return "<img src='$src' alt='image' />";
    }

    protected function printImageGallery(\Pimcore\Model\DataObject\Data\ImageGallery $imageGallery, string $thumbnailName): string
    {
        $firstItem = $imageGallery->current();
        if ($firstItem && $firstItem->getImage()) {
            return $this->printImage($firstItem->getImage(), $thumbnailName);
        }

        return '';
    }

    protected function printHotspotImage(\Pimcore\Model\DataObject\Data\Hotspotimage $hotspotimage, string $thumbnailName): string
    {
        $image = $hotspotimage->getImage();
        if ($image) {
            return "<img src='{$hotspotimage->getThumbnail($thumbnailName)}' alt='image' />";
        }

        return '';
    }

    protected function printSelectValue(string $value): string
    {
        return $this->translator->trans('attribute.' . strtolower($value));
    }

    protected function printMultiSelectValue(?array $value = null): string
    {
        $result = [];
        if ($value) {
            foreach ($value as $v) {
                $result[] = $this->translator->trans('attribute.' . strtolower($v));
            }
        }

        return implode(', ', $result);
    }

    protected function printManyToManyObjects(array $value): string
    {
        $result = [];
        if ($value) {
            foreach ($value as $v) {
                if (method_exists($v, 'getName')) {
                    $result[] = $v->getName();
                }
            }
        }

        return implode(', ', $result);
    }

    protected function printManyToOne(?AbstractElement $element = null): string
    {
        if ($element && method_exists($element, 'getName')) {
            return $element->getName();
        }

        return '';
    }

    public function createRegisterTitleStyling(?string $name, ?string $registerType = ''): void
    {
        $key = $this->getRegisterName($name);

        if ($name) {
            $this->placeholderHelper->__invoke('register-title-definition')->append("

                @page $key:right {
                    @left-top {
                        content: xhtml(\"<div class='register left register-$registerType'>$name</div>\");
                        z-index: 2000;
                    }
                }

                @page $key:left {
                    @right-top {
                        content: xhtml(\"<div class='register register-$registerType'>$name</div>\");
                        z-index: 2000;
                    }
                }

                .page.$key {
                    page: $key;
                }

        ");
        }
    }

    public function getRegisterName(?string $name): string
    {
        return self::toUrl($name);
    }

    public static function toUrl(?string $text): string
    {
        // to ASCII
        $text = trim(transliterator_transliterate('Any-Latin; Latin-ASCII; [^\u001F-\u007f] remove', $text));

        $search = ['?', '\'', '"', '/', '-', '+', '.', ',', ';', '(', ')', ' ', '&', 'ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß', 'É', 'é', 'È', 'è', 'Ê', 'ê', 'E', 'e', 'Ë', 'ë',
            'À', 'à', 'Á', 'á', 'Å', 'å', 'a', 'Â', 'â', 'Ã', 'ã', 'ª', 'Æ', 'æ', 'C', 'c', 'Ç', 'ç', 'C', 'c', 'Í', 'í', 'Ì', 'ì', 'Î', 'î', 'Ï', 'ï',
            'Ó', 'ó', 'Ò', 'ò', 'Ô', 'ô', 'º', 'Õ', 'õ', 'Œ', 'O', 'o', 'Ø', 'ø', 'Ú', 'ú', 'Ù', 'ù', 'Û', 'û', 'U', 'u', 'U', 'u', 'Š', 'š', 'S', 's',
            'Ž', 'ž', 'Z', 'z', 'Z', 'z', 'L', 'l', 'N', 'n', 'Ñ', 'ñ', '¡', '¿',  'Ÿ', 'ÿ', '_', ':' ];
        $replace = ['', '', '', '', '-', '', '', '-', '-', '', '', '-', '', 'ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e',
            'A', 'a', 'A', 'a', 'A', 'a', 'a', 'A', 'a', 'A', 'a', 'a', 'AE', 'ae', 'C', 'c', 'C', 'c', 'C', 'c', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i',
            'O', 'o', 'O', 'o', 'O', 'o', 'o', 'O', 'o', 'OE', 'O', 'o', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'S', 's', 'S', 's',
            'Z', 'z', 'Z', 'z', 'Z', 'z', 'L', 'l', 'N', 'n', 'N', 'n', '', '', 'Y', 'y', '-', '-' ];

        return urlencode(str_replace($search, $replace, $text));
    }
}
