<?php

/*
 *    This file is part of QForms
 *
 *    qForms is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    QForms is distributed in the hope that it will be useful,
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

$data_filters= array ();
$data_orderby= array ();
$data_headers= array ();
$data_descs= array ();
$data_cells= array ();
$data_hidden= '';
$errors= array ();
$actions_top= '';
$actions_bottom= '';
$prevPage= null;
$nextPage= null;

// ordeno la lista
uasort($this->abm_fields, create_function('&$a,&$b', '
        $al = sprintf(\'%s |%4.4d\',"", $a->field_listord);
        $bl = sprintf(\'%s |%4.4d\',"", $b->field_listord);
        if($al == $bl) return 0;
        return (($al > $bl) ? +1 : -1);'));

$visible_filters= $this->GetFilterList('visible');
$visible_fields= $this->GetFieldList('visible');
$hidden_fields= $this->GetFieldList('hidden');
$sortable_items= $this->GetSortList();

/**
* Render filters
**/
foreach ($visible_filters as $name) {
	if ($t= $this->RenderFilter($name)) {
		$data_filters[]= $t;
	}
}

/** Hack para colorear las rows de una lista: **/
$filtro_estado= array ();
$event_dblclick= array ();

/**
* Render data
**/
$as_static= (($this->currAction != QFORMS_ACTION_LISTFORM) ? QFORMS_RENDER_STATIC : QFORMS_RENDER_CONTROL);
foreach ($this->abm_data as $rid => $rec) {

	/** Hack para colorear las rows de una lista: **/
	if (@ $rec['filtro_estado'])
		$filtro_estado[$rid]= "filtroEstado_" .
		$rec['filtro_estado'];

	if (method_exists($this, 'template_filterHookRow'))
		$filtro_estado[$rid]= $this->template_filterHookRow($rid, $rec);
	foreach ($visible_fields as $name) {
		if ($t= $this->RenderField($name, $rid, $as_static)) {
			if (method_exists($this, 'template_filterHook'))
				$t= call_user_func(array (
					& $this,
					'template_filterHook'
				), $t, $rid);
			$data_cells[$rid][$name]= $t;
		}
	}
	foreach ($hidden_fields as $name) {
		if ($t= $this->RenderField($name, $rid, QFORMS_RENDER_HIDDEN))
			$data_hidden .= $t['html'];
	}
	// Append Edit & View button, if required
	if ($this->perm_update) {
		$currPK= $this->getPK($rid, $rec, true);
		//$event_dblclick[$rid] = "self.location='$this->abm_update_url&xF_record=$currPK'; event.stopPropagation(); event.preventDefault(); return false;";
	}

	if ( ($this->currAction != QFORMS_ACTION_LISTFORM) && ( ($t1=$this->htmlListButtonsLeft($rid, $rec)) | ($t2=$this->htmlListButtonsRight($rid, $rec)) )  ) {
	#	echo '<pre>'; var_dump($t1,$t2); die();
		if( !empty($t1) ) {
			$flag_abm_controls_left= true;
			$data_cells[$rid]= array (
				'xfABMControlsLeft' => array (
					'class' => 'xfABMMainControls',
					'html' => $t1
				)
			) + (($data_cells && @ $data_cells[$rid]) ? $data_cells[$rid] : array ());
		}
		if( !empty($t2) ) {
			$flag_abm_controls_right = true;
			$data_cells[$rid]['xfABMControlsRight']= array (
					'class' => 'xfABMMainControls',
					'html' => $t2);
		}
	}
}
$this->tmp_count_tablecols= count(reset($data_cells));

