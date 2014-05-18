
<h1>Confirm Delete</h1>
<p>Are you sure you want to delete this post?</p>

<?php

echo $this->post->title;
echo $this->post->body;

$this->Form->create('users');
$this->Form->add('id', array(
    'type' => 'hidden',
    'value' => $this->post->id,
));
$this->Form->end(array(
    'value' => 'Delete Post',
));

?>