<div class="wrap">
<h3>WordPress Integrity checker</h3>
<p>
    <?php _e('Test the integrity of your WordPress files and folders. ', 'integrity-checker'); ?>
</p>

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
