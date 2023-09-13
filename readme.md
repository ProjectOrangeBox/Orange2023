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

Don't forget to add a .env file in the root of your project even if it's empty `touch .env` works.

A sample .env has been provided and sample.env

The .env file is in [.ini format](https://en.wikipedia.org/wiki/INI_file)

## Samples Modules Folders added to show the possiblily of modular design

/modulea

/moduleb

You could than move the config folder and bootstrap file for example into a folder named "core" and put all of your controllers, models and views into "module" folders.

## Recommended

For handling Dates

https://carbon.nesbot.com/

https://github.com/briannesbitt/carbon

