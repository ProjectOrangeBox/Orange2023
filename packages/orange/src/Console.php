<?php

declare(strict_types=1);

namespace dmyers\orange;

use dmyers\orange\exceptions\ExitException;
use dmyers\orange\interfaces\InputInterface;
use dmyers\orange\interfaces\ConsoleInterface;
use dmyers\orange\exceptions\InvalidConfigurationValue;
use Exception;

class Console implements ConsoleInterface
{
    private static $instance;
    protected array $config = [];

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
        'primary'   => ['icon' => '', 'verbose' => 1, 'stream' => 'STDOUT', 'color' => '<cyan>', 'stop' => false],
        'secondary' => ['icon' => '', 'verbose' => 1, 'stream' => 'STDOUT', 'color' => '<yellow>', 'stop' => false],
        'success'   => ['icon' => '✔', 'verbose' => 1, 'stream' => 'STDOUT', 'color' => '<green>', 'stop' => false],
        'danger'    => ['icon' => '✘', 'verbose' => 1, 'stream' => 'STDERR', 'color' => '<bright red>', 'stop' => false],
        'warning'   => ['icon' => '❖', 'verbose' => 1, 'stream' => 'STDOUT', 'color' => '<bright yellow>', 'stop' => false],
        'info'      => ['icon' => '➜', 'verbose' => 1, 'stream' => 'STDOUT', 'color' => '<bright blue>', 'stop' => false],
        'stop'      => ['icon' => '✘', 'verbose' => 1, 'stream' => 'STDERR', 'color' => '<bright red>', 'stop' => true],
        'error'     => ['icon' => '✘', 'verbose' => 1, 'stream' => 'STDERR', 'color' => '<bright red>', 'stop' => false],
    ];

    protected string $listFormat = '<off>[<cyan>%key%<off>] %value%';
    protected string $lf = "\n";
    protected bool $color = true;
    protected string $bell = '';

    protected array $argv = [];
    protected int $argc = 0;
    protected int $verboseLevel = 1;
    protected bool $verboseSet = false;

    // unit testing storage
    protected bool $simulate = false;
    protected int $simulatedWidth = 80;
    protected string $stdin = '';
    protected string $stderr = '';
    protected string $stdout = '';
    protected string $system = '';

    public function __construct(array $config, InputInterface $input)
    {
        $this->config = mergeDefaultConfig($config, __DIR__ . '/config/console.php');

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

    public static function getInstance(array $config, InputInterface $input): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config, $input);
        }

        return self::$instance;
    }

    /**
     * set verbose level
     */
    public function verbose(int $level): self
    {
        $this->verboseSet = true;

        $this->verboseLevel = $level;

        return $this;
    }

    /**
     * auto detect the verbose level
     *
     * supports up to 4 levels
     */
    public function getVerboseLevel(): int
    {
        // was it set or already read?
        if (!$this->verboseSet) {
            $level = 0;

            // we support up to 4 levels
            for ($vlevel = 1; $vlevel <= 4; $vlevel++) {
                if ($this->getArgumentExists('-' . str_repeat('v', $vlevel))) {
                    $this->verboseSet = true;
                    $level = $vlevel;
                    break;
                }
            }

            // set it
            $this->verbose($level);
        }

        return $this->verboseLevel;
    }

    /**
     * test against the verbose level
     */
    public function ifVerbose(int $level): bool
    {
        return ($level <= $this->verboseLevel);
    }

    /**
     * send output to stream
     *
     * @param string $string
     * @param int $level
     * @param bool $linefeed
     * @param string $stream
     * @return Console
     * @throws InvalidConfigurationValue
     * @throws ExitException
     */
    public function echo(string $string, int $level = 1, bool $linefeed = true, string $stream = 'STDOUT', bool $stop = false): self
    {
        $this->write($stream, $this->formatOutput($string, $linefeed), $level);

        if ($stop) {
            if ($this->simulate) {
                throw new ExitException('exit(1)');
            }

            exit(1);
        }

        return $this;
    }

    /**
     * handle the named methods
     */
    public function __call($name, $arguments)
    {
        $rtn = null;
        $verboseLevel = -1;

        // convert once
        $lowercaseMethodName = strtolower($name);

        // support level1... and lvl1... to auto set verbose level
        if (substr($lowercaseMethodName, 0, 3) == 'lvl') {
            $verboseLevel = (int)substr($lowercaseMethodName, 3, 1);
            $lowercaseMethodName = substr($lowercaseMethodName, 4);
        } elseif (substr($lowercaseMethodName, 0, 5) == 'level') {
            $verboseLevel = (int)substr($lowercaseMethodName, 5, 1);
            $lowercaseMethodName = substr($lowercaseMethodName, 6);
        }

        switch ($lowercaseMethodName) {
            case 'bell':
                $times = $this->validateArgument($arguments, 0, 1, 'is_int');

                $rtn = $this->bell((int)$times, $verboseLevel);
                break;
            case 'line':
                $length = $this->validateArgument($arguments, 0, 1, 'is_int');
                $char = $this->validateArgument($arguments, 1, '-', 'is_string');

                $rtn = $this->line($length, $char, $verboseLevel);
                break;
            case 'clear':
                $rtn = $this->clear($verboseLevel);
                break;
            case 'linefeed':
                $times = $this->validateArgument($arguments, 0, 1, 'is_int');

                $rtn = $this->linefeed((int)$times, $verboseLevel);
                break;
            case 'table':
                $array = $this->validateArgument($arguments, 0, [], 'is_array');

                $rtn = $this->table($array, $verboseLevel);
                break;
            case 'line':
                $array = $this->validateArgument($arguments, 0, [], 'is_array');

                $rtn = $this->list($array, $verboseLevel);
                break;
            default:
                // is this a mapped method?
                if (!isset($this->named[$lowercaseMethodName])) {
                    throw new Exception('Unknown Method "' . $name . '".');
                }

                $match = $this->named[$lowercaseMethodName];

                $icon = (empty($match['icon'])) ? '' : $match['icon'] . ' ';
                $text = $this->validateArgument($arguments, 0, '', 'is_string');

                // did they set the verbose level with the method name?
                if ($verboseLevel == -1) {
                    $verboseLevel = $this->validateArgument($arguments, 1, $match['verbose'], 'is_int');
                }

                $lineFeed = $this->validateArgument($arguments, 2, true, 'is_bool');
                $stream = $this->validateArgument($arguments, 3, $match['stream'], 'is_string');
                $stop = $this->validateArgument($arguments, 4, $match['stop'], 'is_bool');

                $rtn = $this->echo($match['color'] . $icon . $text, $verboseLevel, $lineFeed, $stream, $stop);
        }

        return $rtn;
    }

    /* misc */
    public function bell(int $times = 1, int $level = 1): self
    {
        if ($this->ifVerbose($level)) {
            $this->write('STDOUT', str_repeat($this->bell, $times), $level);
        }

        return $this;
    }

    public function line(int $length = null, string $char = '-', int $level = 1): self
    {
        if ($this->ifVerbose($level)) {
            if ($length || !$this->simulate) {
                $times = ($length) ?? (int)system('tput cols');
            } else {
                // fixed amount in simulate mode
                $times = $this->simulatedWidth;
            }

            $times = (int)floor($times / strlen($char));

            $this->write('STDOUT', str_repeat($char, $times) .  $this->lf, $level);
        }

        return $this;
    }

    public function clear(int $level = 1): self
    {
        if ($this->ifVerbose($level)) {
            if ($this->simulate) {
                // if simulating "clear" the output
                $this->stderr = '';
                $this->stdout = '';
            } else {
                system('clear');
            }
        }

        return $this;
    }

    public function linefeed(int $times = 1, int $level = 1): self
    {
        return $this->write('STDOUT', str_repeat($this->lf, $times), $level);
    }

    public function table(array $table, int $level = 1): self
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

    public function list(array $list, int $level = 1): self
    {
        foreach ($list as $key => $value) {
            $this->echo(str_replace(['%key%', '%value%'], [$key, $value], $this->listFormat));
        }

        return $this;
    }

    /* get input until return is pressed */

    public function getLine(string $prompt = null): string
    {
        if ($prompt) {
            $this->echo($prompt);
        }

        // if in simulate send back std in
        if ($this->simulate) {
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

    /** 
     * single character (no return needed)
     * 
     * This method has a extra exit for simulation mode
     */
    public function get(string $prompt = null): string
    {
        if ($prompt) {
            $this->echo($prompt);
        }

        // if in simulate send back std in
        if ($this->simulate) {
            // BAIL NOW - multiple exits
            return $this->stdin;
        }

        // setup console no buffer
        system('stty -icanon');

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
        if ($this->argc < ($num + 1)) {
            if (!$error) {
                $error = 'Please provide ' . $num . ' arguments';
            }

            $this->stop($error);
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

            $this->stop($error);
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
        $string = $this->stripTags($string);

        $turnOff = '';

        // find all the <tags>
        preg_match_all('/<([^>]*)>/i', $string, $tags, PREG_SET_ORDER, 0);

        foreach ($tags as $tag) {
            $colorsEscaped = '';

            // apply color escape codes
            if (!isset($this->ANSICodes[$tag[1]])) {
                $this->stop('Could not find tag "' . $tag[1] . '"');
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

            $this->linefeed();

            $this->error($error);

            $success = false;
        }

        return $success;
    }

    protected function write(string $stream, string $string, int $level = 1): self
    {
        if (!in_array($stream, ['STDOUT', 'STDERR'])) {
            throw new Exception('Stream must be STDOUT or STDERR. "' . $stream . '" sent in.');
        }

        if ($stream == 'STDERR') {
            if ($this->verboseLevel >= $level && !$this->simulate) {
                fwrite(\STDERR, $string);
            }
            $this->stderr .= $string;
        } else {
            if ($this->verboseLevel >= $level && !$this->simulate) {
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
            'config' => $this->config,
            'ansicolors' => $this->ANSICodes,
            'list format' => $this->listFormat,
            'lf' => $this->lf,
            'color' => $this->color,
            'simulate' => $this->simulate,
            'stderr' => $this->stderr,
            'stdout' => $this->stdout,
            'stdin' => $this->stdin,
        ];
    }
}
