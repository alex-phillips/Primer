<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 4/6/14
 * Time: 9:52 AM
 */

class sidebar_renderer
{
    public static function render()
    {
        // If viewing a user's page, attempt to render user's music feed
        if (Router::$controller === 'users' && Router::$action === 'view') {
            if ($user = Primer::getValue('rendering_object')) {
                return self::render_music_feed($user->username);
            }
        }

        // Render the default sidebar
        return self::render_default();
    }

    public static function render_default()
    {
        return <<<__TEXT__
            <h5>Links</h5>
            <ul class="side-nav">
                <li><a href="https://github.com/exonintrendo/PrimerPHP">PrimerPHP</a></li>
            </ul>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Featured</h3>
                </div>
                <div class="panel-body">
                    <p>Pork drumstick turkey fugiat. Tri-tip elit turducken pork chop in. Swine short ribs meatball irure bacon nulla pork belly cupidatat meatloaf cow.</p>
                    <a href="#">Read More →</a>
                </div>
            </div>
__TEXT__;

    }

    public static function render_music_feed($username)
    {
        $song_markup = '';
        if (file_exists(APP_ROOT . '/public/content/' . $username . '_music_feed.json')) {
            $music_feed = json_decode(file_get_contents(APP_ROOT . '/public/content/' . $username . '_music_feed.json'));
            foreach ($music_feed as $song_info) {
                $song_markup .= <<<__TEXT__
                <div class="media">
                    <a class="pull-left" href="#">
                        <img class="media-object" src="{$song_info->album_artwork}" alt="{$song_info->track}" width="64">
                    </a>
                    <div class="media-body">
                        <h4 class="media-heading">{$song_info->artist}</h4>
                        {$song_info->track}
                    </div>
                </div>
__TEXT__;

            }
        }

        if ($song_markup) {
            $song_markup = <<<__TEXT__
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">Music Feed</h3>
                    </div>
                    <div class="panel-body">
                        $song_markup
                    </div>
                </div>
__TEXT__;

        }
        return $song_markup;
    }
}