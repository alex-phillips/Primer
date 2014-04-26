define(['./module', 'jquery'], function (controllers, $) {
    'use strict';

    controllers.controller('moviesController', ['$scope', '$http',
        function ($scope, $http) {
            $('div').filter('[ng-controller=moviesController]').removeAttr('hidden');
            $http.get('/public/movies.json').success(function(data) {
                $scope.movies = data;
            });
        }]);

});