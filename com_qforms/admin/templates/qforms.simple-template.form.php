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
$data_hidden    = '';

// ordeno la lista
uasort($this->abm_fields,create_function('&$a,&$b','
        $al = sprintf(\'%s |%4.4d\',strtolower($a->field_group), $a->field_order);
        $bl = sprintf(\'%s |%4.4d\',strtolower($b->field_group), $b->field_order);
        if($al == $bl) return 0;
        return (($al > $bl) ? +1 : -1);'));

$visible_fields     = $this->GetFieldList( 'visible' );
$hidden_fields      = $this->GetFieldList( 'hidden' );

/**
* Render data
**/
$as_static = (($this->currAction==QFORMS_ACTION_UPDATE||$this->currAction==QFORMS_ACTION_INSERT)?QFORMS_RENDER_CONTROL:QFORMS_RENDER_STATIC);
$data_cells=array();
$grouped_cells=array();
$fieldgroups = array();
$crow=0;
foreach($visible_fields as $idx=>$name) {
    if( $t = $this->RenderField($name, 0, $as_static) ) {
        $t['html_req']='';
        if($this->currAction==QFORMS_ACTION_UPDATE||$this->currAction==QFORMS_ACTION_INSERT) {
            if( $as_static==QFORMS_RENDER_CONTROL && ($this->abm_fields[$name]->is_required || in_array('as_required', $this->abm_fields[$name]->_TAGS)) ) {
                //$t['html_req']='<nobr>&nbsp;&nbsp;<span class="requiredBlock">&nbsp;</span></nobr>';
                $t['caption'] .= ' *';
            }/*else{
                $t['html_req']='<nobr>&nbsp;&nbsp;<span class="optionalBlock">&nbsp;</span></nobr>';
            }*/
        }

        $t['group']=preg_replace('/^[0-9][0-9]?_/','', trim($t['group']));
        if(!$t['group']) $t['group'] = ' ';

        $fieldgroups[strtolower($t['group'])] = $t['group'];

        $data_cells[$name] = $t;

        $g=$t['group'];
        if(empty($grouped_cells[$g])) { $grouped_cells[$g]=array(); }
        $grouped_cells[$g][$name] = $t;
    }
}

foreach($hidden_fields as $name) {
    if( $t=$this->RenderField( $name, 0, QFORMS_RENDER_HIDDEN) )
       $data_hidden .= $t['html'];
}

if(empty($this->show_groups_as_tabs)) $fieldgroups=array();

#if(count($data_cells)<20&&empty($this->flag_force_twocols))
$grouped_cells=array();

foreach($grouped_cells as $group=>$set) {
    $grouped_cells[$group]=array(1=>array(),2=>array());
    $counter=0;

    foreach($set as $name=>$item) {

        //Hack para agregar un campo (como prefixname) a otro
        if( in_array(strtolower($item['name']), array('prefixname','position1')) ) {
            $last_item=$item;
            continue;
        }elseif(!empty($last_item)) {
            $item['html'] = '<nobr>'.$last_item['html'] . $item['html'].'</nobr>';
            $grouped_cells[$group][ ($counter%2)?2:1 ][]=$item;
            $last_item=null;
            $counter++;
            continue;
        }

        $grouped_cells[$group][ ($counter%2)?2:1 ][]=$item;
        $counter++;
    }

}

/**
* Render actions
**/
$flag_use_tabs = (!empty($this->setof_tabsheets) && in_array($this->currAction,array(QFORMS_ACTION_UPDATE,QFORMS_ACTION_VIEW,QFORMS_ACTION_DELETE)) && !empty($this->abm_data[0]) && empty($_GET['NFR']) );
$button_cancel = '';
if($t=$this->htmlFormActions()) {
    # HACK, para quitar el botón de cancelar del set de acciones del form
    $r=array();
    if($flag_use_tabs && preg_match('|(<[^>]+ name="'.$this->abm_prefix.'CancelButton"[^>]+>)|Ui',$t,$r)) {
        $t=str_replace($r[1],'',$t);
        $button_cancel=$r[1];
    }
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




/* 
 * TODO: implementar la vista de el form con tabs  
 
if( false && $flag_use_tabs) { ?>
<scrtip type="text/javascript">
jQuery(document).ready(function(){
	jQuery(".tabSheets > ul").tabs({ fx: {opacity: 'toggle'}});
});
</script>
<?php if($button_cancel) { ?>
    <div style="border: none; background-color:none; float: left; width: 100px;"><?php echo $button_cancel; ?></div>
<?php }/*if($button_cancel)
    
<div class="tabSheets" >

    <ul>
    	<li ><a href="#tab1" ><?php echo $this->main_tabsheet ?></a></li>
    <?php
    foreach( $this->setof_tabsheets as $caption=>$url ){
        $url=LWUtils::SITE_URL(QForms::x_replacer($url, '@@', array_map(create_function('$e','return urlencode($e);'),@$this->abm_data[0]) ));
        echo "<li><a href=\"".htmlspecialchars($url)."\" onclick=\"return event.returnValue=false;\" accesskey=\"$caption\">$caption</a></li>";
    }
     ?>
    </ul><!--br class="clear" /-->

    <div class="tabSheet" >
<?php }/* if($flag_use_tabs) */ ?>

<?php include_jquery(); ?>
<script type="text/javascript" charset="UTF-8" src="<?php echo QFORMS_URI_TEMPLATES; ?>qforms.simple-template.js"></script>

<div class="qforms">
<h2><?php echo $title; ?></h2>
<?php if($subtitle) { ?>
	<h3><?php echo $subtitle; ?></h3>
<?php }/*if($subtitle*/ ?>

<div class="form">

<form id="XFABMForm" method="post" action="<?php echo $abm_url; ?>" onsubmit="if(self.<?php echo $this->abm_prefix; ?>Validate) return self.<?php echo $this->abm_prefix; ?>Validate(this);" enctype="multipart/form-data">
<?php $this->evtBeforeTable(); ?>

<?php echo $extra_html; ?>

<?php
if($data_cells && $actions) {
	if($actions) { echo "<div class=\"actions\">$actions</div>"; }/*if($actions*/
}/*if($data_cells && $actions*/
?>

<?php $this->evtBeforeData(); ?>

<div id="errMsg" class="warning" <?php if( empty($errors) ) echo ' style="display: none;" ';?>><?php echo @$errors; ?></div>


<?php 
if($data_cells) { 
	$old_group=-1; 

    $field_counter = 0;
    foreach($data_cells as $key=>$cell) {
    	$field_counter ++;
        ?>

        <?php if( $cell['group']!=$old_group ){
			if( $old_group=!-1 ) {
				echo '</table>';
			}				
			if( $cell['group'] ) { ?>
				<h4><?php echo $cell['group']; ?></h4><table width="100%" class="table" >
			<?php 
			$old_group=$cell['group'];
			} /* if( $old_group=!-1 ) */ ?>


        <?php if($cell['group']!=$old_group && $old_group==-1) {  ?>
		<?php } ?>

            <table width="100%" class="table" >
        <?php }/*if(!empty($cell['group*/ ?>

        <tr <?php if(!empty($cell['class'])) echo "class=\"$cell[class]\""; ?>>
        <?php if( $cell['type']=='statichtml' ) { ?>
            <td colspan="2" <?php if(!empty($cell['class'])) echo "class=\"$cell[class]\""; ?>><?php echo $cell['html']; ?>
                <?php if($cell['description']) { ?><div class="description"><?php echo $cell['description']; ?></div><?php }/*if($cell['description*/ ?>
            </td>
        <?php }elseif( $cell['type']=='buttonascheckbox' ) { ?>
            <td <?php if(!empty($cell['class'])) echo "class=\"$cell[class]\""; ?> colspan="2" ><?php echo $cell['html']; ?>
                <?php if($cell['description']) { ?><div class="description"><?php echo $cell['description']; ?></div><?php }/*if($cell['description*/ ?>
            </td>
        <?php }else{ ?>
            <th <?php if(!empty($cell['class'])) echo "class=\"$cell[class]\""; ?>><label title="<?php echo $cell['caption']; ?>" for="<?php echo $cell['id']; ?>" <?php if(!empty($cell['required'])) echo 'class="required"'; ?>><?php echo $cell['caption']; ?></label><?php echo $cell['html_req']; ?></th>
            <td <?php if(!empty($cell['class'])) echo "class=\"$cell[class]\""; ?>><?php echo $cell['html']; ?>
                <?php if($cell['description']) { ?><div class="description"><?php echo $cell['description']; ?></div><?php }/*if($cell['description*/ ?>
            </td>
        <?php } ?>
        </tr>

	    <?php }/*foreach($data_cells*/ ?>
	
    </table>

    <?php }/*if($data_cells*/ ?>


	<?php if($actions) { echo "<div class=\"actions\">$actions</div>"; }/*if($actions*/ ?>


<?php $this->evtAfterTable(); ?>
<?php echo $data_hidden; ?>
</form>

</div>
<?php $this->evtAfterData(); ?>


<script type="text/javascript">
xforms_form_init();

</script>
</div>