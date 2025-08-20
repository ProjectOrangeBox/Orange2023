<?php

declare(strict_types=1);

namespace peels\console;

use Exception;
use peels\console\BitWise;
use peels\console\ConsoleInterface;
use orange\framework\base\Singleton;
use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\InputInterface;
use orange\framework\traits\ConfigurationTrait;
use orange\framework\exceptions\fatal\ExitException;

class Console extends Singleton implements ConsoleInterface
{
    use ConfigurationTrait;

    protected $ansiCodes = [
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
        'primary'   => ['icon' => '', 'stream' => \STDOUT, 'color' => '<cyan>', 'stop' => false],
        'secondary' => ['icon' => '', 'stream' => \STDOUT, 'color' => '<yellow>', 'stop' => false],
        'success'   => ['icon' => '✔', 'stream' => \STDOUT, 'color' => '<green>', 'stop' => false],
        'danger'    => ['icon' => '✘', 'stream' => \STDERR, 'color' => '<bright red>', 'stop' => false],
        'warning'   => ['icon' => '❖', 'stream' => \STDOUT, 'color' => '<bright yellow>', 'stop' => false],
        'info'      => ['icon' => '➜', 'stream' => \STDOUT, 'color' => '<bright blue>', 'stop' => false],
        'stop'      => ['icon' => '✘', 'stream' => \STDERR, 'color' => '<bright red>', 'stop' => true],
        'error'     => ['icon' => '✘', 'stream' => \STDERR, 'color' => '<bright red>', 'stop' => false],
    ];

    protected string $listFormat = '<off>[<cyan>%key%<off>] %value%';
    protected string $lf = "\n";
    protected bool $color = true;
    protected string $bell = '';

    protected array $argv = [];
    protected int $argc = 0;
    protected BitWise $verbose;
    protected string $verboseChar = 'v';
    protected array $alreadyCaptured = [];
    protected string $defaultVerbose = 'info';
    protected string $defaultUpperCaseVerbose = 'everything';

    // unit testing storage
    protected bool $simulate = false;
    protected int $simulatedWidth = 80;
    protected string $stdin = '';
    protected string $stderr = '';
    protected string $stdout = '';

    protected function __construct(array $config, InputInterface $input)
    {
        $this->config = $this->mergeConfigWith($config);

        $this->lf = $this->config['Linefeed Character'] ?? $this->lf;

        $this->simulate = $this->config['simulate'] ?? $this->simulate;

        $this->listFormat = $this->config['List Format'] ?? $this->listFormat;

        $this->color = $this->config['color'] ?? $this->color;

        if (isset($this->config['ANSI Codes'])) {
            $this->ansiCodes = array_replace($this->ansiCodes, $this->config['ANSI Codes']);
        }

        $this->named = $this->config['named'] ?? $this->named;

        $this->verboseChar = $this->config['verbose char'] ?? $this->verboseChar;
        // setup bitwise with our named values
        $this->verbose = new BitWise([
            'info' => self::INFO,
            'notice' => self::NOTICE,
            'warning' => self::WARNING,
            'error' => self::ERROR,
            'critical' => self::CRITICAL,
            'alert' => self::ALERT,
            'emergency' => self::EMERGENCY,
            'debug' => self::DEBUG,
            'none' => 0,
            'everything' => 65535,
            'always' => 0,
        ]);

        $this->verboseChar = $this->config['verbose char'] ?? $this->verboseChar;
        $this->defaultVerbose = $this->config['default verbose'] ?? $this->defaultVerbose;
        $this->defaultUpperCaseVerbose = $this->config['default uppercase verbose'] ?? $this->defaultUpperCaseVerbose;

        $this->argv = $input->server('argv', []);
        $this->argc = $input->server('argc', 0);

        $this->bell = $this->config['bell'] ?? chr(7);
    }

    /**
     * change verbose level
     */
    public function verbose(?int $level = null): int
    {
        if ($level !== null) {
            $this->verbose->set($level);
        }

        return $this->verbose->get();
    }

