<?php

$markdownParser = new Parsedown();
$this->post->body = $markdownParser->text(htmlspecialchars($this->post->body, ENT_QUOTES, 'utf-8'));
$this->post->body = preg_replace_callback('#\<code(.+?)\>(.+?)\<\/code\>#s', function ($matches) {
    return '<code' . $matches[1] . '>' . htmlspecialchars_decode($matches[2]) . '</code>';
}, $this->post->body);

$markup = <<<___HTML___
<p>{$this->post->body}</p>
___HTML___;

echo $markup;
