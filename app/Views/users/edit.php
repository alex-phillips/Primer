<?php

$this->Form->create('users');
$this->Form->add('id', array(
    'type' => 'hidden',
    'value' => $this->Session->read('Auth.id'),
));
$this->Form->add('email', array(
    'label' => 'Email Address',
    'value' => $this->user->email,
));
$this->Form->add('name', array(
    'label' => 'Name',
    'value' => $this->user->name,
));
$this->Form->add('bio', array(
    'label' => 'Bio',
    'value' => $this->user->bio,
));
$this->Form->add('avatar', array(
    'label' => 'Avatar',
    'value' => $this->user->avatar,
));
$this->Form->add('password', array(
    'label' => 'Current Password',
    'type' => 'password',
));
$this->Form->add('newpass1', array(
    'label' => 'New Password',
    'type' => 'password',
));
$this->Form->add('newpass2', array(
    'label' => 'Repeat Password',
    'type' => 'password',
));
$this->Form->end();
