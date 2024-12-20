# Orange Framework #

My orange MVC framework aims to provide a unified way to access the standard MVC workflow in as few classes as possible.

Instead of creating 10 or 20 classes to model, sending out output. Orange does it in 1. Instead of another 5-10 to model handling input, Orange does it in 1.

This makes it easy for ANY PHP developer to wrap their head around what is happening. In addition, everything is extendable and testable in this way, including global functions.

Additionally, using interfaces to enforce typing instead of concrete classes, as long as a class implements that interface, will ensure that everything will still work as planned.

This makes extending classes with your functions easy and STILL injecting them into another class, maintaining PHP-type enforcement.

It also makes it easier to use UNIT Test code because you can inject mock classes, and as long as they implement the same interface, the class you inject them into won't know the difference if it's getting the real class or a mock.

## Unit Testing

You can run the base Orange Framework UnitTests, which are at

/packages/orange/bin/tests/runUnitTests.sh

This unit tests the base framework classes.
Everything is built on top of this. If one or more of these fail, then there is a good chance that other features will fail.

You can review the output here: https://github.com/ProjectOrangeBox/Orange2023/blob/main/packages/orange/bin/tests/results.txt

## Setup

I used PHP 8.2 but have yet to test it for backward compatibility. Fortunately, the framework only has a few classes, so fixing any incompatibilities with an older version of PHP should be straightforward.

Don't forget to run `composer install`

Remember to add a .env file at the root of your project. Even an empty `.env` file works.

A sample .env has been provided in ./support/samples/sample.env

The .env file is in [.ini format](https://en.wikipedia.org/wiki/INI_file)

## An HMVC structure has been provided inside /application

/application/people

/application/rest

/application/shared

/application/welcome

You could also configure Orange to have everything in a single application folder.

## Recommended

For handling Dates

Carbon - A simple PHP API extension for DateTime.

https://carbon.nesbot.com/

https://github.com/briannesbitt/carbon

For handling Migrations

Phinx - PHP Database Migrations For Everyone.

https://phinx.org/
