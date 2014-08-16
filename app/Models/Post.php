<?php

class Post extends App
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

    protected function beforeSave()
    {
        if (!isset($this->slug) || $this->slug === null) {
            // Only create slug on creation so bookmarks always work in title is edited/changed
            $slug = Inflector::slug($this->title, '-');
            // Check to make sure slug doesn't exist, if it does, add timestamp
            $posts = Post::find(array(
                'conditions' => array(
                    'OR' => array(
                        'slug' => $slug,
                        'slug' => $slug . '-' . date('Y-m-d', time()),
                    )
                )
            ));

            // Check if slugs exist, set accordingly
            switch(sizeof($posts)) {
                case 0:
                    $this->slug = $slug;
                    break;
                case 1:
                    $this->slug = $slug . '-' . date('Y-m-d', time());
                    break;
                default:
                    $this->slug = $slug . '-' . date('Y-m-d_h-m-s', time());
                    break;
            }
        }

        return true;
    }
}