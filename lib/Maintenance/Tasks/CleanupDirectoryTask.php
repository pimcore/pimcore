<?php

namespace Pimcore\Maintenance\Tasks;

use Pimcore\Maintenance\TaskInterface;

class CleanupDirectoryTask implements TaskInterface
{
    public function __construct(
        protected int $tmpFileTime,
        protected array $cleanupDirectories
    ) {
    }


    public function execute(): void
    {
        foreach ($this->cleanupDirectories as $directory) {
            $this->deleteFilesInFolderOlderThanSeconds($directory, $this->tmpFileTime);
        }
    }

    /**
     * @param string $folder
     * @param int $seconds
     */
    private function deleteFilesInFolderOlderThanSeconds(string $folder, int $seconds): void
    {
        if (!is_dir($folder)) {
            return;
        }

        $directory = new \RecursiveDirectoryIterator($folder);
        $filter = new \RecursiveCallbackFilterIterator($directory, function (\SplFileInfo $current, $key, $iterator) use ($seconds) {
            if (strpos($current->getFilename(), '-low-quality-preview.svg')) {
                // do not delete low quality image previews
                return false;
            }

            if ($current->isFile()) {
                if ($current->getATime() && $current->getATime() < (time() - $seconds)) {
                    return true;
                }
            } else {
                return true;
            }

            return false;
        });

        $iterator = new \RecursiveIteratorIterator($filter);

        foreach ($iterator as $file) {
            /**
             * @var \SplFileInfo $file
             */
            if ($file->isFile()) {
                @unlink($file->getPathname());
            }

            if (is_dir_empty($file->getPath())) {
                @rmdir($file->getPath());
            }
        }
    }
}
