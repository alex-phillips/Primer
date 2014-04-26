# PrimerPHP Framework

PrimerPHP is a custom MVC framework build with PHP. The world does not need any more frameworks, however, I believe
that building one is a great learning experience into the MVC architecture iteslf, the language you are building it
with, and general concepts of programming. I have based a lot of the framework's structure and ideas off of CakePHP,
but its capabilities are far from those of Cake.

###HOW TO INSTALL

The framework is built to run out-of-the-box with some possible, minor, changes. First, make sure that the included
.htaccess files were copied over correctly into the following directories:
```
/.htaccess
/app/.htaccess
/app/public/.htaccess
```
These files provide the correct routing to the index.php which will configure and bootstrap the application you are building.
You do, however, need to make sure that mod_rewrite module is enabled.

A general StackOverflow discussion about the activation of mod_rewrite (and troubleshooting) here:
http://stackoverflow.com/q/869092/1114320

### In The Code
Open up and edit app/Config/config.php and edit all of the site-specific values here. This is where you will set up the
connection variables to your database, as well as optional SMTP settings, site URLs, and default values for emails tha are
automatically sent out for user registration, password resetting, etc.

###REQUIREMENTS

* needs **PHP 5.3.7+**, PHP 5.4+ or PHP 5.5+
* needs mySQL 5.1+
* needs the PHP mysqli extension activated (standard on nearly all modern servers)

### Credits
This framework was originally started as a fork of panique's PHP login system. However, with all of the changes and
modifications I have made to the code, very little of it is still the same.
