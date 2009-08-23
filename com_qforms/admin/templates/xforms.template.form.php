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
$data_cells=array();
foreach($visible_fields as $name) {
    if( $t = $this->RenderField($name, 0, $as_static) ) {
        if($this->abm_fields[$name]->is_required)
            $t['caption'].=' *';
        $data_cells[$name] = $t;
    }
}

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
<form id="XFABMForm" method="post" action="<?php echo $abm_url; ?>" onsubmit="if(self.xF_Validate) return self.xF_Validate(this);">
<?php $this->evtBeforeTable(); ?>
<?php echo $extra_html; ?>
<table class="xfABMFormTable">
<tr><td class="xfABMFormTitle"><?php echo $title; ?><?php if($subtitle) { ?> - <?php echo $subtitle; ?><?php }/*if($subtitle*/ ?></td></tr>
<tr><td><?php $this->evtBeforeData(); ?></td></tr>
<?php if($errors) { ?>
    <tr><td><?php echo $errors; ?></td></tr>
<?php }/*if($errors*/ ?>
<tr><td align="center">
    <?php if($data_cells) { ?>
        <table width="100%">
        <?php foreach($data_cells as $key=>$cell) { ?>
            <?php if(!empty($cell['group']) && $cell['group']!=@$old_group) { $old_group=$cell['group']; ?>
                <tr><th colspan="2"><p><?php echo htmlspecialchars($cell['group']); ?></p></th></tr>
            <?php }/*if(!empty($cell['group*/ ?>
            <tr>
            <th><?php echo $cell['caption']; ?></th>
            <td<?php if(!empty($cell['class'])) echo " class=\"$cell[class]\""; ?>><?php echo $cell['html']; ?>
                <?php if($cell['description']) { ?> - <?php echo $cell['description']; ?><?php }/*if($cell['description*/ ?></td>
            </tr>
        <?php }/*foreach($data_filters*/ ?>
        </table>
    <?php }/*if($data_cells*/ ?>
</td></tr>
    <?php if($data_cells && $actions) { ?>
        <tr><td class="xfABMFormFooter"><?php echo $actions; ?></td></tr>
    <?php }/*if($data_cells && $actions*/ ?>
</table>
<?php $this->evtAfterTable(); ?>
</form>
<script type="text/javascript">
function    xforms_form_init() {
    var t=null;
    if( (t = self.document.getElementById('XFABMForm')) )
        if(t.elements[0] && t.elements[0].focus)
            t.elements[0].focus();
}
xforms_form_init();

    document.write( '' + Math.round(document.body.innerHTML.length / 1024) + 'kb' );
</script>
