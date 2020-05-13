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
 * @package    Document
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Hardlink\Wrapper;

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Document\Hardlink\Dao getDao()
 */
class Link extends Model\Document\Link implements Model\Document\Hardlink\Wrapper\WrapperInterface
{
    use Model\Document\Hardlink\Wrapper;

    public function getHref()
    {
        if ($this->getLinktype() == 'internal' && $this->getInternalType() == 'document') {
            if (strpos($this->getObject()->getRealFullPath(), $this->getHardLinkSource()->getSourceDocument()->getRealFullPath() . '/') === 0
                || $this->getHardLinkSource()->getSourceDocument()->getRealFullPath() === $this->getObject()->getRealFullPath()
            ) {
                // link target is child of hardlink source
                $c = Model\Document\Hardlink\Service::wrap($this->getObject());
                if ($c instanceof WrapperInterface) {
                    $hardLink = $this->getHardLinkSource();
                    $c->setHardLinkSource($hardLink);

                    if ($hardLink->getSourceDocument()->getRealFullpath() == $c->getRealFullPath()) {
                        $c->setPath($hardLink->getPath());
                        $c->setKey($hardLink->getKey());
                    } else {
                        $c->setPath(preg_replace('@^' . preg_quote($hardLink->getSourceDocument()->getRealFullpath(), '@') . '@', $hardLink->getRealFullpath(), $c->getRealPath()));
                    }

                    $this->setObject($c);
                }
            }
        }

        return parent::getHref();
    }
}
