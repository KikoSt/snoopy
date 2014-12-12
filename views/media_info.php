<div id="swf_content">
    <div id="swfbox">
        <div class="preview_header">
            <h2>preview</h2>
            <?php echo $fileInfo->getHtml(); ?>
        </div>
    </div>
    <div id="infobox" class="<?php echo $fileInfo->classes['infobox']; ?>">
        <div class="prop_header">
            <h2>properties</h2>
        </div>
        <!-- swf info fields -->
        <?php foreach($fileInfo->fields AS $curField): ?>
        <div class="info<?php echo ($fileInfo->recommendations{$curField} != '' ? ' tooltip' : ''); echo ' ' . $fileInfo->classes[$curField]; ?>"
             id="<?php echo $curField; ?>"
             title="<?php echo $fileInfo->recommendations[$curField]; ?>"
             data-content="<?php echo nl2br($fileInfo->recommendations[$curField]); ?>"
            >
            <span class="infolabel"><?php echo $fileInfo->labels[$curField]; ?>:</span>
            <span><?php echo $fileInfo->{$curField}; ?> <?php echo $fileInfo->units[$curField]; ?></span>
        </div>
        <?php endforeach; ?>
        <!-- end swf info fields -->
    </div>
</div>
<script>
    // if js is enabled, jquery tooltips are used, no title attribute is required and it would
    // actually break the design being additionally displayed. Thus we remove it entirely here.
    $('.info').removeAttr('title');
    $('.tooltip').webuiPopover({trigger:'hover'});
</script>
