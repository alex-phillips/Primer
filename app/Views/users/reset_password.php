<h1>Change Password</h1>
<?php

$this->Form->create('users');
$this->Form->add('username', array(
    'type' => 'hidden',
    'value' => $this->username
));
$this->Form->add('password_reset_hash', array(
    'type' => 'hidden',
    'value' => $this->password_reset_hash,
));
$this->Form->add('newpass1', array(
    'type' => 'password',
    'label' => 'New Password',
));
$this->Form->add('newpass2', array(
    'type' => 'password',
    'label' => 'Repeat New Password',
));
$this->Form->end();
