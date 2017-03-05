jQuery(document).ready(function($) {
    renderAboutTab();

    $('a.refreshQuota').live('click', function (e) {
        e.preventDefault();
        var refreshBtn = $(this);
        startSpin(refreshBtn);
        renderAboutTab();
    });

    /**
     *
     */
    function renderAboutTab() {
        var quotaInfo = $('#checksum-quota');
        getRest('/integrity-checker/v1/quota' + '?esc=1', function (data) {
            quotaInfo.html('');
            var quotaTmpl = wp.template('quotaTmpl');
            $('<div></div>').html(quotaTmpl(data.data)).appendTo(quotaInfo);

            var refreshBtn = $('.refreshQuota');
            if (refreshBtn.data('orgtxt')) {
                refreshBtn.html(refreshBtn.data('orgtxt'));
                refreshBtn.prop("disabled", false);
            }
        });
    }

});