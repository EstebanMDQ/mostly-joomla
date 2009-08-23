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
$errors         = array();
$actions        = array();

/**
* Render actions
**/
if($t=$this->htmlConfirmActions()) {
    $actions = $t;
}

/**
* Render errors, urls and messages
**/
$errors     = implode('<br/>', $this->abm_errors);
$title      = htmlspecialchars($this->abm_title);
$subtitle   = htmlspecialchars($this->abm_subtitle);
$timeout    = $this->confirm_reload;
$url        = (empty($this->abm_confirm_url)?$this->abm_back_url:$this->abm_confirm_url);

switch($this->currAction) {
case QFORMS_ACTION_INSERT:
    if( empty($this->abm_confirm_url) &&!empty($this->tmp_last_insert_id) )
        $url = $this->abm_view_url.='&xF_record='.$this->tmp_last_insert_id;
    break;
case QFORMS_ACTION_UPDATE:
    if(empty($this->abm_confirm_url))
        $url = $this->abm_view_url.='&xF_record='.$this->currRecord;
    break;
case QFORMS_ACTION_DELETE:
    break;
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html><head><title>Redirecting...</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head>
<body>

<style type="text/css" media="screen">
@import url(<?php echo QFORMS_URI_TEMPLATES; ?>/xforms.template2.css);
</style>

<?php if($url) { ?>
<?php if($timeout>-1) { ?>
<script type="text/javascript">
if(t=top.document.getElementById('xfChangesMadeMessage')) {
    t.style.display='block';
    if(t=top.document.getElementById('xF_FilterForm'))
        t.style.display='block';
}else if(top.Gui && top.Gui.tempFrame && top.Gui.tempFrame.opener) {
    top.Gui.tempFrame.opener.location.href=top.Gui.tempFrame.opener.location.href;
}
if(top.Gui) top.Gui.closeLast();
self.setTimeout("self.location='<?php echo $url; ?>'",0);
</script>
<?php if(!empty($errors)){?>
	<div id="errors">
		<?php echo $errors; ?>
	</div>
<?php }?>


<p><?php echo TR('(if the page does not reload, click <a href="%s">here</a>)', $url) ; ?></p>
<?php }/*if($timeout*/ ?>
<?php }/*if($url*/ ?>
</body>
</html>
