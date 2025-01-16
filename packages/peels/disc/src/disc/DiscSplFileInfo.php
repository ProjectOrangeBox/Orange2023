<?php

declare(strict_types=1);

namespace peels\disc\disc;

use SplFileInfo;
use peels\disc\Disc;
use peels\disc\exceptions\DiscException;

/**
 * Shared between Disc and File classes
 * (both extend it)
 */

class DiscSplFileInfo extends SplFileInfo
{
    public function __construct(string $filename)
    {
        parent::__construct(Disc::resolve($filename));
    }

    public function touch(): bool
    {
        return \touch($this->getPath(true));
    }

    public function info(?string $option = null, $arg1 = null): array|false
    {
        $info = [];

        $absPath = $this->getPath(true);

        $info += \stat($absPath);
        $info += \pathInfo($absPath);

        $info['dirname'] = Disc::resolve($info['dirname'], true);

        $info['type'] = $this->getType();

        $dateFormat = ($arg1) ? $arg1 : 'r';

        $info['atime_display'] = $this->accessTime($dateFormat);
        $info['mtime_display'] = $this->modificationTime($dateFormat);
        $info['ctime_display'] = $this->changeTime($dateFormat);

        $permissions = $this->getPerms();

        $info['permissions_display'] = Disc::formatMode($permissions, DISC::ALL);
        $info['permissions_t'] = Disc::formatMode($permissions, DISC::TYPE);
        $info['permissions_ugw'] = Disc::formatMode($permissions, DISC::PERMISSION);

        $info['uid_display'] = $this->ownerName();
        $info['gid_display'] = $this->groupName();

        $info['size_display'] = Disc::formatSize($this->size());

        $info['isDirectory'] = (bool)$this->isDirectory();
        $info['isWritable'] = (bool)$this->isWritable();
        $info['isReadable'] = (bool)$this->isReadable();
        $info['isFile'] = (bool)$this->isFile();

        $info['root'] = Disc::getRoot();

        if ($option) {
            if (!in_array($option, $info)) {
                throw new DiscException('Unknown option ' . $option);
            }

            $info = $info[$option];
        }

        return $info;
    }

    public function isDirectory(): bool
    {
        return $this->isDir();
    }

    public function directory(): string
    {
        return dirname($this->getPath(true, true));
    }

    public function size(bool $format = false): int|string
    {
        clearstatcache();

        return ($format) ? Disc::formatSize($this->getSize()) : $this->getSize();
    }

    public function accessTime(string $dateFormat = null): int|string
    {
        return Disc::formatTime($this->getATime(), $dateFormat);
    }

    public function changeTime(string $dateFormat = null): int|string
    {
        return Disc::formatTime($this->getCTime(), $dateFormat);
    }

    public function modificationTime(string $dateFormat = null): int|string
    {
        return Disc::formatTime($this->getMTime(), $dateFormat);
    }

    public function group(): array|int|false
    {
        return $this->getGroup();
    }

    public function groupName(): string
    {
        return posix_getgrgid($this->group())['name'];
    }

    public function owner(): array|int|false
    {
        return $this->getOwner();
    }

    public function ownerName(): string
    {
        return posix_getpwuid($this->owner())['name'];
    }

    public function permissions(int $options = 0)
    {
        $rawPerms = $this->getPerms();

        return ($options) ? Disc::formatMode($rawPerms, $options) : octdec(substr(sprintf('%o', $rawPerms), -4));
    }

    public function changePermissions(int $mode): bool
    {
        $oMask = umask(0);

        $rtn = \chmod($this->getPath(true), $mode);

        umask($oMask);

        return $rtn;
    }

    public function changeGroup($group): bool
    {
        return \chgrp($this->getPath(true), $group);
    }

    public function changeOwner($user): bool
    {
        return \chown($this->getPath(true), $user);
    }

    public function type(): string|false
    {
        return $this->getType();
    }

    public function rename(string $name): self
    {
        if (strpos($name, DIRECTORY_SEPARATOR) !== false) {
            throw new DiscException('New name must not include a path. Please use move(...)');
        }

        return $this->move(dirname($this->getPath(true)) . DIRECTORY_SEPARATOR . $name);
    }

    public function move(string $destination): self
    {
        $destination = Disc::resolve($destination);

        if (!is_dir($destination) && file_exists($destination)) {
            throw new DiscException('Destination already exists');
        }

        if (!is_dir($destination)) {
            (new Directory($destination))->create();
        }

        \rename($this->getPath(true), $destination);

        parent::__construct($destination);

        return $this;
    }

    public function exists(string $insideDir = null): bool
    {
        $path = ($insideDir == null) ? $this->getPath() : $this->getPath() . DIRECTORY_SEPARATOR . ltrim($insideDir, DIRECTORY_SEPARATOR);

        return \file_exists($path);
    }
}
