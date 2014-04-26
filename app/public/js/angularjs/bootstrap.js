define([
    'require',
    'angular',
    'jquery',
    './ng_app'
//    './routes'
], function (require, ng, $) {
    'use strict';

    $(document).ready(function(){
        ng.bootstrap(document, ['app']);
    });

});