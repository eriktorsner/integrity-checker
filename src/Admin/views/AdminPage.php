<div class="wrap">
    <div class="integrity-checker-header">
        <div class="head">
            <h3>WordPress Integrity checker</h3>
            <p>
                <?php _e('Test the integrity of your WordPress files and folders. ', 'integrity-checker'); ?>
            </p>
        </div>
        <div class="puff">
            <div class="integrity-checker-puff-content access-anonymous">
                <h3>Register your email</h3>
                ...be more secure with monthly scheduled scans.<br>
                Register on the <a href="?page=integrity-checker_options&tab=tab-upgrade">upgrade tab</a>.
            </div>
            <div class="integrity-checker-puff-content access-registered">
                <h3>Upgrade to a paid subscription</h3>
                Better protection starts at $39 per year<br>
                Read more on the <a href="?page=integrity-checker_options&tab=tab-upgrade">upgrade tab</a>.
            </div>

        </div>
    </div>

    <h2 class="nav-tab-wrapper">
        <?php foreach ($this->tabs as $tab):?>
        <a href="#" id="tab-<?php echo$tab->tabId;?>"
           class="nav-tab opt-tab <?php echo $tab->active?'nav-tab-active':''?>"
           data-optcontent="#tab-content-<?php echo $tab->tabId;?>">
            <?php echo $tab->name; ?>
        </a>
        <?php endforeach ?>
    </h2>

    <?php foreach ($this->tabs as $tab):?>
        <div class="card opt-content" id="tab-content-<?php echo $tab->tabId;?>" style="display: <?php echo $tab->display();?>;">
            <?php $tab->render(); ?>
        </div>
    <?php endforeach; ?>
</div>
