/**
 * These are formatters attached directly to tinybind
 *
 * http://rivetsjs.com/docs/guide/#formatters
 * popular https://github.com/matthieuriolo/rivetsjs-stdlib
 *
 */

/* General */

/*
    log

    Displays target in the browser console

    target: any
*/
tinybind.formatters.log = function (target) {
    return console.log(target); /* void */
};

/*
    default
    This formatter returns a default value for target if it is empty (detected with the formatter isEmpty)

    target: any
    param val: any | if target is empty then this value will be returned
    return: any
    Example:

    <span rv-text="notexistingvalue | default 'property does not exist'"></span>
    Result:

    <span>property does not exist</span>
*/
tinybind.formatters.default = function (target, val) {
    return !tinybind.formatters.isEmpty(target) ? target : val;
};

/*
    add
    Uses the + operation between target and the given parameter without doing any conversion (see sum). Therefor this function can be used to concat strings as well

    target: any
    param val: any
    return: int, float, NaN, str
    Example:

    <span rv-text="12 | add 1"></span>
    Result:

    <span>13</span>
*/
tinybind.formatters.add = function (target, val) {
    return target + val;
};

/*
    sub
    Uses the - operation between target and the given parameter without doing any conversion (see substract)

    target: any
    param val: any
    return: int, float, NaN, str
    Example:

    <span rv-text="12 | sub 1"></span>
    Result:

    <span>11</span>
*/
tinybind.formatters.sub = function (target, val) {
    return target - val;
};

/*
    map
    Calls a method on the given object. The first parameters defines the object and the second the methodname. Target will be passed as the first argument to the method.

    target: any
    param obj: object
    param property: string
    variadic: any
    return: any
    Example:

    <span rv-text="10 | map 'Math' 'sin'"></span>
    Result:

    <span>-0.5440211108893699</span>
*/
tinybind.formatters.map = function (target, obj, prop) {
    var args = Array.prototype.slice.call(arguments);
    args.splice(1, 2);
    return obj[prop].apply(obj, args);
};

/* Type Detection */

/*
    isEmpty
    Returns true if the target represents an empty state (empty array, empty string, false, etc)

    target: any
    return: boolean
*/
tinybind.formatters.isEmpty = function (target) {
    if (!tinybind.formatters.toBoolean(target)) {
        return true;
    }

    return tinybind.formatters.toArray(target).length === 0;
};

/*
    isBoolean
    Returns true if the given target is of the type boolean

    target: any
    return: bool
*/
tinybind.formatters.isBoolean = function (target) {
    return typeof target === "boolean";
};

/*
    isNumeric
    Returns true if the given target can be expressed as a numeric value. This covers integers, floats, strings and booleans

    target: any
    return: bool
*/
tinybind.formatters.isNumeric = function (target) {
    return !isNaN(target);
};

/*
    isNaN
    Returns true if the given target can not be expressed as a numeric value. This covers objects, arrays and strings

    target: any
    return: bool
*/
tinybind.formatters.isNaN = function (target) {
    if (tinybind.formatters.isArray(target)) {
        return true;
    }

    return isNaN(target);
};

/*
    isInteger
    Returns true if the given target is an integer

    target: any
    return: bool
*/
tinybind.formatters.isInteger = function (n) {
    /**
     * thanks a lot to Dagg Nabbit
     * http://stackoverflow.com/questions/3885817/how-to-check-if-a-number-is-float-or-integer
     */
    return n === +n && n === (n | 0);
};

/*
    isFloat
    Returns true if the given target is a float

    target: any
    return: bool
*/
tinybind.formatters.isFloat = function (n) {
    /**
     * thanks a lot to Dagg Nabbit
     * http://stackoverflow.com/questions/3885817/how-to-check-if-a-number-is-float-or-integer
     */
    return Infinity !== n && n === +n && n !== (n | 0);
};

/*
    isNumber
    Returns true if the given target is an integer or a float

    target: any
    return: bool
*/
tinybind.formatters.isNumber = function (target) {
    return (
        tinybind.formatters.isFloat(target) || tinybind.formatters.isInteger(target)
    );
};

/*
    isObject
    Returns true if the given target is an object

    target: any
    return: bool
*/
tinybind.formatters.isObject = function (target) {
    return (
        tinybind.formatters.toBoolean(target) &&
        typeof target === "object" &&
        !tinybind.formatters.isArray(target)
    );
};

/*
    isFunction
    Returns true if the given target is a function

    target: any
    return: bool
*/
tinybind.formatters.isFunction = function (target) {
    return typeof target === "function";
};

