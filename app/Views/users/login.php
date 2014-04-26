<?php

$this->Form->create('users');
$this->Form->add('username', array(
    'label' => 'Username',
));
$this->Form->add('password', array(
    'label' => 'Password',
    'type' => 'password',
));
$this->Form->add('rememberme', array(
    'label' => 'Remember Me',
    'type' => 'checkbox'
));
$this->Form->end();

?>

<a href="/register/">Register</a>
|
<a href="/users/requestpasswordreset">Forgot my Password</a>