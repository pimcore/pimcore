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

/**
 * ----------------------------------------------------------------------------------
 * based on @author ZF1 Zend_View_Helper_HeadTitle
 * ----------------------------------------------------------------------------------
 */

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

namespace Pimcore\Twig\Extension\Templating;

use Pimcore\Twig\Extension\Templating\Placeholder\AbstractExtension;
use Pimcore\Twig\Extension\Templating\Placeholder\Container;
use Pimcore\Twig\Extension\Templating\Placeholder\Exception;
use Twig\Extension\RuntimeExtensionInterface;

class HeadTitle extends AbstractExtension implements RuntimeExtensionInterface
{
    /**
     * Registry key for placeholder
     *
     */
    protected string $_regKey = 'HeadTitle';

    /**
     * Default title rendering order (i.e. order in which each title attached)
     *
     */
    protected ?string $_defaultAttachOrder = null;

    /**
     *
     * @return $this
     */
    public function __invoke(string $title = null, string $setType = null): static
    {
        if (null === $setType) {
            $setType = (null === $this->getDefaultAttachOrder())
                ? Container::APPEND
                : $this->getDefaultAttachOrder();
        }

        $title = (string) $title;

        if ($title !== '') {
            if ($setType == Container::SET) {
                $this->set($title);
            } elseif ($setType == Container::PREPEND) {
                $this->prepend($title);
            } else {
                $this->append($title);
            }
        }

        return $this;
    }

    /**
     * Set a default order to add titles
     *
     *
     * @return $this
     */
    public function setDefaultAttachOrder(string $setType): static
    {
        if (!in_array($setType, [
            Container::APPEND,
            Container::SET,
            Container::PREPEND,
        ])) {
            throw new Exception("You must use a valid attach order: 'PREPEND', 'APPEND' or 'SET'");
        }

        $this->_defaultAttachOrder = $setType;

        return $this;
    }

    /**
     * Get the default attach order, if any.
     *
     */
    public function getDefaultAttachOrder(): ?string
    {
        return $this->_defaultAttachOrder;
    }

    /**
     * Turn helper into string
     *
     *
     */
    public function toString(string $indent = null, string $locale = null): string
    {
        $indent = (null !== $indent)
            ? $this->getWhitespace($indent)
            : $this->getIndent();

        $output = '';
        if (($prefix = $this->getPrefix())) {
            $output .= $prefix;
        }

        $output .= $this->getRawContent();

        if (($postfix = $this->getPostfix())) {
            $output .= $postfix;
        }

        $output = ($this->_autoEscape) ? $this->_escape($output) : $output;

        return $indent . '<title>' . $output . '</title>';
    }

    /**
     * Get container content without indentation, prefix or postfix
     *
     */
    public function getRawContent(): string
    {
        return implode(
            $this->getContainer()->getSeparator(),
            $this->getContainer()->getArrayCopy()
        );
    }
}
