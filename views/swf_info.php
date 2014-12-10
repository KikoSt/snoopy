<script>
    var flp = document.getElementById('flash');
    console.log(flp);
</script>
<div id="swf_content">
    <div id="swfbox">
        <div class="preview_header">
            <h2>preview</h2>
        </div>
        <object id="flash"
                width="<?php echo $swf->width; ?>"
                height="<?php echo $swf->height; ?>"
                data="<?php echo $swf->fname; ?>"
                type="application/x-shockwave-flash">
            <param name="src" value="<?php echo 'tmp/' . $swf->fname; ?>">
            <!-- Alternativinhalt -->
        </object>
    </div>
    <div id="infobox" class="<?php echo $classes['infobox']; ?>">
        <div class="prop_header">
            <h2>properties</h2>
        </div>
        <!-- swf info fields -->
        <?php foreach($fields AS $curField): ?>
        <div class="info <?php echo $classes[$curField]; ?>"
             id="<?php echo $curField; ?>"
             title="<?php echo $recommendations[$curField]; ?>">
            <span class="infolabel"><?php echo $labels[$curField]; ?>:</span>
            <span><?php echo $swf->{$curField}; ?> <?php echo $units[$curField]; ?></span>
        </div>
        <?php endforeach; ?>
        <!-- end swf info fields -->
    </div>
</div>
