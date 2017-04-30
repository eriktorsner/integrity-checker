jQuery(document).ready(function($) {
    /**
     * Wrapper for REST calls
     *
     * @param endpoint  url
     * @param f         onSuccess function
     * @param fError    onError function
     * @param headers   Additional headers
     */
    getRest = function (endpoint, f, fError, headers) {
        rest('GET', endpoint, null, f, fError, headers);
    };

    /**
     * Wrapper for REST calls
     *
     * @param endpoint  url
     * @param postData  Object
     * @param f         onSuccess function
     * @param fError    onError function
     * @param headers   Additional headers
     */
    putRest = function (endpoint, postData, f, fError, headers) {
        rest('PUT', endpoint, JSON.stringify(postData), f, fError, headers);
    };

    /**
     * Wrapper for REST calls
     *
     * @param endpoint  url
     * @param postData  Object
     * @param f         onSuccess function
     * @param fError    onError function
     * @param headers   Additional headers
     */
    postRest = function (endpoint, postData, f, fError, headers) {
        rest('POST', endpoint, JSON.stringify(postData), f, fError, headers);
    };

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
    rest = function (method, endpoint, postData, f, fError, headers) {
        var base = integrityCheckerApi.url;

        if (base.includes('rest_route=')) {
            endpoint = endpoint.replace('?', '&');
        }

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

        $.ajax(args)
            .done(function(data, textStatus, request) {
                f(data, textStatus, request);
            })
            .fail(function(data) {
                if (fError) {
                    data = data.responseJSON;
                    fError(data);
                }
            })
            .always(function(data) {
                i = 0;
            });
    };

    accessLevelAdjust = function() {

        $('.access-anonymous').hide();
        $('.access-registered').hide();
        $('.access-paid').hide();

        if (integrityCheckerApi.access == 'anonymous') {
            $('.access-anonymous').show();
            $('.jqcronSched').hide();
            $("#scheduledScans :input").attr("disabled", true);

            //$('#alertSettingsWrapper').hide();
            //$("a.saveSchedSettings").hide();
        }

        if (integrityCheckerApi.access == 'registered') {
            $('.jqcronSched').show();
            $('.access-registered').show();
        }

        if (integrityCheckerApi.access == 'paid') {
            $('.jqcronSched').show();
            $('.access-paid').show();
        }
    };

    accessLevelAdjust();
    tabSwitch();

    getProcessStatus(null, function() {
        renderChecksumScanResults();
        renderFilesScanResults();
        renderSettingsScanResults();
    });


    $('a.itemIssuesToggle').live('click', function(e) {
        var slug = $(this).data('slug');
        $('#scan-plugins-' + slug).toggle();
    });

    $('a.startScan').live('click', function (e) {
        e.preventDefault();
        var startScanBtn = $(this);
        startSpin(startScanBtn);
        var scanType = startScanBtn.data('scantype');

        switch (scanType) {
            case 'checksum':
                showDivLoading($('#checksum-scan-results'));
                break;
            case 'files':
                showDivLoading($('#files-ownerandpermission-scan-results'));
                showDivLoading($('#files-monitor-scan-results'));
                break;
            case 'settings':
                showDivLoading($('#settings-scan-results'));
                break;
        }

        var state = {state:'started', source:'manual'};
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
        getRest(
            url,
            function(data, textStatus, request) {
                showDiff(data, textStatus, request);
            },
            false,
            {'X-Filename': file}
        );
    });

    $('.opt-tab').on('click', function(e) {
        e.preventDefault();
        tabSwitch($(this));
    });

    $('a.deleteScanHistory').live('click', function (e) {
        e.preventDefault();
        var range = $('select[name="deleteScanHistoryRange"]').val();

        if (range == '0') {
            return;
        }

        var data = {deleteScanHistoryRange: range};

        putRest('/integrity-checker/v1/testresult/scanall/truncatehistory', data, function(data) {
            renderFilesScanResults();
        });

    });



    /**
     *
     * @param data
     * @param process
     * @param startScanBtn
     */
    function renderResults(data, process, startScanBtn)
    {
        var scanType = startScanBtn.data('scantype');

        if (data.data.state != 'finished') {
            if (data.data.jobCount) {
                var scanStatus = $('div.scanStatus.' + scanType);
                var scanStatusCount = $('div.scanStatus.' + scanType + ' > span')
                scanStatus.show();
                scanStatusCount.html(data.data.jobCount);
            }

            setTimeout(function() {
                    getRest('/integrity-checker/v1/process/status/' + process + '?esc=1', function(data) {
                    renderResults(data, process, startScanBtn);
                })},
                5000
            );
        } else {
            startScanBtn.html(startScanBtn.data('orgtxt'));
            switch (scanType) {
                case 'checksum':
                    showDivLoading($('#checksum-scan-summary'));
                    renderChecksumScanResults();
                    break;
                case 'files':
                    showDivLoading($('#files-scan-summary'));
                    renderFilesScanResults();
                    break;
                case 'settings':
                    showDivLoading($('#settings-scan-summary'));
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

        //getRest('/integrity-checker/v1/process/status/settings' + '?esc=1', function (data) {
        getProcessStatus('settings', function(data) {
            scanSummary.html('');
            var summaryTmpl = wp.template('settingsSummaryTmpl');
            $('<div></div>').html(summaryTmpl(data)).appendTo(scanSummary);
            hideDivLoading(scanSummary);
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
            hideDivLoading(scanResults);
        });
    }

    /**
     *
     */
    function renderFilesScanResults()
    {
        var scanSummary = $('#files-scan-summary');
        var scanPermResults = $('#files-ownerandpermission-scan-results');
        var scanMonitorResults = $('#files-monitor-scan-results');

        //getRest('/integrity-checker/v1/process/status/files' + '?esc=1', function (data) {
        getProcessStatus('files', function(data) {
            scanSummary.html('');
            var summaryTmpl = wp.template('filesSummaryTmpl');
            $('<div></div>').html(summaryTmpl(data)).appendTo(scanSummary);
            hideDivLoading(scanSummary);
        });

        getRest('/integrity-checker/v1/testresult/files' + '?esc=1', function (data) {
            scanPermResults.html('');
            scanMonitorResults.html('');

            if (data.data.permissions) {

                var issuesTmpl = wp.template('filesMaskOwnerIssuesTmpl');
                $('<div></div>').html(issuesTmpl(data.data.permissions)).appendTo(scanPermResults);

                var fileScanPermissionsData = data.data.permissions.files;
                $('#table-plugins-permissions-issues').DataTable({
                    data: fileScanPermissionsData,
                    columns: [
                        {title: 'File', data: 'file'},
                        {title: 'Type', data: 'type'},
                        {title: 'Mode', data: 'mode'},
                        {title: 'Owner', data: 'owner'},
                        {title: 'Group', data: 'group'},
                        {title: 'Date', data: 'date'},
                        {title: 'Size', data: 'size'},
                        {title: 'Issue', data: 'issue'}
                    ]
                });

                var issuesTmpl = wp.template('filesMonitorIssuesTmpl');
                $('<div></div>').html(issuesTmpl(data.data.modifiedfiles)).appendTo(scanMonitorResults);

                var fileScanFileMonitorData = data.data.modifiedfiles.files;
                $('#table-plugins-filemonitor-issues').DataTable({
                    data: fileScanFileMonitorData,
                    columns: [
                        {title: 'File', data: 'file'},
                        {title: 'Type', data: 'type'},
                        {title: 'Mode', data: 'mode'},
                        {title: 'Owner', data: 'owner'},
                        {title: 'Group', data: 'group'},
                        {title: 'Date', data: 'date'},
                        {title: 'Size', data: 'size'},
                        {title: 'Change', data: 'issue'}
                    ]
                });

                hideDivLoading(scanPermResults);
                hideDivLoading(scanMonitorResults);

            }
        });
    }

    /**
     *
     */
    function renderChecksumScanResults() {
        var scanSummary = $('#checksum-scan-summary');
        var scanResults = $('#checksum-scan-results');

        getProcessStatus('checksum', function(data) {
            scanSummary.html('');
            var summaryTmpl = wp.template('checksumSummaryTmpl');
            $('<div></div>').html(summaryTmpl(data)).appendTo(scanSummary);
            hideDivLoading(scanSummary);
        });

        getRest('/integrity-checker/v1/testresult/checksum' + '?esc=1', function (data) {
            scanResults.html('');
            hideDivLoading(scanResults);

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
     * @param process
     * @param f
     */
    function getProcessStatus(process, f) {
        now = Math.round(+new Date()/1000);
        if (processStatus && (processStatus.ts > (now + 15))) {
            if (f) f(processStatus.data[process]);
            return;
        }

        getRest('/integrity-checker/v1/process/status?esc=1', function (data) {
            data.ts = now;
            processStatus = data;
            if (f) f(processStatus.data[process]);
        });
    }


    /**
     * Show diff in a popup
     *
     * @param diff The HTML to dislpay
     */
    function showDiff(diff, textStatus, request) {
        if ((typeof diff === 'object') && (diff !== null)) {
            showDiffError(diff);
            return;
        }
        if (diff.length == 0) {
            var data = {
                'code': 200,
                'message': 'Diff is just white space'
            };
            showDiffError(data);
            return;
        }
        var content = $(diff);
        var height = $(window).height();
        var title = 'Diff';

        if (typeof request === 'object' && request !== null) {
            remainingDiffs = request.getResponseHeader('x-integrity-checker-diff-remain');
            if (remainingDiffs && remainingDiffs != '-1') {
                title = title + ' - ' + remainingDiffs + ' diff request(s) remaining';
                //title = title + ' <i class="fa fa-info-circle blue diff-quota-info"></i>'
            }
        }

        content.dialog({
            dialogClass   : 'wp-dialog',
            modal         : true,
            closeOnEscape : true,
            width         : $(window).width()*0.93,
            height        : height*0.9,
            maxHeight     : height*0.9,
            position      : { my: "top", at: "top", of: $(window) },
            dialogClass   : 'integrity-checker-filediff',
            buttons       : {
                "Close": function () {
                    $(this).dialog('close');
                }
            },
            'close'        : function() { content.remove(); }
        });

        $('.integrity-checker-filediff div span').html(title);
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
        var content = $(diffErrorTmpl(data));
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
var rest, getRest, putRest, postRest;
var accessLevelAdjust;
var processStatus;


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

function showDivLoading(div) {
    div.addClass('div-loading');
}

function hideDivLoading(div) {
    div.removeClass('div-loading');
}

