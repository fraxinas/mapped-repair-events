<?php
/**
 * @param string field (eg Post.image)
 * @param objectType
 * @param string image
 * @param int uid
 * @parms string label
 */
 
if (empty($image)) {
    $linkSrcForOverlay = '';
    $linkTagForForm = '<i class="fa fa-camera fa-border"></i>';
} else {
    $linkSrcForOverlay = $this->Html->getThumbs300Image($image, $objectType);
    if ($field == 'Users.image') {
        $linkSrcForOverlay = $this->Html->getThumbs150Image($image, $objectType);
    }
    $linkTagForForm = '<img src="' . $this->Html->getThumbs150Image($image, $objectType) . '" />';
}

echo $this->element('upload/base', [
    'type' => 'single',
    'objectType' => $objectType,
    'uid' => $uid,
    'linkSrcForOverlay' => $linkSrcForOverlay
]);

?>

<div class="input" style="width: 100%;">
    <label style="vertical-align: top;"><?php echo $label; ?></label>
    <?php
    if ($uid === null) {
        echo 'Um ein Logo hochzuladen, bitte zuerst speichern.';   
    } else {
        echo $this->Html->link(
            $linkTagForForm,
            'javascript:void(0);',
            [
                'class' => 'add-image-button single',
                'title' => 'Neues Bild hochladen bzw. austauschen',
                'escape' => false
            ]
        );
        echo $this->Form->hidden($field, ['class' => 'image-field', 'value' => !empty($image) ? $image : '']);
    }
    ?>
</div>
