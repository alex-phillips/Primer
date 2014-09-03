
<h1>Confirm Delete</h1>
<p>Are you sure you want to delete this post?</p>

<?php

echo $this->post->title;
echo $this->post->body;

Form::create('users');
Form::add('id', array(
    'type' => 'hidden',
    'value' => $this->post->id,
));
Form::end(array(
    'value' => 'Delete Post',
));
