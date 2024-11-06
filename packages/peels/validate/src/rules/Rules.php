<?php

declare(strict_types=1);

namespace peels\validate\rules;

use DateTime;
use peels\validate\rules\RuleAbstract;
use peels\validate\exceptions\RuleFailed;

/**
 * default rules
 *
 * These can be overridden by providing additional rules in the validation configuration file
 * You can also override these by simply pointing to your own class and method
 */
class Rules extends RuleAbstract
{
    public function allowEmpty(): void
    {
        // a valid object or bool value
        if (is_object($this->input) || is_bool($this->input)) {
            // already contain something so return
            return;
        }

        // a array with more than 1 entry
        if (is_array($this->input) && count($this->input) > 0) {
            // already contain something so return
            return;
        }

        // something else?
        if (is_scalar($this->input)) {
            if (trim((string)$this->input) === '') {
                // this is ok
                $this->parent->stopProcessing();
            }
        }
    }

    public function ifEmpty(): void
    {
        if (empty($this->input)) {
            $this->input = $this->option;
        }
    }

    public function trim(): void
    {
        $this->inputIsStringNumber();

        $this->input = trim($this->input);
    }

    public function convertDate(): void
    {
        $this->inputIsStringNumber()->optionDefault('Y-m-d H:i:s');

        $timestamp = strtotime($this->input);

        if ($timestamp === false) {
            throw new RuleFailed('Could not convert "' . $this->input . '" into a valid timestamp.');
        }

        $this->input = date($this->option, $timestamp);
    }

    public function differs(): void
    {
        $this->optionIsRequired();

        $currentValues = $this->parent->values();

        if (!isset($currentValues[$this->option])) {
            throw new RuleFailed('Could not find the field ' . $this->option . '.');
        }

        if ($currentValues[$this->option] === $this->input) {
            throw new RuleFailed('%s matches %s.');
        }
    }

    public function human(): void
    {
        $this->inputIsStringNumberEmpty();

        if (preg_replace('/[\x00-\x1F\x7F]/u', '', $this->input) !== $this->input) {
            throw new RuleFailed('%s contains invalid characters.');
        }
    }

    public function input(): void
    {
        $this->inputIsStringNumber()->inputHuman()->trimLength();
    }

    public function integer(): void
    {
        $this->inputIsStringNumber();

        $pos = strpos($this->input, '.');

        if ($pos !== false) {
            $this->input = substr($this->input, 0, $pos);
        }

        $this->input  = preg_replace('/[^\-\+0-9]+/', '', $this->input);

        $prefix = ($this->input[0] == '-' || $this->input[0] == '+') ? $this->input[0] : '';

        $this->input  = $prefix . preg_replace('/[^0-9]+/', '', $this->input);

        $this->trimLength();
    }

    public function isAlpha(): void
    {
        $this->inputIsStringNumberEmpty();

        if (!ctype_alpha($this->input)) {
            throw new RuleFailed('%s may only contain alpha characters.');
        }
    }

    public function isAlphaDash(): void
    {
        $this->inputIsStringNumberEmpty();

        if (preg_match('/^[A-Z-]+$/i', $this->input) !== 1) {
            throw new RuleFailed('%s may only contain alpha characters and dashes.');
        }
    }

    public function isAlphaNumeric(): void
    {
        $this->inputIsStringNumberEmpty();

        if (!ctype_alnum($this->input)) {
            throw new RuleFailed('%s may only contain alpha characters and numbers.');
        }
    }

    public function isAlphaNumericDash(): void
    {
        $this->inputIsStringNumberEmpty();

        if (preg_match('/^[A-Z0-9-]+$/i', (string)$this->input) !== 1) {
            throw new RuleFailed('%s may only contain alpha characters, numbers, and dashes.');
        }
    }

    public function isAlphaNumericSpace(): void
    {
        $this->inputIsStringNumberEmpty();

        if (preg_match('/^[A-Z0-9 ]+$/i', $this->input) !== 1) {
            throw new RuleFailed('%s may only contain alpha characters, numbers, and spaces.');
        }
    }

    public function isAlphaSpace(): void
    {
        $this->inputIsStringNumberEmpty();

        if (preg_match('/^[A-Z ]+$/i', $this->input) !== 1) {
            throw new RuleFailed('%s may only contain alpha characters and spaces.');
        }
    }

    public function isArray(): void
    {
        if (!is_array($this->input)) {
            throw new RuleFailed('%s is not an array.');
        }
    }

    public function isBool(): void
    {
        $this->inputIsBool();
    }

    public function isClass(): void
    {
        $this->optionIsRequired();

        if (!is_object($this->input) || get_class($this->input) != $this->option) {
            throw new RuleFailed('%s is not a instance of %s.');
        }
    }

