<?php

declare(strict_types=1);

namespace peels\console;

use Exception;
use orange\framework\base\Singleton;
use peels\console\ConsoleInterface;
use orange\framework\traits\ConfigurationTrait;
use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\InputInterface;
use orange\framework\exceptions\fatal\ExitException;

class Console extends Singleton implements ConsoleInterface
{
    use ConfigurationTrait;

    protected $ANSICodes = [
        'off'               => 0,

        'bold'              => 1,
        'dim'               => 2,
        'italic'            => 3,
        'underline'         => 4,
        'blink'             => 5,
        'inverse'           => 7,
        'hidden'            => 8,

        'bold off'          => 21,
        'dim off'           => 22,
        'italic off'        => 23,
        'underline off'     => 24,
        'blink off'         => 25,
        'inverse off'       => 27,
        'hidden off'        => 28,

        'black'             => 30,
        'red'               => 31,
        'green'             => 32,
        'yellow'            => 33,
        'blue'              => 34,
        'magenta'           => 35,
        'cyan'              => 36,
        'white'             => 37,
        'default'           => 39,

        'black bg'          => 40,
        'red bg'            => 41,
        'green bg'          => 42,
        'yellow bg'         => 43,
        'blue bg'           => 44,
        'magenta bg'        => 45,
        'cyan bg'           => 46,
        'white bg'          => 47,
        'default bg'        => 49,

        'bright black'      => 90,
        'bright red'        => 91,
        'bright green'      => 92,
        'bright yellow'     => 93,
        'bright blue'       => 94,
        'bright magenta'    => 95,
        'bright cyan'       => 96,
        'bright white'      => 97,
        'bright default'    => 99,

        'bright black bg'   => 100,
        'bright red bg'     => 101,
        'bright green bg'   => 102,
        'bright yellow bg'  => 103,
        'bright blue bg'    => 104,
        'bright magenta bg' => 105,
        'bright cyan bg'    => 106,
        'bright white bg'   => 107,
        'bright default bg' => 109,

        // custom
        'primary' => 36,
        'secondary' => 33,
    ];

    protected array $named = [
        'primary'   => ['icon' => '', 'stream' => 'STDOUT', 'color' => '<cyan>', 'stop' => false],
        'secondary' => ['icon' => '', 'stream' => 'STDOUT', 'color' => '<yellow>', 'stop' => false],
        'success'   => ['icon' => '✔', 'stream' => 'STDOUT', 'color' => '<green>', 'stop' => false],
        'danger'    => ['icon' => '✘', 'stream' => 'STDERR', 'color' => '<bright red>', 'stop' => false],
        'warning'   => ['icon' => '❖', 'stream' => 'STDOUT', 'color' => '<bright yellow>', 'stop' => false],
        'info'      => ['icon' => '➜', 'stream' => 'STDOUT', 'color' => '<bright blue>', 'stop' => false],
        'stop'      => ['icon' => '✘', 'stream' => 'STDERR', 'color' => '<bright red>', 'stop' => true],
        'error'     => ['icon' => '✘', 'stream' => 'STDERR', 'color' => '<bright red>', 'stop' => false],
    ];

    protected string $listFormat = '<off>[<cyan>%key%<off>] %value%';
    protected string $lf = "\n";
    protected bool $color = true;
    protected string $bell = '';

    protected array $argv = [];
    protected int $argc = 0;

    protected int $verboseLevel = 0;

    // unit testing storage
    protected bool $simulate = false;
    protected int $simulatedWidth = 80;
    protected string $stdin = '';
    protected string $stderr = '';
    protected string $stdout = '';

    protected function __construct(array $config, InputInterface $input)
    {
        $this->config = $this->mergeWithDefault($config, 'console');

        $this->lf = $this->config['Linefeed Character'] ?? $this->lf;

        $this->simulate = $this->config['simulate'] ?? $this->simulate;

        $this->listFormat = $this->config['List Format'] ?? $this->listFormat;

        $this->color = $this->config['color'] ?? $this->color;

        if (isset($this->config['ANSI Codes'])) {
            $this->ANSICodes = array_replace($this->ANSICodes, $this->config['ANSI Codes']);
        }

        $this->named = $this->config['named'] ?? $this->named;

        $this->argv = $input->server('argv', []);
        $this->argc = $input->server('argc', 0);

        $this->bell = $this->config['bell'] ?? chr(7);
    }

    /**
     * change verbose level
     */
    public function verbose(int $level): int
    {
        $this->verboseLevel = $level;

        return $this->verboseLevel;
    }

    /**
     * auto detect the verbose level
     */
    public function readVerboseLevel(bool $set = true): int
    {
        $level = 0;
        
        for ($vlevel = 1; $vlevel <= 8; $vlevel++) {
            if ($this->getArgumentExists('-' . str_repeat('v', $vlevel))) {
                $level = $vlevel;
                break;
            }
        }

        if ($set) {
            $this->verbose($level);
        }

        return $level;
    }

