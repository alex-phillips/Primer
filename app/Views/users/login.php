<?php

Form::create('users');
Form::add('username', array(
        'label' => 'Username',
    ));
Form::add('password', array(
        'label' => 'Password',
    ));
Form::add('rememberme', array(
        'label' => 'Remember Me',
        'type' => 'checkbox',
    ));
Form::end();

?>

<a href="/register/">Register</a>
|
<a href="/users/forgot_password">Forgot my Password</a>