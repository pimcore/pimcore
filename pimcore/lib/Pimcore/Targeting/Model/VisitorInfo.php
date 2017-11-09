<?php

declare(strict_types=1);

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

namespace Pimcore\Targeting\Model;

use Pimcore\Model\Tool\Targeting\Persona as TargetGroup;
use Pimcore\Model\Tool\Targeting\Rule;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class VisitorInfo implements \IteratorAggregate
{
    const VISITOR_ID_COOKIE_NAME = '_pc_vis';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var string|null
     */
    private $visitorId;

    /**
     * Matched targeting rules
     *
     * @var Rule[]
     */
    private $targetingRules = [];

    /**
     * Applied target groups
     *
     * @var TargetGroup[]
     */
    private $targetGroups = [];

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var Response
     */
    private $response;

    public function __construct(Request $request, string $visitorId = null, array $data = [])
    {
        $this->request   = $request;
        $this->visitorId = $visitorId;
        $this->data      = $data;
    }

    public static function fromRequest(Request $request): self
    {
        $visitorId = $request->cookies->get(self::VISITOR_ID_COOKIE_NAME);

        return new static($request, $visitorId);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function hasVisitorId(): bool
    {
        return !empty($this->visitorId);
    }

    /**
     * @return string|null
     */
    public function getVisitorId()
    {
        return $this->visitorId;
    }

    /**
     * @return Rule[]
     */
    public function getTargetingRules(): array
    {
        return $this->targetingRules;
    }

    /**
     * @param Rule[] $targetingRules
     */
    public function setTargetingRules(array $targetingRules = [])
    {
        $this->targetingRules = [];
        foreach ($targetingRules as $targetingRule) {
            $this->addTargetingRule($targetingRule);
        }
    }

    public function addTargetingRule(Rule $targetingRule)
    {
        $this->targetingRules[] = $targetingRule;
    }

    /**
     * @return TargetGroup[]
     */
    public function getTargetGroups(): array
    {
        return array_values($this->targetGroups);
    }

    /**
     * @param TargetGroup[] $targetGroups
     */
    public function setTargetGroups(array $targetGroups = [])
    {
        $this->targetGroups = [];
        foreach ($targetGroups as $targetGroup) {
            $this->addTargetGroup($targetGroup);
        }
    }

    public function addTargetGroup(TargetGroup $targetGroup)
    {
        $this->targetGroups[$targetGroup->getId()] = $targetGroup;
    }

    public function hasResponse(): bool
    {
        return null !== $this->response;
    }

    /**
     * @return Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    public function has($key): bool
    {
        return isset($this->data[$key]);
    }

    public function get($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }
}
