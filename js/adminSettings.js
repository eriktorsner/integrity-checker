jQuery(document).ready(function($) {
    renderSettings();

    function renderSettings()
    {
        var schedule = $('#scheduledScans');
        schedule.html('');
        var scheduledScansTmpl = wp.template('scheduledScansTmpl');
        $('<div></div>').html(scheduledScansTmpl()).appendTo(schedule);

        /*var alerts = $('#alertSettings');
        alerts.html('');
        var alertsTmpl = wp.template('alertsTmpl');
        $('<div></div>').html(alertsTmpl()).appendTo(alerts);*/

        var files = $('#filesSettings');
        files.html('');
        var filesSettingsTmpl = wp.template('filesSettingsTmpl');
        $('<div></div>').html(filesSettingsTmpl()).appendTo(files);

        if (integrityCheckerApi.access == 'anonymous') {
            var options = {
                enabled_day: false,
                enabled_week: false,
                enabled_month: false,
                multiple_dom: false,
                multiple_dow: false,
                disabled: true,
                multiple_time_minutes: false,
                multiple_time_hours: false
            }
        } else if(integrityCheckerApi.access == 'registered') {
            var options = {
                enabled_day: false,
                enabled_week: false,
                enabled_month: true,
                multiple_dom: false,
                multiple_dow: false,
                disabled: false,
                multiple_time_minutes: false,
                multiple_time_hours: false
            }
        } else {
            var options = {
                enabled_day: true,
                enabled_week: true,
                enabled_month: true,
                multiple_dom: true,
                multiple_dow: true,
                disabled: false,
                multiple_time_minutes: true,
                multiple_time_hours: true
            }
        }

        // Init schedule UI
        cron = $('.jqcronSched');
        cron.jqCron({
            enabled_day: options.enabled_day,
            enabled_week: options.enabled_week,
            enabled_month: options.enabled_month,
            multiple_dom: options.multiple_dom,
            multiple_dow: options.multiple_dow,
            disabled: options.disabled,
            multiple_time_hours: options.multiple_time_hours,
            multiple_time_minutes: options.multiple_time_minutes,

            enabled_year: false,
            enabled_minute: false,
            enabled_hour: false,
            multiple_month: false,
            multiple_mins: false,
            default_period: 'week',
            default_value: '0 1 1 * *',
            no_reset_button: true,
            numeric_zero_pad: true,
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

        accessLevelAdjust();
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
                'maxFileSize': $('input[name="maxFileSize"]').val(),
                'followSymlinks': $('input[name="followSymlinks"]').is(':checked')?1:0,
                'fileMasks': $('input[name="fileMasks"]').val(),
                'folderMasks': $('input[name="folderMasks"]').val(),
                'fileOwners': $('input[name="fileOwners"]').val(),
                'fileGroups': $('input[name="fileGroups"]').val(),
                'fileIgnoreFolders': $('select[name="fileIgnoreFolders"]').val()
            },
            function(data) {
                $('input[name="maxFileSize"]').val(data.data.maxFileSize);
                $('input[name="followSymlinks"]').prop('checked', (data.data.followSymlinks == 1));
                $('input[name="fileMasks"]').val(data.data.fileMasks);
                $('input[name="folderMasks"]').val(data.data.folderMasks);
                $('input[name="fileOwners"]').val(data.data.fileOwners);
                $('input[name="fileGroups"]').val(data.data.fileGroups);
                $('input[name="fileIgnoreFolders"]').val(data.data.fileIgnoreFolders);

                $('.saveFileSettingsOk').show();
            },
            function(data) {
                $('.saveFileSettingsFail').show();
            }
        );
    });


});