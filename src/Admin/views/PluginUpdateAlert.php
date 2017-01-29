<tr class="plugin-update-tr" id="<?php echo $slug;?>-reinstall" data-slug="<?php echo $slug?>"
    data-plugin="<?php echo $pluginFile?>">
    <td colspan="99" class="plugin-update colspanchange">
        <div class="update-message notice inline notice-warning notice-alt">

            <?php echo sprintf(
                    __('Integrity Checker has found modified files in the %s plugin. '),
                    $pluginName
                );
            ?>
            <a href="<?php echo $upgradeUrl ?>" %6$s>Reinstall now</a>


        </div>
    </td>
</tr>