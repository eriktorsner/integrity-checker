
<p>
<?php _e('By submitting my email address, I confirm that I have read and accepted ' .
         'the <a class="termsLink">terms</a>','integrity-checker');?>

</p>

<input id="submitEmailPopup" type="text" value="" />
<a class="button-primary submitEmailBtnPopup">
    <?php _e('Register', 'integrity-checker'); ?>
</a>
<br>

<div id="submitEmailMessagePopup" style="display: none;">
    <p>
    <?php _e('Your email address is PENDING validation. ', 'integrity-checker');?>
    <?php _e('A verification email has been sent to you. Please check your inbox. ', 'integrity-checker');?>
    <br>
    <?php _e('If you haven\'t received the email, click Register above to retry.', 'integrity-checker');?>
    </p>
</div>
<div id="submitEmailMessagePopupErr" style="display: none;">
    <p>
        <?php _e('Your email address is PENDING validation. ', 'integrity-checker');?>
        <?php _e('A verification email has been sent to you. Please check your inbox. ', 'integrity-checker');?>
        <br>
        <?php _e('If you haven\'t received the email, click Register above to retry.', 'integrity-checker');?>
    </p>
</div>