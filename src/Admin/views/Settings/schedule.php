<h3><?php _e('Scheduled scans', 'integrity-checker')?></h3>
<div id="scheduledScans" class="scheduled-scans"></div>

<script type="text/html" id="tmpl-scheduledScansTmpl">
    <div class="access-anonymous" >
        <p>
            <?php _e("You are using an anonymous API key that was generated for you when you first installed" .
                     "Integrity Checker. To enable scheduled scans, you need to register your email address or " .
                     "upgrade to a paid package", "integrity-checker");?>
            <br><?php _e("Bor basic scheduling, register your email address, go to the ", "integrity-checker");?>
            <a href="tools.php?page=integrity-checker_options&tab=tab-about">
                <?php _e("About tab", "integrity-checker");?>
            </a>
            <br><?php _e("For more frequent scheduling and more features, visit our ", "integrity-checker");?>
            <a target="_blank" href="https://www.wpessentials.io/plugins/integrity-checker/?utm_source=integrity-checker-free&utm_medium=web&utm_content=tab-about">
                <?php _e("plugin home page", "integrity-checker");?>
            </a>
        </p>
    </div>
    <div class="access-registered" >
        <p>
            <?php _e("You are using a free registered API key. Monthly scheduled scans are available, for more detailed " .
                     "and frequent scheduling consider upgrading to a paid subscription", "integrity-checker");?>
            <?php _e("For more information visit our ", "integrity-checker");?>
            <a target="_blank" href="https://www.wpessentials.io/plugins/integrity-checker/?utm_source=integrity-checker-free&utm_medium=web&utm_content=tab-about">
                <?php _e("plugin home page", "integrity-checker");?>
            </a>
        </p>
    </div>

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
                <div class="jqcronSched access-registered access-paid"></div>
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
                <a class="button-primary saveSchedSettings access-registered access-paid">
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