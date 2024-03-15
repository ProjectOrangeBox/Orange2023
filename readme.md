# Orange Framework #

The object of my orange mvc framework is to provide a unified way to access the standard MVC workflow in as few classes as possible.

Instead of creating 10 or 20 classes to model sending out output. Orange does it in 1, Instead of another 5-10 to model handling input Orange does it in 1.

This makes it easy for ANY PHP developer to wrap their head around what exactly is going on. In addition everything is extendable and testable this way including the global functions.

Additionally by using Interfaces to enforce typing instead of concrete classes as long as a class implements that interface then everything will still work as planned.

This makes it easy to extend a classes with your own functions and STILL inject it into another class maintaining PHPs type enforcement.

It also makes it easier to UNIT Test code because you can inject mock classes and as long as they implement the same interface the class you are injecting them into won't know the difference if it's getting the real class or a mock.


## Setup

I used PHP 8.2 but haven't tested for backward compatibility. Fortunately the framework is only about 10 classes so fixed any incompatibilities with a older version of PHP should be pretty easy.

Don't forget to run `composer install`

Don't forget to add a .env file in the root of your project even a empty `.env` file works.

A sample .env has been provided in ./support/samples/sample.env

The .env file is in [.ini format](https://en.wikipedia.org/wiki/INI_file)

## Sample database data

Addtionally a sample sql dump has been provided to create a sample database

## a HMVC structure has been provied inside /application

/application/people

/application/rest

/application/shared

/application/welcome

You could also configure orange to have everything in a single application folder

This is setup in /config/config.php and loaded into the http() or cli() methods as well as the configuration in composer.json

Finally a ./bin/copyAssets script has been included to provide a "migration" method to copy module assets into the correct places.

A example of this would be inside the ./application/welcome/assets folder and by calling ./copyAssets /application/welcome/assets

## Recommended

For handling Dates

Carbon - A simple PHP API extension for DateTime.

https://carbon.nesbot.com/

https://github.com/briannesbitt/carbon

For handling Migrations

Phinx - PHP Database Migrations For Everyone.

https://phinx.org/