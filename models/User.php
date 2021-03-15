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
 * @package    User
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model;

use Pimcore\Config;
use Pimcore\File;
use Pimcore\Model\User\Role;
use Pimcore\Tool;

/**
 * @method \Pimcore\Model\User\Dao getDao()
 */
class User extends User\UserRole
{
    /**
     * @var string
     */
    public $type = 'user';

    /**
     * @var string
     */
    public $password;

    /**
     * @var string
     */
    public $firstname;

    /**
     * @var string
     */
    public $lastname;

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $language = 'en';

    /**
     * @var bool
     */
    public $admin = false;

    /**
     * @var bool
     */
    public $active = true;

    /**
     * @var array
     */
    public $roles = [];

    /**
     * @var bool
     */
    public $welcomescreen = false;

    /**
     * @var bool
     */
    public $closeWarning = true;

    /**
     * @var bool
     */
    public $memorizeTabs = true;

    /**
     * @var bool
     */
    public $allowDirtyClose = false;

    /**
     * @deprecated
     *
     * @var string|null
     */
    public $apiKey;

    /**
     * @var string|null
     */
    public $contentLanguages;

    /**
     * @var string|null
     */
    public $activePerspective;

    /**
     * @var null|array
     */
    protected $mergedPerspectives = null;

    /**
     * @var null|array
     */
    protected $mergedWebsiteTranslationLanguagesEdit = null;

    /**
     * @var null|array
     */
    protected $mergedWebsiteTranslationLanguagesView = null;

    /**
     * @var int
     */
    public $lastLogin;

    /**
     * @var string
     */
    public $keyBindings;

    /**
     * @var array
     */
    public $twoFactorAuthentication;

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return $this
     */
    public function setPassword($password)
    {
        if (strlen($password) > 4) {
            $this->password = $password;
        }

        return $this;
    }

    /**
     * Alias for getName()
     *
     * @deprecated
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->getName();
    }

    /**
     * @param string $username
     *
     * @return $this
     */
    public function setUsername($username)
    {
        $this->setName($username);

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @param string $firstname
     *
     * @return $this
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @param string $lastname
     *
     * @return $this
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     *
     * @return $this
     */
    public function setLanguage($language)
    {
        if ($language) {
            $this->language = $language;
        }

        return $this;
    }

    /**
     * @see getAdmin()
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->getAdmin();
    }

    /**
     * @return bool
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * @param bool $admin
     *
     * @return $this
     */
    public function setAdmin($admin)
    {
        $this->admin = (bool)$admin;

        return $this;
    }

    /**
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
     *
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = (bool)$active;

        return $this;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->getActive();
    }

    /**
     * @param string $key
     * @param string $type
     *
     * @return bool
     */
    public function isAllowed($key, $type = 'permission')
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($type == 'permission') {
            if (!$this->getPermission($key)) {
                // check roles
                foreach ($this->getRoles() as $roleId) {
                    /** @var Role $role */
                    $role = User\Role::getById($roleId);
                    if ($role->getPermission($key)) {
                        return true;
                    }
                }
            }

            return $this->getPermission($key);
        } elseif ($type == 'class') {
            $classes = $this->getClasses();
            foreach ($this->getRoles() as $roleId) {
                /** @var Role $role */
                $role = User\Role::getById($roleId);
                $classes = array_merge($classes, $role->getClasses());
            }

            if (!empty($classes)) {
                return in_array($key, $classes);
            } else {
                return true;
            }
        } elseif ($type == 'docType') {
            $docTypes = $this->getDocTypes();
            foreach ($this->getRoles() as $roleId) {
                /** @var Role $role */
                $role = User\Role::getById($roleId);
                $docTypes = array_merge($docTypes, $role->getDocTypes());
            }

            if (!empty($docTypes)) {
                return in_array($key, $docTypes);
            } else {
                return true;
            }
        } elseif ($type == 'perspective') {
            //returns true if required perspective is allowed to use by the user
            return in_array($key, $this->getMergedPerspectives());
        }

        return false;
    }

