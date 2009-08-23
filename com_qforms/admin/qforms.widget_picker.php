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

require_once(dirname(__FILE__).'/../include/prepend.php');
db_connect();
$W_ID= @strval($_GET['W_ID']);
$W_V = @strval($_GET['W_V']);
$W_F = @strval($_GET['W_F']);

if( $t = @$_SESSION['QForms_widget_picker_sets'][$W_ID] ) {
    $result = eval($t);
    if(is_array($result)) {
        if(!is_numeric($W_V)) {
            $temp=$result;
            foreach($temp as $k=>$v) {
                //if( stripos($v,$W_V)===FALSE ) {
                if( strtolower(substr($v,0,strlen($W_V)))!=strtolower($W_V) ) {
                    unset($result[$k]);
                }
            }
            $W_V=null;
        }
?>
<form method="get" onsubmit="window.opener.jscode_setofitems_setvalue('<?php echo $W_F; ?>',this.t.options[this.t.selectedIndex].value,this.t.options[this.t.selectedIndex].text); window.close(); return event.returnValue=false"
<table width="100%">
<tr><td colspan="3" valign="top" align="center">
    <select size="10" name="t" id="t" style="width:100%; height:90%">
    <?php echo LWUtils::HTML_Options($result, $W_V); ?>
    </select>
</td></tr>
<tr>
    <td><input type="button" value="Close" onclick="window.opener.jscode_setofitems_setvalue('<?php echo $W_F; ?>',null,null); window.close();" /></td>
    <td>&nbsp;</td>
    <td><input type="submit" value="Select" /></td>
</tr></table>
</form>

<?php
    }
}else{
    die("UID $W_ID Not found");
}

?>