/*
    isArray
    Returns true if the given target is an array

    target: any
    return: bool
*/
tinybind.formatters.isArray = function (target) {
    return tinybind.formatters.isFunction(Array.isArray) ?
        Array.isArray(target) :
        target instanceof Array;
};

/*
    isString
    Returns true if the given target is a string

    target: any
    return: bool
*/
tinybind.formatters.isString = function (target) {
    return typeof target === "string" || target instanceof String;
};

/*
    isInfinity
    Returns true if the given target is infinity

    target: any
    return: bool
*/
tinybind.formatters.isInfinity = function (target) {
    return target === Infinity;
};

/* Type conversion */

/*
    toBoolean
    Returns the boolean representation of the given target. The conversion is similiar to the behaviour of if() {}

    target: any
    return: bool
*/
tinybind.formatters.toBoolean = function (target) {
    return !!target;
};

/*
    toInteger
    Returns the integer representation of the given target.

    target: any
    return: integer
*/
tinybind.formatters.toInteger = function (target) {
    var ret = parseInt(target * 1, 10);
    return isNaN(ret) ? 0 : ret;
};

/*
integer
Exactly the same as toInteger but implemented as two-way formatter

two-way
target: any
return: string
*/
tinybind.formatters.integer = {
    read: function (target) {
        return tinybind.formatters.toInteger(target);
    },

    publish: function (target) {
        return tinybind.formatters.toInteger(target);
    }
};

/*
    toFloat
    Returns the float representation of the given target

    target: any
    return: float
*/
tinybind.formatters.toFloat = function (target) {
    var ret = parseFloat(target * 1.0);
    return isNaN(ret) ? 0.0 : ret;
};

/*
    toDecimal
    Returns the integer representation of the given target if the float representation is not more precise

    target: any
    return: integer|float
*/
tinybind.formatters.toDecimal = function (target) {
    var retI = tinybind.formatters.toInteger(target * 1);
    var retF = tinybind.formatters.toFloat(target);
    return retI === retF ? retI : retF;
};

/*
    toArray
    Returns the array representation of the given target. Objects will be flatten down by their values and single values will be wrapped in a array. Arrays will be returned unchanged

    target: any
    return: array
*/
tinybind.formatters.toArray = function (target) {
    if (tinybind.formatters.isArray(target)) {
        return target;
    } else if (tinybind.formatters.isObject(target)) {
        return tinybind.formatters.values(target);
    }

    return [target];
};

/*
    toString
    Returns the string representation of the given target. This actually calls the JS method toString()

    target: any
    return: string
*/
tinybind.formatters.toString = function (target) {
    return target ? target.toString() : "";
};

/*
    Returns target in a human readable string

    <span rv-text="foobar | prettyPrint"></span>
*/
tinybind.formatters.prettyPrint = function (target) {
    return JSON.stringify(target, null, 2); /* string */
};

/* Comparisons */

/*
    isEqual
    Returns true if the target and the first parameter are equal
    target: any
    parameter val: any
    return: bool
*/
tinybind.formatters.isEqual = function (target, val) {
    return target === val;
};

tinybind.formatters.isNotEqual = function (target, val) {
    return target !== val;
};

/*
    isLess
    Returns true if the target is smaller as first parameter. Both values will be converted to a numeric representation
    target: any
    parameter val: any
    return: bool
*/
tinybind.formatters.isLess = function (target, val) {
    return target * 1 < val * 1;
};

/*
    isGreater
    Returns true if the target is greater as first parameter. Both values will be converted to a numeric representation
    target: any
    parameter val: any
    return: bool
*/
tinybind.formatters.isGreater = function (target, val) {
    return target * 1 > val * 1;
};

/*
    isLessEqual
    Returns true if the target is smaller or is equal to the first parameter. Both values will be converted to a numeric representation
    target: any
    parameter val: any
    return: bool
*/
tinybind.formatters.isLessEqual = function (target, val) {
    return target * 1 <= val * 1;
};

/*
    isGreaterEqual
    Returns true if the target is greater or is equal to the first parameter. Both values will be converted to a numeric representation
    target: any
    parameter val: any
    return: bool
*/
tinybind.formatters.isGreaterEqual = function (target, val) {
    return target * 1 >= val * 1;
};

/* Logical formatters */

/*
    or
    Returns true if the target or one of parameters are true
    target: any
    variadic: any
    return: bool
*/
tinybind.formatters.or = function () {
    for (var i = 0; i < arguments.length; i++) {
        if (tinybind.formatters.toBoolean(arguments[i])) {
            return true;
        }
    }

    return false;
};