    /**
     * send output to stream
     */
    public function echo(int $level, string $string, bool $linefeed = true, string $stream = 'STDOUT', bool $stop = false): self
    {
        $this->write($level, $stream, $this->formatOutput($string, $linefeed));

        if ($stop) {
            if ($this->simulate) {
                throw new ExitException('exit(1)');
            }

            exit(1);
        }

        return $this;
    }

    protected function named(int $level, string $name, string $string, bool $linefeed): self
    {
        if (!isset($this->named[$name])) {
            throw new InvalidValue('Unknown named method ' . $name);
        }

        $icon = (empty($this->named[$name]['icon'])) ? '' : $this->named[$name]['icon'] . ' ';

        return $this->echo($level, $this->named[$name]['color'] . $icon . $string, $linefeed, $this->named[$name]['stream'], $this->named[$name]['stop']);
    }

    public function primary(int $level, string $string, bool $linefeed = true): self
    {
        return $this->named($level, 'primary', $string, $linefeed);
    }

    public function secondary(int $level, string $string, bool $linefeed = true): self
    {
        return $this->named($level, 'secondary', $string, $linefeed);
    }

    public function success(int $level, string $string, bool $linefeed = true): self
    {
        return $this->named($level, 'success', $string, $linefeed);
    }

    public function danger(int $level, string $string, bool $linefeed = true): self
    {
        return $this->named($level, 'danger', $string, $linefeed);
    }

    public function warning(int $level, string $string, bool $linefeed = true): self
    {
        return $this->named($level, 'warning', $string, $linefeed);
    }

    public function info(int $level, string $string, bool $linefeed = true): self
    {
        return $this->named($level, 'info', $string, $linefeed);
    }

    public function stop(int $level, string $string, bool $linefeed = true): self
    {
        return $this->named($level, 'stop', $string, $linefeed);
    }

    public function error(int $level, string $string, bool $linefeed = true): self
    {
        return $this->named($level, 'error', $string, $linefeed);
    }

    public function bell(int $times = 1): self
    {
        $this->write(0, 'STDOUT', str_repeat($this->bell, $times));

        return $this;
    }

    public function line(int $level, int $length = null, string $char = '-'): self
    {
        if ($length == null && $this->simulate) {
            // fixed amount in simulate mode
            $times = $this->simulatedWidth;
        } else {
            $times = ($length) ?? (int)$this->system('tput cols');
        }

        $times = (int)floor($times / strlen($char));

        $this->write($level, 'STDOUT', str_repeat($char, $times) .  $this->lf);

        return $this;
    }

    public function clear(int $level): self
    {
        if ($this->simulate) {
            // if simulating "clear" the output
            $this->stderr = '';
            $this->stdout = '';
        } else {
            if ($level <= $this->verboseLevel && !$this->simulate) {
                $this->system('clear');
            }
        }

        return $this;
    }

    public function linefeed(int $level,int $times = 1): self
    {
        return $this->write($level, 'STDOUT', str_repeat($this->lf, $times));
    }

    public function table(int $level, array $table): self
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

        $this->line($level, $totalWidth);

        foreach ($table as $rowIndex => $row) {
            array_unshift($row, $masks[$rowIndex]);

            ob_start();

            call_user_func_array('printf', $row);

            $this->echo($level, trim(ob_get_clean()));

            if ($rowIndex == 0) {
                $this->line($level, $totalWidth);
            }
        }

        $this->line($level, $totalWidth);

