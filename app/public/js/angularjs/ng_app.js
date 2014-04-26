define([
    'angular',
    'angularjs/controllers/index'
//    './angular/directives/index',
//    './angular/filters/index',
//    './angular/services/index'
], function (ng) {
    'use strict';

    return ng.module('app', [
//        'app.services',
        'app.controllers'
//        'app.filters',
//        'app.directives'
    ]);
});