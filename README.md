Concentrate
===========
This package provides a command-line tool to bundle and minify static resources for a website using silverorange's Site package. Files are combined according to configuration files passed on the command-line.

<pre>
Usage:
  /usr/bin/concentrate [options] &lt;webroot&gt;

Options:
  -c pearrc, --pearrc=pearrc           Location of PEAR configuration file.
  -d directory, --directory=directory  Optional additional directory to
                                       search for dependency data files.
  -C, --combine                        Write combined files.
  -m, --minify                         Write minified files.
  -l, --compile                        Write compiled LESS files. See
                                       http://www.lesscss.org/.
  -v, --verbose                        Sets verbosity level. Use multiples
                                       for more detail (e.g. "-vv").
  -h, --help                           show this help message and exit
  --version                            show the program version and exit

Arguments:
  webroot  The directory to which files will be written.
</pre>

Installation
------------

```sh
composer require silverorange/concentrate
```
