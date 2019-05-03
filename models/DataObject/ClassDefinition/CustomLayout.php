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
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition;

use Pimcore\Cache;
use Pimcore\Event\DataObjectCustomLayoutEvents;
use Pimcore\Event\Model\DataObject\CustomLayoutEvent;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;

/**
 * @method \Pimcore\Model\DataObject\ClassDefinition\CustomLayout\Dao getDao()
 */
class CustomLayout extends Model\AbstractModel
{
    use DataObject\ClassDefinition\Helper\VarExport;

    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    /**
     * @var int
     */
    public $creationDate;

    /**
     * @var int
     */
    public $modificationDate;

    /**
     * @var int
     */
    public $userOwner;

    /**
     * @var int
     */
    public $userModification;

    /**
     * @var int
     */
    public $classId;

    /**
     * @var array
     */
    public $layoutDefinitions;

    /**
     * @var int
     */
    public $default;

    /**
     * @param $id
     *
     * @return mixed|null|CustomLayout
     *
     * @throws \Exception
     */
    public static function getById($id)
    {
        if ($id === null) {
            throw new \Exception('CustomLayout id is null');
        }

        $cacheKey = 'customlayout_' . $id;

        try {
            $customLayout = \Pimcore\Cache\Runtime::get($cacheKey);
            if (!$customLayout) {
                throw new \Exception('Custom Layout in registry is null');
            }
        } catch (\Exception $e) {
            try {
                $customLayout = new self();
                $customLayout->getDao()->getById($id);

                DataObject\Service::synchronizeCustomLayout($customLayout);
                \Pimcore\Cache\Runtime::set($cacheKey, $customLayout);
            } catch (\Exception $e) {
                Logger::error($e);

                return null;
            }
        }

        return $customLayout;
    }

    /**
     * @param string $name
     *
     * @return null|CustomLayout|mixed
     */
    public static function getByName(string $name)
    {
        try {
            $customLayout = new self();
            $id = $customLayout->getDao()->getIdByName($name);
            if ($id) {
                return self::getById($id);
            } else {
                throw new \Exception('There is no customlayout with the name: ' . $name);
            }
        } catch (\Exception $e) {
            Logger::error($e);
        }

        return null;
    }

    /**
     * @param string $field
     *
     * @return \Pimcore\Model\DataObject\ClassDefinition\Data | null
     */
    public function getFieldDefinition($field)
    {
        $findElement = function ($key, $definition) use (&$findElement) {
            if ($definition->getName() == $key) {
                return $definition;
            } else {
                if (method_exists($definition, 'getChilds')) {
                    foreach ($definition->getChilds() as $definition) {
                        if ($definition = $findElement($key, $definition)) {
                            return $definition;
                        }
                    }
                } else {
                    if ($definition->getName() == $key) {
                        return $definition;
                    }
                }
            }
        };

        return $findElement($field, $this->getLayoutDefinitions());
    }

    /**
     * @param array $values
     *
     * @return CustomLayout
     */
    public static function create($values = [])
    {
        $class = new self();
        $class->setValues($values);

        if (!$class->getId()) {
            $class->getDao()->getNewId();
        }

        return $class;
    }

    /**
     * @todo: $isUpdate is not needed
     */
    public function save()
    {
        $isUpdate = $this->exists();

        if ($isUpdate) {
            \Pimcore::getEventDispatcher()->dispatch(DataObjectCustomLayoutEvents::PRE_UPDATE, new CustomLayoutEvent($this));
        } else {
            \Pimcore::getEventDispatcher()->dispatch(DataObjectCustomLayoutEvents::PRE_ADD, new CustomLayoutEvent($this));
        }

        $this->setModificationDate(time());

        // create directory if not exists
        if (!is_dir(PIMCORE_CUSTOMLAYOUT_DIRECTORY)) {
            \Pimcore\File::mkdir(PIMCORE_CUSTOMLAYOUT_DIRECTORY);
        }

        $this->getDao()->save($isUpdate);

        $this->saveCustomLayoutFile();

        // empty custom layout cache
        try {
            Cache::clearTag('customlayout_' . $this->getId());
        } catch (\Exception $e) {
        }
    }

