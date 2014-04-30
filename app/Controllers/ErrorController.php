<?php

class ErrorController extends AppController
{
	function error404()
    {
		$this->view->msg = 'This page doesnt exist';
		$this->view->render('error/404');
	}
}