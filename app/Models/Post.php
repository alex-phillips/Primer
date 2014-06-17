<?php

class Post extends Model
{
    protected static $_validate = array(
        'title' => array(
            'required' => array(
                'message' => 'Title cannot be left blank'
            ),
        ),
        'type' => array(
            'in_list' =>  array(
                'list' => array(
                    'post',
                    'quote',
                ),
                'message' => "Invalid option selected"
            )
        ),
        'body' => array(
            'required' => array(
                'message' => 'Body cannot be left blank'
            ),
        ),
    );

    public function set($params)
    {
        parent::set($params);
        if (is_array($this->custom_properties) || is_object($this->custom_properties)) {
            $this->custom_properties = serialize($this->custom_properties);
        }
    }

    public function getCustomProperties()
    {
        return unserialize($this->custom_properties);
    }

    public function setCustomProperties($properties)
    {
        $this->custom_properties = serialize($properties);
    }
}