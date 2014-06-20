require.config({
    paths: {
        'jquery': '//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min',
        'jquery-ui': '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min',
        'bootstrap': '//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min',
        'underscore': '//cdnjs.cloudflare.com/ajax/libs/underscore.js/1.6.0/underscore-min',
        'mmenu': 'vendor/jquery.mmenu/jquery.mmenu',
        'modernizr': 'vendor/modernizr',
        'prism': 'vendor/prism'
    },
    shim: {
        'jquery': {
            exports: '$'
        },
        'jquery-ui': {
            deps: ['jquery']
        },
        'bootstrap': {
            deps: ['jquery']
        },
        'mmenu': {
            deps: ['jquery']
        }
    },
    enforeDefine: true,
    deps: ['app']
});