/*
    and
    Returns true if the target and all parameters are true
    target: any
    variadic: any
    return: bool
*/
tinybind.formatters.and = function () {
    for (var i = 0; i < arguments.length; i++) {
        if (!tinybind.formatters.toBoolean(arguments[i])) {
            return false;
        }
    }

    return true;
};

/*
    negate
    Returns the negated value of target
    target: any
    return: bool
*/
tinybind.formatters.negate = function (target) {
    return !tinybind.formatters.toBoolean(target);
};

/*
    if
    Returns the first parameter if the target is true or returns the second parameter
    target: bool
    param trueCase: any | will be returned if target is true
    param falseCase: any | will be returned if target is false
    return: any
*/
tinybind.formatters.if = function (target, trueCase, falseCase) {
    return tinybind.formatters.toBoolean(target) ? trueCase : falseCase;
};

/* Numeric formatters */

/*
    sum
    Returns the sum of the target and the first parameter. Both values will be converted to a numeric representation
    target: any
    parameter val: any
    return: integer|float
*/
tinybind.formatters.sum = function (target, val) {
    return 1 * target + 1 * val;
};

/*
    substract
    Returns the substraction of the target and the first parameter. Both values will be converted to a numeric representation
    target: any
    parameter val: any
    return: integer|float
*/
tinybind.formatters.substract = function (target, val) {
    return 1 * target - 1 * val;
};

/*
    multiply
    Returns the multiplication of the target and the first parameter. Both values will be converted to a numeric representation
    target: any
    parameter val: any
    return: integer|float
*/
tinybind.formatters.multiply = function (target, val) {
    return 1 * target * (1 * val);
};

/*
    divide
    Returns the division of the target and the first parameter. Both values will be converted to a numeric representation. If the denominator is 0 then Infinity is returned
    target: any
    parameter val: any
    return: integer|float|Infinity
*/
tinybind.formatters.divide = function (target, val) {
    return (1 * target) / (1 * val);
};

/*
    min
    Returns the smallest number from the passed parameters and the target
    target: integer|float
    variadic: integer|float
    return: integer|float|Infinity|NaN
*/
tinybind.formatters.min = function () {
    return Math.min.apply(Math, arguments);
};

/*
    max
    Returns the biggest number from the passed parameters and the target
    target: integer|float
    variadic: integer|float
    return: integer|float|Infinity|NaN
*/
tinybind.formatters.max = function () {
    return Math.max.apply(Math, arguments);
};

/*
    numberFormat
    Returns a formatted version of the target as string. The number will always be rounded after the DIN 1333 (1.55 => 1.6 and -1.55 => -1.6)
    target: integer|float
    parameter precision: integer default rivets.stdlib.defaultPrecision
    parameter decimalSeparator: string default rivets.stdlib.defaultDecimalSeparator
    parameter thousandSeparator: string default rivets.stdlib.defaultThousandSeparator
    return: string
*/
tinybind.formatters.numberFormat = function (
    target,
    precision,
    decimalSeparator,
    thousandSeparator
) {
    target = tinybind.formatters.isNumber(target) ?
        target :
        tinybind.formatters.toDecimal(target);

    if (!tinybind.formatters.isInteger(precision)) {
        precision = app.config.defaults.precision;
    }

    if (!decimalSeparator) {
        decimalSeparator = app.config.defaults.DecimalSeparator;
    }

    if (!thousandSeparator) {
        thousandSeparator = app.config.defaults.ThousandSeparator;
    }

    /*
     thanks to user2823670

     http://stackoverflow.com/questions/10015027/javascript-tofixed-not-rounding
    */
    var ret = (+(
        Math.round(+(Math.abs(target) + "e" + precision)) +
        "e" +
        -precision
    )).toFixed(precision);

    if (target < 0) {
        ret = "-" + ret;
    }

    /*
     thanks to Elias Zamaria

     http://stackoverflow.com/questions/2901102/how-to-print-a-number-with-commas-as-thousands-separators-in-javascript
    */
    ret = ret.split(".");

    if (ret.length === 2) {
        return (
            ret[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator) +
            decimalSeparator +
            ret[1]
        );
    }

    return ret[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);
};

/* Date formatters */

/*
    date
    Returns the date portion as string from target (JS Date). Default formatting is rivets.stdlib.defaultDateFormat
    target: Date
    return: string
*/
tinybind.formatters.date = function (target) {
    return moment(target).format(app.config.defaults.DateFormat);
};

