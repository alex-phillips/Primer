<?php
/**
 * @author Alex Phillips
 * Date: 10/24/13
 * Time: 5:28 PM
 */

class Form
{
    private $_controller;
    private $_action;
    private $_objectName = 'default';
    private $_schema = array();
    private $_markup;

    public function __construct($controller, $action)
    {
        $this->_controller = $controller;
        $this->_action = $action;
    }

    public function create($object, $method = 'post', $params = null)
    {
        $modelName = Inflector::singularize($object);
        $model = new $modelName;
        $class = "";

        // TODO: there's gotta be a better way to do this...
        $this->_objectName = $model::getClassName();
        $this->_schema = $model::getSchema();

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

        $this->_markup = <<<__TEXT__
            <form id="{$this->_controller}{$this->_action}Form" method="$method" action="$action" class="$class"$attrs>
__TEXT__;

    }

    public function add($name, $params = array())
    {
        $label = $name;
        $class = "";

        if (isset($params['label'])) {
            $label = $params['label'];
        }

        if (isset($params['class'])) {
            $class = $params['class'];
        }

        $value = '';
        if (isset($params['value'])) {
            $value = $params['value'];
        }

        $type = 'text';
        if (isset($params['type'])) {
            $type = $params['type'];
        }
        else if (array_key_exists($name, $this->_schema)) {
            if (isset($this->_schema[$name]['options'])) {
                $type = 'select';
                $options_markup = '';
                foreach ($this->_schema[$name]['options'] as $option) {
                    if ($value == $option) {
                        $options_markup .= "<option value=\"{$option}\" selected=\"selected\">$option</option>";
                    }
                    else {
                        $options_markup .= "<option value=\"{$option}\">$option</option>";
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

        $label_markup = $this->build_label($form_name, $label, $type);

        switch ($type) {
            case 'textarea':
                $this->_markup .= <<<__TEXT__
                    <div class="field $name">
                        $label_markup
                        <textarea id="$name" name="$form_name" class="$class">$value</textarea>
                    </div>
__TEXT__;
                break;
            case 'select':
                $this->_markup .= <<<__TEXT__
                    <div class="field $name">
                        $label_markup
                        <select name="$form_name" value="$value" class="$class"/>
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
                            <input type="$type" name="$form_name" value="1" class="$class" $checked/> $label
                        </label>
                    </div>
__TEXT__;
                break;
            default:
                $this->_markup .= <<<__TEXT__
                    <div class="field $name">
                        $label_markup
                        <input type="$type" name="$form_name" value="$value" class="$class"/>
                    </div>
__TEXT__;
                break;
        }

    }

    private function build_label($field, $label = null, $type)
    {
        if ($label == null) {
            $label = $field;
        }

        $hide = '';
        if ($type == 'hidden') {
            $hide = 'hidden';
        }
        return '<label for="' . $field . '" ' . $hide . '>' . $label . '</label>';
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