/**
* Render headers
**/
foreach ($visible_fields as $name) {
	if ($t= $this->RenderField($name, 0, QFORMS_RENDER_STATIC)) {
		if (!empty ($t['caption'])) {
			$data_headers[]= $t['caption'];
			$data_descs[]= $t['description'];
		}
	}
}
if (!empty ($flag_abm_controls_left) ){
	$data_headers= array (
		-1 => 		$this->listLeftActionsCaption , //'&nbsp;'
	) + $data_headers;
}
if( !empty ($flag_abm_controls_right) ) {
	$data_headers[] = 		$this->listRightActionsCaption ;//'&nbsp;';
}
/**
* Desactivo el exportar/imprimir si no hay nada para exportar/imprimir
**/
if (!$this->rows_total) {
	$this->perm_export= false;
	$this->perm_print= false;
}

/**
* Render list options
**/
foreach ($sortable_items as $fname => $caption)
	$data_orderby[$fname]= $caption;

/**
* Render actions
**/
if ($t= $this->htmlListTopActions()) {
	$actions_top= $t;
}
if ($t= $this->htmlListBottomActions()) {
	$actions_bottom= $t;
}

/**
* Render errors, urls, pagination and messages
**/
$errors= implode('<br/>', $this->abm_errors);
$abm_url= $this->abm_url;
$prevPage= (($this->abm_pageNo > 1) ? QForms :: URL($this->abm_url, $this->abm_prefix.'pageNo', $this->abm_pageNo - 1) : null);
$nextPage= (($this->abm_pageNo < $this->abm_pageCount) ? QForms :: URL($this->abm_url, $this->abm_prefix.'pageNo', $this->abm_pageNo + 1) : null);
$title= htmlspecialchars($this->abm_title);
$subtitle= htmlspecialchars($this->abm_subtitle);
$extra_params= $this->htmlGetExtraParams();
$extra_html= $this->htmlFormExtras();
if (!$as_static)
	$extra_html .= $this->ValidateRecJS();

$status_top= sprintf(QForms :: trans('Querying') . " %d " . QForms :: trans('records in') . " %d " . QForms :: trans('pages') . ' &nbsp; ', $this->rows_total, $this->abm_pageCount);

$url_pages= array (
	(($this->abm_pageNo > 1
) ? QForms :: URL($this->abm_url, $this->abm_prefix.'pageNo', 1) : null), (($this->abm_pageNo > 1) ? QForms :: URL($this->abm_url, $this->abm_prefix.'pageNo', $this->abm_pageNo - 1) : null), QForms :: URL($this->abm_url, $this->abm_prefix.'pageNo') .
'&'.$this->abm_prefix.'pageNo=', (($this->abm_pageNo < $this->abm_pageCount) ? QForms :: URL($this->abm_url, $this->abm_prefix.'pageNo', $this->abm_pageNo + 1) : null), (($this->abm_pageNo < $this->abm_pageCount) ? QForms :: URL($this->abm_url, $this->abm_prefix.'pageNo', $this->abm_pageCount) : null), QForms :: URL($this->abm_url, array (
	$this->abm_prefix.'pageNo' => null,
	$this->abm_prefix.'RowsPerPage' => null
)) . '&'.$this->abm_prefix.'RowsPerPage=');
if ($this->abm_pageCount)
	foreach (range(1, $this->abm_pageCount) as $p)
		$set_of_pages[$p]= QForms :: trans("Page") . " $p";
else
	$set_of_pages= array ();
?>


<?php include_jquery(); ?>
<script type="text/javascript" charset="UTF-8" src="<?php echo QFORMS_URI_TEMPLATES; ?>qforms.simple-template.js"></script>

<div class="qforms">

<?php /* $this->evtBeforeABM(); */ ?>

<?php echo $extra_html; ?>



<h2><?php echo $title; ?></h2>
<?php if($subtitle) { ?>
	<h3><?php echo $subtitle; ?></h3>
<?php } /*if($subtitle*/ ?> 
<br class="clear" />

<?php
/**
 * muestro filtros y ordenadores
 */
