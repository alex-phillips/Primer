<h1>Request a password reset</h1>
Enter your username and you'll get an email with instructions
<!-- request password reset form box -->
<?php

Form::create('users');
Form::add('username');
Form::end();