/*
    time
    Returns the time portion as string from target (JS Date). Default formatting is rivets.stdlib.defaultTimeFormat
    target: Date
    return: string
*/
tinybind.formatters.time = function (target) {
    return moment(target).format(app.config.defaults.TimeFormat);
};

/*
    datetime
    Returns a datetime as string from target (JS Date). Default formatting is rivets.stdlib.defaultDatetimeFormat
    target: Date
    return: string
*/
tinybind.formatters.datetime = function (target) {
    return moment(target).format(app.config.defaults.DatetimeFormat);
};

/*
    toTimestamp
    Returns the unix timestamp from target (JS Date)
    target: Date
    return: integer
*/
tinybind.formatters.toTimestamp = function (target) {
    return moment(target).format("X");
};

/*
    toDate
    Returns the JS Date object representing the given unix timestamp
    target: integer
    return: Date
*/
tinybind.formatters.toDate = function (target) {
    return moment.unix(target).toDate();
};

/*
    toMoment
    Returns the momentjs object representing of the given JS Date. (use this method afterwards with the formatter map)
    target: Date
    return: momentjs
*/
tinybind.formatters.toMoment = function (target) {
    return moment(target);
};

/*
    The date formatter returns a formatted date string according to the moment.js
    formatting syntax.

    <span rv-value="model:date | date 'dddd, MMMM Do'"></span>

    @see {@link http://momentjs.com/docs/#/displaying} for format options.

    dateFormat
    Returns a string formatted with momentjs.format. The first parameter specifies the format pattern
    target: JS Date
    param val: string | documented in momentjs
    return: string
*/
tinybind.formatters.dateFormat = function (target, val) {
    return moment(target).format(val);
};

/* Object formatters */

/*
    pairs
    Returns an object holding the object, key and the corresponding value. The returned object has therefor always three properties: object, property and value
    target: object
    return: object
    Example:

    <div rv-each-item="myobject | pairs">
        Found key { item.property | stringify } with value { item.value  | stringify }
    </div>
*/
tinybind.formatters.pairs = function (target) {
    return Object.keys(target).map(function (key) {
        return {
            object: target,
            property: key,
            value: target[key]
        };
    });
};

/*
    keys
    Returns all keys of target

    target: object
    return: array
*/
tinybind.formatters.keys = function (target) {
    return Object.keys(target);
};

/*
    values
    Returns all values of target
    target: object
    return: array
*/
tinybind.formatters.values = function (target) {
    return Object.keys(target).map(function (key) {
        return target[key];
    });
};

/* String formatters */

/*
    split
    Splits the target into an array by a given seperator
    target: string
    param val: string
    return: array
*/
tinybind.formatters.split = function (target, val) {
    return target.split(val);
};

/*
    lower
    Converts the target to lowercase
    target: string
    return: string
*/
tinybind.formatters.lower = function (target) {
    return target.toLowerCase();
};

/*
    upper
    Converts the target to uppercase
    target: string
    return: string
*/
tinybind.formatters.upper = function (target) {
    return target.toUpperCase();
};

/*
    capitalize
    Converts the first letter of every word to uppercase in target. Every substring separated by a space or a - will be detected as a word. The rest stays the it used to (URL will not be converted to Url)
    target: string
    return: string
    Example:

    <span rv-text="'string teXt-caSe uRl' | capitalize"></span>
    Result:

    <span>String TeXt-CaSe URl</span>
*/
tinybind.formatters.capitalize = function (target) {
    target = tinybind.formatters.toString(target);

    return target
        .split(" ")
        .map(function (seq) {
            return seq
                .split("-")
                .map(function (word) {
                    return word.charAt(0).toUpperCase() + word.slice(1);
                })
                .join("-");
        })
        .join(" ");
};

/* string & array functions */

/*
    contains
    Returns true if the target contains the given substring or if target array holds the parameter
    target: string|array
    return: bool
*/
tinybind.formatters.contains = function (target, val) {
    return target.indexOf(val) !== -1;
};

/*
    doesNotContain
    Shortcut for the function ['array'] | contains 'value' | negate
    target: string|array
    return: bool
*/
tinybind.formatters.doesNotContain = function (target, val) {
    return tinybind.formatters.negate(tinybind.formatters.contains(target, val));
};

/*
    length
    Returns the string length, the array length or the count of keys of an object
    target: any
    return: integer
*/
tinybind.formatters.length = function (target) {
    if (tinybind.formatters.isString(target)) {
        return target.length;
    }

    return tinybind.formatters.toArray(target).length;
};

/* Array formatters */

/*
    join
    Returns the string by joining the target with the given parameter
    target: array
    param val: string
    return: string
*/
tinybind.formatters.join = function (target, val) {
    return tinybind.formatters.toArray(target).join(val);
};

