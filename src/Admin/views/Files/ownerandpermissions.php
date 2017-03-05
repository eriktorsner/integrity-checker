<script type="text/html" id="tmpl-filesMaskOwnerIssuesTmpl">
    <h3><?php _e('Ownership and permissions', 'integrity-checker');?></h3>
    <p>
        <?php _e('File permissions determines which users and groups that can read, write, and execute files.', 'integrity-checker');?>
        <?php _e("It's good to be as strict as practically possible to avoid risk.", 'integrity-checker');?>
        <?php _e('Recommended settings for WordPress is 0644 for files and 0755 for folders.', 'integrity-checker');?>
        <?php _e('You can be stricter, but if you are too strict you might lose the ability to upgrade WordPress which is a security risk in itself.', 'integrity-checker');?>
    </p>
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
                    <# if (data.unacceptable > 0) { #>
                        <i class="fa fa-exclamation-triangle red" aria-hidden="true"></i>
                    <# } #>
                    <?php _e('Unsafe permissions','integrity-checker');?>
                </td>
                <td>{{{0 + data.unacceptable}}}</td>
            </tr>
        </table>

        <# if (data.files.length> 0) { #>
            <a class="itemIssuesToggle" data-slug="permissions-issues">Show issues</a>
            <# } #>
                <br>

                <table id="scan-plugins-permissions-issues" class="scan-result" style="display: none;">
                    <thead>
                    <tr>
                        <td style="width: 50%;">Name</td>
                        <td>Type</td>
                        <td>Mode</td>
                        <td>Owner/Group</td>
                        <td>Modified</td>
                        <td>Size</td>
                    </tr>
                    </thead>
                    <tbody>
                    <# for (var index in data.files) { #>
                        <# var file = data.files[index];  #>
                            <# var icon = file.isDir == "1" ? 'fa-folder-o':'fa-file-o'; #>
                                <tr>
                                    <td style="width: 50%;">
                                        <i class="fa {{{icon}}}" aria-hidden="true"></i>
                                        <span class="item-filename">{{{file.file}}}</span>
                                        <br>
                                        <strong>{{{file.reason}}}</strong>
                                    </td>
                                    <td><span class="item-type">{{{file.isDir == "1" ? 'Folder':'File'}}}</span></td>
                                    <td><span class="item-status">{{{file.mode}}}</span></td>
                                    <td><span class="item-status">{{{file.owner}}} / {{{file.group}}} </span></td>
                                    <td><span class="item-filename">{{{file.date}}}</span></td>
                                    <td><span class="item-filename">{{{humanFileSize(file.size)}}}</span></td>
                                </tr>
                                <#}#>
                    </tbody>
                </table>
                <hr>
    </div>
</script>