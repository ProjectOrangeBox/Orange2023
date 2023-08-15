<?php

declare(strict_types=1);

namespace dmyers\orange;

use dmyers\orange\exceptions\ExitException;
use dmyers\orange\interfaces\InputInterface;
use dmyers\orange\interfaces\ConsoleInterface;
use dmyers\orange\exceptions\InvalidConfigurationValue;

class Console implements ConsoleInterface
{
    private static $instance;
    protected array $config = [];
    protected InputInterface $input;

    protected array $foregroundColors = [
        'bold' => '1',
        'dim' => '2',
        'black' => '0;30',
        'dark_gray' => '1;30',
        'blue' => '0;34',
        'light_blue' => '1;34',
        'green' => '0;32',
        'light_green' => '1;32',
        'cyan' => '0;36',
        'light_cyan' => '1;36',
        'red' => '0;31',
        'light_red' => '1;31',
        'purple' => '0;35',
        'light_purple' => '1;35',
        'brown' => '0;33',
        'yellow' => '1;33',
        'light_gray' => '0;37',
        'white' => '1;37',
        'normal' => '0;39',
    ];

    protected array $backgroundColors = [
        'bg_black' => '40',
        'bg_red' => '41',
        'bg_green' => '42',
        'bg_yellow' => '43',
        'bg_blue' => '44',
        'bg_magenta' => '45',
        'bg_cyan' => '46',
        'bg_light_gray' => '47',
    ];

    protected array $options = [
        'underline' => '4',
        'blink' => '5',
        'reverse' => '7',
        'hidden' => '8',
    ];

    protected array $named = [
        'primary' => '0;34',
        'secondary' => '1;34',

        'success' => '0;32',
        'danger' => '1:37,41',
        'warning' => '1;33',
        'info' => '1;36',

        'light' => '1;37',
        'dark' => '0;37',
    ];

    protected string $lf = "\n";
    protected string $normal = "\033[0m";

    // unit testing
    protected bool $simulate = false;
    protected string $stdin = '';
    protected string $stderr = '';
    protected string $stdout = '';
    protected string $system = '';

    protected array $icons = [
        'success' => '✔',
        'danger' => '✘',
        'warning' => '❖',
        'info' => '➜',
    ];
    protected array $combined = [];

    public function __construct(array $config, InputInterface $input)
    {
        $this->config = $config;

        $this->lf = $this->config['Linefeed Character'] ?? chr(10);

        $this->named = $this->config['named'];

        $this->icons = $this->config['icons'];

        $this->simulate = $this->config['simulate'] ?? false;

        $this->combined = $this->foregroundColors +  $this->backgroundColors +  $this->options + $this->named;

        $this->input = $input;
    }

