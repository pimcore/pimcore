<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Contracts\PimcoreAgent;

/**
 * Reads pending proposals attached to an agent session, for consumers that render a
 * "Agent proposed N changes — review" summary in their own timeline (e.g. collab-bundle).
 *
 * "Pending" here means proposals that were produced by the agent but not yet accepted or
 * rejected by a human reviewer. The exact schema of a proposal is implementation-specific;
 * `ProposalSummary` is the shared subset that consumers can rely on.
 */
interface PendingProposalsReaderInterface
{
    /**
     * @return list<ProposalSummary>
     */
    public function listPendingForSession(string $sessionId): array;
}
