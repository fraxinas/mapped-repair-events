<p style="margin:15px 0 5px 0;"><?php echo $label; ?></p>

<form id="list-search-form" action="<?php echo $baseUrl; ?>" accept-charset="UTF-8">
    <?php
        echo $this->Form->control('keyword', ['label' => '', 'value' => $keyword]);
        if (!empty($categories)) {
            echo $this->Form->hidden('categories', ['label' => '', 'value' => $categories]);
        }
    ?>
    <button type="submit" class="button filter"><?php echo __('Search'); ?></button>
    <?php if ($resetButton) { ?>
        <a href="<?php echo $baseUrl; ?>" class="button gray"><?php echo __('Clear'); ?></a>
    <?php } ?>

