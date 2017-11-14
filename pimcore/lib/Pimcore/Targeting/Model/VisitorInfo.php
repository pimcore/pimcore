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
    private $matchingTargetingRules = [];

    /**
     * Assigned target groups with count
     *
     * @var TargetGroupAssignment[]
     */
    private $targetGroupAssignments = [];

    /**
     * Plain list of assigned target groups
     *
     * @var TargetGroup[]
     */
    private $targetGroups;

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
        $this->matchingTargetingRules[] = $targetingRule;
    }

    /**
     * @return TargetGroupAssignment[]
     */
    public function getTargetGroupAssignments(): array
    {
        return $this->targetGroupAssignments;
    }

    public function assignTargetGroup(TargetGroup $targetGroup)
    {
        if (isset($this->targetGroupAssignments[$targetGroup->getId()])) {
            $this->targetGroupAssignments[$targetGroup->getId()]->inc();
        } else {
            $this->targetGroupAssignments[$targetGroup->getId()] = new TargetGroupAssignment($targetGroup, 1);
            $this->targetGroups = null;
        }
    }

    public function clearAssignedTargetGroup(TargetGroup $targetGroup)
    {
        if (isset($this->targetGroupAssignments[$targetGroup->getId()])) {
            unset($this->targetGroupAssignments[$targetGroup->getId()]);
            $this->targetGroups = null;
        }
    }

    /**
     * @return TargetGroup[]
     */
    public function getAssignedTargetGroups(): array
    {
        if (null === $this->targetGroups) {
            $this->targetGroups = array_map(function(TargetGroupAssignment $assignment) {
                return $assignment->getTargetGroup();
            }, $this->targetGroupAssignments);
        }

        return $this->targetGroups;
    }

    /**
     * @return null|TargetGroup
     */
    public function getPrimaryTargetGroup()
    {
        if (0 === count($this->targetGroupAssignments)) {
            return null;
        }

        /** @var TargetGroupAssignment[] $assignments */
        $assignments = array_values($this->targetGroupAssignments);

        // sort reverse (highest count first)
        usort($assignments, function(TargetGroupAssignment $a, TargetGroupAssignment $b) {
            $aCount = $a->getCount();
            $bCount = $b->getCount();

            if ($aCount === $bCount) {
                return 0;
            }

            return $aCount < $bCount ? 1 : -1;
        });

        return $assignments[0]->getTargetGroup();
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
