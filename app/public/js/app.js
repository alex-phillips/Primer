define([
    'jquery',
    'bootstrap',
    'mmenu',
    'vendor/jquery_plugins/unveil'
], function ($) {
    'use strict';

    $("#mobile-nav").mmenu();

    $('img').unveil();

    $(document).ready(function(){
        setTimeout(function(){
            $('.system-message').slideUp();
        }, 5000);
    });

    return {};
});