<?php

$this->Form->create('posts');
$this->Form->add('id_post', array(
    'type' => 'hidden',
    'value' => $this->post->id,
));
$this->Form->add('type', array(
    'value' => $this->post->type,
    'label' => 'Type',
));
$this->Form->add('no_publish', array(
    'value' => $this->post->no_publish,
    'label' => 'No Publish',
));
$this->Form->add('title', array(
    'value' => $this->post->title,
    'label' => 'Title',
));
$this->Form->add('body', array(
    'value' => $this->post->body,
    'label' => 'Body',
));
$this->Form->end();

?>

<div id="custom-properties"></div>