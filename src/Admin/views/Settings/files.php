<h3><?php _e('File scanner','integrity-checker')?></h3>
<div id="filesSettings" class="files-settings"></div>

<script type="text/html" id="tmpl-filesSettingsTmpl">
    <table class="form-table">
        <tr>
            <th><?php _e('Maximum file size', 'integrity-checker');?></th>
            <td>
                <input name="maxFileSize" type="text" size="4" value="<?php echo $settings->maxFileSize?>"> Mb
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
            <th><?php _e('Maximum file size', 'integrity-checker');?></th>
            <td>
                <input name="maxFileSize" type="text" size="4" value="<?php echo $settings->maxFileSize?>"> Mb
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
            <th><?php _e('File permission modes', 'integrity-checker');?></th>
            <td>
                <input name="fileMasks" type="text" size="35" value="<?php echo $settings->fileMasks?>">
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
                <input name="folderMasks" type="text" size="35" value="<?php echo $settings->folderMasks?>">
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
            <th></th>
            <td>
                <a class="button-primary saveFileSettings">
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