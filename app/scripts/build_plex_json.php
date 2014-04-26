<?php

require_once(__DIR__ . '/../Config/config.php');

buildData::run();

class buildData
{
    private static $_movies_src = 'http://127.0.0.1:32400/library/sections/1/all';
    private static $_tv_shows_src = 'http://127.0.0.1:32400/library/sections/2/all';
    private static $_rotten_tomatoes_api = 'http://api.rottentomatoes.com/api/public/v1.0/movies.json?apikey=4tcdm6p46y23yhkfzzu2nre5';

    public static function run()
    {
        self::build_movies();
        self::build_tv_shows();
    }

    private static function build_movies()
    {
        $data = file_get_contents(self::$_movies_src);
        $data = json_decode(json_encode((array)simplexml_load_string($data)));
        $movie_info = array();
        $count = 0;
        foreach($data->Video as $movie) {
            $info = array();
            $info[] = (isset($movie->{"@attributes"}->title) ? $movie->{"@attributes"}->title : '');
            $info[] = (isset($movie->{"@attributes"}->year) ? $movie->{"@attributes"}->year : '');
            $info[] = (isset($movie->{"@attributes"}->contentRating) ? $movie->{"@attributes"}->contentRating : '');
            $info['poster'] = '';

            $api_information = json_decode(file_get_contents(self::$_rotten_tomatoes_api . '&q=' . str_replace(' ', '+', $movie->{"@attributes"}->title) . '&page_limit=1'));

            if (isset($api_information->movies[0]->posters->original)) {
                $image = $api_information->movies[0]->posters->detailed;
                $info['poster'] = $image;
            }

            // Adjust resolution string
            if (isset($movie->Media->{"@attributes"}->videoResolution)) {
                switch($movie->Media->{"@attributes"}->videoResolution) {
                    case 1080:
                        $info[] = '1080p';
                        break;
                    case 480:
                        $info[] = '480p';
                        break;
                    case 'sd':
                        $info[] = 'Standard Definition';
                        break;
                    case 576:
                        $info[] = '576p';
                        break;
                    default:
                        $info[] = '';
                        break;
                }
            }
            else {
                $info[] = '';
            }

            $info['all'] = $movie;

            $movie_info[] = $info;

            if ($count == 4) {
                $count = 0;
                sleep(1.5);
            }
            else {
                $count++;
            }
        }
        file_put_contents(APP_ROOT . '/public/content/movies.json', json_encode($movie_info));
    }

    private static function build_tv_shows()
    {
        $data = file_get_contents(self::$_tv_shows_src);
        $data = json_decode(json_encode((array)simplexml_load_string($data)));
        $show_info  = array();
        foreach($data->Directory as $show) {
            $info = array();
            $info[] = (isset($show->{"@attributes"}->title) ? $show->{"@attributes"}->title : '');
            $info[] = (isset($show->{"@attributes"}->leafCount) ? $show->{"@attributes"}->leafCount : '');
            $show_info[] = $info;
        }
        file_put_contents(APP_ROOT . '/public/content/tv_shows.json', json_encode($show_info));
    }
}
