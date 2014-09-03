<?php

Form::create('users');
Form::add('username', array(
    'label' => 'Username',
));
Form::add('email', array(
    'label' => 'Email Address',
));
Form::add('password1', array(
    'label' => 'New Password',
    'type' => 'password',
));
Form::add('password2', array(
    'label' => 'Repeat Password',
    'type' => 'password',
));
Form::add('captcha', array(
    'label' => 'Are you human? <img src="/users/showCaptcha" alt="captcha"/>',
));
Form::end();