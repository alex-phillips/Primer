<?php
/**
 * StyleFormatter
 *
 * @author Piotr Olaszewski
 */

namespace Primer\Console\Output;

use Primer\Console\XmlParser;

class StyleFormatter
{
    public static $fgColors = array(
        'black' => 30,
        'red' => 31,
        'green' => 32,
        'brown' => 33,
        'blue' => 34,
        'magenta' => 35,
        'cyan' => 36,
        'gray' => 37
    );
    public static $bgColors = array(
        'black' => 40,
        'red' => 41,
        'green' => 42,
        'brown' => 43,
        'blue' => 44,
        'magenta' => 45,
        'cyan' => 46,
        'white' => 47
    );
    public static $effects = array(
        'defaults' => 0,
        'bold' => 1,
        'underline' => 4,
        'blink' => 5,
        'reverse' => 7,
        'conceal' => 8
    );

    private $_fgColor;
    private $_bgColor;
    private $_effects;

    public function __construct($fgColor, $bgColor = '', array $effects = array())
    {
        $this->_fgColor = $fgColor;
        $this->_bgColor = $bgColor;
        $this->_effects = $effects;
    }

    public function render($xmlTag, $message)
    {
        $values = XmlParser::getValueBetweenTags($xmlTag, $message);
        $buildNewMessage = $message;
        foreach ($values as $val) {
            $valueReplaced = '<' . $xmlTag . '>' . $val . '</' . $xmlTag . '>';
            $valueResult = $this->_replaceTagColors($val);

            $buildNewMessage = str_replace($valueReplaced, $valueResult, $buildNewMessage);
        }
        return $buildNewMessage;
    }

    private function _replaceTagColors($text)
    {
        $colors = $this->getBgColorCode() . ';' . $this->getFgColorCode();
        $effects = $this->getParsedToStringEffects();
        $effectsCodeString = $effects ? ';' . $effects : '';
        return sprintf("\033[%sm%s\033[0m", $colors . $effectsCodeString, $text);
    }

    public function getFgColorCode()
    {
        return isset(self::$fgColors[$this->_fgColor]) ? self::$fgColors[$this->_fgColor] : '';
    }

    public function getBgColorCode()
    {
        return self::$bgColors[$this->_bgColor];
    }

    public function getParsedToStringEffects()
    {
        $effectCodeList = array();
        if (!empty($this->_effects)) {
            foreach ($this->_effects as $effectName) {
                $effectCodeList[] = $this->getEffectCode($effectName);
            }
        }
        $effectString = implode(';', $effectCodeList);
        return $effectString;
    }

    public function getEffectCode($effect)
    {
        return self::$effects[$effect];
    }
}