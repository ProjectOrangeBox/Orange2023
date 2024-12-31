<?php

declare(strict_types=1);

namespace peels\disc\disc;

use peels\disc\Disc;
use peels\disc\disc\DiscSplFileInfo;

class Directory extends DiscSplFileInfo
{
    public function name(): string
    {
        return $this->getFilename();
    }

    public function create(int $mode = 0777, bool $recursive = true): bool
    {
        $path = $this->getPath();

        $bool = true;

        if (!\file_exists($path)) {
            $umask = \umask(0);
            $bool = \mkdir($path, $mode, $recursive);
            \umask($umask);
        }

        return $bool;
    }

    public function list(string $pattern = '*', int $flags = 0, bool $recursive = false): array
    {
        $path = $this->getPath(true);

        $array = ($recursive) ? $this->listRecursive($path . DIRECTORY_SEPARATOR . $pattern, $flags) : \glob($path . DIRECTORY_SEPARATOR . $pattern, $flags);

        return Disc::stripRootPaths($array);
    }

    public function listAll(string $pattern = '*', int $flags = 0): array
    {
        return $this->list($pattern, $flags, true);
    }

    /**
     * remove old files & directories inside this directory
     */
    public function clean(int $days): void
    {
        $path = $this->getPath(true);

        // flush temp upload directory
        if (is_dir($path) && $days > 0) {
            // let's remove any uploads sitting around for X days
            $now = time();
            $dir = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
            $dir = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::CHILD_FIRST);

            if (is_array($dir)) {
                foreach ($dir as $file) {
                    // Comparing the current time with the time when file was created
                    if ($file && $now - filemtime($file) >= 60 * 60 * 24 * $days) {
                        if ($file->isFile()) {
                            unlink($file);
                        } elseif ($file->isDir()) {
                            rmdir($file);
                        }
                    }
                }
            }
        }
    }

    /** protected */

    protected function listRecursive(string $pattern, int $flags = 0): array
    {
        $files = \glob($pattern, $flags);

        foreach (\glob(\dirname($pattern) . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR | GLOB_NOSORT) as $directory) {
            /* recursive loop */
            $files = \array_merge($files, self::listRecursive($directory . DIRECTORY_SEPARATOR . \basename($pattern), $flags));
        }

        return $files;
    }

    public function getPath(bool $required = null, bool $strip = false): string
    {
        // show the correct error
        $required = ($required === true) ? Disc::FOLDER : 0;

        return Disc::resolve($this->getPathname(), $strip, $required);
    }
}