    private function saveCustomLayoutFile($saveDefinitionFile = true)
    {
        // save definition as a php file
        $definitionFile = $this->getDefinitionFile();
        if (!is_writable(dirname($definitionFile)) || (is_file($definitionFile) && !is_writable($definitionFile))) {
            throw new \Exception(
                'Cannot write definition file in: '.$definitionFile.' please check write permission on this directory.'
            );
        }

        $infoDocBlock = $this->getInfoDocBlock();

        $clone = clone $this;
        $clone->setDao(null);
        unset($clone->fieldDefinitions);

        self::cleanupForExport($clone->layoutDefinitions);

        if ($saveDefinitionFile) {
            $data = to_php_data_file_format($clone, $infoDocBlock);

            \Pimcore\File::putPhpFile($definitionFile, $data);
        }
    }

    /**
     *
     * @return string
     */
    public function getDefinitionFile()
    {
        $file = PIMCORE_CUSTOMLAYOUT_DIRECTORY.'/custom_definition_'. $this->getId() .'.php';

        return $file;
    }

    /**
     * @param $data
     */
    public static function cleanupForExport(&$data)
    {
        if (isset($data->fieldDefinitionsCache)) {
            unset($data->fieldDefinitionsCache);
        }

        if (method_exists($data, 'getChilds')) {
            $children = $data->getChilds();
            if (is_array($children)) {
                foreach ($children as $child) {
                    self::cleanupForExport($child);
                }
            }
        }
    }

    /**
     * @return string
     */
    protected function getInfoDocBlock()
    {
        $cd = '';

        $cd .= '/** ';
        $cd .= "\n";
        $cd .= '* Generated at: '.date('c')."\n";

        $user = Model\User::getById($this->getUserModification());
        if ($user) {
            $cd .= '* Changed by: '.$user->getName().' ('.$user->getId().')'."\n";
        }

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $cd .= '* IP: '.$_SERVER['REMOTE_ADDR']."\n";
        }

        if ($this->getDescription()) {
            $description = str_replace(['/**', '*/', '//'], '', $this->getDescription());
            $description = str_replace("\n", "\n* ", $description);

            $cd .= '* '.$description."\n";
        }
        $cd .= '*/ ';

        return $cd;
    }

    /**
     * @param mixed $classId
     *
     * @return int|null
     */
    public static function getIdentifier($classId)
    {
        try {
            $customLayout = new self();
            $identifier = $customLayout->getDao()->getLatestIdentifier($classId);

            return $identifier;
        } catch (\Exception $e) {
            Logger::error($e);

            return null;
        }
    }

    public function delete()
    {
        // empty object cache
        try {
            Cache::clearTag('customlayout_' . $this->getId());
        } catch (\Exception $e) {
        }

        // empty output cache
        try {
            Cache::clearTag('output');
        } catch (\Exception $e) {
        }

        $this->getDao()->delete();
    }

    /**
     * @return bool
     */
    public function exists()
    {
        $name = $this->getDao()->getNameById($this->getId());

        return is_string($name);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @return int
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @return int
     */
    public function getUserOwner()
    {
        return $this->userOwner;
    }

    /**
     * @return int
     */
    public function getUserModification()
    {
        return $this->userModification;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return int
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param int $default
     *
     * @return $this
     */
    public function setDefault($default)
    {
        $this->default = (int)$default;

        return $this;
    }

    /**
     * @param int $creationDate
     *
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = (int) $creationDate;

        return $this;
    }

    /**
     * @param int $modificationDate
     *
     * @return $this
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = (int) $modificationDate;

        return $this;
    }

    /**
     * @param int $userOwner
     *
     * @return $this
     */
    public function setUserOwner($userOwner)
    {
        $this->userOwner = (int) $userOwner;

        return $this;
    }

    /**
     * @param int $userModification
     *
     * @return $this
     */
    public function setUserModification($userModification)
    {
        $this->userModification = (int) $userModification;

        return $this;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param array $layoutDefinitions
     */
    public function setLayoutDefinitions($layoutDefinitions)
    {
        $this->layoutDefinitions = $layoutDefinitions;
    }

    /**
     * @return array
     */
    public function getLayoutDefinitions()
    {
        return $this->layoutDefinitions;
    }

    /**
     * @param int $classId
     */
    public function setClassId($classId)
    {
        $this->classId = $classId;
    }

    /**
     * @return string
     */
    public function getClassId()
    {
        return $this->classId;
    }
}