    public static function getInstance(array $config, InputInterface $input): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config, $input);
        }

        return self::$instance;
    }

    /* sending output */

    public function echo(string $string, bool $linefeed = true): self
    {
        return $this->write('STDOUT', $this->formatOutput($string, $linefeed));
    }

    public function error(string $string, bool $linefeed = true): self
    {
        return $this->write('STDERR', $this->formatOutput('<danger>' . $this->getIcon('danger') . $string, $linefeed));
    }

    public function success(string $string, bool $linefeed = true): self
    {
        return $this->write('STDOUT', $this->formatOutput('<success>' . $this->getIcon('success') . $string, $linefeed));
    }

    public function info(string $string, bool $linefeed = true): self
    {
        return $this->write('STDOUT', $this->formatOutput('<info>' . $this->getIcon('info') . $string, $linefeed));
    }

    public function warning(string $string, bool $linefeed = true): self
    {
        return $this->write('STDOUT', $this->formatOutput('<warning>' . $this->getIcon('warning') . $string, $linefeed));
    }

    public function stop(string $string, bool $linefeed = true): void
    {
        $this->error($string, $linefeed);

        if ($this->simulate) {
            throw new ExitException('exit(1)');
        }

        exit(1);
    }

    /* misc */
    public function bell(int $times = 1): self
    {
        return $this->write('STDOUT', str_repeat(chr(7), $times));
    }

    public function line(int $length = null, string $char = '-'): self
    {
        $times = ($length) ?? (int)$this->system('tput cols');

        $times = (int)floor($times / strlen($char));

        return $this->write('STDOUT', str_repeat($char, $times) .  $this->lf);
    }

    public function clear(): self
    {
        $this->system('clear');

        return $this;
    }

    public function linefeed(int $times = 1): self
    {
        return $this->write('STDOUT', str_repeat($this->lf, $times));
    }

    public function table(array $table, array $options = []): self
    {
        // get max column size
        $columnsMaxWidth = [];

        foreach ($table as $rowIndex => $row) {
            foreach ($row as $columnIndex => $column) {
                if (!isset($columnsMaxWidth[$columnIndex])) {
                    $columnsMaxWidth[$columnIndex] = 0;
                }

                $columnsMaxWidth[$columnIndex] = max($columnsMaxWidth[$columnIndex], strlen((string)$column) + 1);
            }
        }

        $totalWidth = 0;

        $masks = [];

        foreach ($table as $rowIndex => $row) {
            $m = [];
            foreach ($row as $columnIndex => $column) {
                $width = $columnsMaxWidth[$columnIndex];

                $m[] = ' %-' . $width . '.' . $width . 's ';

                if ($rowIndex == 0) {
                    $totalWidth = $totalWidth + $width + 2;
                }
            }

            $masks[$rowIndex] = '|' . implode('|', $m) . '|';
        }

        $totalWidth = $totalWidth  + 4;

        $this->line($totalWidth);

        foreach ($table as $rowIndex => $row) {
            array_unshift($row, $masks[$rowIndex]);

            ob_start();

            call_user_func_array('printf', $row);

            $this->echo(trim(ob_get_clean()));

            if ($rowIndex == 0) {
                $this->line($totalWidth);
            }
        }

        $this->line($totalWidth);

        return $this;
    }

    public function list(array $list, array $options = []): Self
    {
        foreach ($list as $key => $value) {
            $this->echo(str_replace(['%key%', '%value%'], [$key, $value], $this->config['List Format']));
        }

        return $this;
    }

    /* get input until return is pressed */

    public function getLine(string $prompt = null): string
    {
        if ($prompt) {
            $this->echo($prompt);
        }

        // specific to testing
        if (!empty($this->stdin)) {
            return $this->stdin;
        }

        return rtrim(fgets(\STDIN), $this->lf);
    }

    public function getLineOneOf(string $prompt = null, array $options): string
    {
        do {
            $input = $this->getLine($prompt);
            $success = $this->oneOf($input, $options);
        } while (!$success);

        return $input;
    }

    /* single character (no return needed) */

    public function get(string $prompt = null): string
    {
        if ($prompt) {
            $this->echo($prompt);
        }

        // specific to testing
        if (!empty($this->stdin)) {
            return $this->stdin;
        }

        // no buffer
        $this->system("stty -icanon");

        while ($char = fread(\STDIN, 1)) {
            return $char;
        }
    }

    public function getOneOf(string $prompt = null, array $options): string
    {
        do {
            $input = $this->get($prompt);
            $success = $this->oneOf($input, $options);
        } while (!$success);

        $this->linefeed();

        return $input;
    }

    /* Arguments */

    public function minimumArguments(int $num, string $error = null): self
    {
        if ($this->input->server('argc') < ($num + 1)) {
            if (!$error) {
                $error = 'Please provide ' . $num . ' arguments';
            }

            $this->stop($error);
        }

        return $this;
    }

    public function getArgument(int $num, string $error = null): string
    {
        $argv = $this->input->server('argv');

        if (!isset($argv[$num])) {
            if (!$error) {
                $error = 'Could not locate a Argument ' . $num;
            }

            $this->stop($error);
        }

        return $argv[$num];
    }

    public function getLastArgument(): string
    {
        $last = '';

        if ($this->input->server('argc') > 0) {
            $argv = $this->input->server('argv');

            $last = end($argv);
        }

        return $last;
    }

    public function getArgumentByOption(string $match, string $error = null): string
    {
        if (!$error) {
            $error = 'Could not locate a option for ' . $match;
        }

        $argv = $this->input->server('argv');

        foreach ($argv as $key => $value) {
            if ($value == $match) {
                $next = $key + 1;

                if (!isset($argv[$next])) {
                    $this->stop($error);
                }

                return $argv[$next];
            }
        }

        $this->stop($error);
    }

    /* protected */

    protected function formatOutput(string $string, bool $linefeed = true): string
    {
        // quick find and replace for all linefeeds
        $string = str_replace('<lf>', $this->lf, $string);

        preg_match_all('/<([^>]*)>/i', $string, $matches, PREG_SET_ORDER, 0);

        $enabled = false;

        foreach ($matches as $match) {
            $name = $match[1];

            if (!isset($this->combined[$name])) {
                $this->stop('Could not find tag "' . $name . '"');
            }

            $colorsEscaped = '';
            $colorEscapes = $this->combined[$name];

            foreach (explode(',', $colorEscapes) as $colorEscape) {
                $enabled = true;
                $colorsEscaped .= "\033[" . $colorEscape . "m";
            }

            $string = str_replace($match[0], $colorsEscaped, $string);
        }

        $lf = ($linefeed) ? $this->lf : '';

        // finish with "off"
        $turnOff = ($enabled) ? $this->normal : '';

        return $string . $turnOff . $lf;
    }

    protected function oneOf(string $input, array $oneOf, string $error = null): bool
    {
        $success = true;

        if (!in_array($input, $oneOf)) {
            if (!$error) {
                $error = 'Your input did not match an option.';
            }

            $this->linefeed();

            $this->error($error);

            $success = false;
        }

        return $success;
    }

    protected function getIcon(string $name): string
    {
        $icon = '';

        if (isset($this->config['icons'][$name]) && !empty($this->config['icons'][$name])) {
            $icon = $this->config['icons'][$name] . ' ';
        } else {
            throw new InvalidConfigurationValue('Icon "' . $name . '" not found.');
        }

        return $icon;
    }

    protected function write(string $handle, string $string): self
    {
        if ($this->simulate) {
            if ($handle == 'STDERR') {
                $this->stderr .= $string;
            } else {
                $this->stdout .= $string;
            }
        } else {
            if ($handle == 'STDERR') {
                fwrite(\STDERR, $string);
                $this->stderr .= $string;
            } else {
                fwrite(\STDOUT, $string);
                $this->stdout .= $string;
            }
        }

        return $this;
    }

    protected function system($command): string
    {
        $results = '';

        $this->system .= $command;

        if (!$this->simulate) {
            $results = exec($command);
        }

        return $results;
    }

    /* var_dump / debug */

    public function __debugInfo(): array
    {
        return [
            'config' => $this->config,
            'foregroundColors' => $this->foregroundColors,
            'backgroundColors' => $this->backgroundColors,
            'options' => $this->options,
            'named' => $this->named,
            'lf' => $this->lf,
            'normal' => $this->normal,
            'simulate' => $this->simulate,
            'stderr' => $this->stderr,
            'stdout' => $this->stdout,
            'stdin' => $this->stdin,
            'system' => $this->system,
        ];
    }
}