/* Function formatters */

/*
    wrap
    Returns a new function which will call target with the arguments given to wrap and with the arguments used in the event caller.
    The arguments passed to wrap can be accessed as the first arguments on the called function

    target: function
    variadic: any
    return: function
    Example:

    <div rv-each-item="collection">
        <div rv-on-click="aClickHandler | args collectionItem"></div>
    </div>

    function aClickHandler(collectionItem, event) {
        ...
    }

    <div rv-on-click="events.clicker | app.page.url">

    events.clicker = function(appPageUrl, event) {
        ...
    }
*/
tinybind.formatters.args = function (target) {
    var args = Array.prototype.slice.call(arguments);
    args.splice(0, 1);

    return function (evt) {
        var cpy = args.slice();
        Array.prototype.push.apply(cpy, Array.prototype.slice.call(arguments));
        return target.apply(this, cpy);
    };
};

/*
    delay
    Returns a anonym functions which calls target with a delay
    target: function
    param ts: integer | delay in milliseconds
    return: function
*/
tinybind.formatters.delay = function (target, ts) {
    var self = this;

    return function () {
        setTimeout(function () {
            target.apply(self, arguments);
        }, ts);
    };
};

/*
    preventDefault
    Returns a anonym functions which calls preventDefault and afterwards target
    target: function
    return: function
*/
tinybind.formatters.preventDefault = function (target) {
    var self = this;
    return function (evt) {
        evt.preventDefault();
        target.call(self, evt);
        return false;
    };
};

/*
    catalog
    Returns a matching value from a object based on the objects properties
    target: function
    return: scalar
    Example:
        <td class="text-center" rv-text="record.select | catalog form.select 'id' 'value'"></td>
*/
tinybind.formatters.catalog = function (input, object, id, value) {
    id = id ? id : "id";
    value = value ? value : "value";

    for (var key in object) {
        if (object[key][id] == input) {
            return object[key][value];
        }
    }

    return "";
};

/*
    enum
    Returns value based on the passed arguments and input
    target: function
    return: scalar
    Example:
        <i rv-class="record.enabled | enum 'fa fa-lg fa-circle-o' 'fa fa-lg fa-check-circle-o'"></i>
*/
tinybind.formatters.enum = function (el, value) {
    return arguments[parseInt(arguments[0]) + 1];
};

/*
    valuesToString
    Returns: Array passed all values are converted to there string values
    target: function
    return: array
    Example:
        <select class="form-control" name="mselect" rv-value="record.mselect | valuesToString" multiple="multiple">
            <option rv-each-row="form.select" rv-value="row.id">{ row.value }</option>
        </select>
*/

tinybind.formatters.arrayValuesToString = function (target) {
    if (Array.isArray(target)) {
        target.forEach(function (value, index) {
            target[index] = value.toString();
        });
    }

    return target;
};

tinybind.formatters.replace = function (target) {
    return String.format(target,...arguments);
}

/* Formatter Aliases */

tinybind.formatters.eq = tinybind.formatters.isEqual;

tinybind.formatters.ne = function (target, val) {
    return tinybind.formatters.negate(tinybind.formatters.isEqual(target, val));
};

tinybind.formatters.lt = tinybind.formatters.isLess;
tinybind.formatters.gt = tinybind.formatters.isGreater;

tinybind.formatters.le = tinybind.formatters.isLessEqual;
tinybind.formatters.lte = tinybind.formatters.isLessEqual;

tinybind.formatters.ge = tinybind.formatters.isGreaterEqual;
tinybind.formatters.gte = tinybind.formatters.isGreaterEqual;

tinybind.formatters.prv = tinybind.formatters.preventDefault;
tinybind.formatters.format = tinybind.formatters.dateFormat;
tinybind.formatters.len = tinybind.formatters.length;
tinybind.formatters.def = tinybind.formatters.default;
tinybind.formatters.neg = tinybind.formatters.negate;

tinybind.formatters.date = tinybind.formatters.dateFormat;

tinybind.formatters.stringify = tinybind.formatters.prettyPrint;
tinybind.formatters.int = tinybind.formatters.integer;

// backwards compatibility
tinybind.formatters.isLower = tinybind.formatters.isLess;
tinybind.formatters.isLowerEqual = tinybind.formatters.isLessEqual;

/* use external sprintf library */
/*
tinybind.formatters.stringFormat = sprintf();
tinybind.formatters.inject = sprintf();
tinybind.formatters.sprintf = sprintf();
*/