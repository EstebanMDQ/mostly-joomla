<?php
/*
 *    This file is part of QForms
 *
 *    qForms is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    qForms is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with qForms.  If not, see <http://www.gnu.org/licenses/>.
 */
/**
* Template
**/
$data_headers   = array();
$data_cells     = array();
$visible_fields     = $this->GetFieldList( 'visible' );

/**
* Render data
**/
foreach($this->abm_data as $rid=>$rec) {
    foreach($visible_fields as $name) {
        if( $t = $this->RenderField($name, $rid, XFORMS_RENDER_STATIC) ) {
            $data_cells[$rid][$name]=$t;
        }
    }
}

/**
* Render headers and list options
**/
$data_preheaders=array();
foreach($visible_fields as $name) {
    if( $t = $this->RenderField($name, 0, XFORMS_RENDER_STATIC) ) {
        if(!empty($t['caption'])) {
            $fname  = htmlspecialchars($name);
            $caption= $t['caption'];
            $data_orderby[ $fname ] = $caption;
            $data_orderby[ $fname."*" ] = $caption." (desc)";
            $data_headers[] = $caption;

            if($t['description'])
                $data_preheaders[] = sprintf("<b>%s</b>: %s", $t['caption'], $t['description']);
        }
    }
}

/**
* Render errors, urls, pagination and messages
**/
$title      = htmlspecialchars($this->abm_title);
$subtitle   = htmlspecialchars($this->abm_subtitle);
$extra_params = $this->htmlGetExtraParams();

?>
<table class="xfABMMainTable">
<tr><td class="xfABMainTitle" style="font-size: 12pt; font-weight: bold;"><?php echo $title; ?><?php if($subtitle) { ?> - <?php echo $subtitle; ?><?php }/*if($subtitle*/ ?></td></tr>
<?php if($data_preheaders) { ?>
<tr><td colspan="<?php echo count($data_cells); ?>" style="border: 1px solid black; ">
    <?php echo '<big>Referencia:</big><br><br>'; ?>
    <?php echo implode("<br>\n", $data_preheaders); ?>
</td></tr>
<?php }/*if($data_preheaders) {*/ ?>
<tr><td>
<?php if($data_cells) { ?>
    <table width="100%" border="1">
    <tr>
    <?php foreach($data_headers as $cid=>$cell) { ?>
        <th><?php echo $cell; ?></th>
    <?php }/*foreach($data_headers*/ ?>
    </tr>
    <?php foreach($data_cells as $rid=>$rec) { ?>
        <tr>
        <?php foreach($rec as $cid=>$cell) { ?>
            <td<?php if(!empty($cell['class'])) echo " class=\"$cell[class]\""; ?>><?php printf('%s',$cell['html']); ?></td>
        <?php }/*foreach($rec*/ ?>
        </tr>
    <?php }/*foreach($data_cells*/ ?>
    </table>
<?php }else{/*if($data_cells*/ ?>
    &nbsp;
<?php }/*if($data_cells*/ ?>
</td></tr>
</table>
<?php $this->evtAfterTable(); ?>
