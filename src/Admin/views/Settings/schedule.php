<h3><?php _e('Scheduled scans', 'integrity-checker')?></h3>
<div id="scheduledScans" class="scheduled-scans"></div>

<script type="text/html" id="tmpl-scheduledScansTmpl">
    <table class="form-table">
        <tr>
            <th><?php _e('Enable schedule scans', 'integrity-checker');?></th>
            <td>
                <input type="checkbox" name="enableScheduleScans" value="1"
                    <?php echo $this->settings->enableScheduleScans?'checked':'';?>>
            </td>
        </tr>

        <tr>
            <th><?php _e('Scan frequency', 'integrity-checker');?></th>
            <td>
                <div class="jqcronSched"></div>
                <input type="hidden" name="cronValue" value="<?php echo $this->settings->cron;?>">
            </td>
        </tr>

        <tr>
            <th><?php _e('Included checks', 'integrity-checker');?></th>
            <td>
                <input type="checkbox" name="scheduleScanChecksums" value="1"
                       <?php echo $this->settings->scheduleScanChecksums?'checked':'';?>>
                &nbsp;<?php _e('Checksum','integrity-checker');?>
                <br>
                <input type="checkbox" name="scheduleScanPermissions" value="1"
                        <?php echo $this->settings->scheduleScanPermissions?'checked':'';?>>
                &nbsp;<?php _e('Files and permissions','integrity-checker');?>
                <br>
                <input type="checkbox" name="scheduleScanSettings" value="1"
                        <?php echo $this->settings->scheduleScanSettings?'checked':'';?>>
                &nbsp;<?php _e('Configuration checks','integrity-checker');?>
                <br>
            </td>
        </tr>

        <tr>
            <th></th>
            <td>
                <a class="button-primary saveSchedSettings">
                    <?php _e('Save schedule settings', 'integrity-checker')?>
                </a>
                <span class="saveSchedSettingsFail" style="display: none">
                    <i class="fa fa-exclamation-triangle red" aria-hidden="true"></i>
                    Failed
                </span>
                <span class="saveSchedSettingsOk" style="display: none">
                    <i class="fa fa-check green" aria-hidden="true"></i>
                    Saved
                </span>
                <hr>
            </td>
        </tr>
    </table>
</script>