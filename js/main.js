jQuery(document).ready(function($) {

    tabSwitch();
    renderChecksumScanResults();
    renderPermissionScanResults();
    renderSettingsScanResults();
    renderAboutTab();

    $('a.itemIssuesToggle').live('click', function(e) {
        var slug = $(this).data('slug');
        $('#scan-plugins-' + slug).toggle();
    });

    $('a.refreshQuota').live('click', function (e) {
        e.preventDefault();
        var refreshBtn = $(this);
        refreshBtn.data('orgtxt', refreshBtn.html());
        refreshBtn.css('width', refreshBtn.outerWidth());
        refreshBtn.html('<i class="fa fa-spinner fa-spin"></i>');
        refreshBtn.prop("disabled", true);
        renderAboutTab();
    });

    $('a.startScan').live('click', function (e) {
        e.preventDefault();

        // Set spinner, poller, disable btn and send async
        var startScanBtn = $(this);
        startScanBtn.data('orgtxt', startScanBtn.html());
        startScanBtn.css('width', startScanBtn.outerWidth());
        startScanBtn.html('<i class="fa fa-spinner fa-spin"></i>');
        startScanBtn.prop("disabled", true);
        var scanType = startScanBtn.data('scantype');

        var state = {state:'started'};
        putRest('/integrity-checker/v1/process/status/' + scanType, state, function(data) {
            renderResults(data, scanType, startScanBtn);
        });
    });

    $('a.btnDiff').live('click', function (e) {
        e.preventDefault();

        var btn = $(this);
        var type = btn.data('type');
        var slug = btn.data('slug');
        var file = btn.data('file');

        var url = '/integrity-checker/v1/diff/'+type+'/'+slug;
        getRest(url, function(data) {
            showDiff(data);
        }, function(data) {
            showDiffError(data);
        }, {'X-Filename': file});
    });

    $('.opt-tab').on('click', function(e) {
        e.preventDefault();
        tabSwitch($(this));
    });

    $('a.updateApiKey').live('click', function (e) {
        e.preventDefault();
        var updateBtn = $(this);
        updateBtn.data('orgtxt', updateBtn.html());
        updateBtn.css('width', updateBtn.outerWidth());
        updateBtn.html('<i class="fa fa-spinner fa-spin"></i>');
        updateBtn.prop("disabled", true);

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
        btn.data('orgtxt', btn.html());
        btn.css('width', btn.outerWidth());
        btn.html('<i class="fa fa-spinner fa-spin"></i>');
        btn.prop("disabled", true);

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

    /**
     *
     * @param data
     * @param process
     * @param startScanBtn
     */
    function renderResults(data, process, startScanBtn)
    {
        i = 0;
        if (data.data.state != 'finished') {
            setTimeout(function() {
                    getRest('/integrity-checker/v1/process/status/' + process + '?esc=1', function(data) {
                    renderResults(data, process, startScanBtn);
                })},
                5000
            );
        } else {
            var scanType = startScanBtn.data('scantype');
            startScanBtn.html(startScanBtn.data('orgtxt'));
            switch (scanType) {
                case 'checksum':
                    renderChecksumScanResults();
                    break;
                case 'permissions':
                    renderPermissionScanResults();
                    break;
                case 'settings':
                    renderSettingsScanResults();
                    break;
            }
        }
    }

    /**
     *
     * @param process
     */
    function checkProcessStatus(process) {
        getRest('/integrity-checker/v1/process/status/' + process + '?esc=1', function (data) {
           return data.data.state;
        });
    }

    /**
     *
     */
    function renderSettingsScanResults()
    {
        var scanSummary = $('#settings-scan-summary');
        var scanResults = $('#settings-scan-results');

        getRest('/integrity-checker/v1/process/status/settings' + '?esc=1', function (data) {
            scanSummary.html('');
            var summaryTmpl = wp.template('settingsSummaryTmpl');
            $('<div></div>').html(summaryTmpl(data.data)).appendTo(scanSummary);
        });

        getRest('/integrity-checker/v1/testresult/settings' + '?esc=1', function (data) {
            scanResults.html('');
            var issuesTmpl = wp.template('settingsIssuesTmpl');

            $('<h3>Important results</h3>').appendTo(scanResults);
            $.each(data.data.checks, function (name, object) {
                if (object.type != 'obscurity') {
                    $('<div></div>').html(issuesTmpl(object)).appendTo(scanResults);
                }
            });

            $('<h3>Potential improvements</h3>').appendTo(scanResults);
            $.each(data.data.checks, function (name, object) {
                if (object.type == 'obscurity') {
                    $('<div></div>').html(issuesTmpl(object)).appendTo(scanResults);
                }
            });
        });
    }

    /**
     *
     */
    function renderPermissionScanResults()
    {
        var scanSummary = $('#permissions-scan-summary');
        var scanResults = $('#permissions-scan-results');

        getRest('/integrity-checker/v1/process/status/permissions' + '?esc=1', function (data) {
            scanSummary.html('');
            var summaryTmpl = wp.template('permissionsSummaryTmpl');
            $('<div></div>').html(summaryTmpl(data.data)).appendTo(scanSummary);
        });

        getRest('/integrity-checker/v1/testresult/permissions' + '?esc=1', function (data) {
            scanResults.html('');
            if (data.data) {
                var issuesTmpl = wp.template('permissionsIssuesTmpl');
                $('<h3>Issues</h3>').appendTo(scanResults);
                $('<div></div>').html(issuesTmpl(data.data)).appendTo(scanResults);
            }
        });
    }

    /**
     *
     */
    function renderChecksumScanResults() {
        var scanSummary = $('#checksum-scan-summary');
        var scanResults = $('#checksum-scan-results');

        getRest('/integrity-checker/v1/process/status/checksum' + '?esc=1', function (data) {
            scanSummary.html('');
            var summaryTmpl = wp.template('checksumSummaryTmpl');
            $('<div></div>').html(summaryTmpl(data.data)).appendTo(scanSummary);
        });

        getRest('/integrity-checker/v1/testresult/checksum' + '?esc=1', function (data) {
            scanResults.html('');

            if (!data.data) {
                return;
            }

            var issuesTmpl = wp.template('checksumIssuesTmpl');

            $('<h3>WordPress</h3>').appendTo(scanResults);
            $.each(data.data.core, function (name, object) {
                object.type = 'core';
                $('<div></div>').html(issuesTmpl(object)).appendTo(scanResults);
            });

            $('<h3>Plugins</h3>').appendTo(scanResults);
            $.each(data.data.plugins, function (name, object) {
                if (object.status == 'checked') {
                    object.type = 'plugin';
                    $('<div></div>').html(issuesTmpl(object)).appendTo(scanResults);
                }
            });
            $.each(data.data.plugins, function (name, object) {
                if (object.status != 'checked') {
                    object.type = 'plugin';
                    $('<div></div>').html(issuesTmpl(object)).appendTo(scanResults);
                }
            });

            $('<h3>Themes</h3>').appendTo(scanResults);
            $.each(data.data.themes, function (name, object) {
                if (object.status == 'checked') {
                    object.type = 'theme';
                    $('<div></div>').html(issuesTmpl(object)).appendTo(scanResults);
                }
            });
            $.each(data.data.themes, function (name, object) {
                if (object.status != 'checked') {
                    object.type = 'theme';
                    $('<div></div>').html(issuesTmpl(object)).appendTo(scanResults);
                }
            });
        });
    }

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

    /**
     * Wrapper for REST calls
     *
     * @param endpoint  url
     * @param f         onSuccess function
     * @param fError    onError function
     * @param headers   Additional headers
     */
    function getRest(endpoint, f, fError, headers) {
        rest('GET', endpoint, null, f, fError, headers);
    }

    /**
     * Wrapper for REST calls
     *
     * @param endpoint  url
     * @param postData  Object
     * @param f         onSuccess function
     * @param fError    onError function
     * @param headers   Additional headers
     */
    function putRest(endpoint, postData, f, fError, headers) {
        rest('PUT', endpoint, JSON.stringify(postData), f, fError, headers);
    }

    /**
     * Wrapper for REST calls
     *
     * @param method    GET|POST|PUT
     * @param endpoint  url
     * @param postData  Object
     * @param f         onSuccess function
     * @param fError    onError function
     * @param headers   Additional headers
     */
    function rest(method, endpoint, postData, f, fError, headers) {
        var base = integrityCheckerApi.url;
        var intendedMethod = method;
        if (method == 'PUT' || method == 'PATCH' || method == 'DELETE') {
            method = 'POST';
        }

        var args = {
            type: method,
            headers: {
                'X-HTTP-Method-Override': intendedMethod,
                'X-WP-NONCE': integrityCheckerApi.nonce
            },
            url: base + endpoint
        };

        if (headers) {
            var names = Object.keys(headers);
            for(var i=0;i<names.length;i++) {
                var name = names[i];
                args.headers[name] = headers[name];
            }
        }

        if (postData && postData.length > 0) {
            args.data = postData;
            args.contentType = 'application/json';
        }

        $.ajax(args).then(function(data) {
            f(data);
        }, function (data) {
            if (fError) fError(data);
        });
    }

    /**
     * Show diff in a popup
     *
     * @param diff The HTML to dislpay
     */
    function showDiff(diff) {
        if (diff.length == 0) {
            var data = { 'responseJSON': {
                'code': 200,
                'message': 'Diff is just white space'
            }};
            showDiffError(data);
            return;
        }
        var content = $(diff);
        var height = $(window).height();
        content.dialog({
            dialogClass   :'wp-dialog',
            modal         : true,
            closeOnEscape : true,
            width         : $(window).width()*0.93,
            height        : height*0.9,
            maxHeight     : height*0.9,
            position      : { my: "top", at: "top", of: $(window) },
            buttons       : {
                "Close": function () {
                    $(this).dialog('close');
                }
            },
            'close'        : function() { content.remove(); }
        });
        var contentTop = $(content).offset().top;
        $('html, body').animate({scrollTop: contentTop - 50}, 100);
    }

    /**
     * Show error in a popup
     *
     * @param data Object containing error info
     */
    function showDiffError(data) {
        var diffErrorTmpl = wp.template('diffErrorTmpl');
        var response = data.responseJSON;
        var content = $(diffErrorTmpl(response));
        content.dialog({
            dialogClass   :'wp-dialog',
            modal         : true,
            closeOnEscape : true,
            buttons       : {
                "Close": function () {
                    $(this).dialog('close');
                }
            }
        });
    }

    /**
     * Make a button spin
     *
     * @param btn
     */
    function startSpin(btn) {
        btn.data('orgtxt', btn.html());
        btn.css('width', btn.outerWidth());
        btn.html('<i class="fa fa-spinner fa-spin"></i>');
        btn.prop("disabled", true);
    }

    /**
     * GETQ
     * Gets the query string.
     *
     * @param name
     * @returns {string}
     *
     * @since 1.0.0
     */
    function getQ( name ) {
        var regex, results;
        name = name.replace(/[\[]/,"\\[").replace(/[\]]/,"\\]");
        regex = new RegExp( "[\\?&]" + name + "=([^&#]*)" );
        results = regex.exec( location.search );
        return results === null ?
            "" : decodeURIComponent( results[1].replace( /\+/g," ") );
    }

    /**
     * TAB SWITCH
     * Changes the tab content when tab is clicked.
     *
     * @param $tab
     *
     * @since 1.0.0
     */
    function tabSwitch( $tab ) {

        var id, content, tab;

        // if $tab was not set, get the tab from the query string
        if (typeof($tab) === 'undefined') {
            tab = getQ('tab').length ? getQ('tab') : null;
            if (!tab) return;
            $tab = $('#' + tab);
        }

        // get id and content location from the chosen tab
        id = $tab.attr('id');
        content = $tab.data('optcontent');

        // hide all tab content areas, show current one
        $('.opt-content').hide();
        $(content).show();

        // set the active tab head
        $('.opt-tab').removeClass('nav-tab-active');
        $tab.addClass('nav-tab-active').blur();

        if (history.pushState) {
            var stateObject = {dummy: true};;
            var url = window.location.protocol
                + "//"
                + window.location.host
                + window.location.pathname
                + '?page=integrity-checker_options&tab=' + id;

            history.pushState(stateObject, $(document).find('title').text(), url);
        }
    }
});

/*
 * Utils - global name space
 */

/**
 * Convert bytes to a human readable form
 *
 * @param size
 * @returns string
 */
function humanFileSize(size) {
    var i = Math.floor( Math.log(size) / Math.log(1024) );
    if (size == 0) return '-';
    return ( size / Math.pow(1024, i) ).toFixed(2) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
}
