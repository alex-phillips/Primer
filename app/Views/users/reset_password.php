<h1>Change Password</h1>
<?php

Form::create('users');
Form::add('username', array(
    'type' => 'hidden',
    'value' => $this->username
));
Form::add('password_reset_hash', array(
    'type' => 'hidden',
    'value' => $this->password_reset_hash,
));
Form::add('newpass1', array(
    'type' => 'password',
    'label' => 'New Password',
));
Form::add('newpass2', array(
    'type' => 'password',
    'label' => 'Repeat New Password',
));
Form::end();
