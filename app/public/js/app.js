define([
    'jquery',
    'underscore',
    'bootstrap',
    'mmenu',
    'vendor/jquery_plugins/unveil',
    'prism'
], function ($, _) {
    'use strict';

    window.Prism.highlightAll();

    $("#mobile-nav").mmenu();
    $("#mobile-nav-header").find(".left-menu").click(function(){
        $('#mobile-nav').trigger("open");
    });

    // If window goes beyond size that mobile nav is available, close automatically
    $(window).resize(_.debounce(function(e){
        if ($(window).width() > 767) {
            $('#mobile-nav').trigger("close");
        }
    }, 500));

    $('img').unveil();

    $(document).ready(function(){
        setTimeout(function(){
            $('.system-message').slideUp();
        }, 5000);
    });

    return {};
});