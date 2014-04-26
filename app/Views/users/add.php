<?php

$this->Form->create('users');
$this->Form->add('username', array(
    'label' => 'Username',
));
$this->Form->add('email', array(
    'label' => 'Email Address',
));
$this->Form->add('password1', array(
    'label' => 'New Password',
    'type' => 'password',
));
$this->Form->add('password2', array(
    'label' => 'Repeat Password',
    'type' => 'password',
));
$this->Form->add('captcha', array(
    'label' => 'Are you human? <img src="/users/showCaptcha" alt="captcha"/>',
));

$this->Form->end();