require.config({
    paths: {
        'jquery': '//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min',
        'bootstrap': '//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min',
        'underscore': '//cdnjs.cloudflare.com/ajax/libs/underscore.js/1.6.0/underscore-min',
        'mmenu': 'vendor/jquery.mmenu/jquery.mmenu',
        'modernizr': 'vendor/modernizr',
        'angular': '//ajax.googleapis.com/ajax/libs/angularjs/1.2.12/angular.min',
        'ckeditor': '../libs/ckeditor/ckeditor',
        'ckeditor-jquery': '../libs/ckeditor/adapters/jquery',
        'prism': 'vendor/prism'
    },
    shim: {
        'jquery': {
            exports: '$'
        },
        'bootstrap': {
            deps: ['jquery']
        },
        'mmenu': {
            deps: ['jquery']
        },
        'angular': {
            exports: 'angular'
        },
        'ckeditor-jquery': {
            deps: ['ckeditor']
        }
    },
    deps: ['app']
});