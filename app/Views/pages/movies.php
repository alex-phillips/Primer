<?php

echo <<<__TEXT__
<div class="input-group">
    <input type="text" id="movies-search" class="form-control"/>
    <span class="input-group-btn">
        <button id="movies-search-button" class="btn-default">Search</button>
    </span>
</div>

<div id="movies-contents"></div>

<!-- Modal -->
<div class="modal fade" id="movie-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="movie-modal-title"></h4>
            </div>
            <div class="modal-body" id="movie-modal-body">
            </div>
        </div>
    </div>
</div>

__TEXT__;
