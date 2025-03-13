<?php

declare(strict_types=1);

namespace application\people\libraries;

use peels\validate\rules\RuleAbstract;
use peels\validate\exceptions\RuleFailed;

/**
 * default rules
 *
 * These can be overridden by providing additional rules in the validation configuration file
 * You can also override these by simply pointing to your own class and method
 */
class RestRules extends RuleAbstract
{
    public function isPrimaryId(): void
    {
        if (!ctype_digit($this->input)) {
            throw new RuleFailed('Record Id is incorrect.');
        }
    }

    public function hasCanRead(): void
    {
        // load from service container user object and check for access, etc...
        // $this->option could have for example access this use needs read,modelname
        // and the $this->input could have the record id they are trying to access

        if (1 != 1) {
            throw new RuleFailed('You can not read records.');
        }
    }

    public function hasCanCreate(): void
    {
        // load from service container user object and check for access, etc...
        // $this->option could have for example access this use needs create,modelname

        if (1 != 1) {
            throw new RuleFailed('You can not create records.');
        }
    }

    public function hasCanUpdate(): void
    {
        // load from service container user object and check for access, etc...
        // $this->option could have for example access this use needs update,modelname
        // and the $this->input could have the record id they are trying to access

        if (1 != 1) {
            throw new RuleFailed('You can not update record this record.');
        }
    }

    public function hasCanDelete(): void
    {
        // load from service container user object and check for access, etc...
        // $this->option could have for example access this use needs delete,modelname
        // and the $this->input could have the record id they are trying to access

        if (1 != 1) {
            throw new RuleFailed('You can not delete record this record.');
        }
    }
}
