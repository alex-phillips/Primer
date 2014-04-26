<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <title>
            <?php echo SITE_NAME ?> | <?php echo $this->title ?>
        </title>
        <link rel="shortcut icon" href="data:image/x-icon;," type="image/x-icon"/>

        <link rel="stylesheet" href="/css/main.css"/>

        <link rel="stylesheet" href="/css/vendor/jquery.mmenu/jquery.mmenu.all.css" />
        <link href='http://fonts.googleapis.com/css?family=Raleway:400,300,500,200,100,600,700' rel='stylesheet' type='text/css'>
        <?php
        foreach (View::getCSS() as $css_file) {
            echo '<link rel="stylesheet" href="/public/css/' . $css_file . '" />';
        }
        ?>
    </head>
    <body>