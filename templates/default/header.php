<!-- replace main image when hover over thumbnail -->
<script type="text/javascript">
    function wplOnThumbnailHover(source) {
        if (source.indexOf('{{var')>-1) return false;
        var big_image;
        big_image = document.getElementById("wpl_main_image").getElementsByTagName("img")[0].src = source;
    }
</script>

