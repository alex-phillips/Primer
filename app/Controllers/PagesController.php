<?php

use Primer\Core\View;

class PagesController extends AppController
{
    public function index()
    {
    }

    public function movies()
    {
        if (isset($_REQUEST['ajax'])) {
            $movies = file_get_contents(APP_ROOT . '/public/content/movies.json');
            $movies = json_decode($movies);


            if (isset($_REQUEST['id_movie'])) {
                echo json_encode($movies[$_REQUEST['id_movie']]);
                exit;
            }

            $markup = '';
            $query = strtolower($_REQUEST['query']);
            foreach ($movies as $index => $movie) {
                $title = strtolower($movie->all->{'@attributes'}->title);
                $summary = strtolower($movie->all->{'@attributes'}->summary);
                if ($query == '' || strpos($title, $query) !== false || strpos($summary, $query) !== false) {
                    $title = (isset($movie->all->{"@attributes"}->title)) ? $movie->all->{"@attributes"}->title : '';
                    $year = (isset($movie->all->{"@attributes"}->year)) ? $movie->all->{"@attributes"}->year : '';
                    $summary = (isset($movie->all->{"@attributes"}->summary)) ? $movie->all->{"@attributes"}->summary : '';
                    $poster = ($movie->poster) ? $movie->poster : 'http://images.rottentomatoescdn.com/images/redesign/poster_default.gif';
                    $default_poster = 'http://images.rottentomatoescdn.com/images/redesign/poster_default.gif';
                    $markup .= <<<__TEXT__
                        <div class="row" style="margin-left: 0; margin-right: 0;">
                            <h3><a href="#" class="movie-info" data-movie-id="$index">$title</a></h3>
                            <h6>$year</h6>
                            <div class="col-sm-2">
                                <img src="$default_poster" data-src="$poster" alt="$title" style="width: 100%;"/>
                            </div>
                            <div class="col-sm-10">
                                <p>$summary</p>
                            </div>
                        </div>
                        <hr/>
__TEXT__;
                }

            }
            if (!$markup) {
                $markup = '<h2>No results found</h2>';
            }
            echo $markup;
            exit;
        }
        View::addJS('pages/movies');
        $this->view->title = 'Movies';
        $this->view->render('pages/movies');
    }

    public function movies_bootstrap()
    {
        $this->view->addJS('angular/bootstrap');
        $this->view->title = 'Movies';
        $this->view->render('pages/movies_bootstrap');
    }

    public function tv_shows()
    {
        $this->view->render('pages/tv_shows');
    }
	
}