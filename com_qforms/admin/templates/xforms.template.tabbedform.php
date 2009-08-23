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

$data_filters   = array();
$data_cells     = array();
$errors         = array();
$actions        = array();

$visible_fields     = $this->GetFieldList( 'visible' );

/**
* Render data
**/
$as_static = (($this->currAction==XFORMS_ACTION_UPDATE||$this->currAction==XFORMS_ACTION_INSERT)?XFORMS_RENDER_CONTROL:XFORMS_RENDER_STATIC);
$fieldgroups = $data_cells = array();
foreach($visible_fields as $name) {
    if( $t = $this->RenderField($name, 0, $as_static) ) {
        if( ($this->currAction==XFORMS_ACTION_UPDATE||$this->currAction==XFORMS_ACTION_INSERT) && $this->abm_fields[$name]->is_required) {
            $t['required'] = 1;
            $t['caption'] .= ' *';
        }
        $t['group']=preg_replace('/^[0-9][0-9]?_/','', trim($t['group']));
        if(!$t['group']) $t['group'] = ' &nbsp; ';
        $fieldgroups[strtolower($t['group'])] = $t['group'];
        $data_cells[$name] = $t;
        //printf("%s | %s | %s <br>\n",$name, $t['caption'], $t['group']);
    }
}
$t=@reset(array_keys($fieldgroups));

if($t==' &nbsp; ' && count($fieldgroups)==1)
	$fieldgroups=array();

/**
* Render actions
**/
if($t=$this->htmlFormActions()) {
    $actions = $t;
}

/**
* Render errors, urls and messages
**/
$errors     = implode('<br/>', $this->abm_errors);
$abm_url    = $this->abm_url;
$title      = htmlspecialchars($this->abm_title);
$subtitle   = htmlspecialchars($this->abm_subtitle);
$extra_html = $this->htmlFormExtras();
$extra_html .= $this->ValidateRecJS();

?>
<link rel="stylesheet" href="<?php echo XFORMS_URI_TEMPLATES; ?>xforms.template.css" />
<script type="text/javascript" src="<?php echo XFORMS_URI_TEMPLATES; ?>cssQuery-p.js"></script>
<script type="text/javascript" src="<?php echo XFORMS_URI_TEMPLATES; ?>prototype.js"></script>
<script type="text/javascript" src="<?php echo XFORMS_URI_TEMPLATES; ?>xforms.base.js"></script>
<script type="text/javascript">window.xf_host='http://<?php echo $_SERVER['HTTP_HOST']; ?>/';</script>

<form id="XFABMForm" name="XFABMForm" method="post" action="<?php printf('%s',$abm_url); ?>" onsubmit="if(self.xF_Validate) return self.xF_Validate(this);" enctype="multipart/form-data">

<script type="text/javascript">document.title +=' - <?php  printf('%s',$title); ?><?php if($subtitle) { ?> - <?php echo $subtitle; ?><?php }/*if($subtitle*/ ?>';</script>

<?php if($this->abm_title) { ?>
    <div id="xfABMMainTitleFr"><div id="xfABMMainTitle"><?php  printf('%s',$title); ?><?php if($subtitle) { ?> - <?php echo $subtitle; ?><?php }/*if($subtitle*/ ?>&nbsp;
    <?php if($data_cells && $actions) { ?>
        <div class="xfABMMainControls"><div id="xfButtonsFrame"><?php printf('%s', $actions); ?></div></div>
    <?php }/*if($data_cells && $actions*/ ?>
    </div></div>
<?php }/*if($this->abm_title) {*/ ?>

<table class="xfABMFormTable" style="border: none;">
<!--
    <?php if($this->abm_title) { ?>
    <tr><td id="xfABMMainTitle1"><div id="xfABMMainTitle"><?php printf('%s',$title); ?><?php if($subtitle) { ?> - <?php echo $subtitle; ?><?php }/*if($subtitle*/ ?></div></td></tr>
    <?php }/*if($this->abm_title) {*/ ?>
