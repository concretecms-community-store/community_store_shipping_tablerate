<?php
defined('C5_EXECUTE') or die("Access Denied.");
extract($vars);
?>

<div class="row">
    <div class="col-xs-12 col-sm-6">
        <div class="form-group">
            <?php  echo $form->label('minimumAmount', t("Minimum Purchase Amount for this rate to apply")); ?>
            <?php  echo $form->text('minimumAmount', $smtm->getMinimumAmount()?$smtm->getMinimumAmount():'0'); ?>
        </div>
    </div>
    <div class="col-xs-12 col-sm-6">
        <div class="form-group">
            <?php  echo $form->label('maximumAmount', t("Maximum Purchase Amount for this rate to apply")); ?>
            <?php  echo $form->text('maximumAmount', $smtm->getMaximumAmount()?$smtm->getMaximumAmount():'0'); ?>
            <p class="help-block"><?php echo t("Leave at 0 for no maximum")?></p>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-xs-12 col-sm-6">
         <div class="form-group">
            <?php  echo $form->label('rateType', t("Rate Based On")); ?>
            <?php  echo $form->select('rateType', array('amountItems' => '# of items vs destination', 'amountPrice' => 'Total price vs destination', 'amountWeight' => 'Weight vs destination'), $smtm->getRateType()); ?>
        </div>
    </div>
    <div class="col-xs-12 col-sm-6">
         <div class="form-group">
           <?php if(!empty($smtm->getShippingMethodID())){ ?>
                <?php  echo $form->label('csvFile', t("CSV File")); ?>
                <span class="small" style="color: #999">
                    <?php echo t('Only select a file to update your table rates'); ?>
                </span>
                <?php  echo $fileUpload->file('csvFile', 'csvFile', t('Choose a csv'), null, array('fKeywords'=>'','fExtension' => 'csv')); ?>
            <?php }else{
              echo '<p><br/><strong>';
                echo t('You need to add the method first. After adding, edit the shipping method to add the csv file.');
              echo '</strong></p>';
            } ?>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-12">
        <?php
        if(!empty($smtm->getCsvFile()) && $smtm->getCsvFile() != 0){
            $f = File::getByID($smtm->getCsvFile());
            $fv = $f->getRecentVersion();
            echo '<a href="'.$fv->getForceDownloadURL().'" class="btn btn-primary">'.t('Download current CSV file').'</a><br/>';
        }
        echo '<p class="small">';
          echo t('To create a csv file compatible with this package, you should create a csv file which has the same structure as a Magento Table rate.<br/>');
          echo '<strong>';
            echo t('Important: Use two letter codes to generate a csv file.');
          echo '</strong><br/>';
          echo t('For an example go to ');
          echo '<a target="_blank" href="https://www.elgentos.nl/tablerates/?twoLetterCodes">';
            echo t('Magento table rate generator');
          echo '</a>';
        echo '</p>';
        ?>
    </div>
</div>
