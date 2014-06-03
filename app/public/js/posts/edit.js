require([
    'jquery',
    'js_config'
],
    function($, js_config) {

        var custom_properties = function()
        {
            var post = {};

            this.init = function(){
                if (js_config.default.post !== undefined) {
                    this.post = js_config.default.post;
                }
                if (!(this.post.custom_properties instanceof Object)) {
                    this.post.custom_properties = {};
                }

                this.build_custom_properties();
                this.form_edit_action();
                this.set_form_submit();
            };

            this.build_custom_properties = function(){
                if (!$.isEmptyObject(this.post)) {
                    for (var field in this.post.custom_properties) {
                        var $table_row = $('<tr><td>' + field + '</td><td>' + this.post.custom_properties[field] + '</td></tr>');
                        $('#custom-properties').append($table_row);
                    }
                }var $table_row = $('<tr><td>' + 'new' + '</td><td>' + 'new' + '</td></tr>');
                $('#custom-properties').append($table_row);
            };

            this.set_form_submit = function(){
                var me = this;
                $('form').first().submit(function(e){
                    var $rows = $('#custom-properties').find('td');
                    for (var i = 0; i < $rows.length; i += 2) {
                        var column = $rows.eq(i).html();
                        var value = $rows.eq(i+1).html();
                        me.post.custom_properties[column] = value;
                    }
                    var $input = $('<input/>').attr('hidden', 1).attr('type', 'text').attr('name', 'data[post][custom_properties]').val(JSON.stringify(me.post.custom_properties));
                    $(this).append($input);
                    return true;
                });
            };

            this.form_edit_action = function(){
                $('#custom-properties').find('td').each(function(){
                    var value = $(this).text();
                    $(this).dblclick(function(){
                        var $field = $(this);
                        var $input_field = $('<input type="text" value="' + value + '"/>');
                        $(this).html($input_field);
                        $input_field.blur(function(){
                            value = $(this).val();
                            $field.html(value);
                        });
                    });
                })
            };
        }
        var form = new custom_properties();
        form.init();

    });