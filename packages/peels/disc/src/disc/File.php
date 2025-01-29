<?php

declare(strict_types=1);

namespace peels\disc\disc;

use peels\disc\Disc;
use peels\disc\disc\Export;
use peels\disc\disc\Import;
use peels\disc\disc\DiscSplFileInfo;
use peels\disc\disc\FileSplFileObject;
use peels\disc\exceptions\FileException;

class File extends DiscSplFileInfo
{
    protected $fileObject = null;

    public $import = null;
    public $export = null;

    public function __construct(string $path)
    {
        parent::__construct($path);

        $this->import = new import($this);
        $this->export = new export($this);
    }

    /**
     * Method __call
     */
    public function __call(string $name, $arguments)
    {
        /* throws error on fail */
        if (!$this->fileObject) {
            throw new FileException('No file open');
        }

        if (!method_exists($this->fileObject, $name)) {
            trigger_error(sprintf('Call to undefined function: %s::%s().', get_class($this), $name), E_USER_ERROR);
        }

        return $this->fileObject->$name(...$arguments);
    }

    public function open(string $mode = 'r'): self
    {
        if (in_array($mode, ['r', 'r+'])) {
            /* required file */
            $path = $this->getPath(true);
        } else {
            /* file not required */
            $path = $this->getPath();

            Disc::autoGenMissingDirectory($path);
        }

        /* close properly */
        unset($this->fileObject);

        /* make a new one */
        $this->fileObject = new fileSplFileObject($path, $mode);

        return $this;
    }

    public function create(string $mode = 'w'): self
    {
        return $this->open($mode);
    }

    public function append(string $mode = 'a'): self
    {
        return $this->open($mode);
    }

    public function close(): self
    {
        if (!$this->fileObject) {
            throw new FileException('No file open');
        }

        unset($this->fileObject);

        return $this;
    }

    public function name(string $suffix = null): string
    {
        // SplFileInfo::getBasename — Gets the base name of the file
        // SplFileInfo::getFilename — Gets the filename
        return ($suffix) ? $this->getBasename($suffix) : $this->getFilename();
    }

    public function asArray(int $flags = 0): array
    {
        return \file($this->getPath(true), $flags);
    }

    public function echo(): int
    {
        return \readfile($this->getPath(true));
    }

    public function contents(): string
    {
        return \file_get_contents($this->getPath(true));
    }

    /**
     * atomicFilePutContents - atomic file_put_contents
     */
    public function save(string $content): int
    {
        /* create absolute path */
        $path = $this->getPath();

        Disc::autoGenMissingDirectory($path);

        /* get the path where you want to save this file so we can put our file in the same directory */
        $directory = \dirname($path);

        /* is this directory writeable */
        if (!is_writable($directory)) {
            throw new fileException($directory . ' is not writable.');
        }

        /* create a temporary file with unique file name and prefix */
        $temporaryFile = \tempnam($directory, 'afpc_');

        /* did we get a temporary filename */
        if ($temporaryFile === false) {
            throw new fileException('Could not create temporary file ' . $temporaryFile . '.');
        }

        /* write to the temporary file */
        $bytes = \file_put_contents($temporaryFile, $content, LOCK_EX);

        /* did we write anything? */
        if ($bytes === false) {
            throw new fileException('No bytes written by file_put_contents');
        }

        /* move it into place - this is the atomic function */
        if (\rename($temporaryFile, $path) === false) {
            throw new fileException('Could not rename temporary file ' . $temporaryFile . ' ' . $path . '.');
        }

        /* return the number of bytes written */
        return $bytes;
    }

    /* move & rename in DiscSplFileInfo */

    public function mime(): string
    {
        return mime_content_type($this->getPath(true));
    }

    public function isImage(): bool
    {
        return exif_imagetype($this->getPath(true)) !== false;
    }

    public function width(): int
    {
        $path = $this->getPath(true);

        if (exif_imagetype($path) === false) {
            throw new FileException('File "' . $this->getPath(true) . '" is not an image.');
        }

        $details = getimagesize($path);

        return $details[0];
    }

    public function height(): int
    {
        $path = $this->getPath(true);

        if (exif_imagetype($path) === false) {
            throw new FileException('File "' . $this->getPath(true) . '" is not an image.');
        }

        $details = getimagesize($path);

        return $details[1];
    }

    public function datauri(): string
    {
        return 'data:' . $this->mime() . ';base64,' . base64_decode(file_get_contents($this->getPath(true)));
    }

    public function src(): string
    {
        return Disc::resolveWWW($this->getPath(true), Disc::FILE);
    }

    public function download(string $differentFilename = null, string $differentMime = null): void
    {
        $filename = $differentFilename ?? $this->getFilename();
        $mime = $differentMime ?? $this->mime();

        $fp = fopen($this->getPath(true), 'rb');

        // Clean output buffer
        if (ob_get_level() !== 0 && @ob_end_clean() === false) {
            @ob_clean();
        }

        // Generate the server headers
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . $this->getSize());
        header('Cache-Control: private, no-transform, no-store, must-revalidate');

        // Flush 1MB chunks of data
        while (!feof($fp) && ($data = fread($fp, 1048576)) !== false) {
            echo $data;
        }

        fclose($fp);
        exit;
    }

    public function getPath(bool $required = null, bool $strip = false): string
    {
        // show the correct error
        $required = ($required === true) ? Disc::FILE : 0;

        return Disc::resolve($this->getPathname(), $strip, $required);
    }
}
