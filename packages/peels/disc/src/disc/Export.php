<?php

declare(strict_types=1);

namespace peels\disc\disc;

use peels\disc\Disc;
use peels\disc\disc\File;
use peels\disc\exceptions\FileException;

class Export
{
    public const JSONDEFAULT = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;
    public const JSONPRETTY = JSON_PRETTY_PRINT;

    protected $fileInfo = null;
    protected $path = null;

    public function __construct(File $fileInfo)
    {
        $this->fileInfo = $fileInfo;

        $this->path = $fileInfo->getPath();
    }

    public function php2String($input): string
    {
        $string = '';

        if (\is_array($input) || \is_object($input)) {
            $string = '<?php return ' . \str_replace(['Closure::__set_state', 'stdClass::__set_state'], '(object)', \var_export($input, true)) . ';';
        } elseif (\is_scalar($input)) {
            $string = '<?php return "' . \str_replace('"', '\"', $input) . '";';
        } else {
            throw new FileException('Unknown input type.');
        }

        return $string;
    }

    public function json2String($input, bool $pretty = false, ?int $flags = null, ?int $depth = 512): string
    {
        $flags = ($flags) ?? self::JSONDEFAULT;
        $depth = ($depth) ?? 512;

        if ($pretty) {
            $flags = $flags | JSON_PRETTY_PRINT;
        }

        return json_encode($input, $flags, $depth);
    }

    public function ini2String(array $array, array $parent = []): string
    {
        $ini = '';

        foreach ($array as $key => $value) {
            if (\is_array($value)) {
                //subsection case
                //merge all the sections into one array...
                $subsection = \array_merge((array) $parent, (array) $key);
                //add section information to the output
                $ini .= '[' . \join('.', $subsection) . ']' . PHP_EOL;
                //recursively traverse deeper
                $ini .= $this->ini2String($value, $subsection);
            } else {
                //plain key->value case
                $ini .= "$key=$value" . PHP_EOL;
            }
        }

        return $ini;
    }

    public function php($data, ?int $chmod = null): int
    {
        $bytes = $this->changeModeOnBytes($this->fileInfo->save($this->php2String($data)), $chmod);

        /* if it's cached we need to flush it out so the old one isn't loaded */
        $this->removePhpFileFromOpcache($this->path);

        return $bytes;
    }

    public function removePhpFileFromOpcache(string $path): bool
    {
        return (\function_exists('opcache_invalidate')) ? \opcache_invalidate(Disc::resolve($path), true) : true;
    }

    public function json($jsonObj, ?bool $pretty = false, ?int $flags = null, ?int $depth = 512, ?int $chmod = null): int
    {
        $pretty = ($pretty) ?? false;
        $depth = ($depth) ?? 512;

        return $this->changeModeOnBytes($this->fileInfo->save($this->json2String($jsonObj, $pretty, $flags, $depth)), $chmod);
    }

    public function ini(array $array, ?int $chmod = null): int
    {
        return $this->changeModeOnBytes($this->fileInfo->save($this->ini2String($array)), $chmod);
    }

    public function content(string $content, ?int $chmod = null): int
    {
        return $this->changeModeOnBytes($this->fileInfo->save($content), $chmod);
    }

    public function csv(array $table, bool $includeHeader = true, string $separator = ",", string $enclosure = "\"", string $escape = "\\", string $eol = "\n"): bool
    {
        $fp = fopen($this->path, 'w');

        foreach ($table as $fields) {
            if ($includeHeader) {
                fputcsv($fp, array_keys($fields), $separator, $enclosure, $escape, $eol);

                $includeHeader = false;
            }
            fputcsv($fp, $fields, $separator, $enclosure, $escape, $eol);
        }

        return fclose($fp);
    }

    protected function changeModeOnBytes(int $bytes, ?int $chmod): int
    {
        if ($bytes && $chmod) {
            \chmod($this->path, $chmod);
        }

        return $bytes;
    }
} /* end class */