    /**
     * auto detect the verbose level
     * command.php -v (info)
     * command.php -vDebug (debug)
     * command.php -vDebug -vInfo (debug & info)
     * command.php -V (everything)
     */
    public function detectVerboseLevel(bool $set = true, ?string $char = null): int
    {
        $level = 0;

        $char = $char ?? $this->verboseChar;

        foreach ($this->argv as $arg) {
            if ($arg == '-' . strtoupper($char)) {
                $level = $this->verbose->getFlag($this->defaultUpperCaseVerbose);
                $this->alreadyCaptured[$this->defaultUpperCaseVerbose] = true;
            } elseif ($arg == '-' . $char) {
                $level = $this->verbose->getFlag($this->defaultVerbose);
                $this->alreadyCaptured[$this->defaultVerbose] = true;
            } elseif (substr($arg, 0, 2) == '-' . $char) {
                $captured = substr($arg, 2);

                if (!isset($this->alreadyCaptured[$captured])) {
                    $this->alreadyCaptured[$captured] = true;

                    $level = $level + $this->verbose->getFlag($captured);
                }
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
    public function echo(string $string, ?int $level = null, bool $linefeed = true, $stream = \STDOUT, bool $stop = false): self
    {
        $level = $level ?? BitWise::info();

        $this->write($level, $stream, $this->formatOutput($string, $linefeed));

        if ($stop) {
            if ($this->simulate) {
                throw new ExitException('exit - Terminate the current script with a status code 1');
            }

            exit(1);
        }

        return $this;
    }

    public function primary(string $string, ?int $level = null, bool $linefeed = true): self
    {
        return $this->named($level, 'primary', $string, $linefeed);
    }

    public function secondary(string $string, ?int $level = null, bool $linefeed = true): self
    {
        return $this->named($level, 'secondary', $string, $linefeed);
    }

    public function success(string $string, ?int $level = null, bool $linefeed = true): self
    {
        return $this->named($level, 'success', $string, $linefeed);
    }

    public function danger(string $string, ?int $level = null, bool $linefeed = true): self
    {
        return $this->named($level, 'danger', $string, $linefeed);
    }

    public function warning(string $string, ?int $level = null, bool $linefeed = true): self
    {
        return $this->named($level, 'warning', $string, $linefeed);
    }

    public function info(string $string, ?int $level = null, bool $linefeed = true): self
    {
        return $this->named($level, 'info', $string, $linefeed);
    }

    public function stop(string $string, ?int $level = null, bool $linefeed = true): self
    {
        $level = $level ?? BitWise::always();

        return $this->named($level, 'stop', $string, $linefeed);
    }

    public function error(string $string, ?int $level = null, bool $linefeed = true): self
    {
        $level = $level ?? BitWise::always();

        return $this->named($level, 'error', $string, $linefeed);
    }

    public function bell(int $times = 1, ?int $level = null): self
    {
        $this->write($level, \STDOUT, str_repeat($this->bell, $times));

        return $this;
    }

    public function line(?int $length = null, string $char = '-', ?int $level = null): self
    {
        if ($length == null && $this->simulate) {
            // fixed amount in simulate mode
            $times = $this->simulatedWidth;
        } else {
            $times = ($length) ?? (int)$this->system('tput cols');
        }

        $times = (int)floor($times / strlen($char));

        $this->write($level, \STDOUT, str_repeat($char, $times) .  $this->lf);

        return $this;
    }

    public function clear(?int $level = null): self
    {
        if ($this->simulate) {
            // if simulating "clear" the output
            $this->stderr = '';
            $this->stdout = '';
        } elseif ($this->verbose->isFlagSet($level)) {
            $this->system('clear');
        }

        return $this;
    }

    public function linefeed(int $times = 1, ?int $level = null): self
    {
        return $this->write($level, \STDOUT, str_repeat($this->lf, $times));
    }

    public function table(array $table, ?int $level = null): self
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

        $this->line($totalWidth, '-', $level);

        foreach ($table as $rowIndex => $row) {
            array_unshift($row, $masks[$rowIndex]);

            ob_start();

            call_user_func_array('printf', $row);

            $this->echo(trim(ob_get_clean()), $level);

            if ($rowIndex == 0) {
                $this->line($totalWidth, '-', $level);
            }
        }

        $this->line($totalWidth, '-', $level);

        return $this;
    }

    public function list(array $list, ?int $level = null): self
    {
        foreach ($list as $key => $value) {
            $this->echo(str_replace(['%key%', '%value%'], [$key, $value], $this->listFormat), $level);
        }

        return $this;
    }

    /* get input until return is pressed */

    public function getLine(?string $prompt = null): string
    {
        if ($prompt) {
            $this->echo($prompt, BitWise::always());
        }

        // if in simulate send back std in
        return ($this->simulate) ? $this->stdin : rtrim(fgets(\STDIN), $this->lf);
    }

    public function getLineOneOf(?string $prompt = null, array $options = []): string
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
    public function get(?string $prompt = null): string
    {
        if ($prompt) {
            $this->echo($prompt, BitWise::always());
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

        // just incase we slip through to here
        return '';
    }

    public function getOneOf(?string $prompt = null, array $options = []): string
    {
        do {
            $input = $this->get($prompt);
            $success = $this->oneOf($input, $options);
        } while (!$success);

        $this->linefeed(1, BitWise::info());

        return $input;
    }

    /* Arguments */

    public function minimumArguments(int $num, ?string $error = null): self
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

    public function getArgument(int $num, ?string $error = null): string
    {
        if (!isset($this->argv[$num])) {
            if (!$error) {
                $error = 'Could not locate a Argument ' . $num;
            }

            $this->stop($error);
        }

        return $this->argv[$num];
    }

    public function getLastArgument(): string
    {
        $last = '';

        if ($this->argc > 0) {
            $last = end($this->argv);
        }

        return $last;
    }

    public function getArgumentByOption(string $match, ?string $error = null): string
    {
        if (!$error) {
            $error = 'Could not locate a option for ' . $match;
        }

        foreach ($this->argv as $key => $value) {
            if ($value == $match) {
                $next = $key + 1;

                if (!isset($this->argv[$next])) {
                    $this->stop($error);
                }

                return $this->argv[$next];
            }
        }

        $this->stop($error);

        return '';
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
            if (!isset($this->ansiCodes[$tag[1]])) {
                $this->stop('Could not find tag "' . $tag[1] . '"');
            }

            foreach (explode(',', (string)$this->ansiCodes[$tag[1]]) as $colorEscapeCode) {
                $colorsEscaped .= "\033[" . $colorEscapeCode . "m";
            }

            $string = str_replace($tag[0], $colorsEscaped, $string);

            $turnOff = "\033[" . $this->ansiCodes['off'] . "m";
        }

        return $string . $turnOff . (($linefeed) ? $this->lf : '');
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

    protected function oneOf(string $input, array $oneOf, ?string $error = null): bool
    {
        $success = true;

        if (!in_array($input, $oneOf)) {
            if (!$error) {
                $error = 'Your input did not match an option.';
            }

            $this->linefeed(0);

            $this->error($error, BitWise::always());

            $success = false;
        }

        return $success;
    }

    protected function write(int $level, $stream, string $string): self
    {
        if ($this->verbose->isFlagSet($level)) {
            if ($this->simulate) {
                if ($stream == \STDERR) {
                    $this->stderr .= $string;
                } else {
                    $this->stdout .= $string;
                }
            } else {
                fwrite($stream, $string);
            }
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
                throw new \Exception('Argument ' . ($index + 1) . ' must be ' . $type . '.');
            }

            $return = $arguments[$index];
        }

        return $return;
    }

    protected function system(string $command): string
    {
        $resultCode = 0;
        $output = [];

        exec($command, $output, $resultCode);

        return (empty($output)) ? '' : $output[0];
    }

    protected function named(?int $level, string $name, string $string, bool $linefeed): self
    {
        $level = $level ?? BitWise::info();

        if (!isset($this->named[$name])) {
            throw new InvalidValue('Unknown "named" method ' . $name);
        }

        $icon = (empty($this->named[$name]['icon'])) ? '' : $this->named[$name]['icon'] . ' ';

        return $this->echo($this->named[$name]['color'] . $icon . $string, $level, $linefeed, $this->named[$name]['stream'], $this->named[$name]['stop']);
    }
}
