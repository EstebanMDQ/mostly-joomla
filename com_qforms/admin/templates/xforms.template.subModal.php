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
$data_cells     = array();
$data_hidden    = '';
$errors         = array();
$actions_top    = '';
$actions_bottom = '';
$prevPage       = null;
$nextPage       = null;

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

/**
* Render data
**/
$as_static = (($this->currAction!=XFORMS_ACTION_LISTFORM)?XFORMS_RENDER_STATIC:XFORMS_RENDER_CONTROL);
foreach($this->abm_data as $rid=>$rec) {
    /** Hack para colorear las rows de una lista: **/
    if(isset($rec['filtro_estado']) && $rec['filtro_estado']>0)
        $filtro_estado[$rid]="filtroEstado".$rec['filtro_estado'];
    foreach($visible_fields as $name) {
        if( $t = $this->RenderField($name, $rid, $as_static) ) {
            $data_cells[$rid][$name]=$t;
        }
    }
    foreach($hidden_fields as $name) {
        if( $t=$this->RenderField( $name, $rid, XFORMS_RENDER_HIDDEN) )
            $data_hidden .= $t['html'];
    }
    // Append Edit & View button, if required
    if($t=$this->htmlListButtons_submodal($rid, $rec)) {
        $flag_abm_controls=true;
        $data_cells[$rid]['xfABMControls'] = array('class'=>'xfABMMainControls','html'=>$t);
    }
}
/**
* Render headers
**/
foreach($visible_fields as $name) {
    if( $t = $this->RenderField($name, 0, XFORMS_RENDER_STATIC) ) {
        if(!empty($t['caption'])) {
            $data_headers[] = $t['caption'];
        }
    }
}
if(!empty($flag_abm_controls)) $data_headers[] = '&nbsp;';

/**
* Render list options
**/
foreach($sortable_items as $fname=>$caption)
    $data_orderby[ $fname ] = $caption;

