<?php

$title = '';
$body = '';

if (isset($_REQUEST['data']['post']['title'])) {
    $title = $_REQUEST['data']['post']['title'];
}
if (isset($_REQUEST['data']['post']['body'])) {
    $body = $_REQUEST['data']['post']['body'];
    Primer::setJSValue('body', $body, 'post');
}

// TODO get fields to populate if error thrown and unable to save
?>

<div class="box">
    <div class="box-body">
        <?php

        $this->Form->create('posts');
        $this->Form->add('type', array(
            'label' => 'Type',
        ));
        $this->Form->add('no_publish', array(
            'label' => 'No Publish',
        ));
        $this->Form->add('title', array(
            'label' => 'Title',
        ));
        $this->Form->add('body', array(
            'label' => 'Body',
        ));
        $this->Form->end();

        ?>
    </div>
</div>