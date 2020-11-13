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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Templating;

use Symfony\Component\Templating\DelegatingEngine as BaseDelegatingEngine;
use Symfony\Component\Templating\EngineInterface;
use Twig\Environment;

class TwigDefaultDelegatingEngine extends BaseDelegatingEngine
{
    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var bool
     */
    protected $delegate = false;

    /**
     * @param Environment $twig
     * @param EngineInterface[] $engines
     */
    public function __construct(Environment $twig, array $engines = [])
    {
        $this->twig = $twig;

        parent::__construct($engines);
    }

    /**
     * @inheritdoc
     */
    public function exists($name)
    {
        if (!$this->delegate) {
            return $this->twig->getLoader()->exists($name);
        } else {
            return parent::exists($name);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws \Exception
     */
    public function render($name, array $parameters = [])
    {
        if (!$this->delegate) {
            return $this->twig->render($name, $parameters);
        } else {
            return parent::render($name, $parameters);
        }
    }

    /**
     * @inheritdoc
     */
    public function supports($name)
    {
        if (!$this->delegate) {
            return true;
        } else {
            return parent::supports($name);
        }
    }

    /**
     * @param bool $delegate
     */
    public function setDelegate(bool $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * @return bool $delegate
     */
    public function isDelegate()
    {
        return $this->delegate;
    }
}