/**
* Render actions
**/
if($t=$this->htmlListTopActions_submodal()) {
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
$prevPage   = (($this->abm_pageNo>1) ?XForms::URL($this->abm_url,'xF_pageNo',$this->abm_pageNo-1) :null);
$nextPage   = (($this->abm_pageNo<$this->abm_pageCount) ?XForms::URL($this->abm_url,'xF_pageNo',$this->abm_pageNo+1) :null);
$title      = htmlspecialchars($this->abm_title);
$subtitle   = htmlspecialchars($this->abm_subtitle);
$extra_params = $this->htmlGetExtraParams();
$extra_html = $this->htmlFormExtras();
if(!$as_static)
    $extra_html .= $this->ValidateRecJS();

$status_top = sprintf(XForms::trans('Showing')." %d ".XForms::trans('records in')." %d ".XForms::trans('pages').' &nbsp; ', $this->rows_total, $this->abm_pageCount);

$url_pages  = array(
    (($this->abm_pageNo>1) ?XForms::URL($this->abm_url,'xF_pageNo',1) :null),
    (($this->abm_pageNo>1) ?XForms::URL($this->abm_url,'xF_pageNo',$this->abm_pageNo-1) :null),
    XForms::URL($this->abm_url,'xF_pageNo').'&xF_pageNo=',
    (($this->abm_pageNo<$this->abm_pageCount) ?XForms::URL($this->abm_url,'xF_pageNo',$this->abm_pageNo+1) :null),
    (($this->abm_pageNo<$this->abm_pageCount) ?XForms::URL($this->abm_url,'xF_pageNo',$this->abm_pageCount) :null),
    XForms::URL($this->abm_url,array('xF_pageNo'=>null,'xF_RowsPerPage'=>null)).'&xF_RowsPerPage='
    );
if($this->abm_pageCount)
    foreach(range(1,$this->abm_pageCount) as $p) $set_of_pages[$p] = XForms::trans("Page")." $p";
else $set_of_pages=array();

?>

<link rel="stylesheet" type="text/css" href="<?php echo XFORMS_URI_TEMPLATES; ?>subModal/subModal.css" />
<script type="text/javascript" src="<?php echo XFORMS_URI_TEMPLATES; ?>subModal/common.js"></script>
<script type="text/javascript" src="<?php echo XFORMS_URI_TEMPLATES; ?>subModal/subModal.js"></script>
<script type="text/javascript">

function    subModalClosedEvent() {
    var elt = document.getElementById('doAutoReload');
    if(elt && elt.checked) {
        var t=window.location.href;
        t+= ((t.indexOf('?')>-1)?'&':'?')+'RvL='+(new Date()).getTime();
        window.location=t;
    }
}
function getSetCookie(name,value,days) {
    if(undefined!=value) {
        if(days) {
            var date = new Date();
            date.setTime(date.getTime()+(days*24*60*60*1000));
            var expires = "; expires="+date.toGMTString();
        }else var expires = "";
        document.cookie = name+"="+value+expires+"; path=/";
    }
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}
</script>
<div id="popupMask">&nbsp;</div>

<div id="popupContainer">
	<div id="popupInner">
		<div id="popupTitleBar">
			<div id="popupTitle"></div>
			<div id="popupControls">
				<img src="<?php echo SITE_URI; ?>xforms/subModal/close.gif" onclick="hidePopWin(false);" />
			</div>
		</div>
		<iframe src="<?php echo SITE_URI; ?>xforms/subModal/loading.html" style="width:100%;height:100%;background-color:transparent;" scrolling="auto" frameborder="0" allowtransparency="true" id="popupFrame" name="popupFrame" width="100%" height="100%"></iframe>
	</div>
</div>

<?php $this->evtBeforeTable(); ?>
<?php echo $extra_html; ?>
<table class="xfABMMainTable">
<tr><td class="xfABMMainTitle"><?php printf('%s',$title); ?><?php if($subtitle) { ?> - <?php printf('%s',$subtitle); ?><?php }/*if($subtitle*/ ?></td></tr>
<tr><td><?php $this->evtBeforeData(); ?></td></tr>
<tr><td align="left">
    <?php if($errors) { ?>
        <?php printf('%s',$errors); ?>
    <?php }/*if($errors*/ ?>
    <?php if($data_filters||$data_orderby) { ?>
        <form name="xF_FilterForm" method="get" action="<?php $abm_url; ?>">
        <table width="400" class="xfABMFilterTable"><tr><td>
        <?php if($data_filters) { ?>
            <table width="100%">
            <?php foreach($data_filters as $cell) { ?>
                <tr><th><?php printf('%s',$cell['caption']); ?></th><td<?php if(!empty($cell['class'])) echo " class=\"$cell[class]\""; ?>><?php echo $cell['html']; ?></td></tr>
            <?php }/*foreach($data_filters*/ ?>
            </table>
        <?php }/*if($data_filters*/ ?>
        </td><td valign="top">
        <?php if($data_orderby) { ?>
            <table width="100%">
            <tr><th><?php echo XForms::trans('Order by'); ?></th><td><select name="xF_orderBy">
            <?php echo XForms::HTML_options($data_orderby, $this->abm_orderBy,true); ?>
            </select></td></tr>
            <tr><th><?php echo XForms::trans('Showing'); ?></th><td><select name="xF_RowsPerPage">
            <?php echo XForms::HTML_options(array(10=>10,50=>50,100=>100,1000=>1000), $this->abm_rowsPerPage); ?>
            </select> <?php echo XForms::trans('records'); ?></td></tr>
            </table>
        <?php }/*if($data_orderby*/ ?>
        </td></tr>
        <tr><td colspan="2" style="text-align: center;">
            <input type="submit" value="<?php echo XForms::trans('Apply'); ?>"  class="xfButtonBase xfButtonFilter" /> <input type="submit" value="<?php echo XForms::trans('Clear'); ?>" onclick="for(var i=0 ; i < this.form.elements.length ; i++) { if(this.form.elements[i].selectedIndex) this.form.elements[i].selectedIndex=-1; else if(this.form.elements[i].type!='hidden') this.form.elements[i].value=''; }" class="xfButtonBase xfButtonFilterReset" />
            <?php printf('%s',$extra_params); ?>
        </td></tr></table>
        </form>
    <?php }/*if($data_filters||$data_orderby*/ ?>

<p><?php echo XForms::trans('If you have modified records and wish to wiew the changes in this list, press the button:'); ?>

<input type="button" value="<?php echo XForms::trans('Refresh'); ?>" onclick="window.history.go(0)" class="xfButtonBase" />
<input id="doAutoReload" name="doAutoReload" type="checkbox" value="1" onclick="getSetCookie('xforms_doAutoReload', this.checked?1:0)" /> Always refresh after changes.
<script type="text/javascript">
var t,elt;
if(t=getSetCookie('xforms_doAutoReload')) {
    if(elt = document.getElementById('doAutoReload')) { elt.checked = (t==1); }
}
</script>

</p>

<div align="right">

<table width="100%"><tr>
<?php if( count($set_of_pages)>1 ) { ?>
<td>
    <input value=" &lt;&lt; " name="BackFirst" type="button" onclick="self.location='<?php echo $url_pages[0]; ?>'" <?php if(!$url_pages[0]) echo 'disabled="true"'; ?> />
    <input value=" &lt; " name="Back" type="button"  onclick="self.location='<?php echo $url_pages[1]; ?>'" <?php if(!$url_pages[1]) echo 'disabled="true"'; ?> >
    <select value="GoToPage" name="PageNumber" onchange="self.location='<?php echo $url_pages[2]; ?>'+this.options[this.selectedIndex].value">
    <?php echo XForms::HTML_options($set_of_pages,$this->abm_pageNo); ?>
    </select>
    <input name="Next" value=" &gt; " type="button" onclick="self.location='<?php echo $url_pages[3]; ?>'" <?php if(!$url_pages[3]) echo 'disabled="true"'; ?> />
    <input name="NextLast" value=" &gt;&gt; " type="button" onclick="self.location='<?php echo $url_pages[4]; ?>'" <?php if(!$url_pages[4]) echo 'disabled="true"'; ?> />
<br>
<?php printf('%s',$status_top); ?>
</td>
<?php }/*if( $set_of_pages*/ ?>
<td>
<?php if($actions_top) { ?>
    <?php printf('%s',$actions_top); ?><br/>
<?php }/*if($actions_top*/ ?>
</td>
</tr>
</table>
</div>

</td></tr>
<tr><td>
<?php if($data_cells) { ?>
    <?php if($errors) { ?>
        <div><?php printf('%s',$errors); ?></div>
    <?php }/*if($errors*/ ?>

    <form name="xF_MainForm" method="post" action="<?php echo $abm_url; ?>"><table width="100%" border="0" cellspacing="1" id="xfABMDataTable">
    <tr>
    <?php foreach($data_headers as $cid=>$cell) { ?>
        <th><?php printf('%s',$cell); ?></th>
    <?php }/*foreach($data_headers*/ ?>
    </tr>
    <?php foreach($data_cells as $rid=>$rec) { ?>
        <tr<?php if(!empty($filtro_estado[$rid])) echo ' class="'.$filtro_estado[$rid].'"'; ?>>
        <?php foreach($rec as $cid=>$cell) { ?>
            <td<?php if(!empty($cell['class'])) echo " class=\"$cell[class]\""; ?>><?php echo $cell['html']; ?></td>
        <?php }/*foreach($rec*/ ?>
        </tr>
    <?php }/*foreach($data_cells*/ ?>
    </table><?php printf('%s',$data_hidden); ?><input type="hidden" name="xF_SubmitData" value="1" class="xfABMWidgetButton" /></form>
<?php }else{/*if($data_cells*/ ?>
    &nbsp;
<?php }/*if($data_cells*/ ?>
</td></tr>
<?php if($actions_bottom) { ?>
<tr><th>
    <?php printf('%s',$actions_bottom); ?><br/>
</th></tr>
<?php }/*if($actions_bottom*/ ?>
<?php if( count($set_of_pages)>1 ) { ?>
<tr><th>
    <input value=" &lt;&lt; " name="BackFirst" type="button" onclick="self.location='<?php echo $url_pages[0]; ?>'" <?php if(!$url_pages[0]) echo 'disabled="true"'; ?> />
    <input value=" &lt; " name="Back" type="button"  onclick="self.location='<?php echo $url_pages[1]; ?>'" <?php if(!$url_pages[1]) echo 'disabled="true"'; ?> >
    <select value="GoToPage" name="PageNumber" onchange="self.location='<?php echo $url_pages[2]; ?>'+this.options[this.selectedIndex].value">
    <?php echo XForms::HTML_options($set_of_pages,$this->abm_pageNo); ?>
    </select>
    <input name="Next" value=" &gt; " type="button" onclick="self.location='<?php echo $url_pages[3]; ?>'" <?php if(!$url_pages[3]) echo 'disabled="true"'; ?> />
    <input name="NextLast" value=" &gt;&gt; " type="button" onclick="self.location='<?php echo $url_pages[4]; ?>'" <?php if(!$url_pages[4]) echo 'disabled="true"'; ?> />
</th></tr>
<?php }/*if( $set_of_pages*/ ?>

</th></tr>
</table>
<?php $this->evtAfterTable(); ?>


<script type="text/javascript">
function    zebra_tables(id,start,click) {
    var t=null;
    if(!start) start=0;
    if( (t = self.document.getElementById(id)) ) {
        t = t.getElementsByTagName('TR')
        for(var i=start ; i < t.length ; i++) {
            if(!t[i].className) t[i].className=((i%2)?' rowOdd ':' rowEven ')+t[i].className;
            t[i].onmouseover= function(){ this.className = ' rowRuled '+this.className; return false; }
            t[i].onmouseout = function(){ this.className=this.className.replace(/\s*rowRuled\s*/,' '); return false; }
            if(click) t[i].onclick    = function(evt){ if(!evt) evt=event; if(evt.target!=this) return true;
                if(this.className.indexOf('rowSelected')>-1)
                this.className = this.className.replace(/\s*rowSelected\s*/,' ');
                else this.className = ' rowSelected '+this.className; return false;}
        }
    }
    return false;
}
zebra_tables('xfABMDataTable',1,true);
if(document.forms['xF_FilterForm'] && document.forms['xF_FilterForm'].elements[0] )
    document.forms['xF_FilterForm'].elements[0].focus();
</script>

