<div id="checksum-scan-summary" class="scan-summary"></div>
<div id="checksum-scan-results" class="scan-results"></div>


<script type="text/html" id="tmpl-checksumSummaryTmpl">
    <# if (data.state == 'finished') {#>
        <p>
            <strong><?php _e('Last scan finished', 'integrity-checker');?></strong>: {{{data.finishedIso}}}
            <?php _e('and took', 'integrity-checker');?>
            <strong>{{{data.finished - data.started}}} <?php _e('seconds', 'integrity-checker');?></strong>
        </p>
    <# } #>

    <# if (data.state == 'started') {#>
            <a class="button-primary startScan" data-scantype="checksum" orgtext="<?php _e('Scan now', 'integrity-checker') ?>">
                <i class="fa fa-spinner fa-spin"></i>
            </a>
    <# } #>

    <# if (data.state != 'started') {#>
        <a class="button-primary startScan" data-scantype="checksum"><?php _e('Scan now', 'integrity-checker' ) ?></a>
    <# } #>

    <div class="scanStatus <?php echo $scanType;?>" style="display: none;">
        <span class="jobCount"></span>
        <?php _e("plugins to go", "integrity-checker");?>
    </div>

    <p>
        <?php _e('This tool detects changes in individual files in WordPress core, themes and plugins.', 'integrity-checker');?>
    </p>
    <p>
        <?php _e("A modified or added file may indicate that the site has been hacked or compromised,", 'integrity-checker');?>
        <?php _e('but it may also mean that you or another administrator have made local changes that risks getting overwritten during an upgrade', 'integrity-checker');?>
        <?php _e('Many premium themes and plugins are not possible to check because their source code is not publicly available.', 'integrity-checker');?>
    </p>
        <p>
            <?php _e('If issues are found, consider reinstalling the problematic theme, plugin or WordPress itself', 'integrity-checker');?>
        </p>

</script>
<script type="text/html" id="tmpl-checksumIssuesTmpl">
    <div class="scan-results-item">
        <# if (data.totalIssues > 0) { #>

            <# if (data.hardIssues > 0) { #>
                <i class="fa fa-exclamation-triangle red" aria-hidden="true"></i>
             <# } else { #>
                 <i class="fa fa-exclamation-triangle orange" aria-hidden="true"></i>
             <# } #>

        <# } else { #>

            <# if (data.status == 'checked') { #>
                <i class="fa fa-check-circle green" aria-hidden="true"></i>
            <# } else { #>
                <i class="fa fa-question-circle" aria-hidden="true"></i>
            <# } #>

        <# } #>
        <strong>{{{data.name}}}</strong>
        <# if (data.status == 'checked') { #>
            - {{{data.issues.length}}} issue(s) found
        <# } else { #>
            - [{{{ data.message }}}]
        <# } #>

        <# if (data.issues.length > 0) { #>
            <a class="itemIssuesToggle" data-slug="{{{data.slug}}}">Show issues</a>
        <# } #>

        <table id="scan-plugins-{{{data.slug}}}" class="scan-result" style="display: none;">
            <thead>
                <tr>
                    <td><?php _e('File', 'integrity-checker');?></td>
                    <td><?php _e('Status', 'integrity-checker');?></td>
                    <td><?php _e('Issue', 'integrity-checker');?></td>
                    <td><?php _e('Modified', 'integrity-checker');?></td>
                    <td><?php _e('Size', 'integrity-checker');?></td>
                </tr>
            </thead>
            <tbody>
                <# for (var index in data.issues) { #>
                    <#
                        var issue = data.issues[index];
                        var type = '<?php _e('HARD', 'integrity-checker');?>';
                        var typeClass = 'red';
                        if (issue.isSoft) {
                            type = '<?php _e('SOFT', 'integrity-checker');?>';
                            typeClass = 'orange';
                        }
                    #>
                    <tr>
                        <td><span class="item-filename">{{{issue.file}}}</span></td>
                        <td>
                            <span class="item-status">{{{issue.status}}}</span>
                            <# if (issue.status == 'MODIFIED') {#>
                                <a class="btnDiff" href="#" data-type="{{{data.type}}}"
                                   data-slug="{{{data.slug}}}" data-file="{{{issue.file}}}">
                                    &nbsp;<?php _e('Diff', 'integrity-checker')?>
                                </a>
                            <# } #>
                        </td>
                        <td>
                            <span class="item-type {{{typeClass}}}">{{{type}}}</span>
                        </td>
                        <td><span class="item-filename">{{{issue.date}}}</span></td>
                        <td><span class="item-filename">{{{humanFileSize(issue.size)}}}</span></td>
                    </tr>
                <#}#>
            </tbody>
        </table>
        <hr>
    </div>
</script>

<script type="text/html" id="tmpl-diffErrorTmpl">
    <div class="diffError">
        <p>
            <?php _e('There was a problem getting the original version of the file','integrity-checker');?>
            <?php _e('The server said:','integrity-checker');?>
        </p>
        <strong>Code:</strong> {{{ data.code }}} <br>
        <strong>Message:</strong> {{{ data.message }}} <br>
    </div>
</script>