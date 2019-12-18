<?php
use Cake\Core\Configure;

    // if ($isEditMode) {
    //     $this->element('addScript', array('script' =>
    //         JS_NAMESPACE.".Helper.doCurrentlyUpdatedActions(".$isCurrentlyUpdated.");"
    //     ));
    // }
    if ($this->request->getSession()->read('isMobile')) {
        $this->element('addScript', ['script' =>
            JS_NAMESPACE.".MobileFrontend.putSaveAndCancelButtonToEndOfForm();
        "]);
    }
    $this->element('addScript', array('script' => 
        JS_NAMESPACE.".Helper.bindCancelButton(".$workshop->uid.");".
        JS_NAMESPACE.".Helper.layoutEditButtons();"));
    echo $this->element('highlightNavi', ['main' => 'INITIATIVEN']);
?>

<div class="admin organize">

        <?php
        echo $this->Form->create($workshop, [
            'novalidate' => 'novalidate',
            'url' => $this->Html->urlWorkshopOrganize($workshop->uid),
            'id' => 'workshopEditForm'
        ]);
        echo $this->Form->hidden('referer', ['value' => $referer]);
        ?>
        <div class="organize">
       
           <?php echo $this->element('heading', ['first' => $metaTags['title']]); ?>
            
           <?php
            echo "Name der Initiative: ".$workshop->name;

            echo $this->Form->control('Orgatool.enabled', ['type' => 'checkbox', 'label' => 'Helfer automatisch einladen?']).'<br />';
            
            echo $this->Form->control('Orgatool.helper_invitation_days', array('label' => 'Tage vor dem nächsten Event einladen')).'<br />';

            echo $this->Form->control('Orgatool.helper_reminder_days', array('label' => 'Tage vor dem nächsten Event erinnern')).'<br />';
            
            if (!$useDefaultValidation) {
                echo $this->element('metatagsFormfields', ['entity' => 'Workshops']);
            }
          ?>
        
        <?php echo $this->element('cancelAndSaveButton', ['saveLabel' => 'Speichern']); ?>
        <div class="sc"></div>
    </div>
    
    <div class="ckeditor-edit">
      <?php
        echo $this->element('ckeditorEdit', [
            'value' => $orgatool->helper_invitation_text,
            'name' => 'Orgatool.helper_invitation_text',
            'uid' => $orgatool->uid,
            'objectType' => 'orgatool'
           ]);
      ?>
    </div>

    <div class="ckeditor-edit">
    <?php
      echo $this->element('ckeditorEdit', [
          'value' => $orgatool->helper_reminder_text,
          'name' => 'Orgatool.helper_reminder_text',
          'uid' => $orgatool->uid,
          'objectType' => 'orgatool'
         ]);
      ?>
    </div>

    <?php echo $this->Form->end(); ?>
  
</div>

<div class="sc"></div> <?php /* wegen ckeditor */ ?>