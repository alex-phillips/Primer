<?php

class Error_Controller extends App_Controller
{
	function error404()
    {
		$this->view->msg = 'This page doesnt exist';
		$this->view->render('error/404');
	}
}