    public function isDecimal(): void
    {
        $this->inputIsStringNumberEmpty();

        if (preg_match('/\A[-+]?\d{0,}\.?\d+\z/', $this->input) !== 1) {
            throw new RuleFailed('%s is not a valid decimal value.');
        }
    }

    public function isExactLength(): void
    {
        $this->inputIsStringNumberEmpty()->optionIsInteger();

        if ($this->option !== strlen($this->input)) {
            throw new RuleFailed('%s is not %s in length.');
        }
    }

    public function isFloat(): void
    {
        $this->inputIsStringNumberEmpty();

        if (preg_match('/\A[-+]?\d{0,}\.?\d+\z/', $this->input) !== 1) {
            throw new RuleFailed('%s is not a valid floating point value.');
        }
    }

    public function isGreaterThan(): void
    {
        $this->inputIsStringNumberEmpty()->optionIsInteger();

        if ((float)$this->input <= (float)$this->option) {
            throw new RuleFailed('%s is not greater than %s.');
        }
    }

    public function isGreaterThanEqualTo(): void
    {
        $this->inputIsStringNumberEmpty()->optionIsInteger();

        if ((float)$this->input >= (float)$this->option) {
            throw new RuleFailed('%s is not greater than or equal to %s.');
        }
    }

    public function isHex(): void
    {
        $this->inputIsStringNumberEmpty();

        if (!ctype_xdigit((string)$this->input)) {
            throw new RuleFailed('%s is not a hex value.');
        }
    }

    public function isInteger(): void
    {
        $this->inputIsNumber();

        if (preg_match('/^-?\d{1,}$/', $this->input) !== 1) {
            throw new RuleFailed('%s is not an integer.');
        }
    }

    public function isLessThan(): void
    {
        $this->inputIsStringNumberEmpty()->optionIsInteger();

        if ((float)$this->input >= (float)$this->option) {
            throw new RuleFailed('%s is not less than %s.');
        }
    }

    public function isLessThanEqualTo(): void
    {
        $this->inputIsStringNumberEmpty()->optionIsInteger();

        if ((float)$this->input <= (float)$this->option) {
            throw new RuleFailed('%s is not less than or equal to %s.');
        }
    }

    public function isLowercase(): void
    {
        $this->inputIsStringNumber();

        if (strtolower($this->input) !== $this->input) {
            throw new RuleFailed('%s does not contain lowercase characters.');
        }
    }

    public function isNatural(): void
    {
        $this->inputIsNumber();

        if (preg_match('/^-?\d{1,}$/', $this->input) !== 1) {
            throw new RuleFailed('%s is not a natural number.');
        }
    }

    public function isNaturalNoZero(): void
    {
        $this->inputIsNumber();

        if (preg_match('/^-?\d{1,}$/', $this->input) !== 1 || $this->input === '0') {
            throw new RuleFailed('%s is not a natural number greater than 0.');
        }
    }

    public function isNumeric(): void
    {
        $this->inputIsNumber();

        if (preg_match('/\A[\-+]?\d*\.?\d+\z/', $this->input) !== 1) {
            throw new RuleFailed('%s is not a numeric value.');
        }
    }

    public function isOneOf(): void
    {
        $this->inputIsStringNumberEmpty()->optionIsRequired();

        $options = explode($this->defaultOptionSeparator, $this->options);

        if (!in_array($this->input, $options, true)) {
            throw new RuleFailed('%s is not one of ' . implode(', ', $options) . '.');
        }
    }

    public function isRegexMatch(): void
    {
        $this->inputIsStringNumberEmpty()->optionIsRequired();

        if (preg_match($this->option, $this->input) !== 1) {
            throw new RuleFailed('Your regular expression for %s does not match.');
        }
    }

    public function notEmpty(): void
    {
        $errorMsg = '%s cannot be empty.';

        if (is_bool($this->input) || (is_array($this->input) && count($this->input) == 0) || empty($this->input)) {
            throw new RuleFailed($errorMsg);
        }
    }

    public function isRequired(): void
    {
        $errorMsg = '%s is required.';

        if (is_bool($this->input) || (is_array($this->input) && count($this->input) == 0) || empty($this->input)) {
            throw new RuleFailed($errorMsg);
        }
    }

    public function isScalar(): void
    {
        $this->inputIsStringNumberBoolean();
    }

    public function isStdClass(): void
    {
        if (!is_object($this->input) || get_class($this->input) == \stdClass::class) {
            throw new RuleFailed('%s is not a Standard Class.');
        }
    }

    public function isString(): void
    {
        $this->inputIsStringNumberEmpty();
    }

    public function isUppercase(): void
    {
        $this->inputIsStringNumber();

        if (strtoupper($this->input) !== $this->input) {
            throw new RuleFailed('%s does not contain uppercase characters.');
        }
    }

