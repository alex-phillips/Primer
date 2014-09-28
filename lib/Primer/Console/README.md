PHP Shell Framework
===================

This framework can be used to creating fully shell scripts. Originally forked from https://github.com/piotrooo/php-shell-framework.

Creating new application
------------------------
To create new application, create a new Console application and calling run.

```php
<?php
/*
 * Console.php
 */
$app = new \Primer\Console\Console();
$app->run();
```

Then simply run the php script from the command line.

Creating Commands
-----------------

Newly created file should have number of requirements:
* Class should extends from `BaseCommand`.
* Should include the `run` method which contains the execution code.
* You can also include the `configure` method for any setup code. This is automatically
called before running the command.

So created command should looks like:
```php
<?php
class HelloCommand extends \Primer\Console\BaseCommand
{
    public function configure()
    {
    }

    public function main()
    {
        $this->out("Hello world");
    }
}
```

Running application
-------------------

Note: running the application without any arguments will output all available
options and commands.

After creating the command, you'll need to add it to the application. Pass either the class name
or an instance of the class and an array of aliases the class can be called by. After adding
the command, we want run it from our console.

```php
<?php
$app->addCommand(new HelloCommand(), array('hello'));
```

### Basicly call from shell
    $ php console.php hello

After this call, our application print - by use `out` method - on `STDOUT` string __Hello world__.

### Calling with arguments
    $ php console.php hello -N --user=Alex

Application can accept short and long types of parameters.

This framework implements this approach:

[http://www.gnu.org/software/libc/manual/html_node/Argument-Syntax.html](http://www.gnu.org/software/libc/manual/html_node/Argument-Syntax.html)

[http://pubs.opengroup.org/onlinepubs/009695399/basedefs/xbd_chap12.html](http://pubs.opengroup.org/onlinepubs/009695399/basedefs/xbd_chap12.html)

Possible combinations:

* `-n` *short parameter without argument, return true*
* `-n hello` *short parameter with argument, space separed, return hello*
* `-nhello` *short parameter with argument, no space separed, return hello*
* `--user` *long parameter without argument, return true*
* `--user=Alex` *long parameter with argument, equal sign separed, return Alex*

To add support to parameters in application in method `configure`, we must set parameters of each parameter.
Support is added for short parameters, long parameters, and a value requirement.

```php
public function configure()
{
    $this->addParameter('u', 'user', \Primer\Console\Input\DefinedInput::VALUE_REQUIRED);
}
```

This configuration allows us to many possibilities call our parameters.

This call:

    $ php console.php hello -u Alex

is corresponding to:

    $ php console.php hello --username Alex

In `main` method we get parameter like this:

    $namespace = $this->getParameterValue('username');

this getter working on `-u` and `--user` parameter equally.

__Special case.__ If we call application like that:

    $ php psf.php app:hello -u Alex --username AlexP

The `getParameterValue` method will return `AlexP`.

Output
------

When you want display someting on `STDOUT` you can use `out` method:

```php
$this->out("Hello World Today!!!");
```

print:

```
Hello World Today!!!
```

You can aslo defined how many new lines should be after output message:

```php
$this->out("Hello World Today!!!", 5);
```

print:

```
Hello World Today!!!




```
### Console output levels

Sometimes you need different levels of verbosity. PHP Shell Framework provide three levels:

1. QUIET
2. NORMAL
3. VERBOSE

Default all outputs working in `NORMAL` level. If you want change level you must define this in `out` method.

__Example:__

```php
$this->out('This message is in normal verbosity');
$this->out('This message is in quiet verbosity', 1, \Primer\Console\Output\Writer::VERBOSITY_QUIET);
$this->out('This message is in verbose verbosity', 1, \Primer\Console\Output\Writer::VERBOSITY_VERBOSE);
```

If you want run application in `NORMAL` level:

    $ php console.php hello

output:

    This message is in normal verbosity
    This message is in quiet verbosity

If you want run application in `QUIET` level:

    $ php console.php hello --quiet

output:

    This message is in quiet verbosity

If you want run application in `VERBOSE` level:

    $ php console.php hello --verbose

output:

    This message is in normal verbosity
    This message is in quiet verbosity
    This message is in verbose verbosity

Styling output
--------------

Styling output is done by user-defined tags - like XML. PHP Shell Framework using style formetter will replace XML tag to correct defined ANSI code sequence.

To declare new XML tag and corresonding with him ANSI code you do:

```php
$styleFormat = new \Primer\Console\Output\StyleFormatter('gray', 'magenta', array('blink', 'underline'));
$this->setFormatter('special', $styleFormat);
```

This would you to allow `<special>` tag in you output messages and will set text color to `gray`, background color to `magenta` and have two effects - `blink` and `underline`.

```php
$this->out("<special>Hello</special> orld <special>Today</special>!!!");
```

You can use following color for text attributes:

* black
* red
* green
* brown
* blue
* magenta
* cyan
* gray

For background color use:

* black
* red
* green
* brown
* blue
* magenta
* cyan
* white

Also you can use following effects:

* defaults
* bold
* underline
* blink
* reverse
* conceal

Reading
-------

Method `read` reads and interprest characters from `STDIN`, which usually recives what the user type at the keyboard.

Usage of `read`:

```php
$this->out("Type how old are you: ", 0);
$age = $this->read();
if (!empty($age)) {
    $this->out('You have ' . $age . ' years old - nice!');
}
```

This piece of code wait unit user type something on keyboard.

Helpers
-------

In framework we can use helpers to generate some views.

### Table
Table is simple helper which generate tabular data.

Usage of `table`:

```php
$table = $this->getHelper('Table');
$table
    ->setHeaders(array('ID', 'Name', 'Surname'))
    ->setRows(array(
        array('1', 'John', 'Smith'),
        array('2', 'Brad', 'Pitt'),
        array('3', 'Denzel', 'Washington'),
        array('4', 'Angelina', 'Jolie')
    ));
$table->render($this->getStdout());
```

will generate:

    +----+----------+------------+
    | ID | Name     | Surname    |
    +----+----------+------------+
    | 1  | John     | Smith      |
    | 2  | Brad     | Pitt       |
    | 3  | Denzel   | Washington |
    | 4  | Angelina | Jolie      |
    +----+----------+------------+

Additionaly we can add single row to our table by using `addRow` method:

```php
$table->addRow(array('5', 'Peter', 'Nosurname'));
```

will produce:

    +----+----------+------------+
    | ID | Name     | Surname    |
    +----+----------+------------+
    | 1  | John     | Smith      |
    | 2  | Brad     | Pitt       |
    | 3  | Denzel   | Washington |
    | 4  | Angelina | Jolie      |
    | 5  | Peter    | Nosurname  |
    +----+----------+------------+

### Progress bar
This helper provide progress functionality.

Usage of `progress bar`:
```php
$progress = $this->getHelper('ProgressBar');
$progress->initialize($this->getStdout(), 9);
for ($i = 0; $i < 9; $i++) {
    $progress->increment();
    sleep(1);
}
```

will produce:

    4/9 (44%) [======================............................]

### Loader
Loader helper get possibility of display loader pseudo animation.

Usage of `loader`:
```php
$loader = $this->getHelper('Loader');
$loader->initialize($this->getStdout());
for ($i = 0; $i < 10; $i++) {
    $loader->start();
    sleep(1);
}
```

Also we can customizing loader through setting display char sequence by method `setCharSequence`:
```php
$loader->setCharSequence(array('.', '..', '...'));
```
