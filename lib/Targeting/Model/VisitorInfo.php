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

use Pimcore\Model\Tool\Targeting\Rule;
use Pimcore\Model\Tool\Targeting\TargetGroup;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class VisitorInfo implements \IteratorAggregate
{
    const VISITOR_ID_COOKIE_NAME = '_pc_vis';
    const SESSION_ID_COOKIE_NAME = '_pc_ses';

    const ACTION_SCOPE_RESPONSE = 'response';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var string|null
     */
    private $visitorId;

    /**
     * @var string|null
     */
    private $sessionId;

    /**
     * Matched targeting rules
     *
     * @var Rule[]
     */
    private $matchingTargetingRules = [];

    /**
     * Assigned target groups with count
     *
     * @var TargetGroupAssignment[]
     */
    private $targetGroupAssignments = [];

    /**
     * Target group assignments sorted by count
     *
     * @var TargetGroupAssignment[]|null
     */
    private $sortedTargetGroupAssignments;

    /**
     * Plain list of assigned target groups
     *
     * @var TargetGroup[]|null
     */
    private $targetGroups;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var array
     */
    private $actions = [];

    /**
     * List of frontend data providers which are expected to provide data
     *
     * @var array
     */
    private $frontendDataProviders = [];

    /**
     * @var Response
     */
    private $response;

    public function __construct(Request $request, string $visitorId = null, string $sessionId = null)
    {
        $this->request = $request;
        $this->visitorId = $visitorId;
        $this->sessionId = $sessionId;
    }

    public static function fromRequest(Request $request): self
    {
        $visitorId = $request->cookies->get(self::VISITOR_ID_COOKIE_NAME);
        if (!empty($visitorId)) {
            $visitorId = (string)$visitorId;
        } else {
            $visitorId = null;
        }

        $sessionId = $request->cookies->get(self::SESSION_ID_COOKIE_NAME);
        if (!empty($sessionId)) {
            $sessionId = (string)$sessionId;
        } else {
            $sessionId = null;
        }

        return new static($request, $visitorId, $sessionId);
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

    public function hasSessionId(): bool
    {
        return !empty($this->sessionId);
    }

    /**
     * @return string|null
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @return Rule[]
     */
    public function getMatchingTargetingRules(): array
    {
        return $this->matchingTargetingRules;
    }

    /**
     * @param Rule[] $targetingRules
     */
    public function setMatchingTargetingRules(array $targetingRules = [])
    {
        $this->matchingTargetingRules = [];
        foreach ($targetingRules as $targetingRule) {
            $this->addMatchingTargetingRule($targetingRule);
        }
    }

    public function addMatchingTargetingRule(Rule $targetingRule)
    {
        if (!in_array($targetingRule, $this->matchingTargetingRules, true)) {
            $this->matchingTargetingRules[] = $targetingRule;
        }
    }

    /**
     * Returns target group assignments ordered by assignment count
     *
     * @return TargetGroupAssignment[]
     */
    public function getTargetGroupAssignments(): array
    {
        if (null !== $this->sortedTargetGroupAssignments) {
            return $this->sortedTargetGroupAssignments;
        }

        /** @var TargetGroupAssignment[] $assignments */
        $assignments = array_values($this->targetGroupAssignments);

        // sort reverse (highest count first)
        usort($assignments, function (TargetGroupAssignment $a, TargetGroupAssignment $b) {
            $aCount = $a->getCount();
            $bCount = $b->getCount();

            if ($aCount === $bCount) {
                return 0;
            }

            return $aCount < $bCount ? 1 : -1;
        });

        $this->sortedTargetGroupAssignments = $assignments;

        return $this->sortedTargetGroupAssignments;
    }

    public function hasTargetGroupAssignment(TargetGroup $targetGroup): bool
    {
        return isset($this->targetGroupAssignments[$targetGroup->getId()]);
    }

    public function getTargetGroupAssignment(TargetGroup $targetGroup): TargetGroupAssignment
    {
        return $this->targetGroupAssignments[$targetGroup->getId()];
    }

    public function assignTargetGroup(TargetGroup $targetGroup, int $count = 1, bool $overwrite = false)
    {
        if ($count < 1) {
            throw new \InvalidArgumentException('Count must be greater than 0');
        }

        if (isset($this->targetGroupAssignments[$targetGroup->getId()])) {
            if ($overwrite) {
                $this->targetGroupAssignments[$targetGroup->getId()]->setCount($count);
            } else {
                $this->targetGroupAssignments[$targetGroup->getId()]->inc($count);
            }
        } else {
            $this->targetGroupAssignments[$targetGroup->getId()] = new TargetGroupAssignment($targetGroup, $count);
        }

        $this->targetGroups = null;
        $this->sortedTargetGroupAssignments = null;
    }

    public function clearAssignedTargetGroup(TargetGroup $targetGroup)
    {
        if (isset($this->targetGroupAssignments[$targetGroup->getId()])) {
            unset($this->targetGroupAssignments[$targetGroup->getId()]);

            $this->targetGroups = null;
            $this->sortedTargetGroupAssignments = null;
        }
    }

    /**
     * Returns assigned target groups ordered by assignment count
     *
     * @return TargetGroup[]
     */
    public function getAssignedTargetGroups(): array
    {
        if (null === $this->targetGroups) {
            $this->targetGroups = array_map(function (TargetGroupAssignment $assignment) {
                return $assignment->getTargetGroup();
            }, $this->getTargetGroupAssignments());
        }

        return $this->targetGroups;
    }

    public function getFrontendDataProviders(): array
    {
        return $this->frontendDataProviders;
    }

    public function setFrontendDataProviders(array $providers)
    {
        $this->frontendDataProviders = [];
        foreach ($providers as $provider) {
            $this->addFrontendDataProvider($provider);
        }
    }

    public function addFrontendDataProvider(string $key)
    {
        if (!in_array($key, $this->frontendDataProviders, true)) {
            $this->frontendDataProviders[] = $key;
        }
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

    public function addAction(array $action)
    {
        $this->actions[] = $action;
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function hasActions(): bool
    {
        return count($this->actions) > 0;
    }
}
