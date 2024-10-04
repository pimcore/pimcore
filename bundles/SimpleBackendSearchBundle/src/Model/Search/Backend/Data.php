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

namespace Pimcore\Bundle\SimpleBackendSearchBundle\Model\Search\Backend;

use Doctrine\DBAL\Exception\DeadlockException;
use Exception;
use ForceUTF8\Encoding;
use Pimcore;
use Pimcore\Bundle\SimpleBackendSearchBundle\Event\Model\SearchBackendEvent;
use Pimcore\Bundle\SimpleBackendSearchBundle\Event\SearchBackendEvents;
use Pimcore\Bundle\SimpleBackendSearchBundle\Model\Search\Backend\Data\Dao;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Loader\ImplementationLoader\Exception\UnsupportedException;
use Pimcore\Logger;
use Pimcore\Model\AbstractModel;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element;

/**
 * @internal
 *
 * @method Dao getDao()
 */
class Data extends AbstractModel
{
    use RecursionBlockingEventDispatchHelperTrait;

    // if a word occures more often than this number it will get stripped to keep the search_backend_data table from getting too big
    const MAX_WORD_OCCURENCES = 3;

    protected ?Data\Id $id = null;

    protected ?string $key = null;

    protected ?int $index = null;

    protected string $fullPath;

    /**
     * document | object | asset
     *
     */
    protected string $maintype;

    /**
     * webresource type (e.g. page, snippet ...)
     *
     */
    protected string $type;

    /**
     * currently only relevant for objects where it portrays the class name
     *
     */
    protected string $subtype;

    /**
     * published or not
     *
     */
    protected bool $published = false;

    /**
     * timestamp of creation date
     *
     */
    protected ?int $creationDate = null;

    /**
     * timestamp of modification date
     *
     */
    protected ?int $modificationDate = null;

    /**
     * User-ID of the owner
     *
     */
    protected ?int $userOwner = null;

    /**
     * User-ID of the user last modified the element
     *
     */
    protected ?int $userModification = null;

    protected ?string $data = null;

    protected string $properties;

    public function __construct(Element\ElementInterface $element = null)
    {
        if ($element instanceof Element\ElementInterface) {
            $this->setDataFromElement($element);
        }
    }

