<h1>Request a password reset</h1>
Enter your username and you'll get an email with instructions
<!-- request password reset form box -->
<?php

$this->Form->create('users');
$this->Form->add('username');
$this->Form->end();