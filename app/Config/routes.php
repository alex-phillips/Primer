<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 4/12/14
 * Time: 1:44 PM
 */

Router::route('/', array('controller' => 'posts', 'action' => 'index'));
Router::route('/user/:username', array('controller' => 'users', 'action' => 'view', 'username'));
Router::route('/login/', array('controller' => 'users', 'action' => 'login'));
Router::route('/logout/', array('controller' => 'users', 'action' => 'logout'));
Router::route('/register/', array('controller' => 'users', 'action' => 'add'));
Router::route('/movies', array('controller' => 'pages', 'action' => 'movies'));