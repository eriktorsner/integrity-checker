<script type="text/html" id="tmpl-filesMonitorIssuesTmpl">
    <h3><?php _e('File monitor', 'integrity-checker');?></h3>
    <p>
        <?php _e('Keep track of new, deleted or modified files in your WordPress installation', 'integrity-checker');?>
    </p>

    <div class="scan-results-item">
        <table></table>

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