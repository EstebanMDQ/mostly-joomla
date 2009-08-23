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
                $t['html_req']='<nobr>&nbsp;&nbsp;<span class="requiredBlock">&nbsp;</span></nobr>';
                //$t['caption'] .= ' *';
            }else{
                $t['html_req']='<nobr>&nbsp;&nbsp;<span class="optionalBlock">&nbsp;</span></nobr>';
            }
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
    if($flag_use_tabs && preg_match('|(<[^>]+ name="xF_CancelButton"[^>]+>)|Ui',$t,$r)) {
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
?>
<link rel="stylesheet" href="<?php echo QFORMS_URI_TEMPLATES; ?>xforms.template2.css" />
<script type="text/javascript" src="<?php echo QFORMS_URI_TEMPLATES; ?>prototype.js"></script>
<script type="text/javascript" src="<?php echo QFORMS_URI_TEMPLATES; ?>gui.js"></script>
<script type="text/javascript" src="<?php echo QFORMS_URI_TEMPLATES; ?>xforms.template2.js"></script>

<?php if($flag_use_tabs) { ?>
    <div id="tabs2-tabs" class="tabSheets" >

    <?php if($button_cancel) { ?>
        <div style="border: none; background-color:none; float: left; width: 100px;"><?php echo $button_cancel; ?></div>
    <?php }/*if($button_cancel)*/ ?>
    <ul id="tabs2" class="tabSheetSelector">
    <li class="firstTab"><a href="#" onclick="return event.returnValue=false;" style="font-weight: bold;"><?php echo $this->main_tabsheet ?></a></li>
    <?php
    foreach( $this->setof_tabsheets as $caption=>$url ){
        $url=LWUtils::SITE_URL(QForms::x_replacer($url, '@@', array_map(create_function('$e','return urlencode($e);'),@$this->abm_data[0]) ));
        echo "<li><a href=\"".htmlspecialchars($url)."\" onclick=\"return event.returnValue=false;\" accesskey=\"$caption\">$caption</a></li>";
    }
     ?>
    </ul><!--br class="clear" /-->

    <div class="tabSheet" >
<?php }/*if(!empty($this->setof_tabsheets)) {*/ ?>

<form id="XFABMForm" method="post" action="<?php echo $abm_url; ?>" onsubmit="if(self.xF_Validate) return self.xF_Validate(this);" enctype="multipart/form-data">
<?php $this->evtBeforeTable(); ?>

<?php echo $extra_html; ?>
<table class="xfABMFormTable">
<tr><td class="xfABMFormTitle">
    <?php if($data_cells && $actions) { ?>

<table width="100%" style="border-top: 5px solid #006699;">
<tr><td nowrap="nowrap" valign="top" align="left" width="300"><b><?php echo $title; ?><?php if($subtitle) { ?> - <?php echo $subtitle; ?><?php }/*if($subtitle*/ ?></b></td>
    <td width="100%" align="left"><?php if($actions) { ?><?php echo $actions; ?><?php }/*if($actions*/ ?></td>
</td></tr></table>

    <?php }/*if($data_cells && $actions*/ ?>

</td></tr>
<tr><td><?php $this->evtBeforeData(); ?></td></tr>

    <tr><td id="xfABMErrors"><?php echo $errors; ?></td></tr>
<tr><td align="center">
    <?php if($data_cells) { ?>

        <?php if( count($fieldgroups)>1 ) { ?>
            <ul id="tabs1" class="tabSheetSelector">
            <?php
            foreach( $fieldgroups as $group ){
                echo "<li><a href=\"#\" onclick=\"return event.returnValue=false;\" accesskey=\"".substr(trim($group),0,1)."\">$group</a></li>";
            }
            ?>
            </ul><br class="clear" />

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
                        <th><label title="<?php echo $cell['caption']; ?>" for="<?php echo $cell['id']; ?>" <?php if(!empty($cell['required'])) echo 'class="required"'; ?>><?php echo $cell['caption']; ?></label><?php echo $cell['html_req']; ?></th>
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

        <?php }else{/*if($fieldgroups*/ ?>

            <table width="100%">

            <?php if(!empty($grouped_cells)) { ?>
                <?php foreach($grouped_cells as $group=>$sets) { ?>
                    <tr><td colspan="4" class="groupDivision">
                        <div style="width: 75%; float: left;"><?php echo htmlspecialchars($group); ?></div>
                        <?php if($as_static==QFORMS_RENDER_CONTROL) { ?>
                        <div style="width: 18%; float: right; font-size:7pt; text-align: right; background-color: white; color: black; font-weight: normal; line-height: 16px;"><nobr><span class="requiredBlock">&nbsp;</span> = Required information &nbsp; &nbsp; </nobr></div>
                        <?php }/*if($as_static==QFORMS_RENDER_CONTROL) {*/ ?>
                    </td></tr>

                    <tr>
                    <?php foreach($sets[1] as $idx=>$rows_1) { ?>
                        <tr class="rowed">
                        <th><label for="<?php echo $sets[1][$idx]['id']; ?>"><?php echo $sets[1][$idx]['caption']; ?></label><?php echo $sets[1][$idx]['html_req']; ?></th>
                        <td<?php if(!empty($sets[1][$idx]['class'])) echo " class=\"".$sets[1][$idx]['class']."\""; ?> <?php if($sets[1][$idx]['name']=='notes') echo " colspan=\"3\"" ; ?> >
                            <?php echo $sets[1][$idx]['html']; ?>
                            <?php if($sets[1][$idx]['description']) { ?> - <?php echo $sets[1][$idx]['description']; ?><?php }/*if($sets[1][$idx]['description*/ ?></td>
                        <?php if(@$sets[2][$idx]) { ?>
                            <th><label for="<?php echo $sets[2][$idx]['id']; ?>"><?php echo $sets[2][$idx]['caption']; ?></label><?php echo $sets[2][$idx]['html_req']; ?></th>
                            <td<?php if(!empty($sets[2][$idx]['class'])) echo " class=\"".$sets[2][$idx]['class']."\""; ?>><?php echo $sets[2][$idx]['html']; ?>
                                <?php if($sets[2][$idx]['description']) { ?> - <?php echo $sets[2][$idx]['description']; ?><?php }/*if($sets[2][$idx]['description*/ ?></td>
                        <?php }elseif($sets[1][$idx]['name']!='notes'){/*if($sets[2][$idx]) {*/ ?>
                            <th>&nbsp;</th>
                            <td>&nbsp;</td>
                        <?php }/*if($sets[2][$idx]) {*/ ?>
                        </tr>
                    <?php }/*foreach($sets[1] as $idx=>$rows_1) {*/ ?>
                <?php }/*foreach(array_keys($grouped_cells) as $group) {*/ ?>
            <?php }else{/*if(!empty($grouped_cells)) {*/ ?>
                <?php foreach($data_cells as $key=>$cell) { ?>
                    <?php if(!empty($cell['group']) && $cell['group']!=@$old_group) { $old_group=$cell['group']; ?>
                        <tr><td colspan="2" class="groupDivision">
                            <div style="width: 75%; float: left;"><?php echo htmlspecialchars($cell['group']); ?></div>
                            <?php if($as_static==QFORMS_RENDER_CONTROL) { ?>
                            <div style="width: 18%; float: right; font-size:7pt; text-align: right; background-color: white; color: black; font-weight: normal; line-height: 16px;"><nobr><span class="requiredBlock">&nbsp;</span> = Required information &nbsp; &nbsp; </nobr></div>
                            <?php }/*if($as_static==QFORMS_RENDER_CONTROL) {*/ ?>
                        </td></tr>
                    <?php }/*if(!empty($cell['group*/ ?>
                    <tr class="rowed">
                    <th><label for="<?php echo $cell['id']; ?>"><?php echo $cell['caption']; ?><label><?php echo $cell['html_req']; ?></th>
                    <td<?php if(!empty($cell['class'])) echo " class=\"$cell[class]\""; ?>><?php echo $cell['html']; ?>&nbsp;
                        <?php if($cell['description']) { ?> - <?php echo $cell['description']; ?><?php }/*if($cell['description*/ ?></td>
                    </tr>
                <?php }/*foreach($data_filters*/ ?>
            <?php }/*if(!empty($grouped_cells)) {*/ ?>

            </table>

    <?php }/*if($fieldgroups*/ ?>

    <?php }/*if($data_cells*/ ?>
</td></tr>

<tr>
<tr><td colspan="0">
    <table width="100%" style="border-bottom: 5px solid #006699;">
    <tr><td nowrap="nowrap" valign="top" align="left" width="300">&nbsp;</td>
        <td width="100%" align="left"><?php if($actions) { ?><?php echo $actions; ?><?php }/*if($actions*/ ?></td>
    </td></tr></table>
<td></tr>

</table>

<?php $this->evtAfterTable(); ?>
<?php echo $data_hidden; ?>
</form>

<?php if(!empty($this->setof_tabsheets) && in_array($this->currAction,array(QFORMS_ACTION_UPDATE,QFORMS_ACTION_VIEW,QFORMS_ACTION_DELETE)) && !empty($this->abm_data[0]) ) { ?>

    </div>
    <script type="text/javascript">
    function    df(d) { return (d.height?d.height:(d.body?(d.body.scrollHeight?d.body.scrollHeight:(d.body.scrollHeight?d.body.offsetHeight:null)):null)); }
    function    tabs2_tabdisplay(frname, url) {
        var ifr=document.getElementById(frname);
        recvIFrameHeight = function(d) { ifr.style.height=''+(200+df(d))+'px'; }
        if( ifr.src.indexOf(url)<0 ) ifr.src=url;
        if( ifr.contentWindow.document && df(ifr.contentWindow.document) )
            ifr.style.height = ''+(200+df(ifr.contentWindow.document))+'px';
        else
            ifr.style.height = '100%';
    }
    </script>

    <?php $idx=1; foreach( $this->setof_tabsheets as $caption=>$url ){
        $url=LWUtils::SITE_URL(QForms::x_replacer($url, '@@', array_map(create_function('$e','return urlencode($e);'),@$this->abm_data[0]) ));
        ?>
        <div class="tabSheet" ontabdisplay="tabs2_tabdisplay('_html_tabs2_<?php echo $idx; ?>','<?php echo $url; ?>')">
        <iframe id="_html_tabs2_<?php echo $idx; ?>" name="_html_tabs2_<?php echo $idx; ?>"
        src="about:blank"
        width="100%" height="50" style="border: none;" >IFRAMES REQUIRED</iframe>
        </div>
    <?php $idx++; }/*foreach( $this->setof_tabsheets as $caption=>$url ){*/ ?>

    </div>
<?php }/*if(!empty($this->setof_tabsheets)) {*/ ?>

<?php if( false && $this->dataobject && $this->dataobject->id ) {
    $t =& $this->dataobject->metadata();
    $a = new Audit();
    $a = $a->select( array(
        array('record_table','=',$t['table']),
        array('record_id','=',$this->dataobject->id)),
        array('creation_date*'), array(0,1) );
    if($a) {
        $a=reset($a);
        printf('<div style="text-align: right;"><small>Last changed by: %s, #%s, on %s <a href="index.php?V=generic.inspector&giCLASS=Audit&xFF_record_table=%s&xFF_record_id=%s" target="_top">view recent changes</a></small></div>',
            $a->name, $a->id_user, date('Y-m-d H:i:s', $a->creation_date),  $t['table'], $this->dataobject->id );
    }else{
        printf('<div style="text-align: right;"><small><a href="index.php?V=generic.inspector&giCLASS=Audit&xFF_record_table=%s&xFF_record_id=%s" target="_top">view recent changes</a></small></div>',
            $t['table'], $this->dataobject->id );
    }
}/*if( $this->dataobject && $this->dataobject->id ) {*/ ?>

<script type="text/javascript">
xforms_form_init();
tabSheet('tabs1','tabs1-tabs');
tabSheet('tabs2','tabs2-tabs');
</script>
