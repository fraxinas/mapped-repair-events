<?php
use Cake\Core\Configure;

    if ($isEditMode) {
        $this->element('addScript', array('script' =>
            JS_NAMESPACE.".Helper.doCurrentlyUpdatedActions(".$isCurrentlyUpdated.");"
        ));
    }
    if ($this->request->getSession()->read('isMobile')) {
        $this->element('addScript', ['script' =>
            JS_NAMESPACE.".MobileFrontend.putSaveAndCancelButtonToEndOfForm();
        "]);
    }
    $this->element('addScript', array('script' => 
        JS_NAMESPACE.".Helper.bindCancelButton(".$event->uid.");".
        JS_NAMESPACE.".Helper.layoutEditButtons();".
        JS_NAMESPACE.".Helper.initCustomCoordinatesCheckbox('#events-use-custom-coordinates');
        $('.edit .apply-workshop-data-button').on('click', function() { ".
            JS_NAMESPACE.".Helper.bindApplyWorkshopButton($(this));
        });
            
    "));
    
    echo $this->element('datepicker', array('fields' => array('events-datumstart')));
?>


<div class="admin edit">
	<div class="edit">
   
       <?php echo $this->element('heading', ['first' => $metaTags['title']]); ?>
       
       <?php
        echo $this->Form->create($event, [
            'novalidate' => 'novalidate',
            'url' => $editFormUrl,
            'id' => 'eventEditForm'
        ]);
        echo $this->Form->hidden('referer', ['value' => $referer]);
        
        if (!$isEditMode && isset($preselectedWorkshopUid)) {
            echo $this->Form->control('Events.workshop_uid', array(
                'type' => 'select',
                'options' => $workshopsForDropdown,
                'default' => $preselectedWorkshopUid,
                'empty' => '== Initiative auswählen ==',
                'label' => __('Workshop')
            ));
        }
        
        if (!empty($event->workshop->name)) {
            echo '<div class="input">';
                echo '<label>' . Configure::read('AppConfig.initiativeNameSingular').'</label>';
                echo $event->workshop->name;
            echo '</div>';
        }
        
        if ($isEditMode) {
            echo $this->element('hint', [
                'content' => 'Die Logo-Datei sollte rechteckig und mindestens 300 Pixel breit sein.'
            ]);
        }
        echo $this->element('upload/single', [
             'field' => 'Events.image'
            ,'objectType'   => 'events'
            ,'image' => $event->image
            ,'uid' => $event->uid
            ,'label' => 'Terminbild'
        ]).'<br />';
        
        echo '<div class="date-time-wrapper">';
            echo $this->Form->control('Events.datumstart',  array('type' => 'text', 'label' => __('Add Event: Start Date'), 'value' => $event->datumstart->i18nFormat(Configure::read('DateFormat.de.DateLong2'))));
            echo $this->Form->control('Events.uhrzeitstart', array('type' => 'time', 'label' => __('Add Event: Start Time'), 'timeFormat' => 24));
            echo $this->Form->control('Events.uhrzeitend', array('type' => 'time', 'label' => __('Add Event: End Time'), 'timeFormat' => 24));
        echo '</div>';
        echo '<div class="sc"></div>';

        echo $this->Form->control('Events.eventbeschreibung', array('label' => __('Add Event: event description'))).'<br />';
        
        if (!$isEditMode && isset($preselectedWorkshopUid)) {
            echo '<br /><br />';
            echo '<a class="button apply-workshop-data-button" href="javascript:void(0);">Profildaten übernehmen</a><img class="ajaxLoader" src="/img/ajax-loader.gif" width="32" height="32" />';
        }
        
        echo $this->Form->control('Events.veranstaltungsort', array('div' => 'input text long', 'type' => 'text','label' => __('Add Event: Venue'))).'<br />';
        
        echo $this->Form->control('Events.strasse', array('type' => 'text','label' => __('Add Event: Street and Number'))).'<br />';
        
        echo $this->Form->control('Events.zip', array('type' => 'text','label' => __('Add Event: Zip Code'))).'<br />';
        echo $this->Form->control('Events.ort', array('type' => 'text','label' => __('Add Event: City'))).'<br />';
        echo $this->Form->control('Events.land', array('type' => 'text','label' => __('Add Event: Country'))).'<br />';
        
        echo $this->Form->control('Events.use_custom_coordinates', ['type' => 'checkbox', 'label' => 'Koordinaten selbst festlegen?']).'<br />';
        echo '<div class="custom-coordinates-wrapper">';
            echo $this->Form->control('Events.lat', ['label' => 'Breite (Lat)', 'type' => 'text']).'<br />';
            echo $this->Form->control('Events.lng', ['label' => 'Länge (Long)', 'type' => 'text']).'<br />';
        echo '</div>';
        
        echo '<br />';
        echo '<div class="categories-checkbox-wrapper">';
            echo '<b>Reparaturbereiche</b>';
                echo $this->Form->control('Events.categories._ids', [
                    'multiple' => 'checkbox',
                    'label' => false
                ]);
            echo '</div>
        <div class="sc"></div>'; 

        if ($isEditMode) {
            echo $this->Form->control('Events.renotify', array('type' => 'checkbox', 'label' => 'Ich habe diesen Termin überarbeitet und möchte, dass alle Interessenten darüber nochmals informiert werden.')).'<br />';
        }
        
        echo $this->element('hint', [
            'content' => 'Achtung! Wenn der Status des Termins auf „online“ gesetzt ist und du auf speichern klickst, dann ist der Termin sofort sichtbar und alle Interessenten, die deiner ' . Configure::read('AppConfig.initiativeNameSingular').' folgen ("Ich möchte über anstehende Veranstaltungen dieser Initiative per E-Mail informiert werden") werden informiert.'
        ]);
        echo $this->Form->control('Events.status', ['type' => 'select', 'options' => Configure::read('AppConfig.status')]).'<br />';
        
        if (!$useDefaultValidation) {
            echo $this->element('metatagsFormfields', ['entity' => 'Events']);
        }
    ?>
    
  	<?php echo $this->element('cancelAndSaveButton', ['saveLabel' => 'Speichern']); ?>
    <div class="sc"></div>
    
    </div>
</div>

<?php echo $this->Form->end(); ?>