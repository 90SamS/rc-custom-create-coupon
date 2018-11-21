ajax_url = "/wp-admin/admin-ajax.php";
(function ($) {
    $(document).ready(function () {

        $('.popap-coupon').on( "click", function() {
            $(this)
                .parent()
                .append('<div class="blockUI processing blockOverlay" style="z-index: 99999999999; border: none; margin: 0px; padding: 0px; width: 100%; height: 100%; top: 0px; left: 0px; background: rgb(255, 255, 255); opacity: 0.6; cursor: wait; position: absolute;"></div>');
            $.ajax({
                method: 'post',
                type:'post',
                url: ajax_url,
                data:'action=create_coupon'
            }).done(function(res) {
                window.location.href = res;
            })
        });
    });
})(jQuery);