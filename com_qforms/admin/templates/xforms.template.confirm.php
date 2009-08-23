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
$errors     = implode('<br/>', $errors);
$title      = htmlspecialchars($this->abm_title);
$subtitle   = htmlspecialchars($this->abm_subtitle);
$timeout    = $this->confirm_reload;
$url        = (empty($this->abm_confirm_url)?$this->abm_back_url:$this->abm_confirm_url);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head><title>Site</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<style type="text/css" media="screen">
@import url(<?php echo XFORMS_URI_TEMPLATES; ?>xforms.template.css);
</style>
</head>
<body>
<table class="xfABMMainTable">
<tr><td class="xfABMFormTitle"><?php echo $title; ?><?php if($subtitle) { ?> - <?php echo $subtitle; ?><?php }/*if($subtitle*/ ?> - Fin</td></tr>
<tr><td align="center">
    <?php if($actions) { ?>
        <?php echo $actions; ?><br/>
    <?php }/*if($actions*/ ?>
    <br/>
    <h3>La operación ha sido ejecutada satisfactoriamente.</h3>
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
            self.setTimeout("self.location='<?php echo $url; ?>'",<?php echo $timeout*1000; ?>);
            </script>
            <div style="text-align:center; background-color: #EBEBEB; border-top: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #AAAAAA; border-bottom: 1px solid #AAAAAA; font-weight : bold;">
            <p><?php printf('Si ve que la página no recarga automáticamente en %s segundos, <br />Por favor presione <a href="%s">Aquí</a></p>',$timeout,$url);?>
            </div>
        <?php }/*if($timeout*/ ?>
    <?php }/*if($url*/ ?>
</td></tr>
</table>
</body>
</html>
