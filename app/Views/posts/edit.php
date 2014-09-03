<?php

Form::create('posts');
Form::add('id_post', array(
    'type' => 'hidden',
    'value' => $this->post->id,
));
Form::add('type', array(
    'value' => $this->post->type,
    'label' => 'Type',
));
Form::add('no_publish', array(
    'value' => $this->post->no_publish,
    'label' => 'No Publish',
));
Form::add('title', array(
    'value' => $this->post->title,
    'label' => 'Title',
));
Form::add('body', array(
    'value' => $this->post->body,
    'label' => 'Body',
    'additional_attrs' => array(
        'rows' => 10,
    ),
));
Form::end();

?>

<div id="custom-properties"></div>