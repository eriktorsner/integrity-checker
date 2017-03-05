jQuery(document).ready(function($) {
    renderSettings();

    function renderSettings()
    {
        var schedule = $('#scheduledScans');
        schedule.html('');
        var scheduledScansTmpl = wp.template('scheduledScansTmpl');
        $('<div></div>').html(scheduledScansTmpl()).appendTo(schedule);

        var alerts = $('#alertSettings');
        alerts.html('');
        var alertsTmpl = wp.template('alertsTmpl');
        $('<div></div>').html(alertsTmpl()).appendTo(alerts);

        var files = $('#filesSettings');
        files.html('');
        var filesSettingsTmpl = wp.template('filesSettingsTmpl');
        $('<div></div>').html(filesSettingsTmpl()).appendTo(files);

        // Init schedule UI
        $('.jqcronSched').jqCron({
            enabled_minute: false,
            enabled_hour: false,
            enabled_year: false,
            multiple_dom: true,
            multiple_month: true,
            multiple_mins: true,
            multiple_dow: true,
            multiple_time_hours: true,
            multiple_time_minutes: false,
            default_period: 'week',
            default_value: '10 23 * * 1-7',
            no_reset_button: true,
            bind_to: $('input[name="cronValue"]'),
            bind_method: {
                set: function($element, value) {
                    $element.val(value);
                },
                get: function($element) {
                    return $element.val();
                }
            },
            lang: 'en'
        });
    }

    $('a.saveSchedSettings').live('click', function(e) {
        $('.saveSchedSettingsOk').hide();
        $('.saveSchedSettingsFail').hide();
        putRest(
            '/integrity-checker/v1/settings',
            {
                'enableScheduleScans': $('input[name="enableScheduleScans"]').is(':checked')?1:0,
                'cron': $('input[name="cronValue"]').val(),
                'scheduleScanChecksums': $('input[name="scheduleScanChecksums"]').is(':checked')?1:0,
                'scheduleScanPermissions': $('input[name="scheduleScanPermissions"]').is(':checked')?1:0,
                'scheduleScanSettings': $('input[name="scheduleScanSettings"]').is(':checked')?1:0
            },
            function(data) {
                $('.saveSchedSettingsOk').show();
            },
            function(data) {
                $('.saveSchedSettingsFail').show();
            }
        );
    });

    $('a.saveAlertSettings').live('click', function(e) {
        $('.saveAlertSettingsOk').hide();
        $('.saveAlertSettingsFail').hide();
        putRest(
            '/integrity-checker/v1/settings',
            {
                'enableAlerts': $('input[name="enableAlerts"]').is(':checked')?1:0,
                'alertEmails': $('input[name="alertEmails"]').val()
            },
            function(data) {
                $('.saveAlertSettingsOk').show();
            },
            function(data) {
                $('.saveAlertSettingsFail').show();
            }
        );
    });

    $('a.testAlertEmails').live('click', function(e) {
        $('.testAlertEmailsOk').hide();
        var emails = $('input[name="alertEmails"]').val();
        getRest(
            '/integrity-checker/v1/testemail/' + emails,
            function(data) {
                $('.testAlertEmailsOk').show();
            },
            function(data) {
            }
        );
    });

    $('a.saveFileSettings').live('click', function(e) {
        $('.saveFileSettingsOk').hide();
        $('.saveFileSettingsFail').hide();
        putRest(
            '/integrity-checker/v1/settings',
            {
                'fileMasks': $('input[name="fileMasks"]').val(),
                'folderMasks': $('input[name="folderMasks"]').val(),
                'maxFileSize': $('input[name="maxFileSize"]').val()
            },
            function(data) {
                $('input[name="fileMasks"]').val(data.data.fileMasks);
                $('input[name="folderMasks"]').val(data.data.folderMasks);
                $('input[name="maxFileSize"]').val(data.data.maxFileSize);
                $('.saveFileSettingsOk').show();
            },
            function(data) {
                $('.saveFileSettingsFail').show();
            }
        );
    });


});