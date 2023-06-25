# Orange Framework #

The object of my orange mvc framework is to provide a unified way to access the standard MVC workflow in as few classes as possible.

Instead of creating 10 or 20 classes to model sending out output. Orange does it in 1, Instead of another 5-10 to model handling input Orange does it in 1.

This makes it easy for ANY PHP developer to wrap their head around what exactly is going on. In addition everything is extendable and testable this way including the global functions.

Additionally by using Interfaces to enforce typing instead of concrete classes as long as a class implements that interface then everything will still work as planned.

This makes it easy to extend a classes with your own functions and STILL inject it into another class maintaining PHPs type enforcement.

It also makes it easier to UNIT Test code because you can inject mock classes and as long as they implement the same interface the class you are injecting them into won't know the difference if it's getting the real class or a mock.


## Setup

I used PHP 8.2 but haven't tested for backward compatibility. Fortunately the framework is only about 10 classes so fixed any incompatibilities with a older version of PHP should be pretty easy.

Don't forget to run `composer update`

Don't forget to add a .env file in the root of your project even if it's empty `touch .env` works.

The .env file is in [.ini format](https://en.wikipedia.org/wiki/INI_file)

## Important Files ##

`/app/config/services.php` contains the service container configuration

`/app/config/config.php` contains the minimum configuration to get started

`/app/config/routes.php` contains all of your routes and reverse routes

`/app/controllers/*` default location for your controllers

`/app/views/*` default location for your views


## Getting Started ##

If you look in /htdocs/index.php you can see we do a few simple things.

Setup a "root" path to our project as well as the location of our htdocs folder "www" to make locating files easier and keep everything in the root folder (nothing should be some other place on the system this makes unit testing impossible)

We do a quick "change directory" to the root folder of the project.

We load the standard composer autoloader file.

Then merge the .env with the $_ENV to set up server environmental valuables. These should be read using the fetchEnv(...) function to make sure you get a value. These make sure the value exists and also provide for a default if it doesn't.

The .env normally includes database connections for that particular server since the .env file is NOT committed with git.
These settings are kept private from those that don't need to know them. 
It might also include a "run mode" (production, testing, unittest, development, etc...) which could be used to further extend the loading of configurations.

Finally we call http(...) and include the bare basics in configuration as an array this includes:

./app/config/config.php

- config folder - the absolute path of the configuration folder & there for files
- environment - the configuration environment which could for example be fetched from the .env
- services - the absolute path of the services configuration file for the container
- bootstrap file - the absolute path of the bootstrap file to load before any other functions are loaded (optional)


Additionally the file ../packages/orange/src/Orange.php is autoloaded at startup. it contains the global functions to get the application started.

If you look in the /app/controller folder you can see some examples of controllers.

/app/config/routes.php file contains the routes.

/app/config/services.php might look complex but it's actually pretty simple. This config uses closures to generate the services. It is done this way so they are only generated when they are needed and not before

I also have additional composer packages for caching, sessions, cookies, input filtering and validation, different view template engines, Flash Messages, etc... I just wanted to keep this package to the bare minimums to keep it fast and small.


## Folder Overview ##

    /app
        location of your application files. Using namespacing you can have multiple "application" folders. with multiple controllers, models, views, etc...

    /app/Bootstrap.php
        loaded right after composer autoload and .env are merged.
        If present this can be used to override any orange global functions because each of those test to see if they are defined before defining them
    
    /app/config
        location of all config files. each file returns an array of key values pairs.
        supports environmental folders. These are loaded and merged over the root folder config values
    
    /app/controllers (namespaced)
        the location of your applications MVC controllers. You can actually put these any where because the router simply loads namespaced classes
    
    /app/helpers (optional)
        php files of global or static function

    /app/libraries
        php classes. You can actually put these any where because they are simply namespaced classes

    /app/models (optional)
        MVC database models. You can actually put these any where because they are simply namespaced classes

    /app/view
        php view files. (PHP "template" files)

    /bin
        folder of helper command line scripts these usually DO NOT load the framework

    /cli
        folder of command line scripts these usually DO load the framework

    /db
        database migrations and seeding

    /htdocs
        www root folder

    /htdocs/index.php
        main entry file .htaccess send pretty much everything in there

    /packages
        location of the non composer installed namespaced packages

    /packages/orange
        Main files of the Orange MVC framework. Keeping it simple everything is pretty much done in a single class file. No need to load multiple classes and such to extract $_GET values. MVC Core is a total of only about 10 classes all less than a thousand lines.
        
    /packages/orange/src/Config.php
        The configuration loader. This loads and returns arrays. The base configurations are usually based on the class name. output class = output config file.

    /packages/orange/src/Container.php
        This is the main service container. By attaching everything (most reused) to a single injection container as a "service" it is easier to mock those classes as well as extend them. This is also why orange uses interfaces to enforce type. You aren't passing a "Output" class you are passing a "Output" class which implements the "OutputInterface" this way the child class doesn't know if it's the "Output" class or "OutputMock" class because both implement the same interface and the injector simply injects the "output" service (also unaware of the class difference).

    /packages/orange/src/Controller.php
        The abstract base controller.
        This is injected with Input, Output, and Config
    
    /packages/orange/src/Data.php
        This is a data object which can be passed around (by reference because it's a class) and "data" can be attached to it for other processes to use. Many times this is sent to "View" in order to create a view.

    /packages/orange/src/Dispatcher.php
        Based on the Router output this simply calls the appropriate controller and send the final output.

    /packages/orange/src/Error.php
        This handles all of the exceptions and errors which bubble up to the top as well as provide a unified way to send errors to html,ajax,cli requests.

    /packages/orange/src/Event.php (optional)
        If you don't need events you can simple use the Event mock as a service for a little faster speed.

    /packages/orange/src/Input.php
        This provides a unified way to handle input as well as provides a way to mock input for unit tests instead of just calling "$_GET" (which may or may not have what you need for unit testing)

    /packages/orange/src/Log.php (optional)
        Simple file based logging system with support for monolog

    /packages/orange/src/Model.php (optional)
        Simple Model abstract class

    /packages/orange/src/Orange.php
        main startup functions
    
    /packages/orange/src/Output.php
        handle response code, body, header

    /packages/orange/src/Router.php
        convert url to class, method, args.
        also reverse a url to provide a path

    /packages/orange/src/View.php
        load a views based on a view name and array of data (DataInterface)

    /packages/orange/src/exceptions
        exceptions by class name to provide more context just based on the name.

    /packages/orange/src/interfaces
        these are what enforces the type when passing

    /packages/orange/src/stubs
        some unit testing stubs

    /packages/orange/src/views
        default error views. this is the last place views are searched for when trying to load a view. (Normally App/views is searched first).

    /support
        misc testing support etc...

    /tests
        phpunit tests

    /var/*
        server read / write folders

    /vendor/*
        composer vendor folder

    