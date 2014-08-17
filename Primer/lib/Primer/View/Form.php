<?php
/**
 * @author Alex Phillips
 * Date: 10/24/13
 * Time: 5:28 PM
 */

namespace Primer\View;

use Primer\Core\Primer;
use Primer\Component\RequestComponent;

class Form
{
    private $_controller;
    private $_action;
    private $_model;
    private $_objectName = 'default';
    private $_schema = array();
    private $_validate = array();
    private $_markup;
    private static $_fileCounter = 0;

    private $request;

    public function __construct($controller, $action)
    {
        $this->_controller = $controller;
        $this->_action = $action;

        $this->request = RequestComponent::getInstance();
    }

    public function create($object, $method = 'post', $params = null)
    {
        $modelName = Primer::getModelName($object);
        $this->_model = new $modelName;
        $class = "";

        // TODO: there's gotta be a better way to do this...
        $this->_objectName = call_user_func(array($this->_model, 'getClassName'));
        $this->_schema = call_user_func(array($this->_model, 'getSchema'));
        $this->_validate = call_user_func(array($this->_model, 'getValidationArray'));

        $action = '';
        if (isset($params['action'])) {
            $action = $params['action'];
        }

        if (isset($params['class'])) {
            $class = $params['class'];
        }

        $attrs = "";
        if (isset($params['attrs'])) {
            foreach ($params['attrs'] as $k => $v) {
                $attrs .= " $k=\"$v\" ";
            }
        }

        $enctype = '';
        if (isset($params['enctype'])) {
            $enctype = 'enctype="' . $params['enctype'] . '"';
        }

        $this->_markup = <<<__TEXT__
            <form id="{$this->_controller}{$this->_action}Form" $enctype method="$method" action="$action" class="$class"$attrs>
__TEXT__;

    }

    public function add($name, $params = array())
    {
        $label = $name;
        $class = "";

        // @TODO: add support for future associtions such as HABTM, has many, etc.
        if (preg_match('#\Aid_(.+)$#', $name, $matches)) {
            if (isset($this->_model->belongsTo)) {
                if (is_array($this->_model->belongsTo)) {

                }
                else if (is_string($this->_model->belongsTo)) {
                    if (Primer::getModelName($matches[1]) === $this->_model->belongsTo) {
                        $owners = call_user_func(array(Primer::getModelName($matches[1]), 'find'));
                        $params['options'] = array();
                        $params['use_option_keys'] = true;
                        foreach ($owners as $owner) {
                            $params['options'][$owner->id] = $owner->name;
                        }
                    }
                }
            }
        }

        if (isset($params['label'])) {
            $label = $params['label'];
        }

        if (isset($params['class'])) {
            $class = $params['class'];
        }

        $value = '';
        if (isset($params['type']) && $params['type'] === 'password') {
            $value = '';
        }
        else if ($this->request->post->get('data.' . $this->_objectName . '.' . $name)) {
            $value = $this->request->post->get('data.' . $this->_objectName . '.' . $name);
        }
        else if ($this->request->query->get('data.' . $this->_objectName . '.' . $name)) {
            $value = $this->request->query->get('data.' . $this->_objectName . '.' . $name);
        }
        else if (isset($params['value'])) {
            $value = $params['value'];
        }

        $type = 'text';
        if (isset($params['type'])) {
            $type = $params['type'];
        }
        else if (array_key_exists($name, $this->_schema)) {
            if (array_key_exists($name, $this->_validate) && array_key_exists('in_list', $this->_validate[$name]) && !isset($params['options'])) {
                $params['options'] = $this->_validate[$name]['in_list']['list'];
            }
            if (isset($params['options'])) {
                $type = 'select';
                $options_markup = '';
                foreach ($params['options'] as $index => $option) {
                    if (isset($params['use_option_keys']) && $params['use_option_keys'] === true) {
                        $optionValue = $index;
                    }
                    else {
                        $optionValue = $option;
                    }
                    if ($value == $option) {
                        $options_markup .= "<option value=\"{$optionValue}\" selected=\"selected\">$option</option>";
                    }
                    else {
                        $options_markup .= "<option value=\"{$optionValue}\">$option</option>";
                    }

                }
            }
            else {
                switch ($this->_schema[$name]['type']) {
                    case 'text':
                        $type = 'textarea';
                        break;
                    case 'varchar':
                    case 'char':
                        $type = 'text';
                        break;
                    case 'tinyint(1)':
                        $type = 'checkbox';
                        break;
                }
            }
        }
        else {
            $type = 'text';
        }

        $form_name = "data[{$this->_objectName}][$name]";
        if ($type === 'file') {
            $form_name = 'file' . self::$_fileCounter;
            self::$_fileCounter++;
        }

        $required = isset($params['required']) ? $params['required'] : false;
        $label_markup = $this->build_label($form_name, $label, $type, $required);

        $additionalAttrs = array();
        if (isset($params['additional_attrs'])) {
            foreach ($params['additional_attrs'] as $attribute => $val) {
                $additionalAttrs[] = "$attribute=\"$val\"";
            }
        }
        $additionalAttrs = implode(' ', $additionalAttrs);

        switch ($type) {
            case 'textarea':
                $this->_markup .= <<<__TEXT__
                    <div class="field $name">
                        $label_markup
                        <textarea id="$name" name="$form_name" class="$class" $additionalAttrs>$value</textarea>
                    </div>
__TEXT__;
                break;
            case 'select':
                $this->_markup .= <<<__TEXT__
                    <div class="field $name">
                        $label_markup
                        <select name="$form_name" value="$value" class="$class" $additionalAttrs/>
                            $options_markup
                        </select>
                    </div>
__TEXT__;
                break;
            case 'checkbox':
                $checked = '';
                if ($value != '' && $value) {
                    $checked = 'checked="checked"';
                }
                $this->_markup .= <<<__TEXT__
                    <div class="field checkbox">
                        <label>
                            <input type="$type" name="$form_name" value="1" class="$class" $checked $additionalAttrs/> $label
                        </label>
                    </div>
__TEXT__;
                break;
            default:
                $this->_markup .= <<<__TEXT__
                    <div class="field $name">
                        $label_markup
                        <input type="$type" name="$form_name" value="$value" class="$class" $additionalAttrs/>
                    </div>
__TEXT__;
                break;
        }

    }

    private function build_label($field, $label = null, $type, $required = false)
    {
        if ($label == null) {
            $label = $field;
        }

        $required = ($required === true) ? 'required' : '';

        $hide = '';
        if ($type == 'hidden') {
            $hide = 'hidden';
        }
        return '<label for="' . $field . '" ' . $hide . ' class="' . $required . '">' . $label . '</label>';
    }

    public function end($params = array(), $return = false)
    {
        $value = 'Submit';
        if (isset($params['value'])) {
            $value = $params['value'];
        }

        $class = "button ";
        if (isset($params['class'])) {
            $class = $params['class'];
        }

        $this->_markup .= '<div class="actions"><input type="Submit" value="' . $value . '"  class="' . $class . '"/></div></form>';

        if ($return == true) {
            return $this->_markup;
        }
        echo $this->_markup;
    }
}