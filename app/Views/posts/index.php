<?php

$markup = '';

if ($this->posts) {
    foreach ($this->posts as $post) {
        $edit_link = '';
        if ($this->Session->read('role') === 'admin') {
            $edit_link = '<a href="/posts/edit/' . $post->id . '">Edit</a>';
        }
        $date = date('F d, Y', strtotime($post->created));
        $panel = 'panel-default';
        if ($post->no_publish == 1) {
            $panel = 'panel-danger';
        }

        $admin_links = '';
        if ($this->Session->read('role') === 'admin' || $this->Session->read('id') == $post->id_user) {
            $admin_links = <<<__TEXT__
                <br/><br/>
                <a href="/posts/edit/$post->id">Edit</a> |
                <a href="/posts/delete/$post->id">Delete</a>
__TEXT__;

        }

        $markdownParser = new Parsedown();
        $body = $markdownParser->text(htmlspecialchars($post->body, ENT_QUOTES, 'utf-8'));

        $markup .= <<<___HTML___
            <article>
                <h3><a href="/posts/view/$post->id">$post->title</a></h3>
                <h6>$date</h6>
                <p>$body</p>
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
