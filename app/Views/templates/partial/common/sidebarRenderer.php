<?php
/**
 * Created by PhpStorm.
 * User: exonintrendo
 * Date: 4/6/14
 * Time: 9:52 AM
 */

class SidebarRenderer
{
    public static function render()
    {
        // If viewing a user's page, attempt to render user's music feed
        if (Router::getController() === 'users' && Router::getAction() === 'view') {
            if ($user = Primer::getValue('rendering_object')) {
                return self::render_music_feed($user->username);
            }
        }

        // Render the default sidebar
        return self::render_default();
    }

    public static function render_default()
    {
        $posts = Post::find(array(
            'order' => array(
                'created DESC',
            ),
            'limit' => 5
        ));

        $markup = '<ul>';
        foreach ($posts as $post) {
            if ($post->no_publish) {
                continue;
            }
            $markup .= '<li><a href="/posts/view/' . $post->id . '">' . $post->title . '</a></li>';
        }

        return <<<__TEXT__
            <h5>Links</h5>
            <ul class="side-nav">
                <li><a href="https://github.com/exonintrendo/PrimerPHP">PrimerPHP</a></li>
            </ul>

            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Recent</h3>
                </div>
                <div class="panel-body">
                    $markup
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