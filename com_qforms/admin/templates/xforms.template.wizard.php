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
$data_cells=array();
foreach($visible_fields as $name) {
    if( $t = $this->RenderField($name, 0, XFORMS_RENDER_CONTROL) ) {
        if($this->abm_fields[$name]->is_required)
            $t['caption'].=' *';
        $data_cells[$name] = $t;
    }
}

/**
* Render actions
**/
if($t=$this->htmlWizardActions()) {
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
<link rel="stylesheet" href="<?php echo XFORMS_URI_TEMPLATES; ?>nicetitle.css" />
<link rel="stylesheet" href="<?php echo XFORMS_URI_TEMPLATES; ?>xforms.template.css" />
<script type="text/javascript" src="<?php echo XFORMS_URI_TEMPLATES; ?>nicetitle.js" language="JavaScript1.2"></script>
<script type="text/javascript" src="<?php echo XFORMS_URI_TEMPLATES; ?>cssQuery-p.js"></script>
<script type="text/javascript" src="<?php echo XFORMS_URI_TEMPLATES; ?>prototype.js"></script>
<script type="text/javascript" src="<?php echo XFORMS_URI_TEMPLATES; ?>xforms.base.js"></script>
<script type="text/javascript" src="<?php echo XFORMS_URI_TEMPLATES; ?>gui.js"></script>

<form method="post" action="<?php echo $abm_url; ?>" onsubmit="if(self.xF_Validate) return self.xF_Validate(this);">
<?php $this->evtBeforeTable(); ?>
<?php echo $extra_html; ?>
<table class="xfABMFormTable">
<tr><td id="xfABMMainTitle"><?php echo $title; ?><?php if($subtitle) { ?> - <?php echo $subtitle; ?><?php }/*if($subtitle*/ ?></td></tr>
    <?php if($data_cells && $actions) { ?>
        <tr><td class="xfABMMainControls"><?php echo $actions; ?></td></tr>
    <?php }/*if($data_cells && $actions*/ ?>
<?php if($errors) { ?>
    <tr><td><?php echo $errors; ?></td></tr>
<?php }/*if($errors*/ ?>
<tr><td align="center">
<?php echo @$this->wizard_description; ?>
    <?php if($data_cells) { ?>
        <table width="100%">
        <?php foreach($data_cells as $key=>$cell) { ?>
            <?php if(!empty($cell['group']) && $cell['group']!=@$old_group) { $old_group=$cell['group']; ?>
                <tr><th colspan="2"><p><?php echo htmlspecialchars($cell['group']); ?></p></th></tr>
            <?php }/*if(!empty($cell['group*/ ?>
            <tr>
            <?php if(trim($cell['caption'])) { ?><th><?php echo $cell['caption']; ?></th><?php }/*if(trim($cell['caption'])) {*/ ?>
            <td<?php if(!trim($cell['caption'])) { echo ' colspan="2"'; } ?><?php if(!empty($cell['class'])) echo " class=\"$cell[class]\""; ?>><?php echo $cell['html']; ?>
                <?php if($cell['description']) { ?> - <?php echo $cell['description']; ?><?php }/*if($cell['description*/ ?></td>
            </tr>
        <?php }/*foreach($data_filters*/ ?>
        </table>
    <?php }/*if($data_cells*/ ?>
</td></tr>
</table>
<?php $this->evtAfterTable(); ?>
</form>