if ( !empty ($data_filters) || !empty ($data_orderby)) {
?>
	<form id="xF_FilterForm" name="xF_FilterForm" method="get" action="<?php echo $abm_url; ?>">
    <?php if( !empty($data_filters) ) { ?>
	<a href="#" id="toggle-list-control-pannel" ><?php echo QForms::trans('Filters & Options'); ?></a> 
	<div id="list-control-pannel" class="round-corners">
		<div class="filters">
		<?php
        	$filter_idx = 0;
        	foreach($data_filters as $cell) { $filter_idx++; ?>
            <span class="input_item <?php echo $cell['class'], ' filter_'.$filter_idx;?>"><span class="label"><?php echo $cell['caption']; ?></span><?php echo $cell['html']; ?></span>
        <?php } /*foreach($data_filters*/ ?>
    	</div>
    <?php } /*if($data_filters*/ ?>
    <?php if( !empty($data_orderby) ) { ?>
		<div class="order-by">
        	<span class="order-select"><span class="label"><?php echo QForms::trans('Order by'); ?></span>
    		<select name="<?php echo $this->abm_prefix; ?>orderBy"><?php echo QForms::HTML_options($data_orderby, $this->abm_orderBy,true); ?></select>
        	</span>
        	<span class="order-rpp"><span class="label"><?php echo QForms::trans('Showing'); ?></span>
    		<select name="<?php echo $this->abm_prefix; ?>RowsPerPage"><?php echo QForms::HTML_options(array(50=>50,100=>100,200=>200,300=>300), $this->abm_rowsPerPage); ?></select>
    		<span class="label"><?php echo QForms::trans('records'); ?></span></span>
    	</div>
		<div class="filter-controls">
        <input type="submit" value="<?php echo QForms::trans('Apply'); ?>" class="button" />
        <input type="submit" value="<?php echo QForms::trans('Clear'); ?>" onclick="for(var i=0 ; i < this.form.elements.length ; i++) { if(this.form.elements[i].selectedIndex) this.form.elements[i].selectedIndex=-1; else if(this.form.elements[i].type!='hidden') this.form.elements[i].value=''; }" class="button" />
        </div>
    <?php } /*if($data_orderby*/ ?>
	</div>
<div class="beforeData" ><?php $this->evtBeforeData(); ?></div>

        <?php echo $extra_params; ?>
  		<?php
 /*
       <?php if(empty($this->tmp_message_filter)&&$this->currAction==QFORMS_ACTION_LIST) { ?><script type="text/javascript">xfABM_ShowHideElt('xF_FilterForm');</script><?php } ?>
       <span id="xfChangesMadeMessage">Se realizaron cambios en este listado. Si desea verlos ahora, presione Aplicar para recargar esta página.</span> */
?>
	</form>
<?php

} /*if($data_filters) */

?>

<form name="xF_MainForm" id="xF_MainForm" method="post" action="<?php echo $abm_url; ?>">

<?php if($actions_top) { ?>
	<div class="actions-top">
		<?php echo $actions_top; ?>
	</div>
<?php } /* if($actions_top) */ ?>
<?php if($actions_bottom) { ?>
	<div class="actions-bottom" >
		<?php echo $actions_bottom; ?>
    </div>
<?php } /* if($actions_bottom) {*/ ?>

<?php if( !empty($this->message) ) { ?>
	<div class="comment" id="qformMessage"><?php echo $this->message; ?></div>
<?php }else{?>
	<div class="comment" id="qformMessage" style="display: none;"></div>
<?php } ?>

