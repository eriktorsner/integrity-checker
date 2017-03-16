<script type="text/html" id="tmpl-filesMaskOwnerIssuesTmpl">
    <h3><?php _e('Ownership and permissions', 'integrity-checker');?></h3>
    <p>
        <?php _e('File permissions determines which users and groups that can read, write, and execute files.', 'integrity-checker');?>
        <?php _e("It's good to be as strict as practically possible to avoid risk.", 'integrity-checker');?>
        <?php _e('Recommended settings for WordPress is 0644 for files and 0755 for folders.', 'integrity-checker');?>
        <?php _e('You can be stricter, but if you are too strict you might lose the ability to upgrade WordPress which is a security risk in itself.', 'integrity-checker');?>
        <?php _e('You can change your preferred permission and owner/group settings on the Settings tab.', 'integrity-checker');?>
        <a href="tools.php?page=integrity-checker_options&tab=tab-settings"><?php _e('Go to Settings', 'integrity-checker');?></a>
    </p>

    <div class="scan-results-item">
        <table>
            <tr>
                <td><strong><?php _e('Total files and folders','integrity-checker');?></strong></td>
                <td>{{{data.total}}}</td>
            </tr>

            <tr>
                <td>
                    <# if (data.acceptable > 0) { #>
                        <i class="fa fa-check green" aria-hidden="true"></i>
                        <# } #>
                    <?php _e('Acceptable permissions and ownership','integrity-checker');?>
                </td>
                <td>{{{0 + data.acceptable}}}</td>
            </tr>

            <tr>
                <td>
                    <# if (data.unacceptable > 0) { #>
                        <i class="fa fa-exclamation-triangle red" aria-hidden="true"></i>
                    <# } #>
                    <?php _e('Unsafe permissions and/or ownership','integrity-checker');?>
                </td>
                <td>{{{0 + data.unacceptable}}}</td>
            </tr>

        </table>


        <# if (data.unacceptable > 0) { #>
            <a class="itemIssuesToggle" data-slug="permissions-issues">Show issues</a>
        <# } #>

         <div id="scan-plugins-permissions-issues" style="display: none;">
             <table id="table-plugins-permissions-issues" width="100%" class="display" ></table>
         </div>

        <br>
        <hr>

    </div>
</script>