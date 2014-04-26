<?php

echo <<<__TEXT__
<div id="movies" ng-controller="moviesController" ng-cloak hidden>
    Search: <input type="text'ng-model="query">

    <article ng-repeat="movie in movies | filter:query">
        <h3>{{movie.all['@attributes'].title}}</h3>
        <h6>{{movie.all['@attributes'].year}}</h6>
        <div class="row">
            <div class="large-2 columns">
                <img ng-src="{{movie.poster}}" alt="{{movie.all['@attributes'].title}}" style="width: 100%;">
            </div>
            <div class="large-10 columns">
                <p>{{movie.all['@attributes'].summary}}</p>
            </div>
        </div>
    </article>

</div>
__TEXT__;
