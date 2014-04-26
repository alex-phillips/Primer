require([
    'jquery'
],
    function($){

        var movie_handler = function(){
            var $moviesContainer = $('#movies-contents');

            $('#movies-search').keypress(function(e){
                if (e.keyCode == 13) {
                    submit_search();
                }
            });
            $('#movies-search-button').click(function(){
                submit_search();
            });

            var submit_search = function(){
                $moviesContainer.html('');
                var val = $('#movies-search').val();
                $.ajax({
                    url: '/pages/movies/',
                    data: {
                        'ajax': true,
                        'query': val
                    },
                    success: function(data) {
                        $moviesContainer.html(data);
                        $('#movies-contents img').unveil();
                        $('.movie-info').click(function(e){
                            e.preventDefault();
                            get_details($(this).data('movie-id'));
                        });
                    }
                });
            };

            var get_details = function(id){
                $.ajax({
                    url: '/pages/movies/',
                    data: {
                        'ajax': true,
                        'id_movie': id
                    },
                    success: function(data) {
                        data = JSON.parse(data);
                        $('#movie-modal #movie-modal-title').html(data.all['@attributes'].title);
                        $('#movie-modal #movie-modal-body').html(data.all['@attributes'].summary);
                        $('#movie-modal').modal();
                    }
                });
            }
        }
        movie_handler();

    }
);