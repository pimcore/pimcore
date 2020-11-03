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

use Twig\Environment;
use Symfony\Component\Templating\EngineInterface;
use \Symfony\Component\Templating\DelegatingEngine as BaseDelegatingEngine;

class DelegatingEngine extends BaseDelegatingEngine
{
    const ENGINE_TWIG = 'twig';
    const ENGINE_CUSTOM = 'custom';

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var string
     */
    protected $defaultEngine = self::ENGINE_TWIG;

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
        if ($this->defaultEngine === self::ENGINE_TWIG) {
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
        if ($this->defaultEngine === self::ENGINE_TWIG) {
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
        if ($this->defaultEngine === self::ENGINE_TWIG) {
            return true;
        } else {
            return parent::supports($name);
        }
    }
}
