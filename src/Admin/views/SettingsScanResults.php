<div id="settings-scan-summary" class="scan-summary div-loading"></div>
<div id="settings-scan-results" class="scan-results div-loading"></div>

<!-- wp-util templates -->
<script type="text/html" id="tmpl-settingsSummaryTmpl">
    <?php $scanType = 'settings';?>
    <# if (data.state == 'finished') {#>
        <p>
            <strong><?php _e('Last scan finished', 'integrity-checker');?></strong>: {{{data.finishedIso}}}
            <?php _e('and took', 'integrity-checker');?>
            <strong>{{{data.finished - data.started}}} <?php _e('seconds', 'integrity-checker');?></strong>
        </p>
        <# } #>

            <# if (data.state == 'started') {#>
                <a class="button-primary startScan" data-scantype="<?php echo $scanType;?>"
                   orgtext="<?php _e('Scan now', 'integrity-checker' ) ?>">
                    <i class="fa fa-spinner fa-spin"></i>
                </a>
                <# } #>

                <# if (data.state != 'started') {#>
                    <a class="button-primary startScan" data-scantype="<?php echo $scanType;?>"><?php _e('Scan now', 'integrity-checker' ) ?></a>
                <# } #>

                <div class="scanStatus <?php echo $scanType;?>" style="display: none;">
                    <span class="jobCount"></span>
                    <?php _e("checks to go", "integrity-checker");?>
                </div>

        <p>
            <?php _e('This tool examines miscellaneous WordPress settings for potential security issues.', 'integrity-checker');?>
            <br>
            <strong><?php _e('Note:', 'integrity-checker');?></strong>
            <?php _e('This scan might trigger errors in your web server logs.', 'integrity-checker');?>
        </p>
        <p>
    </p>

</script>

<script type="text/html" id="tmpl-settingsIssuesTmpl">
    <div class="scan-results-item">
        <# if (!data.acceptable) { #>
            <i class="fa fa-exclamation-triangle red" aria-hidden="true"></i>
        <# } else { #>
            <i class="fa fa-check-circle green" aria-hidden="true"></i>
        <# } #>
        <strong>{{{data.name}}}</strong>
        <a class="itemIssuesToggle" data-slug="{{{data.slug}}}">Show details</a>
        <div id="scan-plugins-{{{data.slug}}}" class="scan-result" style="display: none;">
            <table>
                <tr>
                    <td valign="top">
                        <strong><?php _e('Result', 'integrity-checker'); ?></strong>
                    </td>
                    <td valign="top">
                        <# if (data.acceptable) { #>
                            <i class="fa fa-check-circle green" aria-hidden="true"></i>
                            <?php _e('OK', 'integrity-checker'); ?>
                        <# } else { #>
                            <i class="fa fa-exclamation-triangle red" aria-hidden="true"></i>
                            <?php _e('Not OK', 'integrity-checker'); ?>
                        <# } #>
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        <strong><?php _e('Details', 'integrity-checker'); ?></strong>
                    </td>
                    <td valign="top">
                        {{{ data.result }}}
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        <strong><?php _e('Description', 'integrity-checker'); ?></strong>
                    </td>
                    <td valign="top">
                        {{{ data.description }}}
                    </td>
                </tr>
                <tr>
                    <td valign="top">
                        <strong></strong>
                    </td>
                    <td valign="top">
                        <a target="_blank" href="https://wpessentials.io/plugins/integrity-checker/readmore/{{{data.slug}}}">
                            <?php _e('Read more', 'integrity-checker'); ?>
                        </a>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</script>

