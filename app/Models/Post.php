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
}