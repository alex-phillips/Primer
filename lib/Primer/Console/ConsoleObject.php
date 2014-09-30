<?php
/**
 * ConsoleObject
 *
 * @author Alex Phillips <exonintrendo@gmail.com>
 * @date 9/27/14
 * @time 11:57 AM
 */

namespace Primer\Console;

use Primer\Console\Input\Reader;
use Primer\Console\Output\Writer;
use Primer\Console\Output\StyleFormatter;

class ConsoleObject
{
    protected $_stdout;
    protected $_stderr;
    protected $_stdin;

    public function __construct()
    {
        $this->_stdout = new Writer(Writer::STREAM_STDOUT);
        $this->_stderr = new Writer(Writer::STREAM_STDERR);
        $this->_stdin = new Reader(Reader::STREAM_READ);
        $this->setupStyles();
    }

    protected function setupStyles()
    {
        $info = new StyleFormatter('green');
        $this->setFormatter('info', $info);

        $warning = new StyleFormatter('yellow');
        $this->setFormatter('warning', $warning);

        $error = new StyleFormatter('red');
        $this->setFormatter('error', $error);
    }

    public function setFormatter($xmlTag, StyleFormatter $displayFormat)
    {
        $this->_stdout->setFormatter($xmlTag, $displayFormat);
    }

    /**
     * @return \Primer\Console\Output\Writer
     */
    public function getStdout()
    {
        return $this->_stdout;
    }

    public function out($message, $numberOfNewLines = 1, $verbosityLevel = Writer::VERBOSITY_NORMAL)
    {
        $this->_stdout->setVerbosityForOutput($verbosityLevel);
        $this->_stdout->writeMessage($message, $numberOfNewLines);
    }

    public function err($message, $numberOfNewLines = 1, $verbosityLevel = Writer::VERBOSITY_NORMAL)
    {
        $this->_stderr->writeMessage($message, $numberOfNewLines, $verbosityLevel);
    }

    public function read()
    {
        return $this->_stdin->getReadedValue();
    }
}