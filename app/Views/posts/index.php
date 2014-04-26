<?php

$markup = '';

if ($this->posts) {
    foreach ($this->posts as $post) {
        $edit_link = '';
        if (Session::read('role') === 'admin') {
            $edit_link = '<a href="/posts/edit/' . $post->id_post . '">Edit</a>';
        }
        $date = date('F d, Y', strtotime($post->created));
        $panel = 'panel-default';
        if ($post->no_publish == 1) {
            $panel = 'panel-danger';
        }

        $admin_links = '';
        if (Session::read('role') === 'admin' || Session::read('id_user') == $post->id_user) {
            $admin_links = <<<__TEXT__
                <br/><br/>
                <a href="/posts/edit/$post->id_post">Edit</a> |
                <a href="/posts/delete/$post->id_post">Delete</a>
__TEXT__;

        }

        $markup .= <<<___HTML___
            <article>
                <h3><a href="/posts/view/$post->slug">$post->title</a></h3>
                <h6>$date</h6>
                <p>$post->body</p>
                $admin_links
            </article>
            <hr/>
___HTML___;

    }
}
else {
    $markup = '<h1>No content</h1>';
}

echo $markup;
