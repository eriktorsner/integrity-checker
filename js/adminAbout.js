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

    $('a.updateApiKey').live('click', function (e) {
        e.preventDefault();
        var updateBtn = $(this);
        startSpin(updateBtn);

        var newKey = $('#apikey').val();
        var postData = {'apiKey': newKey};

        var msgElem = $('#updateKeyMessage');

        putRest('/integrity-checker/v1/apikey' + '?esc=1', postData,
            function (data) {
                updateBtn.html(updateBtn.data('orgtxt'));
                updateBtn.prop("disabled", false);
                msgElem.html(data.data.message);
                msgElem.removeClass('red');
                msgElem.addClass('green');
                renderAboutTab();
            }, function (data) {
                updateBtn.html(updateBtn.data('orgtxt'));
                updateBtn.prop("disabled", false);
                msgElem.html(data.responseJSON.message);
                msgElem.removeClass('green');
                msgElem.addClass('red');

                var apiKeyElem = $('#apikey');
                apiKeyElem.val(apiKeyElem.data('original'));
            }
        );
    });

    $('a.submitEmailBtn').live('click', function (e) {
        e.preventDefault();
        var btn = $(this);
        startSpin(btn);
        var email = $('#submitEmail').val();
        var postData = {'email': email};

        var msgElem = $('#submitEmailMessage');

        putRest('/integrity-checker/v1/userdata' + '?esc=1', postData,
            function (data) {
                btn.html(btn.data('orgtxt'));
                btn.prop("disabled", false);
                if (data.data.status == 200) {
                    renderAboutTab();
                } else {
                    msgElem.html(data.data.message);
                    msgElem.removeClass('green');
                    msgElem.addClass('red');
                }
            }, function (data) {
                btn.html(btn.data('orgtxt'));
                btn.prop("disabled", false);
                msgElem.html(data.responseJSON.message);
                msgElem.removeClass('green');
                msgElem.addClass('red');
            }
        );
    });

    $('a.termsLink').live('click', function (e) {
        e.preventDefault();
        var html = $('#termsText').html();
        var content = $('<div>' + html + '</div>');
        var height = $(window).height();
        content.dialog({
            dialogClass   :'wp-dialog',
            modal         : true,
            width         : $(window).width()*0.93,
            height        : height*0.9,
            maxHeight     : height*0.9,
            position      : { my: "top", at: "top", of: $(window) },
            closeOnEscape : true,
            buttons       : {
                "Close": function () {
                    $(this).dialog('close');
                }
            }
        });
        var contentTop = $(content).offset().top;
        $('html, body').animate({scrollTop: contentTop - 50}, 100);
    });

});