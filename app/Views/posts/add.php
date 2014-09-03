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

        Form::create('posts');
        Form::add('type', array(
            'label' => 'Type',
        ));
        Form::add('no_publish', array(
            'label' => 'No Publish',
        ));
        Form::add('title', array(
            'label' => 'Title',
        ));
        Form::add('body', array(
            'label' => 'Body',
            'additional_attrs' => array(
                'rows' => 10,
            ),
        ));
        Form::end();

        ?>
    </div>
</div>