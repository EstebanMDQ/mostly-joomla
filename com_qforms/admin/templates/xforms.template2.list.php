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
$data_orderby   = array();
$data_headers   = array();
$data_descs     = array();
$data_cells     = array();
$data_hidden    = '';
$errors         = array();
$actions_top    = '';
$actions_bottom = '';
$prevPage       = null;
$nextPage       = null;

// ordeno la lista
uasort($this->abm_fields,create_function('&$a,&$b','
        $al = sprintf(\'%s |%4.4d\',"", $a->field_listord);
        $bl = sprintf(\'%s |%4.4d\',"", $b->field_listord);
        if($al == $bl) return 0;
        return (($al > $bl) ? +1 : -1);'));

$visible_filters    = $this->GetFilterList( 'visible' );
$visible_fields     = $this->GetFieldList( 'visible' );
$hidden_fields      = $this->GetFieldList( 'hidden' );
$sortable_items     = $this->GetSortList();

/**
* Render filters
**/
foreach($visible_filters as $name) {
    if($t=$this->RenderFilter($name)) {
        $data_filters[] = $t;
    }
}


/** Hack para colorear las rows de una lista: **/
$filtro_estado = array();
$event_dblclick = array();

/**
* Render data
**/
$as_static = (($this->currAction!=QFORMS_ACTION_LISTFORM)?QFORMS_RENDER_STATIC:QFORMS_RENDER_CONTROL);
foreach($this->abm_data as $rid=>$rec) {

    /** Hack para colorear las rows de una lista: **/
    if( @$rec['filtro_estado'] )
        $filtro_estado[$rid]="filtroEstado_".$rec['filtro_estado'];

    if(method_exists($this,'template_filterHookRow'))
        $filtro_estado[$rid]=$this->template_filterHookRow($rid,$rec);
    foreach($visible_fields as $name) {
        if( $t = $this->RenderField($name, $rid, $as_static) ) {
            if(method_exists($this,'template_filterHook'))
                $t=call_user_func(array(&$this, 'template_filterHook'), $t, $rid);
            $data_cells[$rid][$name]=$t;
        }
    }
    foreach($hidden_fields as $name) {
        if( $t=$this->RenderField( $name, $rid, QFORMS_RENDER_HIDDEN) )
            $data_hidden .= $t['html'];
    }
    // Append Edit & View button, if required
    if($this->perm_update) {
        $currPK = $this->getPK($rid, $rec, true);
        //$event_dblclick[$rid] = "self.location='$this->abm_update_url&xF_record=$currPK'; event.stopPropagation(); event.preventDefault(); return false;";
    }

    if( ($this->currAction!=QFORMS_ACTION_LISTFORM) && ($t=$this->htmlListButtons($rid, $rec)) ) {
        $flag_abm_controls=true;
        $data_cells[$rid]=array('xfABMControls' => array('class'=>'xfABMMainControls','html'=>$t))
            + (($data_cells&&@$data_cells[$rid])?$data_cells[$rid]:array() );
    }
}
$this->tmp_count_tablecols=count(reset($data_cells));

/**
* Render headers
**/
foreach($visible_fields as $name) {
    if( $t = $this->RenderField($name, 0, QFORMS_RENDER_STATIC) ) {
        if(!empty($t['caption'])) {
            $data_headers[] = $t['caption'];
            $data_descs[] = $t['description'];
        }
    }
}
if(!empty($flag_abm_controls)) $data_headers = array(-1=>'&nbsp;')+$data_headers;

/**
* Desactivo el exportar/imprimir si no hay nada para exportar/imprimir
**/
if(!$this->rows_total) {
    $this->perm_export=false;
    $this->perm_print=false;
}

/**
* Render list options
**/
foreach($sortable_items as $fname=>$caption)
    $data_orderby[ $fname ] = $caption;

/**
* Render actions
**/
if($t=$this->htmlListTopActions()) {
    $actions_top = $t;
}
if($t=$this->htmlListBottomActions()) {
    $actions_bottom = $t;
}

/**
* Render errors, urls, pagination and messages
**/
$errors     = implode('<br/>', $this->abm_errors);
$abm_url    = $this->abm_url;
$prevPage   = (($this->abm_pageNo>1) ?QForms::URL($this->abm_url,'xF_pageNo',$this->abm_pageNo-1) :null);
$nextPage   = (($this->abm_pageNo<$this->abm_pageCount) ?QForms::URL($this->abm_url,'xF_pageNo',$this->abm_pageNo+1) :null);
$title      = htmlspecialchars($this->abm_title);
$subtitle   = htmlspecialchars($this->abm_subtitle);
$extra_params = $this->htmlGetExtraParams();
$extra_html = $this->htmlFormExtras();
if(!$as_static)
    $extra_html .= $this->ValidateRecJS();

$status_top = sprintf(QForms::trans('Querying')." %d ".QForms::trans('records in')." %d ".QForms::trans('pages').' &nbsp; ', $this->rows_total, $this->abm_pageCount);

$url_pages  = array(
    (($this->abm_pageNo>1) ?QForms::URL($this->abm_url,'xF_pageNo',1) :null),
    (($this->abm_pageNo>1) ?QForms::URL($this->abm_url,'xF_pageNo',$this->abm_pageNo-1) :null),
    QForms::URL($this->abm_url,'xF_pageNo').'&xF_pageNo=',
    (($this->abm_pageNo<$this->abm_pageCount) ?QForms::URL($this->abm_url,'xF_pageNo',$this->abm_pageNo+1) :null),
    (($this->abm_pageNo<$this->abm_pageCount) ?QForms::URL($this->abm_url,'xF_pageNo',$this->abm_pageCount) :null),
    QForms::URL($this->abm_url,array('xF_pageNo'=>null,'xF_RowsPerPage'=>null)).'&xF_RowsPerPage='
    );
if($this->abm_pageCount)
    foreach(range(1,$this->abm_pageCount) as $p) $set_of_pages[$p] = QForms::trans("Page")." $p";
else $set_of_pages=array();



?>

<link rel="stylesheet" href="<?php echo QFORMS_URI_TEMPLATES; ?>xforms.template2.css" />

<script type="text/javascript" src="<?php echo QFORMS_URI_TEMPLATES; ?>prototype.js"></script>
<script type="text/javascript" src="<?php echo QFORMS_URI_TEMPLATES; ?>cssQuery-p.js"></script>
<script type="text/javascript" src="<?php echo QFORMS_URI_TEMPLATES; ?>gui.js"></script>
<script type="text/javascript" src="<?php echo QFORMS_URI_TEMPLATES; ?>xforms.base.js"></script>


<?php $this->evtBeforeTable(); ?>
<?php echo $extra_html; ?>
<script type="text/javascript">document.title +=' - <?php echo $title; ?><?php if($subtitle) { ?> - <?php echo $subtitle; ?><?php }/*if($subtitle*/ ?>';</script>

<table id="xfABMMainTable">

<tr><td><?php $this->evtBeforeData(); ?></td></tr>

<tr><td align="left">

    <?php if($errors) { ?><?php echo $errors; ?><?php }/*if($errors*/ ?>
    <?php if($data_filters||$data_orderby) { ?>
        <fieldset>
        <legend>&nbsp; <input type="button" onclick="return event.returnValue=xfABM_ShowHideElt('xF_FilterForm');" class="xfButtonBase" value="Search"> &nbsp;</legend>
        <form id="xF_FilterForm" 	<?php if($this->currAction==QFORMS_ACTION_LISTFORM) { echo 'style="display:none;"'; } ?> name="xF_FilterForm" method="get" action="<?php echo $abm_url; ?>">
        <table width="400" id="xfABMFilterTable"><tr><td>
        <?php if($data_filters) { ?>
            <table width="100%">
            <?php foreach($data_filters as $cell) { ?>
                <tr><th><?php echo $cell['caption']; ?></th><td<?php if(!empty($cell['class'])) echo " class=\"$cell[class]\""; ?>><?php echo $cell['html']; ?></td></tr>
            <?php }/*foreach($data_filters*/ ?>
            </table>
        <?php }/*if($data_filters*/ ?>
        </td><td valign="top">
        <?php if($data_orderby) { ?>
            <table width="100%">
            <tr><th><?php echo QForms::trans('Order by'); ?></th><td><select name="xF_orderBy">
            <?php echo QForms::HTML_options($data_orderby, $this->abm_orderBy,true); ?>
            </select></td></tr>
            <tr><th><?php echo QForms::trans('Showing'); ?></th><td><select name="xF_RowsPerPage">
            <?php echo QForms::HTML_options(array(10=>10,50=>50,100=>100,1000=>1000), $this->abm_rowsPerPage); ?>
            </select> <?php echo QForms::trans('records'); ?></td></tr>
            </table>
        <?php }/*if($data_orderby*/ ?>
        </td></tr></table>
		<div align="center">
        <input type="submit" value="<?php echo QForms::trans('Apply'); ?>" class="xfButtonBase xfButtonFilter" />
        <input type="submit" value="<?php echo QForms::trans('Clear'); ?>" onclick="for(var i=0 ; i < this.form.elements.length ; i++) { if(this.form.elements[i].selectedIndex) this.form.elements[i].selectedIndex=-1; else if(this.form.elements[i].type!='hidden') this.form.elements[i].value=''; }" class="xfButtonBase xfButtonFilterReset" />
        </div>
        <?php echo $extra_params; ?>
        </form>
        <?php if(empty($this->tmp_message_filter)&&$this->currAction==QFORMS_ACTION_LIST) { ?><script type="text/javascript">xfABM_ShowHideElt('xF_FilterForm');</script><?php } ?>
        <span id="xfChangesMadeMessage">Se realizaron cambios en este listado. Si desea verlos ahora, presione Aplicar para recargar esta página.</span>
        </fieldset>
    <?php }/*if($data_filters||$data_orderby*/ ?>

<table width="100%" style="border-top: 5px solid #006699;">
<tr><td nowrap="nowrap" valign="top" align="left" width="300"><b><?php echo $title; ?></b></td>
    <td align="left"><?php if($actions_top) { ?><?php echo $actions_top; ?><?php }/*if($actions_top*/ ?>
	<?php echo $actions_bottom; ?>
    <?php if($actions_bottom) { ?>
    <div>
        <?php if($this->currAction==QFORMS_ACTION_LISTFORM) { ?>
        <big>Recuerde utilizar el botón Guardar para aplicar los cambios que realice. </big>
        <?php }/*if($this->currAction==QFORMS_ACTION_LISTFORM) {*/ ?>
    </div>
    <?php }/* if($actions_bottom) {*/ ?>

</td><td align="left" style="text-align: right;">
<nobr>
<?php if( $data_cells && count($set_of_pages)>1 ) { ?>
    <input value=" &lt;&lt; " name="BackFirst" type="button" onclick="self.location='<?php echo $url_pages[0]; ?>'" <?php if(!$url_pages[0]) echo 'disabled="disabled"'; ?> />
    <input value=" &lt; " name="Back" type="button"  onclick="self.location='<?php echo $url_pages[1]; ?>'" <?php if(!$url_pages[1]) echo 'disabled="disabled"'; ?> >
    <select value="GoToPage" name="PageNumber" onchange="self.location='<?php echo $url_pages[2]; ?>'+this.options[this.selectedIndex].value">
    <?php echo QForms::HTML_options($set_of_pages,$this->abm_pageNo); ?>
    </select>
    <input name="Next" value=" &gt; " type="button" onclick="self.location='<?php echo $url_pages[3]; ?>'" <?php if(!$url_pages[3]) echo 'disabled="disabled"'; ?> />
    <input name="NextLast" value=" &gt;&gt; " type="button" onclick="self.location='<?php echo $url_pages[4]; ?>'" <?php if(!$url_pages[4]) echo 'disabled="disabled"'; ?> />
<?php }/*if( $set_of_pages*/ ?>
</nobr>
<nobr><?php printf(" %d ".QForms::trans('records in')." %d ".QForms::trans('pages').' &nbsp; ', $this->rows_total, $this->abm_pageCount); ?></nobr>

</td>
</tr></table>
</td></tr>
<tr><td>
<?php if($data_cells) { ?>

<?php if($errors) { ?>
    <div><?php echo $errors; ?></div>
<?php }/*if($errors*/ ?>



<form name="xF_MainForm" method="post" action="<?php echo $abm_url; ?>"><table width="100%" border="0" cellspacing="0" id="xfABMDataTable">
<?php $this->evtBeforeHeaders(); ?>
<tr>
<?php foreach($data_headers as $cid=>$cell) { ?>
<th<?php if(@$data_descs[$cid]) echo " title=\"$data_descs[$cid]\"";?>><?php echo $cell; ?></th>
<?php }/*foreach($data_headers*/ ?>
</tr>
<?php foreach($data_cells as $rid=>$rec) { ?>
<tr<?php if(!empty($filtro_estado[$rid])) echo ' class="'.$filtro_estado[$rid].'"'; ?> <?php if(!empty($event_dblclick[$rid])) echo ' ondblclick="'.$event_dblclick[$rid].'"'; ?>>
<?php foreach($rec as $cid=>$cell) { ?>
<td<?php if(!empty($cell['class'])) echo " class=\"$cell[class]\""; ?>><?php echo $cell['html']; ?></td>
<?php }/*foreach($rec*/ ?>
</tr>
<?php }/*foreach($data_cells*/ ?>
<?php $this->evtAfterLastRow(); ?>
</table><?php echo $data_hidden; ?><input type="hidden" name="xF_SubmitData" value="1" class="xfABMWidgetButton" />
</form>



<?php }else{/*if($data_cells*/ ?>
    &nbsp;
    <?php if($this->abm_pageCount>0||@$this->tmp_message_filter) { ?>
        <p><?php echo QForms::trans('Please apply some filters'); ?></p>
    <?php }/*if($this->abm_pageCount>0) {*/ ?>
<?php }/*if($data_cells*/ ?>

<?php $this->evtAfterData(); ?>

</td></tr>

</th></tr>
</table>
<?php $this->evtAfterTable(); ?>

<script type="text/javascript">
    zebra_tables('xfABMDataTable',1,true);
</script>



