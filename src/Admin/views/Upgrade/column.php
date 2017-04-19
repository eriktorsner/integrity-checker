<?php
extract($params);
$colClass = 'cols-' . $cols;
?>

<?php _e('','integrity-checker');?>

<div class="ptsEl ptsCol ptsCol-1 ptsElWithArea <?php echo $colClass;?>" data-el="table_col" style="height: auto;">
    <div class="ptsTableElementContent ptsElArea">
        <div class="ptsColHeader" style="height: 125px;">
            <div data-icon="<?php echo $icon;?>" data-type="icon" data-el="table_cell_icon" class="ptsIcon ptsEl ptsElInput">
                <i class="fa fa-2x ptsInputShell <?php echo $icon;?>"></i>
            </div>
        </div>
        <div class="ptsColDesc" style="height: 53px;">
            <div class="ptsEl" data-el="table_cell_txt" data-type="txt">
                <p>
							<span data-mce-style="">
								<?php echo $title;?>
							</span>
                </p>
                <?php echo $subtitle;?>
            </div>
        </div>
        <div class="ptsRows" style="height: auto;">
            <div class="ptsCell" style="height: 90px;">
                <div class="ptsEl" data-el="table_cell_txt" data-type="txt">
                    <p>
                        <span data-mce-style="">
                            <?php echo $desc;?>
                        </span>
                    </p>
                </div>
            </div>
            <?php foreach($rows as $row):?>
                <div class="ptsCell" style="height: 58px;">
                    <div class="ptsEl" data-el="table_cell_txt" data-type="txt">
                        <p>
                            <?php if (isset($row['icon'])): ?>
                                <i class="fa ptsInputShell <?php echo $row['icon'];?>"></i>
                            <?php endif?>
                            <?php
                                $style = '';
                                if (isset($row['neg'])) $style .= 'text-decoration: line-through; ';
                            ?>
                            <span style="<?php echo $style;?>" >
                                <?php echo $row['text'];?>
                            </span>
                        </p>
                    </div>
                </div>

            <?php endforeach;?>
        </div>


        <div class="ptsColFooter" style="height: 156px;">
            <?php if (isset($linkText)): ?>
                <?php
                    if (!isset($linkClass)) {
                        $linkClass = "";
                    }

                ?>
                <div class="ptsActBtnWp">
                    <div class="ptsActBtn ptsEl ptsElInput" data-el="btn" data-bgcolor="#333" data-bgcolor-elements="a" data-bgcolor-to="bg">
                        <a target="_blank" href="<?php echo $link; ?>" <?php echo $id ?>
                           class="<?php echo $linkClass?> ptsEditArea ptsInputShell" style="font-size: 12px; background-color: #333;">
                            <?php _e($linkText,'integrity-checker'); ?>
                        </a>
                    </div>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>