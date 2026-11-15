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

namespace Pimcore\Tests\Support\Util;

use Exception;
use Pimcore\Video\Adapter\Ffmpeg;

/**
 * Stands in for FFmpeg in tests of the video conversion job: save() writes the destination file like
 * FFmpeg would and then reports the configured outcome, so no ffmpeg binary is needed.
 *
 * It is a named class because the conversion job, including its converters, is serialized into the TmpStore.
 */
class FakeVideoConverter extends Ffmpeg
{
    public const OUTCOME_SUCCESS = 'success';

    public const OUTCOME_FAILURE = 'failure';

    public const OUTCOME_EXCEPTION = 'exception';

    public const CONTENT = 'converted video';

    public string $outcome = self::OUTCOME_SUCCESS;

    public function save(): bool
    {
        file_put_contents($this->getDestinationFile(), self::CONTENT);

        if ($this->outcome === self::OUTCOME_EXCEPTION) {
            throw new Exception('Conversion failed');
        }

        return $this->outcome === self::OUTCOME_SUCCESS;
    }

    public function destroy(): void
    {
    }
}