    public function isValidBase64(): void
    {
        $this->inputIsStringNumber();

        if (base64_decode($this->input, true) === false) {
            throw new RuleFailed('%s is not a valid base64 value.');
        }
    }

    public function isValidDate(): void
    {
        $errorMsg = '%s is not a valid date/time value.';

        $this->inputIsStringNumber();

        if (empty($this->option)) {
            if (strtotime($this->input) === false) {
                throw new RuleFailed($errorMsg);
            }
        }

        $date   = DateTime::createFromFormat($this->option, $this->input);

        if ($date === false) {
            throw new RuleFailed($errorMsg);
        }

        $errors = DateTime::getLastErrors();

        // PHP 8.2 or later.
        if (is_bool($errors)) {
            if ($errors !== false) {
                throw new RuleFailed($errorMsg);
            }
        }

        // before 8.2
        if ($errors['warning_count'] !== 0 || $errors['error_count'] !== 0) {
            throw new RuleFailed($errorMsg);
        }
    }

    public function isValidEmail(): void
    {
        $this->inputIsStringNumber();

        if (filter_var($this->input, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuleFailed('%s is not a valid email.');
        }
    }

    public function isValidEmails(): void
    {
        $this->inputIsStringNumber();

        foreach (explode(',', $this->input) as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new RuleFailed('%s contains a invalid email.');
            }
        }
    }

    public function isValidIP(): void
    {
        $this->inputIsStringNumber();

        switch (strtolower($this->option)) {
            case 'ipv4':
                $flag = FILTER_FLAG_IPV4;
                break;
            case 'ipv6':
                $flag = FILTER_FLAG_IPV6;
                break;
            case 'noPrivRange':
                $flag = FILTER_FLAG_NO_PRIV_RANGE;
                break;
            case 'noResRange':
                $flag = FILTER_FLAG_NO_RES_RANGE;
                break;
            case 'globalRange':
                $flag = FILTER_FLAG_GLOBAL_RANGE;
                break;
            default:
                $flag = FILTER_FLAG_IPV4;
        }

        if (filter_var($this->input, FILTER_VALIDATE_IP, $flag) === false) {
            throw new RuleFailed('%s is not a valid ip address.');
        }
    }

    public function isValidTimezone(): void
    {
        $this->inputIsStringNumber();

        if (!in_array($this->input, timezone_identifiers_list(), true)) {
            throw new RuleFailed('%s is not a valid timezone.');
        }
    }

    public function isValidURL(): void
    {
        $this->inputIsStringNumber();

        if (filter_var($this->input, FILTER_VALIDATE_URL) === false) {
            throw new RuleFailed('%s is not a valid URL.');
        }
    }

