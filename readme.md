# Orange Framework #

The Orange MVC framework does not replace the other first-rate PHP frameworks, and while it has powered several sites, it is now mainly to showcase my coding skills. 
It started as a proof concept for a customer, running on just a few Raspberry Pis (model 3). Even the older model could efficiently serve over 1000 requests per second, winning me my first contract. 

It has been continually updated ever since. While I also work with other PHP frameworks, I would love to hear from you if this is the quality of code you are looking for or if you would like to learn more about this framework.

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

You could also configure Orange to have everything in a single application directory.

## Recommended

For handling Dates

Carbon - A simple PHP API extension for DateTime.

https://carbon.nesbot.com/

https://github.com/briannesbitt/carbon

For handling Migrations

Phinx - PHP Database Migrations For Everyone.

https://phinx.org/