-->
    <tr><td><?php $this->evtBeforeData(); ?></td></tr>
<!--
    <?php if($data_cells && $actions) { ?>
        <tr><td class="xfABMMainControls"><div id="xfButtonsFrame"><?php printf('%s',$actions); ?></div></td></tr>
    <?php }/*if($data_cells && $actions*/ ?>
-->
    <?php printf('%s',$extra_html); ?>
    <?php if($errors) { ?>
        <tr><td>
            <p class="errors"><?php printf('%s',$errors); ?></p>
            <script type="text/javascript">alert('<?php echo addslashes(strip_tags(str_replace('<br/>',"\\n",$errors))); ?>');</script>
        </td></tr>
    <?php }/*if($errors*/ ?>
</table>

<?php $this->evtBeforeTable(); ?>

<?php if($fieldgroups) { ?>
<ul id="tabs1" class="tabSheetSelector">
<?php
foreach( $fieldgroups as $group ){
    echo "<li><a href=\"#\" onclick=\"return event.returnValue=false;\" accesskey=\"".substr(trim($group),0,1)."\">$group</a></li>";
}
?>
</ul><br class="clear" />
<?php }/*if($fieldgroups*/ ?>


<div id="tabs1-tabs" class="tabSheets">

    <?php if($data_cells) { $old_group=-1; ?>

        <?php foreach($data_cells as $key=>$cell) { ?>

            <?php if( $cell['group']!=$old_group) { if( $old_group!=-1 ) echo '</table></div><!-- tab -->'; ?>

            <?php if($cell['group']) { ?><div class="tabSheet"><?php } ?>

				<table width="100%" class="xfABMFormTable" >
            <?php }/*if(!empty($cell['group*/ ?>

			<tr class="xfABMFormFieldRow">
			<?php if( $cell['type']=='statichtml' ) { ?>
				<th colspan="2" <?php if(!empty($cell['class'])) echo " class=\"$cell[class]\""; ?>><?php echo $cell['html']; ?>
					<?php if($cell['description']) { ?><div class="xfABMFormTableDescription"><?php echo $cell['description']; ?></div><?php }/*if($cell['description*/ ?>
                </th>
			<?php }elseif( $cell['type']=='buttonascheckbox' ) { ?>
				<td colspan="2" <?php if(!empty($cell['class'])) echo " class=\"$cell[class]\""; ?>><?php echo $cell['html']; ?>
					<?php if($cell['description']) { ?><div class="xfABMFormTableDescription"><?php echo $cell['description']; ?></div><?php }/*if($cell['description*/ ?>
                </td>
			<?php }else{ ?>
				<th><label title="<?php echo $cell['caption']; ?>" for="<?php echo $cell['id']; ?>" <?php if(!empty($cell['required'])) echo 'class="required"'; ?>><?php echo $cell['caption']; ?></label><?php if( in_array('as_required', $cell['_TAGS'])!==false) echo ' *'; ?></th>
				<td<?php if(!empty($cell['class'])) echo " class=\"$cell[class]\""; ?>><?php echo $cell['html']; ?>
					<?php if($cell['description']) { ?><div class="xfABMFormTableDescription"><?php echo $cell['description']; ?></div><?php }/*if($cell['description*/ ?>
                </td>
			<?php } ?>
            </tr>

    <?php $old_group=$cell['group']; }/*foreach($data_cells*/ ?>

    </table>
    <?php if($cell['group']) { ?></div><!-- group --><?php } ?>

    <?php if($old_group) { ?></div><!-- last tab --><?php } ?>

    <?php }/*if($data_cells*/ ?>

</div><!-- tabs1-tabs -->

<?php $this->evtAfterTable(); ?>
</form>
<script type="text/javascript">
xforms_form_init();
tabSheet('tabs1','tabs1-tabs');
</script>
