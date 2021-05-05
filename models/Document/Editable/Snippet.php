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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Editable;

use Pimcore\Cache;
use Pimcore\Document\Editable\EditableHandler;
use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Targeting\Document\DocumentTargetingConfigurator;
use Pimcore\Tool\DeviceDetector;
use Pimcore\Tool\Frontend;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Snippet extends Model\Document\Editable
{
    /**
     * Contains the ID of the linked snippet
     *
     * @internal
     *
     * @var int|null
     */
    protected ?int $id = null;

    /**
     * Contains the object for the snippet
     *
     * @internal
     *
     * @var Document\Snippet|null
     */
    protected $snippet = null;

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'snippet';
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int) $this->id;
    }

    /**
     * Converts the data so it's suitable for the editmode
     *
     * @return mixed
     */
    public function getDataEditmode()
    {
        if ($this->snippet instanceof Document\Snippet) {
            return [
                'id' => $this->id,
                'path' => $this->snippet->getFullPath(),
            ];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function frontend()
    {
        // TODO inject services via DI when editables are built through container
        $container = \Pimcore::getContainer();

        $editableHandler = $container->get(EditableHandler::class);
        $targetingConfigurator = $container->get(DocumentTargetingConfigurator::class);

        if (!$this->snippet instanceof Document\Snippet) {
            return '';
        }

        if (!$this->snippet->isPublished()) {
            return '';
        }

        // apply best matching target group (if any)
        $targetingConfigurator->configureTargetGroup($this->snippet);

        $params = $this->config;
        $params['document'] = $this->snippet;

        // check if output-cache is enabled, if so, we're also using the cache here
        $cacheKey = null;
        $cacheConfig = \Pimcore\Tool\Frontend::isOutputCacheEnabled();
        if ((isset($params['cache']) && $params['cache'] === true) || $cacheConfig) {

            // cleanup params to avoid serializing Element\ElementInterface objects
            $cacheParams = $params;
            array_walk($cacheParams, function (&$value, $key) {
                if ($value instanceof Model\Element\ElementInterface) {
                    $value = $value->getId();
                }
            });

            // TODO is this enough for cache or should we disable caching completely?
            if ($this->snippet->getUseTargetGroup()) {
                $cacheParams['target_group'] = $this->snippet->getUseTargetGroup();
            }

            if (Site::isSiteRequest()) {
                $cacheParams['siteId'] = Site::getCurrentSite()->getId();
            }

            $cacheKey = 'editable_snippet__' . md5(serialize($cacheParams));
            if ($content = Cache::load($cacheKey)) {
                return $content;
            }
        }

        $content = $editableHandler->renderAction($this->snippet->getController(), $params);

        // write contents to the cache, if output-cache is enabled
        if (isset($params['cache']) && $params['cache'] === true) {
            Cache::save($content, $cacheKey, ['output']);
        } elseif ($cacheConfig && !DeviceDetector::getInstance()->wasUsed()) {
            Cache::save($content, $cacheKey, ['output', 'output_inline'], $cacheConfig['lifetime']);
        }

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromResource($data)
    {
        if ((int)$data > 0) {
            $this->id = $data;
            $this->snippet = Document\Snippet::getById($this->id);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromEditmode($data)
    {
        if ((int)$data > 0) {
            $this->id = $data;
            $this->snippet = Document\Snippet::getById($this->id);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        $this->load();

        if ($this->snippet instanceof Document\Snippet) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveDependencies()
    {
        $dependencies = [];

        if ($this->snippet instanceof Document\Snippet) {
            $key = 'document_' . $this->snippet->getId();

            $dependencies[$key] = [
                'id' => $this->snippet->getId(),
                'type' => 'document',
            ];
        }

        return $dependencies;
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        $finalVars = [];
        $parentVars = parent::__sleep();
        $blockedVars = ['snippet'];
        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    /**
     * this method is called by Document\Service::loadAllDocumentFields() to load all lazy loading fields
     */
    public function load()
    {
        if (!$this->snippet && $this->id) {
            $this->snippet = Document\Snippet::getById($this->id);
        }
    }

    /**
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     *
     * @param array $idMapping
     */
    public function rewriteIds($idMapping)
    {
        $id = $this->getId();
        if (array_key_exists('document', $idMapping) && array_key_exists($id, $idMapping['document'])) {
            $this->id = $idMapping['document'][$id];
        }
    }

    /**
     * @param Document\Snippet $snippet
     */
    public function setSnippet($snippet)
    {
        if ($snippet instanceof Document\Snippet) {
            $this->id = $snippet->getId();
            $this->snippet = $snippet;
        }
    }

    /**
     * @return Document\Snippet
     */
    public function getSnippet()
    {
        $this->load();

        return $this->snippet;
    }
}
