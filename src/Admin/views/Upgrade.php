<?php
$userLevel = $this->settings->userLevel();
$pluginUrl = plugin_dir_url('integrity-checker/integrity-checker.php');
$cols = 2;
?>


<?php if ($userLevel == 'anonymous'): ?>
    <div>
        <h3>
            <?php _e('Your current access type is: Anonymous','integrity-checker')?>
        </h3>
    </div>
<?php endif ?>

<?php if ($userLevel == 'registered'): ?>
    <div>
        <h3>
            <?php _e('Your current access type is: Registered','integrity-checker')?>
        </h3>
    </div>
<?php endif ?>

<div class="pricing">
    <div class="ptsTableFrontedShell">
        <div id="ptsBlock_602851" class="ptsBlock" data-id="8" style="width: 100%;">
            <div class="ptsBlockContent"><div class="ptsContainer">
                    <div class="ptsColsWrapper">

                        <?php
                            if ($userLevel == 'anonymous') {
                                $cols = 3;
                                $params = array(
                                    'icon'     => 'fa-user-secret',
                                    'title'    => 'ANONYMOUS - $0',
                                    'subtitle' => 'Limited api access. <a class="termsLink">terms</a>',
                                    'desc'     => '',
                                    'rows'     => array(
                                        array('text' => 'Plugin & Theme scanning for modified files'),
                                        array('text' => 'File owner & permission scanning'),
                                        array('text' => 'File change monitoring'),
                                        array('text' => 'Visual file diffs (very limited)'),
                                        array('text' => 'Scheduled scans', 'neg' => true),
                                        array('text' => 'Alternate checksums', 'neg' => true),
                                        array('text' => 'Premium support', 'neg' => true),
                                    ),
                                );
                                include __DIR__ . '/Upgrade/column.php';
                            }
                        ?>

                        <?php
                        $params = array(
                            'icon'     => 'fa-address-card',
                            'title'    => 'REGISTERED - $0',
                            'subtitle' => 'Less limits <a class="termsLink">terms</a>',
                            'desc'     => '',
                            'rows'     => array(
                                array('text' => 'Plugin & Theme scanning for modified files'),
                                array('text' => 'File owner & permission scanning'),
                                array('text' => 'File change monitoring'),
                                array('text' => 'Visual file diffs (limited)'),
                                array('text' => 'Scheduled scans (only monthly)'),
                                array('text' => 'Alternate checksums'),
                                array('text' => 'Premium support', 'neg' => true),
                            ),
                        );

                        if ($userLevel == 'anonymous') {
                            $params['link'] = '#';
                            $params['linkText'] = 'Register now';
                            $params['linkClass'] = 'registerNowLink';
                        }

                        include __DIR__ . '/Upgrade/column.php';
                        ?>

                        <?php
                        $params = array(
                            'icon'     => 'fa-user-plus',
                            'title'    => 'SUBSCRIBER - $39',
                            'subtitle' => 'Plans starting at $39 / year',
                            'desc'     => 'Subscriber plans starts at $39 per year for 1 site. We offer ' .
                                          'several plans for multiple sites starting as low as $16 per year per site',
                            'rows'     => array(
                                array('text' => 'Plugin & Theme scanning for modified files'),
                                array('text' => 'File owner & permission scanning'),
                                array('text' => 'File change monitoring'),
                                array('text' => 'Visual file diffs (unlimited)'),
                                array('text' => 'Scheduled scans (unlimited)'),
                                array('text' => 'Alternate checksums'),
                                array('text' => 'Premium support'),
                            ),
                            'linkText' => 'Buy now',
                            'link' => 'https://www.wpessentials.io/product-category/integrity-checker/',
                        );
                        include __DIR__ . '/Upgrade/column.php';
                        ?>
                    </div>
                    <div style="clear: both;"></div>
                </div></div>
</div>


<div id="registerEmailPopup" style="display:none;">
    <?php require_once __DIR__ . '/Upgrade/registeremail.php';	?>
</div>