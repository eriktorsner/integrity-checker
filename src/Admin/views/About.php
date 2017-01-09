<?php
use \integrityChecker\Diagnostic;

$diagnostic = new Diagnostic();
$strDiagnostic = $diagnostic->get();
?>

<h3><?php _e('About', 'integrity-checker') ?></h3>
<p>
	<?php echo sprintf(
		__('Integrity Checker is developed by <a target="_blank" href="%s">WP Essentials</a>, a part of ' .
		   'Torgesta Technology AB in Sweden.',
			'integrity-checker'),
		'https://www.wpessentials.io/?utm_source=integrity-checker-free&utm_medium=web&utm_content=tab-about'
	);?>

	<?php echo sprintf(
		__('Visit the <a href="%s" target="_blank">plugin page</a> for more information.', 'integrity-checker'),
		'https://www.wpessentials.io/plugins/integrity-checker/?utm_source=integrity-checker-free&utm_medium=web&utm_content=tab-about'
	);?>
</p>

<p>
	<?php _e(
		"Integrity Checker uses a central database with checksum information about plugins and themes. " .
		"Access to the API is free, but to discourage abuse, there's a limit to the amount of requests " .
		"per hour that can be made without purchasing a license key. ",
		'integrity-checker');
	?>
	<?php _e(
		"When Integrity Checker was first installed, a free API-key was generated for you. This free key allows for 25 requests " .
		"per hour. This should be enough for most small and medium sized WordPress installations to perform a check of all " .
		"installed plugins and themes.", 'integrity-checker');
	?>
	<?php _e(
		"You can increase your hourly limit to 75 requests per hour by registering with your email address below. " .
		"For larger sites Licence keys or for more frequent checks can be purchased at:",
		'integrity-checker');
	?>

	<a href="https://www.wpessentials.io/plugins/integrity-checker/">
		https://www.wpessentials.io/plugins/integrity-checker/
	</a>
</p>
<p>
	<?php echo sprintf(
		__('This is a free plugin - best effort support is given via the <a href="%s">WordPress support forums</a>.',
			'integrity-checker'),
		'http://wordpress.org/support/plugin/integrity-checker');?>
</p>

<h3><?php _e('Quota', 'integrity-checker') ?></h3>
<div id="checksum-quota">Updating <i class="fa fa-spinner fa-spin"></i></div>

<h3><?php _e('API key', 'integrity-checker') ?></h3>
<p>
	<?php _e(
		"If you have purchased an API key, enter it here to verify and save it",
		'integrity-checker');
	?>
	<br>
	<input type="text" style="width: 50%;" id="apikey"
	       value="<?php echo get_option('wp_checksum_apikey', '[NO KEY]'); ?>"
	       data-original="<?php echo get_option('wp_checksum_apikey', '[NO KEY]'); ?>">
	<a class="button-primary updateApiKey">
		<?php _e('Submit', 'integrity-checker' ) ?>
	</a>
	<br>
<div id="updateKeyMessage">&nbsp;</div>
</p>

<h3><?php _e('Diagnostic information','integrity-checker')?></h3>
<p>
	<?php _e('To help us help you. When requesting support for this plugin, copy and paste the information below and ' .
             'add it to the support request','integrity-checker')?>
</p>
<textarea class="diagnostic" autocomplete="off" readonly=""><?php echo $strDiagnostic; ?></textarea>


<script type="text/html" id="tmpl-quotaTmpl">
<table class="quotainfo">
	<tr>
		<td>Rate limit</td>
		<td>{{{ data.limit}}} requests/h</td>
	</tr>
	<tr>
		<td>Current usage</td>
		<td>{{{ data.current}}} requests</td>
	</tr>
	<tr>
		<td>Reset in</td>
		<td>{{{ data.resetIn }}} s</td>
	</tr>
	<tr>
		<td>Key type</td>
		<td>{{{ data.validationStatus }}}</td>
	</tr>
    <tr>
        <td>Email</td>
        <td>
            <# if (data.validationStatus == 'VALIDATED') {#>
                {{{ data.email }}}
	            <?php _e('The <a class="termsLink">terms</a>','integrity-checker');?>
            <# } else { #>
                <input id="submitEmail" type="text" value="{{{ data.email }}}" />
                <a class="button-primary submitEmailBtn">
                    <# if (data.validationStatus == 'PENDING') { #>
                        <?php _e('Resend', 'integrity-checker'); ?>
                    <# } else { #>
                        <?php _e('Register', 'integrity-checker'); ?>
                    <# } #>
                </a>
	            <br>
	            <?php _e('By submitting my email address, I confirm that I have read and accepted ' .
		            'the <a class="termsLink">terms</a>','integrity-checker');?>
                <div id="submitEmailMessage">
                    <# if (data.validationStatus == 'PENDING') { #>
                        <?php _e('A verification email has been sent to you. Please check your inbox. ', 'integrity-checker');?>
                        <br>
                        <?php _e('If you haven\'t received the email, click Resend above to retry.', 'integrity-checker');?>
                    <# } #>
                    &nbsp;
                </div>
            <# } #>
        </td>
    </tr>
	<tr>
		<td colspan="2">
			<a class="button-primary refreshQuota">
				<?php _e('Refresh', 'integrity-checker' ) ?>
			</a>
		</td>
	</tr>
</table>
</script>

<div id="termsText" style="display:none;">
	<?php require_once 'terms.php';	?>
</div>