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
$confirm_html = $this->confirm_html;

?>
<table class="xfABMMainTable">
<tr><td class="xfABMFormTitle"><?php printf('%s',$title); ?><?php if($subtitle) { ?> - <?php echo $subtitle; ?><?php }/*if($subtitle*/ ?> - <?php echo XForms::trans('Finish');?></td></tr>
<tr><td align="center">
    <?php if($actions) { ?>
        <?php printf('%s',$actions); ?><br/>
    <?php }/*if($actions*/ ?>
    <br/>
    <?php if($confirm_html) { ?>
        <?php printf('%s',$confirm_html); ?>
    <?php } else{ ?>
        <h3><?php echo XForms::trans('The operation has been successful.');?></h3>
    <?php }/*if($confirm_html*/ ?>
    <?php if($url) { ?>
        <?php if($timeout>-1) { ?>
            <script type="text/javascript">
            window.setTimeout("self.location='<?php echo $this->abm_back_url; ?>'; if(parent&&parent.subModalClosedEvent) { parent.subModalClosedEvent(); }",<?php echo $timeout*1000; ?>);
            </script>
            <div style="text-align:center; background-color: #EBEBEB; border-top: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #AAAAAA; border-bottom: 1px solid #AAAAAA; font-weight : bold;">
            <p><?php echo XForms::trans('If the page does not refresh automatically in');?> <?php echo $timeout; ?> <?php echo XForms::trans('seconds');?>, <br /><?php echo XForms::trans('Please press');?> <a href="<?php echo $this->abm_back_url; ?>"><?php echo XForms::trans('Here');?></a></p>
            </div>
        <?php }/*if($timeout*/ ?>
    <?php }/*if($url*/ ?>
</td></tr>
</table>
