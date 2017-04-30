<?php
$intervals = array(
    'Week' => '1 week',
    'Month' => '1 month',
    '3 Months' => '3 month',
    'Year' => '1 year'
);
?>

<h3><?php _e('File scanner','integrity-checker')?></h3>
<div id="filesSettings" class="files-settings"></div>

<script type="text/html" id="tmpl-filesSettingsTmpl">
    <table class="form-table">
        <tr>
            <th><?php _e('Maximum file size', 'integrity-checker');?></th>
            <td>
                <input name="maxFileSize" type="text" size="4" value="<?php echo $this->settings->maxFileSize?>"> Mb
                <br>
                <i>
                    <?php _e(
                        'During a scan, Integrity Checker calculates a file checksum (signature) for each file. ' .
                        'This is CPU intense and time consuming, especially for large files such as images. ' .
                        'For performance reasons you can skip checksum calculations for large files by specifying ' .
                        'a size limit. Default 2M',
                        'integrity-checker'
                    )?>
                </i>
            </td>
        </tr>
        <tr>
            <th><?php _e('Follow symlinks', 'integrity-checker');?></th>
            <td>
                <input type="checkbox" name="followSymlinks" value="1"
                    <?php echo $this->settings->enableAlerts?'checked':'';?>>
                <br>
                <i>
                    <?php _e('Should the file scanner follow symlinks?. ', 'integrity-checker')?>
                </i>
            </td>

        </tr>


        <tr>
            <th><?php _e('File permission modes', 'integrity-checker');?></th>
            <td>
                <input name="fileMasks" type="text" size="35" value="<?php echo $this->settings->fileMasks?>">
                <br>
                <i>
                    <?php _e(
                        'Enter acceptable permission modes for files. Separate multiple acceptable modes with comma ' .
                        'Default: 0644, 0640, 0600',
                        'integrity-checker'
                    )?>
                </i>
            </td>
        </tr>

        <tr>
            <th><?php _e('Folder permission modes', 'integrity-checker');?></th>
            <td>
                <input name="folderMasks" type="text" size="35" value="<?php echo $this->settings->folderMasks?>">
                <br>
                <i>
                    <?php _e(
                        'Enter acceptable permission modes for folders. Separate multiple acceptable modes with comma ' .
                        'Default value: 0755, 0750, 0700',
                        'integrity-checker'
                    )?>
                </i>
            </td>
        </tr>

        <tr>
            <th><?php _e('File and folder owner(s)', 'integrity-checker');?></th>
            <td>
                <input name="fileOwners" type="text" size="35" value="<?php echo $this->settings->fileOwners?>">
                <br>
                <i>
                    <?php _e(
                        'Enter the acceptable owner(s) of files and folder in this WordPress installation. Separate  ' .
                        'multiple values with a comma. If left empty, the file scanner will assume that the owner of '.
                        'a few files in the root installation folder is correct for all files and folder. ' .
                        'Default value: [empty]',
                        'integrity-checker'
                    )?>
                </i>
            </td>
        </tr>

        <tr>
            <th><?php _e('File and folder group(s)', 'integrity-checker');?></th>
            <td>
                <input name="fileGroups" type="text" size="35" value="<?php echo $this->settings->fileGroups?>">
                <br>
                <i>
                    <?php _e(
                        'Enter the acceptable group(s) of files and folder in this WordPress installation. Separate  ' .
                        'multiple values with a comma. If left empty, the file scanner will assume that the group of '.
                        'a few files in the root installation folder is correct for all files and folder. ' .
                        'Default value: [empty]',
                        'integrity-checker'
                    )?>
                </i>
            </td>
        </tr>

        <tr>
            <th><?php _e('Ignore folders', 'integrity-checker');?></th>
            <td>
                <textarea name="fileIgnoreFolders" type="text" rows="5" cols="55"><?php echo $this->settings->fileIgnoreFolders?></textarea>
                <br>
                <i>
                    <?php _e(
                        'Enter one or more wild card patterns to indicate folders relative to the WordPress root ' .
                        'folder that the file scanner should ignore. ' .
                        'Each pattern on a separate row. Use standard shell wild card characters like * and ' .
                        '?. <br>Default value: wp-content/cache*',
                        'integrity-checker'
                    )?>
                    <br><br>
                    <?php _e('Your current installation root is:', 'integrity-checker'); ?>
                    <code><?php echo ABSPATH ?></code>
                </i>
            </td>
        </tr>

        wp-content/cache/

        <tr>
            <th></th>
            <td>
                <a class="button-primary saveFileSettings access-registered access-paid">
                    <?php _e('Save file scanner settings', 'integrity-checker')?>
                </a>
                <span class="saveFileSettingsFail" style="display: none">
                    <i class="fa fa-exclamation-triangle red" aria-hidden="true"></i>
                    <?php _e('Failed', 'integrity-checker');?>
                </span>
                <span class="saveFileSettingsOk" style="display: none">
                    <i class="fa fa-check green" aria-hidden="true"></i>
                    <?php _e('Saved', 'integrity-checker');?>
                </span>
                <hr>
            </td>
        </tr>
    </table>
</script>