    /**
     *
     * @param string $permissionName
     *
     * @return bool
     */
    public function getPermission($permissionName)
    {
        if ($this->isAdmin()) {
            return true;
        }

        return parent::getPermission($permissionName);
    }

    /**
     * @param string|array $roles
     *
     * @return $this
     */
    public function setRoles($roles)
    {
        if (is_string($roles) && !empty($roles)) {
            $this->roles = explode(',', $roles);
        } elseif (is_array($roles)) {
            $this->roles = $roles;
        } elseif (empty($roles)) {
            $this->roles = [];
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        if (empty($this->roles)) {
            return [];
        }

        return $this->roles;
    }

    /**
     * @param bool $welcomescreen
     *
     * @return $this
     */
    public function setWelcomescreen($welcomescreen)
    {
        $this->welcomescreen = (bool)$welcomescreen;

        return $this;
    }

    /**
     * @return bool
     */
    public function getWelcomescreen()
    {
        return $this->welcomescreen;
    }

    /**
     * @param bool $closeWarning
     *
     * @return $this
     */
    public function setCloseWarning($closeWarning)
    {
        $this->closeWarning = (bool)$closeWarning;

        return $this;
    }

    /**
     * @return bool
     */
    public function getCloseWarning()
    {
        return $this->closeWarning;
    }

    /**
     * @param bool $memorizeTabs
     *
     * @return $this
     */
    public function setMemorizeTabs($memorizeTabs)
    {
        $this->memorizeTabs = (bool)$memorizeTabs;

        return $this;
    }

    /**
     * @return bool
     */
    public function getMemorizeTabs()
    {
        return $this->memorizeTabs;
    }

    /**
     * @param bool $allowDirtyClose
     *
     * @return $this
     */
    public function setAllowDirtyClose($allowDirtyClose)
    {
        $this->allowDirtyClose = (bool)$allowDirtyClose;

        return $this;
    }

    /**
     * @return bool
     */
    public function getAllowDirtyClose()
    {
        return $this->allowDirtyClose;
    }

    /**
     * @deprecated
     *
     * @param string $apiKey
     *
     * @throws \Exception
     */
    public function setApiKey($apiKey)
    {
        if (!empty($apiKey) && strlen($apiKey) < 32) {
            throw new \Exception('API-Key has to be at least 32 characters long');
        }
        $this->apiKey = $apiKey;
    }

    /**
     * @deprecated
     *
     * @return null|string
     */
    public function getApiKey()
    {
        if (empty($this->apiKey)) {
            return null;
        }

        return $this->apiKey;
    }

    /**
     * @param string|null $path
     */
    public function setImage($path)
    {
        if (!is_dir(PIMCORE_USERIMAGE_DIRECTORY)) {
            File::mkdir(PIMCORE_USERIMAGE_DIRECTORY);
        }

        $destFile = PIMCORE_USERIMAGE_DIRECTORY . '/user-' . $this->getId() . '.png';
        $thumb = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/user-thumbnail-' . $this->getId() . '.png';
        @unlink($destFile);
        @unlink($thumb);

        if ($path) {
            copy($path, $destFile);
            @chmod($destFile, File::getDefaultMode());
        }
    }

    /**
     * @param int|null $width
     * @param int|null $height
     *
     * @return string
     */
    public function getImage($width = null, $height = null)
    {
        if (!$width) {
            $width = 46;
        }
        if (!$height) {
            $height = 46;
        }

        $id = $this->getId();
        $user = PIMCORE_USERIMAGE_DIRECTORY . '/user-' . $id . '.png';
        if (file_exists($user)) {
            $thumb = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/user-thumbnail-' . $id . '.png';
            if (!file_exists($thumb)) {
                $image = \Pimcore\Image::getInstance();
                $image->load($user);
                $image->cover($width, $height);
                $image->save($thumb, 'png');
            }

            return $thumb;
        }

        return $this->getFallbackImage();
    }

    /**
     * @return array
     */
    public function getContentLanguages()
    {
        if (strlen($this->contentLanguages)) {
            return explode(',', $this->contentLanguages);
        }

        return [];
    }

    /**
     * @param null|string|array $contentLanguages
     */
    public function setContentLanguages($contentLanguages)
    {
        if ($contentLanguages && is_array($contentLanguages)) {
            $contentLanguages = implode(',', $contentLanguages);
        }
        $this->contentLanguages = $contentLanguages;
    }

    /**
     * @return null|string
     */
    public function getActivePerspective()
    {
        if (!$this->activePerspective) {
            $this->activePerspective = 'default';
        }

        return $this->activePerspective;
    }

    /**
     * @param null|string $activePerspective
     */
    public function setActivePerspective($activePerspective)
    {
        $this->activePerspective = $activePerspective;
    }

    /**
     * Returns array of perspectives names related to user and all related roles
     *
     * @return array|string[]
     */
    public function getMergedPerspectives()
    {
        if (null === $this->mergedPerspectives) {
            $this->mergedPerspectives = $this->getPerspectives();
            foreach ($this->getRoles() as $role) {
                /** @var User\UserRole $userRole */
                $userRole = User\UserRole::getById($role);
                $this->mergedPerspectives = array_merge($this->mergedPerspectives, $userRole->getPerspectives());
            }
            $this->mergedPerspectives = array_values($this->mergedPerspectives);
            if (!$this->mergedPerspectives) {
                // $perspectives = \Pimcore\Config::getAvailablePerspectives($this);
                $allPerspectives = Config::getPerspectivesConfig()->toArray();
                $this->mergedPerspectives = [];

                $this->mergedPerspectives = array_keys($allPerspectives);
            }
        }

        return $this->mergedPerspectives;
    }

    /**
     * Returns the first perspective name
     *
     * @return string
     */
    public function getFirstAllowedPerspective()
    {
        $perspectives = $this->getMergedPerspectives();
        if (!empty($perspectives)) {
            return $perspectives[0];
        } else {
            // all perspectives are allowed
            $perspectives = \Pimcore\Config::getAvailablePerspectives($this);

            return $perspectives[0]['name'];
        }
    }

    /**
     * Returns array of website translation languages for editing related to user and all related roles
     *
     * @return array|null
     */
    public function getMergedWebsiteTranslationLanguagesEdit()
    {
        if (null === $this->mergedWebsiteTranslationLanguagesEdit) {
            $this->mergedWebsiteTranslationLanguagesEdit = $this->getWebsiteTranslationLanguagesEdit();
            foreach ($this->getRoles() as $role) {
                /** @var User\UserRole $userRole */
                $userRole = User\UserRole::getById($role);
                $this->mergedWebsiteTranslationLanguagesEdit = array_merge($this->mergedWebsiteTranslationLanguagesEdit, $userRole->getWebsiteTranslationLanguagesEdit());
            }
            $this->mergedWebsiteTranslationLanguagesEdit = array_values($this->mergedWebsiteTranslationLanguagesEdit);
        }

        return $this->mergedWebsiteTranslationLanguagesEdit;
    }

    /**
     * Returns array of languages allowed for editing. If edit and view languages are empty all languages are allowed.
     * If only edit languages are empty (but view languages not) empty array is returned.
     *
     * @return array|null
     */
    public function getAllowedLanguagesForEditingWebsiteTranslations()
    {
        $mergedWebsiteTranslationLanguagesEdit = $this->getMergedWebsiteTranslationLanguagesEdit();
        if (empty($mergedWebsiteTranslationLanguagesEdit)) {
            $mergedWebsiteTranslationLanguagesView = $this->getMergedWebsiteTranslationLanguagesView();
            if (empty($mergedWebsiteTranslationLanguagesView)) {
                return Tool::getValidLanguages();
            } else {
                return $mergedWebsiteTranslationLanguagesEdit;
            }
        } else {
            return $mergedWebsiteTranslationLanguagesEdit;
        }
    }

    /**
     * Returns array of website translation languages for viewing related to user and all related roles
     *
     * @return array|null
     */
    public function getMergedWebsiteTranslationLanguagesView()
    {
        if (null === $this->mergedWebsiteTranslationLanguagesView) {
            $this->mergedWebsiteTranslationLanguagesView = $this->getWebsiteTranslationLanguagesView();
            foreach ($this->getRoles() as $role) {
                /** @var User\UserRole $userRole */
                $userRole = User\UserRole::getById($role);
                $this->mergedWebsiteTranslationLanguagesView = array_merge($this->mergedWebsiteTranslationLanguagesView, $userRole->getWebsiteTranslationLanguagesView());
            }
            $this->mergedWebsiteTranslationLanguagesView = array_values($this->mergedWebsiteTranslationLanguagesView);
        }

        return $this->mergedWebsiteTranslationLanguagesView;
    }

    /**
     * Returns array of languages allowed for viewing. If view languages are empty all languages are allowed.
     *
     * @return array|null
     */
    public function getAllowedLanguagesForViewingWebsiteTranslations()
    {
        $mergedWebsiteTranslationLanguagesView = $this->getMergedWebsiteTranslationLanguagesView();
        if (empty($mergedWebsiteTranslationLanguagesView)) {
            return Tool::getValidLanguages();
        } else {
            return $mergedWebsiteTranslationLanguagesView;
        }
    }

    /**
     * @return int
     */
    public function getLastLogin()
    {
        return (int)$this->lastLogin;
    }

    /**
     * @param int $lastLogin
     *
     * @return $this
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = (int)$lastLogin;

        return $this;
    }

    /**
     * @return string
     */
    public static function getDefaultKeyBindings()
    {
        return json_encode(
            [
                [
                    'action' => 'save',
                    'key' => ord('S'),
                    'ctrl' => true,
                ],
                [
                    'action' => 'publish',
                    'key' => ord('P'),
                    'ctrl' => true,
                    'shift' => true,
                ],
                [
                    'action' => 'unpublish',
                    'key' => ord('U'),
                    'ctrl' => true,
                    'shift' => true,
                ],
                [
                    'action' => 'rename',
                    'key' => ord('R'),
                    'alt' => true,
                    'shift' => true,
                ],
                [
                    'action' => 'refresh',
                    'key' => 116,
                ],
                [
                    'action' => 'openAsset',
                    'key' => ord('A'),
                    'ctrl' => true,
                    'shift' => true,
                ],
                [
                    'action' => 'openObject',
                    'key' => ord('O'),
                    'ctrl' => true,
                    'shift' => true,
                ],
                [
                    'action' => 'openDocument',
                    'key' => ord('D'),
                    'ctrl' => true,
                    'shift' => true,
                ],
                [
                    'action' => 'openClassEditor',
                    'key' => ord('C'),
                    'ctrl' => true,
                    'shift' => true,

                ],
                [
                    'action' => 'openInTree',
                    'key' => ord('L'),
                    'ctrl' => true,
                    'shift' => true,

                ],
                [
                    'action' => 'showMetaInfo',
                    'key' => ord('I'),
                    'alt' => true,
                ],
                [
                    'action' => 'searchDocument',
                    'key' => ord('W'),
                    'alt' => true,
                ],
                [
                    'action' => 'searchAsset',
                    'key' => ord('A'),
                    'alt' => true,
                ],
                [
                    'action' => 'searchObject',
                    'key' => ord('O'),
                    'alt' => true,
                ],
                [
                    'action' => 'showElementHistory',
                    'key' => ord('H'),
                    'alt' => true,
                ],
                [
                    'action' => 'closeAllTabs',
                    'key' => ord('T'),
                    'alt' => true,
                ],
                [
                    'action' => 'searchAndReplaceAssignments',
                    'key' => ord('S'),
                    'alt' => true,
                ],
                [
                    'action' => 'glossary',
                    'key' => ord('G'),
                    'shift' => true,
                    'alt' => true,
                ],
                [
                    'action' => 'redirects',
                    'key' => ord('R'),
                    'ctrl' => false,
                    'alt' => true,
                ],
                [
                    'action' => 'sharedTranslations',
                    'key' => ord('T'),
                    'ctrl' => true,
                    'alt' => true,
                ],
                [
                    'action' => 'recycleBin',
                    'key' => ord('R'),
                    'ctrl' => true,
                    'alt' => true,
                ],
                [
                    'action' => 'notesEvents',
                    'key' => ord('N'),
                    'ctrl' => true,
                    'alt' => true,
                ],
                [
                    'action' => 'applicationLogger',
                    'key' => ord('L'),
                    'ctrl' => true,
                    'alt' => true,
                ],
                [
                    'action' => 'reports',
                    'key' => ord('M'),
                    'ctrl' => true,
                    'alt' => true,
                ],
                [
                    'action' => 'tagManager',
                    'key' => ord('H'),
                    'ctrl' => true,
                    'alt' => true,
                ],
                [
                    'action' => 'seoDocumentEditor',
                    'key' => ord('S'),
                    'ctrl' => true,
                    'alt' => true,
                ],
                [
                    'action' => 'robots',
                    'key' => ord('J'),
                    'ctrl' => true,
                    'alt' => true,
                ],
                [
                    'action' => 'httpErrorLog',
                    'key' => ord('O'),
                    'ctrl' => true,
                    'alt' => true,
                ],
                [
                    'action' => 'customReports',
                    'key' => ord('C'),
                    'ctrl' => true,
                    'alt' => true,
                ],
                [
                    'action' => 'tagConfiguration',
                    'key' => ord('N'),
                    'ctrl' => true,
                    'alt' => true,
                ],
                [
                    'action' => 'users',
                    'key' => ord('U'),
                    'ctrl' => true,
                    'alt' => true,
                ],
                [
                    'action' => 'roles',
                    'key' => ord('P'),
                    'ctrl' => true,
                    'alt' => true,
                ],
                [
                    'action' => 'clearAllCaches',
                    'key' => ord('Q'),
                    'ctrl' => false,
                    'alt' => true,
                ],
                [
                    'action' => 'clearDataCache',
                    'key' => ord('C'),
                    'ctrl' => false,
                    'alt' => true,
                ],
                [
                    'action' => 'quickSearch',
                    'key' => ord('F'),
                    'ctrl' => true,
                    'shift' => true,
                ],
            ]);
    }

    /**
     * @return string
     */
    public function getKeyBindings()
    {
        return $this->keyBindings ? $this->keyBindings : self::getDefaultKeyBindings();
    }

    /**
     * @param string $keyBindings
     */
    public function setKeyBindings($keyBindings)
    {
        $this->keyBindings = $keyBindings;
    }

    /**
     * @param string|null $key
     *
     * @return array|mixed|null|string
     */
    public function getTwoFactorAuthentication($key = null)
    {
        if (!is_array($this->twoFactorAuthentication) || empty($this->twoFactorAuthentication)) {
            // set defaults if no data is present
            $this->twoFactorAuthentication = [
                'required' => false,
                'enabled' => false,
                'secret' => '',
                'type' => '',
            ];
        }

        if ($key) {
            if (isset($this->twoFactorAuthentication[$key])) {
                return $this->twoFactorAuthentication[$key];
            } else {
                return null;
            }
        } else {
            return $this->twoFactorAuthentication;
        }
    }

    /**
     * You can either pass an array for setting the entire 2fa settings, or a key and a value as the second argument
     *
     * @param array|string $key
     * @param mixed $value
     */
    public function setTwoFactorAuthentication($key, $value = null)
    {
        if (is_string($key) && $value === null && strlen($key) > 3) {
            $this->twoFactorAuthentication = json_decode($key, true);
        } elseif (is_array($key)) {
            $this->twoFactorAuthentication = $key;
        } else {
            if (!is_array($this->twoFactorAuthentication)) {
                // load defaults
                $this->getTwoFactorAuthentication();
            }

            $this->twoFactorAuthentication[$key] = $value;
        }
    }

    public function hasImage()
    {
        if ($this->getImage() == $this->getFallbackImage()) {
            return false;
        }

        return true;
    }

    protected function getFallbackImage()
    {
        return PIMCORE_WEB_ROOT . '/bundles/pimcoreadmin/img/avatar.png';
    }
}
