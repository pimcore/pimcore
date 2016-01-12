<?php
/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @category   Pimcore
 * @package    Translation
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace Pimcore\Model\Translation;

use Pimcore\Model;
use Pimcore\Tool;

class Admin extends AbstractTranslation {

    /**
     * @param $id
     * @param bool $create
     * @param bool $returnIdIfEmpty
     * @param null $language
     * @return array
     * @throws \Exception
     * @throws \Zend_Exception
     */
    public static function getByKeyLocalized($id, $create = false, $returnIdIfEmpty = false, $language = null) {
        if($user = Tool\Admin::getCurrentUser()) {
            $language = $user->getLanguage();
        } else if ($user = Tool\Authentication::authenticateSession()) {
            $language = $user->getLanguage();
        } else if(\Zend_Registry::isRegistered("Zend_Locale")) {
            $language = (string) \Zend_Registry::get("Zend_Locale");
        }

        if(!in_array($language,Tool\Admin::getLanguages())){
            $config = \Pimcore\Config::getSystemConfig();
            $language = $config->general->language;
        }


        return self::getByKey($id, $create, $returnIdIfEmpty)->getTranslation($language);
    }
}
