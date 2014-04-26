<!-- build:js scripts/main.js -->
<script src="/js/require.js"></script>
<script>
    require.config({
        baseUrl: "/js/"
    });
    define('js_config', function(){
        <?php
        echo "return " . json_encode(Primer::getJSValues());
        ?>
    });
    require (['main'], function () {
        <?php
        // load up other js modules required for this page
        $markup = '';
        foreach (View::getJS() as $file){
            $markup .= "    require(['$file']);\n";
        }
        echo $markup;
        ?>
    });
</script>
<!-- endbuild -->

</body>
</html>