    public function isValidUuid(): void
    {
        $this->inputIsStringNumber();

        if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $this->input) !== 1) {
            throw new RuleFailed('%s is not a valid UUID.');
        }
    }

    public function length(): void
    {
        $this->optionIsInteger()->inputIsStringNumber()->trimLength();
    }

    public function copyField(): void
    {
        $this->optionIsRequired();

        list($from, $to) = explode($this->defaultOptionSeparator, $this->options, 2);

        $input = $this->parent->values();

        if (!$this->notation->isset($input, $from)) {
            throw new RuleFailed('Can not location the field ' . $from . ' to copy.');
        }

        $this->notation->set($input, $to, $this->notation->get($input, $from));

        $this->parent->setCurrentInput($input);
    }

    public function moveField(): void
    {
        $this->optionIsRequired();

        list($from, $to) = explode($this->defaultOptionSeparator, $this->options, 2);

        $input = $this->parent->values();

        if (!$this->notation->isset($input, $from)) {
            throw new RuleFailed('Can not location the field ' . $from . ' to move.');
        }

        $this->notation->set($input, $to, $this->notation->get($input, $from));

        $this->notation->unset($input, $from);

        $this->parent->setCurrentInput($input);
    }

    public function deleteField(): void
    {
        $this->optionIsRequired();

        $input = $this->parent->values();

        if (!$this->notation->isset($input, $this->option)) {
            throw new RuleFailed('Can not location the field ' . $this->option . ' to delete.');
        }

        $this->notation->unset($input, $this->option);

        $this->parent->setCurrentInput($input);
    }

    public function matches(): void
    {
        $this->optionIsRequired();

        $currentValues = $this->parent->values();

        if (!isset($currentValues[$this->option])) {
            throw new RuleFailed('Could not find the field ' . $this->option . '.');
        }

        if ($currentValues[$this->option] !== $this->input) {
            throw new RuleFailed('%s does not match %s.');
        }
    }

    public function maxLength(): void
    {
        $this->inputIsStringNumberEmpty()->optionIsInteger();

        if (strlen($this->input) > $this->option) {
            throw new RuleFailed('%s is longer than %s.');
        }
    }

    public function minLength(): void
    {
        $this->inputIsStringNumberEmpty()->optionIsInteger();

        if (strlen($this->input) < $this->option) {
            throw new RuleFailed('%s is shorter than %s.');
        }
    }

    public function number(): void
    {
        $this->inputIsStringNumber();

        $this->input = preg_replace('/[^\-\+0-9.]+/', '', $this->input);

        $prefix = '';

        if (isset($this->input[0])) {
            $prefix = ($this->input[0] == '-' || $this->input[0] == '+') ? $this->input[0] : '';
        }

        $this->input = $prefix . preg_replace('/[^0-9.]+/', '', $this->input);

        $this->trimLength();
    }

    public function readable(): void
    {
        $this->inputIsStringNumber();

        /*
        only word characters - from a-z, A-Z, 0-9, including the _ (underscore) character
        then trim any _ (underscore) characters from the beginning and end of the string
        convert to lowercase
        replace _ (underscore) characters with spaces
        uppercase words
        */
        $this->input = ucwords(str_replace('_', ' ', strtolower(trim(preg_replace('#\W+#', ' ', $this->input), ' '))));

        /* run of spaces */
        $this->input = preg_replace('# +#', ' ', $this->input);

        $this->trimLength();
    }

    public function str(): void
    {
        $this->optionIsInteger()->inputIsStringNumber()->inputHuman()->trimLength();
    }

    public function textarea(): void
    {
        $this->optionIsInteger()->inputIsStringNumber()->inputHumanPlus()->trimLength();
    }

    public function visible(): void
    {
        $this->optionIsInteger()->inputIsStringNumber();

        $this->input = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $this->input);

        $this->trimLength();
    }

    /* table name,column name,primary id column name, pdo servername */
    public function isUnique(): void
    {
        $this->inputIsStringNumber();

        list($table, $column, $primaryIdColumn, $serviceName) = explode($this->defaultOptionSeparator, $this->options);

        if (empty($table) || empty($column)) {
            throw new RuleFailed('Unknown table or columns using is unique rule.');
        }

        $primaryIdColumn = $primaryIdColumn ?? 'id';

        $serviceName = $serviceName ?? 'pdo';

        if (!container()->has($serviceName)) {
            throw new RuleFailed($serviceName . ' service not found using is unique rule.');
        }

        $pdo = container()->$serviceName;

        if (!$pdo instanceof \PDO) {
            throw new RuleFailed($serviceName . ' is not a valid PDO instance using is unique rule.');
        }

        // this needs to be a array or we have no idea what the current record primary id might be
        $currentValues = $this->parent->values();

        if (!is_array($currentValues)) {
            throw new RuleFailed('we can\'t determine the primary id when using the unique rule.');
        }

        // on an insert this will be empty therefore let's make the primary id something that won't find a matching record
        $currentid = (isset($currentValues[$primaryIdColumn])) ? $currentValues[$primaryIdColumn] : -1;

        $query = $pdo->prepare("select count(*) from `" . $table . "` where `" . $column . "` = :column and `" . $primaryIdColumn . "` <> :currentid");
        $query->execute(['column' => $this->input, 'currentid' => $currentid]);

        if ((int)$query->fetchColumn() != 0) {
            throw new RuleFailed($this->input . ' is not unique.');
        }
    }

    public function isCount(): void
    {
        $success = false;

        if (is_array($this->input)) {
            $success = count($this->input) == (int)$this->option;
        }

        if (!$success) {
            throw new RuleFailed('%s does not have ' . (int)$this->option . ' elements');
        }
    }

    public function isCountLessThan(): void
    {
        $success = false;

        if (is_array($this->input)) {
            $success = count($this->input) < (int)$this->option;
        }

        if (!$success) {
            throw new RuleFailed('%s is less than ' . (int)$this->option);
        }
    }

    public function isCountGreaterThan(): void
    {
        $success = false;

        if (is_array($this->input)) {
            $success = count($this->input) > (int)$this->option;
        }

        if (!$success) {
            throw new RuleFailed('%s is greater than ' . (int)$this->option);
        }
    }

    public function isValidJson(): void
    {
        if (is_string($this->input)) {
            $this->inputIsStringNumber();

            // level 1 because single scalar values are actually valid?
            $first = substr(trim($this->input), 0, 1);

            if ($first !== '[' && $first !== '{') {
                throw new RuleFailed('%s is not a valid JSON');
            }

            // level 2
            $json = json_decode($this->input);
        } else {
            $json = $this->input;
        }

        if (!is_object($json) && !is_array($json)) {
            throw new RuleFailed('%s is not a valid JSON');
        }
    }

    public function passwordVerify(): void
    {
        if (!password_verify($this->input, $this->option)) {
            $this->input = false; // fail incase they don't have throw exceptions on!
            throw new RuleFailed('%s is not correct');
        }
    }
}