        return $this;
    }

    public function list(int $level, array $list): self
    {
        foreach ($list as $key => $value) {
            $this->echo($level, str_replace(['%key%', '%value%'], [$key, $value], $this->listFormat));
        }

        return $this;
    }

    /* get input until return is pressed */

    public function getLine(string $prompt = null): string
    {
        if ($prompt) {
            $this->echo(0, $prompt);
        }

        // if in simulate send back std in
        return ($this->simulate) ? $this->stdin : rtrim(fgets(\STDIN), $this->lf);
    }

    public function getLineOneOf(string $prompt = null, array $options = []): string
    {
        do {
            $input = $this->getLine($prompt);
            $success = $this->oneOf($input, $options);
        } while (!$success);

        return $input;
    }

    /**
     * single character (no return needed)
     *
     * This method has a extra exit for simulation mode
     */
    public function get(string $prompt = null): string
    {
        if ($prompt) {
            $this->echo(0, $prompt);
        }

        // if in simulate send back stdin
        if ($this->simulate) {
            // BAIL NOW - multiple exits
            return $this->stdin;
        }

        // setup console no buffer
        $this->system('stty -icanon');

        while ($char = fread(\STDIN, 1)) {
            return $char;
        }
    }

    public function getOneOf(string $prompt = null, array $options = []): string
    {
        do {
            $input = $this->get($prompt);
            $success = $this->oneOf($input, $options);
        } while (!$success);

        $this->linefeed(0);

        return $input;
    }

    /* Arguments */

    public function minimumArguments(int $num, string $error = null): self
    {
        if ($this->argc < ($num + 1)) {
            if (!$error) {
                $error = 'Please provide ' . $num . ' arguments';
            }

            $this->stop(0, $error);
        }

        return $this;
    }

    public function getArgumentExists(string $match): bool
    {
        $found = false;

        foreach ($this->argv as $arg) {
            if ($arg == $match) {
                $found = true;

                break;
            }
        }

        return $found;
    }

    public function getArgument(int $num, string $error = null): string
    {
        $argv = $this->argv;

        if (!isset($argv[$num])) {
            if (!$error) {
                $error = 'Could not locate a Argument ' . $num;
            }

            $this->stop(0, $error);
        }

        return $argv[$num];
    }

    public function getLastArgument(): string
    {
        $last = '';

        if ($this->argc > 0) {
            $argv = $this->argv;

            $last = end($argv);
        }

        return $last;
    }

    public function getArgumentByOption(string $match, string $error = null): string
    {
        if (!$error) {
            $error = 'Could not locate a option for ' . $match;
        }

        $argv = $this->argv;

        foreach ($argv as $key => $value) {
            if ($value == $match) {
                $next = $key + 1;

                if (!isset($argv[$next])) {
                    $this->stop(0,$error);
                }

                return $argv[$next];
            }
        }

        $this->stop(0,$error);
    }

    /* protected */

    protected function formatOutput(string $string, bool $linefeed = true): string
    {
        $string = $this->stripTags($string);

        $turnOff = '';

        // find all the <tags>
        preg_match_all('/<([^>]*)>/i', $string, $tags, PREG_SET_ORDER, 0);

        foreach ($tags as $tag) {
            $colorsEscaped = '';

            // apply color escape codes
            if (!isset($this->ANSICodes[$tag[1]])) {
                $this->stop(0,'Could not find tag "' . $tag[1] . '"');
            }

            foreach (explode(',', (string)$this->ANSICodes[$tag[1]]) as $colorEscapeCode) {
                $colorsEscaped .= "\033[" . $colorEscapeCode . "m";
            }

            $string = str_replace($tag[0], $colorsEscaped, $string);

            $turnOff = "\033[" . $this->ANSICodes['off'] . "m";
        }

        $lf = ($linefeed) ? $this->lf : '';

        return $string . $turnOff . $lf;
    }

    /**
     * strip all tags if we are in no color mode
     */
    protected function stripTags(string $string): string
    {
        // quick find and replace for all linefeeds
        $string = str_replace('<lf>', $this->lf, $string);

        if (!$this->color) {
            preg_match_all('/<([^>]*)>/i', $string, $tags, PREG_SET_ORDER, 0);

            foreach ($tags as $tag) {
                $string = str_replace($tag[0], '', $string);
            }
        }

        return $string;
    }

    protected function oneOf(string $input, array $oneOf, string $error = null): bool
    {
        $success = true;

        if (!in_array($input, $oneOf)) {
            if (!$error) {
                $error = 'Your input did not match an option.';
            }

            $this->linefeed(0);

            $this->error(0,$error);

            $success = false;
        }

        return $success;
    }

    protected function write(int $level, string $stream, string $string): self
    {
        if (!in_array($stream, ['STDOUT', 'STDERR'])) {
            throw new Exception('Stream must be STDOUT or STDERR. "' . $stream . '" sent in.');
        }

        if ($stream == 'STDERR') {
            if ($level <= $this->verboseLevel && !$this->simulate) {
                fwrite(\STDERR, $string);
            }
            $this->stderr .= $string;
        } else {
            if ($level <= $this->verboseLevel && !$this->simulate) {
                fwrite(\STDOUT, $string);
            }
            $this->stdout .= $string;
        }

        return $this;
    }

    protected function validateArgument($arguments, $index, $default, $function)
    {
        $typeMap = [
            'is_string' => 'string',
            'is_int' => 'integer',
            'is_float' => 'floating',
            'is_bool' => 'boolean',
            'is_array' => 'array',
        ];

        $type = $typeMap[$function];

        if (!isset($arguments[$index])) {
            $return = $default;
        } else {
            if (!$function($arguments[$index])) {
                throw new Exception('Argument ' . ($index + 1) . ' must be ' . $type . '.');
            }

            $return = $arguments[$index];
        }

        return $return;
    }

    /* var_dump / debug */

    public function __debugInfo(): array
    {
        return [
            'simulate' => $this->simulate,
            'config' => $this->config,
            'ansicolors' => $this->ANSICodes,
            'list format' => $this->listFormat,
            'lf' => $this->lf,
            'color' => $this->color,
            'stderr' => $this->stderr,
            'stdout' => $this->stdout,
            'stdin' => $this->stdin,
        ];
    }

    protected function system(string $command): string
    {
        $resultCode = 0;
        $output = [];

        exec($command, $output, $resultCode);

        return (empty($output)) ? '' : $output[0];
    }
}
