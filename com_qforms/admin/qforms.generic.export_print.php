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

/** Set flag that this is a parent file */

function    XGEP_DEBUG($s) {
    return;
    $log = dirname(__FILE__).'/export.log.html';
    if( $f=@fopen($log,((@filesize($log) < 102400 )?'a':'w')) ) {
        fwrite($f,$s);
        fclose($f);
    }elseif( ($log='/tmp/export.log.html') && ($f=@fopen($log,((@filesize($log) < 102400 )?'a':'w'))) ) {
        fwrite($f,$s);
        fclose($f);
    }
}

/**
* Configure this:
**/
if(file_exists(dirname(__FILE__)."/../include/prepend.php")) {
    include_once(dirname(__FILE__)."/../include/prepend.php");
}else{
    include_once($d=dirname(__FILE__)."/test/include/prepend.php");
}
db_connect();

/**
* Este IF es para que IE se digne a escribir en disco el archivo descargado.
**/
if(isset($_SERVER['HTTP_USER_AGENT']) && preg_match("/MSIE/", $_SERVER['HTTP_USER_AGENT'])) {
    session_cache_limiter("public"); // allow private caching, but cache MUST check output
    ini_set('use_trans_sid', 0);
    ini_set('session.use_cookies', 0);
    // IE Bug in download name workaround
    ini_set('zlib.output_compression', 'Off');
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Expires: 0");
    header("Pragma: public");
    header("Cache-Control: public");
}


/**
* Configure HERE
**/
$_styles_file   = QFORMS_PATH_TEMPLATES.'xforms.template.css';
$_scripts_path  = SITE_SCRIPTS;
//SITE_ROOT.'scripts/';
$_xforms_export_CSV     = true;
$_xforms_export_ZIPCSV  = true;

/**
* Main exporting/printing code:
**/
$xF_Module = $_scripts_path.basename(@$_GET['xF_Module']);
$xF_action = @$_GET['xF_action'];

$_GET['xF_pageNo']          = null;
$_GET['xF_RowsPerPage']     = @intval($_GET['xF_ShowTopNRows']);
if(!$_GET['xF_RowsPerPage']) $_GET['xF_RowsPerPage'] =999999;
$_GET['xF_action_avoid']    =true;

if( empty($xF_Module) || empty($xF_action) )
    die("abm_base.php: Falta alguno de los parï¿½metros requeridos");

if( $xF_action=='print' ) {
    $_title = 'Printing';
}else{
    header('Content-type: application/x-msexcel');
    header("Content-Disposition: attachment; filename=Listado-".time().".xls" );
    header("Content-Description: Generated Data" );
    flush();
    $_title = 'Exporting';

/**
* Para exportar, si hay un ABM definido, hago el display Acï¿½.
**/
?>
<html><head><title><?php echo $_title; ?></title>
</head><body><?php include("$xF_Module"); if(isset($abm)) { if(!isset($abm->currAction)) { $abm->Run(); } $abm->Display(); } ?></body></html>
<?php
    flush();

    exit;
}

/**
* Código anterior.
**/
?>
<html><head><title><?php echo $_title; ?></title>
<style type="text/css"><?php if(file_exists($_styles_file)) readfile($_styles_file); ?></style>
<?php if($xF_action=='print') { ?>
    <script>
    function do_print() { window.print(); }
    window.onload=do_print;
    </script>
<?php } ?>
</head><body><?php include("$xF_Module"); if(isset($abm)) { if(!isset($abm->currAction)) { $abm->Run(); } $abm->Display(); } ?></body></html>