<div class="grid_pager">
	<div class="results">
		<?php echo QForms :: trans('<strong>Record %s</strong> to <strong>%s</strong> from <strong>%s</strong> Records',
			($this->abm_limit[0]+1),
			($this->abm_limit[0]+$this->abm_limit[1])>$this->rows_total ? $this->rows_total : ($this->abm_limit[0]+$this->abm_limit[1]),
			$this->rows_total			);?>
	</div>
	<div class="pager">
		<input type="button" title="<?php echo QForms::trans('Go to first page'); ?>" class="first" value="|<" <?php if($url_pages[0]){?> onclick="self.location='<?php echo $url_pages[0]; ?>';"<?php } ?> />
		<input type="button" title="<?php echo QForms::trans('Go to previous page'); ?>" class="previous" value="<<" <?php if($url_pages[0]){?> onclick="self.location='<?php echo $url_pages[1]; ?>';"<?php } ?> />
		<span><?php QForms :: trans('Page');?><input type="text" title="<?php echo QForms::trans('Jump to page...');?>" value="<?php echo $this->abm_pageNo;?>" size="2" onchange="if( ! isNaN(this.value) && this.value>=1 && this.value<=<?php echo $this->abm_pageCount;?> ){ self.location='<?php echo $url_pages[2]; ?>'+this.value; }" class="page"/><?php echo QForms :: trans('of');?> <?php echo $this->abm_pageCount;?></span>
		<input type="button" title="<?php echo QForms::trans('Go to next page'); ?>" class="next" value=">>" <?php if($url_pages[3]){?> onclick="self.location='<?php echo $url_pages[3]; ?>';"<?php } ?>/>
		<input type="button" title="<?php echo QForms::trans('Go to last page'); ?>" class="last" value=">|" <?php if($url_pages[4]){?> onclick="self.location='<?php echo $url_pages[4]; ?>';"<?php } ?>/>
	</div>
	<br class="clear"/>
</div>

<div class="grid grid_<?php echo $this->abm_name.'_'.$this->currAction;?>">

<?php if($errors) { ?>
	<div class="comment_warning"><?php echo $errors; ?></div>
<?php } /*if($errors*/ ?>
<?php  $this->evtBeforeTable(); ?>
	<table class="<?php echo $this->abm_name;?>" >
		<thead>
<?php $this->evtBeforeHeaders(); ?>
		<tr>
		<?php
		$headerCount=1;
		foreach($data_headers as $cid=>$cell) { ?>
			<th class="th_<?php printf('%02s',$headerCount++);?>" <?php if(@$data_descs[$cid]) echo " title=\"$data_descs[$cid]\"";?>><?php echo $cell; ?></th>
		<?php
			#$headerCount++;
		} /*foreach($data_headers*/ ?>
		</tr>
		</thead>

<?php if( !empty($data_cells) ) { ?>
		<tbody>

<?php
	foreach($data_cells as $rid=>$rec) {
		$cellCount=1;	?>
		<tr<?php if(!empty($filtro_estado[$rid])) echo ' class="'.$filtro_estado[$rid].'"'; ?> <?php if(!empty($event_dblclick[$rid])) echo ' ondblclick="'.$event_dblclick[$rid].'"'; ?>>
		<?php foreach($rec as $cid=>$cell) { ?>
			<td<?php if(!empty($cell['class'])) printf (' class="td_%02s"', $cellCount++ ); ?>><?php echo $cell['html']; ?></td>
		<?php } /*foreach($rec*/?>
		</tr>
	<?php } /*foreach($data_cells*/ ?>

<?php $this->evtAfterLastRow(); ?>

		</tbody>
<?php } /* if( !empty($data_cells) ) { */ ?>
	</table>

<?php $this->evtAfterTable(); ?>

<?php if(!$data_cells) { ?>
	<div class="comment"><?php echo QForms :: trans('No records available');?></div>
    <?php if($this->abm_pageCount>0||@$this->tmp_message_filter) { ?>
        <div class="comment"><?php echo QForms :: trans('Please apply some filters'); ?></div>
    <?php } ?>
<?php } /* if($data_cells) */ ?>

<?php echo $data_hidden; ?>
<input type="hidden" name="xF_SubmitData" value="1" class="xfABMWidgetButton" />


<?php $this->evtAfterData(); ?>
</div>

</form>

</div>
<?php /*
<script type="text/javascript">
    zebra_tables('xfABMDataTable',1,true);
</script>

*/?>
<script type="text/javascript">
xforms_form_init();
</script>
