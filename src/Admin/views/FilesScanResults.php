<div id="files-scan-summary" class="scan-summary"></div>
<div id="files-monitor-scan-results" class="scan-results"></div>
<div id="files-ownerandpermission-scan-results" class="scan-results"></div>

<!-- wp-util templates -->
<script type="text/html" id="tmpl-filesSummaryTmpl">
    <?php $scanType = 'files';?>
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
        <a class="button-primary startScan" data-scantype="<?php echo $scanType;?>"><?php _e('Scan now', 'integrity-checker') ?></a>
    <# } #>

    <div class="scanStatus <?php echo $scanType;?>" style="display: none;">
        <span class="jobCount"></span>
        <?php _e("jobs left to process", "integrity-checker");?>
    </div>

</script>

<?php include __DIR__ . '/Files/filemonitor.php';?>
<?php include __DIR__ . '/Files/ownerandpermissions.php';?>

