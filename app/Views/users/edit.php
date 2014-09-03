<?php

Form::create('users');
Form::add('id', array(
    'type' => 'hidden',
    'value' => $this->Session->read('Auth.id'),
));
Form::add('email', array(
    'label' => 'Email Address',
    'value' => $this->user->email,
));
Form::add('name', array(
    'label' => 'Name',
    'value' => $this->user->name,
));
Form::add('bio', array(
    'label' => 'Bio',
    'value' => $this->user->bio,
));
Form::add('avatar', array(
    'label' => 'Avatar',
    'value' => $this->user->avatar,
));
Form::add('password', array(
    'label' => 'Current Password',
    'type' => 'password',
));
Form::add('newpass1', array(
    'label' => 'New Password',
    'type' => 'password',
));
Form::add('newpass2', array(
    'label' => 'Repeat Password',
    'type' => 'password',
));
Form::end();
