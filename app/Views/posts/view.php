<?php

$markdownParser = new Parsedown();
$this->post->body = $markdownParser->text(htmlspecialchars($this->post->body, ENT_QUOTES, 'utf-8'));

$markup = <<<___HTML___
<p>{$this->post->body}</p>
___HTML___;

echo $markup;
