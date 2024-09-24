<?php

declare(strict_types=1);

use peels\validate\exceptions\InvalidValue;

/*
 * Great for local cache files because the file is written atomically
 * that way another thread doesn't read a 1/2 written file
 */

if (!function_exists('file_put_contents_atomic')) {
    function file_put_contents_atomic(string $filePath, string $content, int $flags = 0, $context = null): int|false
    {
        // !multiple exits

        $tempFilePath = $filePath . \hrtime(true);
        $strlen = strlen($content);

        if (file_put_contents($tempFilePath, $content, $flags, $context) !== $strlen) {
            return false;
        }

        // atomic function
        if (rename($tempFilePath, $filePath, $context) === false) {
            return false;
        }

        // flush from the cache
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($filePath, true);
        } elseif (function_exists('apc_delete_file')) {
            apc_delete_file($filePath);
        }

        return $strlen;
    }
}

/*
 * build a standard html element
 */
if (!function_exists('element')) {
    function element(string $tag, array $attr = [], string $content = '', bool $escape = true)
    {
        $selfClosing = ['area', 'base', 'br', 'embed', 'hr', 'iframe', 'img', 'input', 'link', 'meta', 'param', 'source'];

        $html = '<' . $tag . ' ' . str_replace("=", '="', http_build_query($attr, '', '" ', PHP_QUERY_RFC3986)) . '">';

        if (!empty($content)) {
            $html .= ($escape) ? htmlentities($content) : $content;
        }

        if (!in_array($tag, $selfClosing)) {
            $html .= '</' . $tag . '>';
        }

        return $html;
    }
}


/*
 * make a datauri
 *
 * <img src="***">
 */
if (!function_exists('dataUri')) {
    function dataUri(string $file)
    {
        echo 'data:' . mime_content_type($file) . ';base64,' . base64_encode(file_get_contents($file));
    }
}

/*
 * convert string to a specific format
 */
if (!function_exists('convertLabel')) {
    function convertLabel(string $value, string $case = 'camel'): string
    {
        switch ($case) {
            case 'normalize':
                $value = mb_convert_case($value, MB_CASE_LOWER, mb_detect_encoding($value));
                $value = preg_replace('/[^a-z0-9]/i', '', $value);
                break;
            case 'lower':
                $value = substr($value, 0, 1) . implode(' ', preg_split('/(?=[A-Z])/', substr($value, 1)));
                $value = mb_convert_case($value, MB_CASE_LOWER, mb_detect_encoding($value));
                $value = str_replace('_', ' ', $value);
                break;
            case 'upper':
                $value = substr($value, 0, 1) . implode(' ', preg_split('/(?=[A-Z])/', substr($value, 1)));
                $value = mb_convert_case($value, MB_CASE_UPPER, mb_detect_encoding($value));
                $value = str_replace('_', ' ', $value);
                break;
            case 'title':
                $value = substr($value, 0, 1) . implode(' ', preg_split('/(?=[A-Z])/', substr($value, 1)));
                $value = mb_convert_case($value, MB_CASE_TITLE, mb_detect_encoding($value));
                $value = str_replace('_', ' ', $value);
                break;
            case 'ucfirst':
                $value = substr($value, 0, 1) . implode(' ', preg_split('/(?=[A-Z])/', substr($value, 1)));
                $value = mb_convert_case($value, MB_CASE_LOWER, mb_detect_encoding($value));
                $value = ucfirst(str_replace('_', ' ', $value));
                break;
            case 'camel':
            case 'pascal':
                $value = preg_replace('/([a-z])([A-Z])/', '\\1 \\2', $value);
                $value = preg_replace('@[^a-zA-Z0-9\-_ ]+@', '', $value);
                $value = str_replace(['-', '_'], ' ', $value);
                $value = str_replace(' ', '', ucwords(convertLabel($value, 'lower')));
                $value = substr(convertLabel($value, 'lower'), 0, 1) . substr($value, 1);
                $value = ($case === 'camel') ? lcfirst($value) : ucfirst($value);
                break;
            case 'snake':
                $value = preg_replace('@[^a-zA-Z0-9\-_ ]+@', '', $value);
                $value = mb_convert_case($value, MB_CASE_LOWER, mb_detect_encoding($value));
                $value = str_replace([' ', '-'], '_', $value);
                break;
            case 'slug':
                $value = preg_replace('/[^a-zA-Z0-9 -]/', '', $value);
                $value = strtolower(str_replace(' ', '-', trim($value)));
                $value = preg_replace('/-+/', '-', $value);
                break;
            default:
                throw new InvalidArgumentException('Invalid case: ' . $case);
        }

        return $value;
    }
}

/**
 * Escape any single quotes with \"
 */
if (!function_exists('esc')) {
    function esc(string $string): string
    {
        return str_replace('"', '\"', $string);
    }
}

/**
 * Escape html special characters
 */
if (!function_exists('e')) {
    function e(mixed $input, int $flags = ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, ?string $encoding = null, bool $double_encode = true): string|array
    {
        if (!empty($input)) {
            if (is_array($input)) {
                foreach (array_keys($input) as $key) {
                    $input[$key] = e($input[$key], $flags, $encoding, $double_encode);
                }
            } else {
                $input = htmlspecialchars($input, $flags, $encoding, $double_encode);
            }
        }

        return $input;
    }
}

/*
 * add the "missing" concat function
 */
if (!function_exists('concat')) {
    function concat(): string
    {
        return implode('', func_get_args());
    }
}

if (!function_exists('strContains')) {
    /**
     * Polyfill of str_contains()
     */
    function strContains(string $haystack, string $needle): bool
    {
        return empty($needle) || strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('nthfield')) {
    function nthfield(string $string, string $separator, int $nth): mixed
    {
        $array = explode($separator, $string);

        return $array[--$nth] ?? null;
    }
}

if (!function_exists('after')) {
    function after(string $tag, string $string): string
    {
        return substr($string, strpos($string, $tag) + strlen($tag));
    }
}

if (!function_exists('before')) {
    function before(string $tag, string $string): string
    {
        return substr($string, 0, strpos($string, $tag));
    }
}

if (!function_exists('between')) {
    function between(string $startTag, string $endTag, string $string): string
    {
        return before($endTag, after($startTag, $string));
    }
}

if (!function_exists('left')) {
    function left(string $string, int $num): string
    {
        return substr($string, 0, $num);
    }
}

if (!function_exists('right')) {
    function right(string $string, int $num): string
    {
        return substr($string, -$num);
    }
}

if (!function_exists('mid')) {
    function mid(string $string, int $start, int $length): string
    {
        return substr($string, $start - 1, $length);
    }
}

if (!function_exists('isAssociative')) {
    function isAssociative(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        // Not found? Return the default value
        if ($value === false) {
            $value = $default;
        } else {
            $value = match (strtolower($value)) {
                'true'  => true,
                'false' => false,
                'empty' => '',
                'null'  => null,
                default => $value,
            };
        }

        return $value;
    }
}

if (!function_exists('forceDownload')) {
    function forceDownload(string $filename = '', string $data = ''): void
    {
        // Try to determine if the filename includes a file extension.
        // We need it in order to set the MIME type
        if (strpos($filename, '.') === false) {
            throw new InvalidValue('Unknown file type');
        }

        // Grab the file extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        // Load the mime types
        $mimes = include __DIR__ . '/../config/mimes.php';

        // Set a default mime if we can't find it
        $mime = $mimes[$extension] ?? 'application/octet-stream';

        header('Content-Type: "' . $mime . '"');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        header('Pragma: no-cache');
        header("Content-Length: " . strlen($data));

        exit($data);
    }
}
