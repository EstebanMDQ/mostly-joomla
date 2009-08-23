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
foreach($visible_fields as $name) {
    if( $t = $this->RenderField($name, 0, XFORMS_RENDER_STATIC) ) {
        if(!empty($t['caption'])) {
            $fname  = htmlspecialchars($name);
            $caption= $t['caption'];
            $data_orderby[ $fname ] = $caption;
            $data_orderby[ $fname."*" ] = $caption." (desc)";
            $data_headers[] = $caption;
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
<tr><td class="jsABMainTitle"><?php echo $title; ?><?php if($subtitle) { ?> - <?php echo $subtitle; ?><?php }/*if($subtitle*/ ?></td></tr>
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
            <td<?php if(!empty($cell['class'])) echo " class=\"$cell[class]\""; ?>><?php echo $cell['html']; ?></td>
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
<?php
/*
EXPORTACION CSV
$data_headers   = array();
$data_cells     = array();

$visible_fields     = $this->GetFieldList( 'visible' );

foreach($this->abm_data as $rid=>$rec) {
    foreach($visible_fields as $name) {
        if( $t = $this->RenderField($name, $rid, XFORMS_RENDER_STATIC) ) {
            if($t['html']=='&nbsp;') $t['html']='';
            $data_cells[$rid][$name] = $t['html'];
        }
    }
}
foreach($visible_fields as $name) {
    if( $t = $this->RenderField($name, 0, XFORMS_RENDER_STATIC) ) {
        if(!empty($t['caption'])) {
            $data_headers[] = $t['caption'];
        }
    }
}

echo implode(";", $data_headers),"\r\n";
foreach($data_cells as $rec)
    echo implode(";", $rec),"\r\n";
*/
?>