    public function getId(): ?Data\Id
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setId(?Data\Id $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * @return $this
     */
    public function setKey(?string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function getIndex(): ?int
    {
        return $this->index;
    }

    /**
     * @return $this
     */
    public function setIndex(?int $index): static
    {
        $this->index = $index;

        return $this;
    }

    public function getFullPath(): string
    {
        return $this->fullPath;
    }

    /**
     * @return $this
     */
    public function setFullPath(string $fullpath): static
    {
        $this->fullPath = $fullpath;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return $this
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSubtype(): string
    {
        return $this->subtype;
    }

    /**
     * @return $this
     */
    public function setSubtype(string $subtype): static
    {
        $this->subtype = $subtype;

        return $this;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    /**
     * @return $this
     */
    public function setCreationDate(?int $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    /**
     * @return $this
     */
    public function setModificationDate(?int $modificationDate): static
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    public function getUserModification(): ?int
    {
        return $this->userModification;
    }

    /**
     * @return $this
     */
    public function setUserModification(?int $userModification): static
    {
        $this->userModification = $userModification;

        return $this;
    }

    public function getUserOwner(): ?int
    {
        return $this->userOwner;
    }

    /**
     * @return $this
     */
    public function setUserOwner(?int $userOwner): static
    {
        $this->userOwner = $userOwner;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->getPublished();
    }

    public function getPublished(): bool
    {
        return $this->published;
    }

    /**
     * @return $this
     */
    public function setPublished(bool $published): static
    {
        $this->published = $published;

        return $this;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    /**
     * @return $this
     */
    public function setData(?string $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getProperties(): string
    {
        return $this->properties;
    }

    /**
     * @return $this
     */
    public function setProperties(string $properties): static
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @return $this
     */
    public function setDataFromElement(Element\ElementInterface $element): static
    {
        $this->data = null;

        $this->id = new Data\Id($element);
        $this->key = $element->getKey();
        $this->fullPath = $element->getRealFullPath();
        $this->creationDate = $element->getCreationDate();
        $this->modificationDate = $element->getModificationDate();
        $this->userModification = $element->getUserModification();
        $this->userOwner = $element->getUserOwner();

        $this->type = $element->getType();
        if ($element instanceof DataObject\Concrete) {
            $this->subtype = $element->getClassName();
            $this->index = $element->getIndex();
        } else {
            $this->subtype = $this->type;
            if ($element instanceof Document) {
                $this->index = $element->getIndex();
            }
        }

        $this->properties = '';
        $properties = $element->getProperties();
        foreach ($properties as $nextProperty) {
            $pData = (string) $nextProperty->getData();
            if ($nextProperty->getName() === 'bool') {
                $pData = $pData ? 'true' : 'false';
            }

            $this->properties .= $nextProperty->getName() . ':' . $pData .' ';
        }

        $this->data = '';

        if ($element instanceof Document) {
            if ($element instanceof Document\Folder) {
                $this->published = true;
            } elseif ($element instanceof Document\Link) {
                $this->published = $element->isPublished();
                $this->data = ' ' . $element->getHref();
            } elseif ($element instanceof Document\PageSnippet) {
                $this->published = $element->isPublished();
                $editables = $element->getEditables();
                foreach ($editables as $editable) {
                    if ($editable instanceof Document\Editable\EditableInterface) {
                        // areabrick elements are handled by getElementTypes()/getElements() as they return area elements as well
                        if ($editable instanceof Document\Editable\Area || $editable instanceof Document\Editable\Areablock) {
                            continue;
                        }

                        ob_start();
                        $this->data .= strip_tags((string) $editable->frontend()).' ';
                        $this->data .= ob_get_clean();
                    }
                }
                if ($element instanceof Document\Page) {
                    $this->published = $element->isPublished();
                    $this->data .= ' '.$element->getTitle().' '.$element->getDescription().' ' . $element->getPrettyUrl();
                }
            }
        } elseif ($element instanceof Asset) {
            $elementMetadata = $element->getMetadata();
            if (is_array($elementMetadata)) {
                foreach ($elementMetadata as $md) {
                    try {
                        $loader = Pimcore::getContainer()->get('pimcore.implementation_loader.asset.metadata.data');
                        /** @var \Pimcore\Model\Asset\MetaData\ClassDefinition\Data\Data $instance */
                        $instance = $loader->build($md['type']);
                        $dataForSearchIndex = $instance->getDataForSearchIndex($md['data'], $md);
                        if ($dataForSearchIndex) {
                            $this->data .= ' ' . $dataForSearchIndex;
                        }
                    } catch (UnsupportedException $e) {
                        Logger::error('asset metadata type ' . $md['type'] . ' could not be resolved');
                    }
                }
            }

            if ($element instanceof Asset\Document && \Pimcore\Document::isAvailable()) {
                if (\Pimcore\Document::isFileTypeSupported($element->getFilename())) {
                    try {
                        $contentText = $element->getText();
                        if ($contentText) {
                            $contentText = Encoding::toUTF8($contentText);
                            $contentText = str_replace(["\r\n", "\r", "\n", "\t", "\f"], ' ', $contentText);
                            $contentText = preg_replace('/[ ]+/', ' ', $contentText);
                            $this->data .= ' ' . $contentText;
                        }
                    } catch (Exception $e) {
                        Logger::error((string) $e);
                    }
                }
            } elseif ($element instanceof Asset\Text) {
                try {
                    if ($element->getFileSize() < 2000000) {
                        // it doesn't make sense to add text files bigger than 2MB to the full text index (performance)
                        $contentText = $element->getData();
                        $contentText = Encoding::toUTF8($contentText);
                        $this->data .= ' ' . $contentText;
                    }
                } catch (Exception $e) {
                    Logger::error((string) $e);
                }
            } elseif ($element instanceof Asset\Image) {
                try {
                    $metaData = array_merge($element->getEXIFData(), $element->getIPTCData());
                    foreach ($metaData as $key => $value) {
                        if (is_array($value)) {
                            $this->data .= ' ' . $key . ' : ' . implode(' - ', $value);
                        } else {
                            $this->data .= ' ' . $key . ' : ' . $value;
                        }
                    }
                } catch (Exception $e) {
                    Logger::error((string) $e);
                }
            }

            $this->published = true;
        } elseif ($element instanceof DataObject\AbstractObject) {
            if ($element instanceof DataObject\Concrete) {
                $getInheritedValues = DataObject::doGetInheritedValues();
                DataObject::setGetInheritedValues(true);

                $this->published = $element->isPublished();
                foreach ($element->getClass()->getFieldDefinitions() as $key => $value) {
                    $this->data .= ' ' . $value->getDataForSearchIndex($element);
                }

                DataObject::setGetInheritedValues($getInheritedValues);
            } elseif ($element instanceof DataObject\Folder) {
                $this->published = true;
            }
        } else {
            Logger::crit('Search\\Backend\\Data received an unknown element!');
        }

        // replace all occurrences of @ to # because when using InnoDB @ is reserved for the @distance operator
        $this->data = str_replace('@', '#', $this->data);

        $pathWords = str_replace([ '-', '_', '/', '.', '(', ')'], ' ', $this->getFullPath());
        $this->data .= ' ' . $pathWords;
        $this->data = 'ID: ' . $element->getId() . "  \nPath: " . $this->getKey() . "  \n"  . $this->cleanupData($this->data);

        return $this;
    }

    protected function cleanupData(string $data): string
    {
        $data = preg_replace('/(<\?.*?(\?>|$)|<[^<]+>)/s', '', $data);

        $data = html_entity_decode($data, ENT_QUOTES, 'UTF-8');

        // we don't remove ".", otherwise it would be impossible to search for email addresses
        $data = str_replace([',', ':', ';', "'", '"'], ' ', $data);
        $data = str_replace(["\r\n", "\n", "\r", "\t"], ' ', $data);
        $data = preg_replace('#[ ]+#', ' ', $data);

        $minWordLength = $this->getDao()->getMinWordLengthForFulltextIndex();
        $maxWordLength = $this->getDao()->getMaxWordLengthForFulltextIndex();

        $words = explode(' ', $data);

        $wordOccurrences = [];
        foreach ($words as $key => $word) {
            $wordLength = mb_strlen($word);
            if ($wordLength < $minWordLength || $wordLength > $maxWordLength) {
                unset($words[$key]);

                continue;
            }

            $wordOccurrences[$word] = ($wordOccurrences[$word] ?? 0) + 1;
            if ($wordOccurrences[$word] > self::MAX_WORD_OCCURENCES) {
                unset($words[$key]);
            }
        }

        $data = implode(' ', $words);

        return $data;
    }

    public static function getForElement(Element\ElementInterface $element): self
    {
        $data = new self();
        $data->getDao()->getForElement($element);

        return $data;
    }

    public function delete(): void
    {
        $this->getDao()->delete();
    }

    /**
     * @throws Exception
     */
    public function save(): void
    {
        if ($this->id instanceof Data\Id) {
            $this->dispatchEvent(new SearchBackendEvent($this), SearchBackendEvents::PRE_SAVE);

            $maxRetries = 5;
            for ($retries = 0; $retries < $maxRetries; $retries++) {
                $this->beginTransaction();

                try {
                    $this->getDao()->save();

                    $this->commit();

                    break; // transaction was successfully completed, so we cancel the loop here -> no restart required
                } catch (Exception $e) {
                    try {
                        $this->rollBack();
                    } catch (Exception $er) {
                        // PDO adapter throws exceptions if rollback fails
                        Logger::error((string) $er);
                    }

                    // we try to start the transaction $maxRetries times again (deadlocks, ...)
                    if ($e instanceof DeadlockException && $retries < ($maxRetries - 1)) {
                        $run = $retries + 1;
                        $waitTime = rand(1, 5) * 100000; // microseconds
                        Logger::warn('Unable to finish transaction (' . $run . ". run) because of the following reason '" . $e->getMessage() . "'. --> Retrying in " . $waitTime . ' microseconds ... (' . ($run + 1) . ' of ' . $maxRetries . ')');

                        usleep($waitTime); // wait specified time until we restart the transaction
                    } else {
                        // if the transaction still fail after $maxRetries retries, we throw out the exception
                        throw $e;
                    }
                }
            }

            $this->dispatchEvent(new SearchBackendEvent($this), SearchBackendEvents::POST_SAVE);
        } else {
            throw new Exception('Search\\Backend\\Data cannot be saved - no id set!');
        }
    }
}
