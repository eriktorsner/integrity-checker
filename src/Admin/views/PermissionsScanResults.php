<div id="permissions-scan-summary" class="scan-summary"></div>
<div id="permissions-scan-results" class="scan-results"></div>

<!-- wp-util templates -->
<script type="text/html" id="tmpl-permissionsSummaryTmpl">
    <?php $scanType = 'permissions';?>
    <# if (data.state == 'finished') {#>
        <p>
            <strong><?php _e('Last scan finished', 'integrity-checker');?></strong>: {{{data.finishedIso}}}
        </p>
    <# } #>

    <# if (data.state == 'started') {#>
        <a class="button-primary startScan" data-scantype="<?php echo $scanType;?>"
           orgtext="<?php _e('Scan now', 'integrity-checker' ) ?>">
            <i class="fa fa-spinner fa-spin"></i>
        </a>
    <# } #>

    <# if (data.state != 'started') {#>
        <a class="button-primary startScan" data-scantype="<?php echo $scanType;?>"><?php _e('Scan now', 'integrity-checker') ?></a>
    <# } #>
    <p>
        <?php _e('This tool examines file permissions in your WordPress installation', 'integrity-checker');?>
    </p>
    <p>
        <?php _e('File permissions determines which users and groups that can read, write, and execute files.', 'integrity-checker');?>
        <?php _e("It's good to be as strict as practically possible to avoid risk.", 'integrity-checker');?>
        <?php _e('Recommended settings for WordPress is 0644 for files and 0755 for folders.', 'integrity-checker');?>
        <?php _e('You can be stricter, but if you are too strict you might lose the ability to upgrade WordPress which is a security risk in itself.', 'integrity-checker');?>
    </p>

</script>

<script type="text/html" id="tmpl-permissionsIssuesTmpl">
    <div class="scan-results-item">
        <table>
            <tr>
                <td><?php _e('Total files and folders','integrity-checker');?></td>
                <td>{{{data.total}}}</td>
            </tr>
            <tr>
                <td><?php _e('Acceptable permissions','integrity-checker');?></td>
                <td>{{{0 + data.acceptable}}}</td>
            </tr>
            <tr>
                <td>
                    <# if (data.files.length> 0) { #>
                        <i class="fa fa-exclamation-triangle red" aria-hidden="true"></i>
                        <# } #>
                    <?php _e('Unsafe permissions','integrity-checker');?>
                </td>
                <td>{{{data.files.length}}}</td>
            </tr>
        </table>

        <# if (data.files.length> 0) { #>
            <a class="itemIssuesToggle" data-slug="permissions-issues">Show issues</a>
        <# } #>
            <br>

        <table id="scan-plugins-permissions-issues" class="scan-result" style="display: none;">
            <thead>
                <tr>
                    <td>Name</td>
                    <td>Type</td>
                    <td>Mode</td>
                    <td>Modified</td>
                    <td>Size</td>
                </tr>
            </thead>
            <tbody>
                <# for (var index in data.files) { #>
                    <# var file = data.files[index];  #>
                    <# var icon = file.isDir == "1" ? 'fa-folder-o':'fa-file-o'; #>
                    <tr>
                        <td>
                             <i class="fa {{{icon}}}" aria-hidden="true"></i>
                            <span class="item-filename">{{{file.file}}}</span>
                        </td>
                        <td><span class="item-type">{{{file.isDir == "1" ? 'Folder':'File'}}}</span></td>
                        <td><span class="item-status">{{{file.mode}}}</span></td>
                        <td><span class="item-filename">{{{file.date}}}</span></td>
                        <td><span class="item-filename">{{{humanFileSize(file.size)}}}</span></td>
                    </tr>
                <#}#>
            </tbody>
        </table>
        <hr>
    </div>
</script>

