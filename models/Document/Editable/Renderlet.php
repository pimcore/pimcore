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

namespace Pimcore\Model\Document\Editable;

use Pimcore\Document\Editable\EditableHandler;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Targeting\Document\DocumentTargetingConfigurator;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Renderlet extends Model\Document\Editable implements IdRewriterInterface, EditmodeDataInterface, LazyLoadingInterface
{
    /**
     * Contains the ID of the linked object
     *
     * @internal
     *
     * @var int|null
     */
    protected $id;

    /**
     * Contains the object
     *
     * @internal
     *
     * @var Document|Asset|DataObject|null
     */
    protected $o;

    /**
     * Contains the type
     *
     * @internal
     *
     * @var string|null
     */
    protected $type;

    /**
     * Contains the subtype
     *
     * @internal
     *
     * @var string|null
     */
    protected $subtype;

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'renderlet';
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return [
            'id' => $this->id,
            'type' => $this->getObjectType(),
            'subtype' => $this->subtype,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDataEditmode() /** : mixed */
    {
        if ($this->o instanceof Element\ElementInterface) {
            return [
                'id' => $this->id,
                'type' => $this->getObjectType(),
                'subtype' => $this->subtype,
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

        if (!is_array($this->config)) {
            $this->config = [];
        }

        if (empty($this->config['controller']) && !empty($this->config['template'])) {
            $this->config['controller'] = $container->getParameter('pimcore.documents.default_controller');
        }

        if (empty($this->config['controller'])) {
            // this can be the case e.g. in \Pimcore\Model\Search\Backend\Data::setDataFromElement() where
            // this method is called without the config, so it would just render the default controller with the default template
            return '';
        }

        $this->load();

        if ($this->o instanceof Element\ElementInterface) {
            if (method_exists($this->o, 'isPublished')) {
                if (!$this->o->isPublished()) {
                    return '';
                }
            }

            // apply best matching target group (if any)
            if ($this->o instanceof Document\Targeting\TargetingDocumentInterface) {
                $targetingConfigurator = $container->get(DocumentTargetingConfigurator::class);
                $targetingConfigurator->configureTargetGroup($this->o);
            }

            $blockparams = ['controller', 'template'];

            $params = [
                'template' => isset($this->config['template']) ? $this->config['template'] : null,
                'id' => $this->id,
                'type' => $this->type,
                'subtype' => $this->subtype,
                'pimcore_request_source' => 'renderlet',
            ];

            foreach ($this->config as $key => $value) {
                if (!array_key_exists($key, $params) && !in_array($key, $blockparams)) {
                    $params[$key] = $value;
                }
            }

            return $editableHandler->renderAction(
                $this->config['controller'],
                $params
            );
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromResource($data)
    {
        $data = \Pimcore\Tool\Serialize::unserialize($data);

        $this->id = $data['id'];
        $this->type = $data['type'];
        $this->subtype = $data['subtype'];

        $this->setElement();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromEditmode($data)
    {
        if (is_array($data) && isset($data['id'])) {
            $this->id = $data['id'];
            $this->type = $data['type'];
            $this->subtype = $data['subtype'];

            $this->setElement();
        }

        return $this;
    }

    /**
     * Sets the element by the data stored for the object
     *
     * @return $this
     */
    public function setElement()
    {
        $this->o = Element\Service::getElementById($this->type, $this->id);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveDependencies()
    {
        $this->load();

        $dependencies = [];

        if ($this->o instanceof Element\ElementInterface) {
            $elementType = Element\Service::getElementType($this->o);
            $key = $elementType . '_' . $this->o->getId();

            $dependencies[$key] = [
                'id' => $this->o->getId(),
                'type' => $elementType,
            ];
        }

        return $dependencies;
    }

    /**
     * get correct type of object as string
     *
     * @param Element\ElementInterface|null $object
     *
     * @return string|null
     *
     * @internal param mixed $data
     */
    private function getObjectType($object = null)
    {
        $this->load();

        if (!$object) {
            $object = $this->o;
        }
        if ($object instanceof Element\ElementInterface) {
            return Element\Service::getElementType($object);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        $this->load();

        if ($this->o instanceof Element\ElementInterface) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity()
    {
        $sane = true;
        if ($this->id) {
            $el = Element\Service::getElementById($this->type, $this->id);
            if (!$el instanceof Element\ElementInterface) {
                $sane = false;
                Logger::notice('Detected insane relation, removing reference to non existent '.$this->type.' with id ['.$this->id.']');
                $this->id = null;
                $this->type = null;
                $this->o = null;
                $this->subtype = null;
            }
        }

        return $sane;
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        $finalVars = [];
        $parentVars = parent::__sleep();
        $blockedVars = ['o'];
        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    /**
     * {@inheritdoc}
     */
    public function load() /** : void */
    {
        if (!$this->o) {
            $this->setElement();
        }
    }

    /**
     * @param int $id
     *
     * @return Document\Editable\Renderlet
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int) $this->id;
    }

    /**
     * @param Asset|Document|DataObject|null $o
     *
     * @return Document\Editable\Renderlet
     */
    public function setO($o)
    {
        $this->o = $o;

        return $this;
    }

    /**
     * @return Asset|Document|DataObject|null
     */
    public function getO()
    {
        return $this->o;
    }

    /**
     * @param string $subtype
     *
     * @return Document\Editable\Renderlet
     */
    public function setSubtype($subtype)
    {
        $this->subtype = $subtype;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubtype()
    {
        return $this->subtype;
    }

    /**
     * { @inheritdoc }
     */
    public function rewriteIds($idMapping) /** : void */
    {
        $type = (string) $this->type;
        if ($type && array_key_exists($this->type, $idMapping) && array_key_exists($this->getId(), $idMapping[$this->type])) {
            $this->setId($idMapping[$this->type][$this->getId()]);
            $this->setO(null);
        }
    }
}
