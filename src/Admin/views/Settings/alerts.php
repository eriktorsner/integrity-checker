<h3><?php _e('Alerts','integrity-checker')?></h3>
<div id="alertSettings" class="alert-settings"></div>

<script type="text/html" id="tmpl-alertsTmpl">
    <table class="form-table">
        <tr>
            <td colspan="2">
                <?php _e(
                    'After a scheduled scan is finished, an alert can be sent out to notify you ' .
                    'about any issues found.',
                    'integrity-checker'
                )?>
            </td>
        </tr>

        <tr>
            <th><?php _e('Enable alerts', 'integrity-checker');?></th>
            <td>
                <input type="checkbox" name="enableAlerts" value="1"
                    <?php echo $settings->enableAlerts?'checked':'';?>>
            </td>
        </tr>

        <tr>
            <th><?php _e('Email', 'integrity-checker');?></th>
            <td>
                <input name="alertEmails" type="text" size="35" value="<?php echo $settings->alertEmails?>">
                <a class="button-primary testAlertEmails">
                    <?php _e('Test', 'integrity-checker')?>
                </a>
                <span class="testAlertEmailsOk" style="display: none">
                    <i class="fa fa-check green" aria-hidden="true"></i>
                    <?php _e('Sent', 'integrity-checker');?>
                </span>

                <br>
                <i>
                <?php _e(
                    'Separate multiple addresses with a comma. Mails are sent from alerts@wpessentials.io.',
                    'integrity-checker'
                )?>
                <?php _e(
                    'Make sure to whitelist alerts@wpessentials.io in your spam filter.',
                    'integrity-checker'
                )?>
                </i>
            </td>
        </tr>
        <tr>
            <th></th>
            <td>
                <a class="button-primary saveAlertSettings">
                    <?php _e('Save alert settings', 'integrity-checker')?>
                </a>
                <span class="saveAlertSettingsFail" style="display: none">
                    <i class="fa fa-exclamation-triangle red" aria-hidden="true"></i>
                    <?php _e('Failed', 'integrity-checker');?>
                </span>
                <span class="saveAlertSettingsOk" style="display: none">
                    <i class="fa fa-check green" aria-hidden="true"></i>
                    <?php _e('Saved', 'integrity-checker');?>
                </span>
                <hr>
            </td>
        </tr>
    </table>
</script>