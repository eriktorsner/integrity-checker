<script type="text/html" id="tmpl-filesMonitorIssuesTmpl">
    <h3><?php _e('File monitor', 'integrity-checker');?></h3>
    <p>
        <?php _e(
            'Integrity Checker keeps track new, modified and deleted files in your installation compared to ' .
            'previous scans.',
            'integrity-checker');
        ?>
    </p>

    <div class="scan-results-item">
        <table>
            <tr>
                <td><strong><?php _e('Detected changes after','integrity-checker');?></strong></td>
                <td>
                    {{{data.checkpointIso}}}
                </td>
            </tr>
            <tr>
                <td><strong><?php _e('Delete history older than','integrity-checker');?></strong></td>
                <td>
                    <select name="deleteScanHistoryRange">
                        <option value="0">--<?php _e('select','integrity-checker');?>--</option>
                        <option value="-3 months"><?php _e('3 months','integrity-checker');?></option>
                        <option value="-1 month"><?php _e('1 month','integrity-checker');?></option>
                        <option value="-1 week"><?php _e('1 week','integrity-checker');?></option>
                        <option value="all"><?php _e('All history','integrity-checker');?></option>
                    </select>
                    <a class="button-primary deleteScanHistory">Go</a>
                </td>
            </tr>

            <tr>
                <td><strong><?php _e('Added files','integrity-checker');?></strong></td>
                <td>{{{data.ADDED}}}</td>
            </tr>
            <tr>
                <td><strong><?php _e('Modified files','integrity-checker');?></strong></td>
                <td>{{{data.MODIFIED}}}</td>
            </tr>
            <tr>
                <td><strong><?php _e('Deleted files','integrity-checker');?></strong></td>
                <td>{{{data.DELETED}}}</td>
            </tr>

            </tr>
        </table>

        <# if (data.ADDED > 0 || data.DELETED > 0 || data.MODIFIED > 0) { #>
            <a class="itemIssuesToggle" data-slug="filemonitor-issues">Show files</a>
        <# } #>

        <div id="scan-plugins-filemonitor-issues" style="display: none;">
            <table id="table-plugins-filemonitor-issues" width="100%" class="display" ></table>
        </div>

        <br>
        <hr>

    </div>
</script>