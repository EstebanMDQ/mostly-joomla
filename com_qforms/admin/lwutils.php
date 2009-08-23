<?php
/*
 *    This file is part of QForms
 *
 *    Foobar is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    Foobar is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
 */

class   LWUtils {

    function    clear_log_file($file) {
        system("/bin/tail -n1000000 $file > $file.1 ; mv -f $file.1 $file");
    }
    function    x_qDate($fecha, $cvt=null) {
        $r=array();
        if(is_array($fecha)) {
            if(count($fecha)==3 && $fecha[0])
                $fecha = adodb_mktime(0,0,0, $fecha[1], $fecha[0], $fecha[2]);
            elseif(count($fecha)==2 && $fecha[0])
                $fecha = adodb_mktime(0,0,0, $fecha[0], 1, $fecha[1]);
            else
                $fecha = null;
        }elseif( is_string($fecha) && (empty($fecha) || $fecha=='0000-00-00') ) {
            $fecha = '';
        }elseif( is_string($fecha) && ereg('^([0-9][0-9][0-9][0-9])[^0-9]([0-9]{1,2})[^0-9]([0-9][0-9])[^0-9]([0-9][0-9])[^0-9]([0-9][0-9])[^0-9]([0-9][0-9])[^0-9][0-9][0-9]$',$fecha,$r)) { // formato YYYY-MM-DD HH:MM:SS-n Postress...
            $fecha = adodb_mktime($r[4],$r[5],$r[6],$r[2],$r[3],$r[1]);
        }elseif( is_string($fecha) && ereg('^([0-9][0-9][0-9][0-9])[^0-9]([0-9]{1,2})[^0-9]([0-9][0-9])[^0-9]([0-9][0-9])[^0-9]([0-9][0-9])[^0-9]([0-9][0-9])$',$fecha,$r)) { // formato YYYY-MM-DD HH:MM:SS o YYYY/MM/DD o HH:MM:SS formato 24hs
            $fecha = adodb_mktime($r[4],$r[5],$r[6],$r[2],$r[3],$r[1]);
        }elseif( is_string($fecha) && ereg('^([0-9][0-9][0-9][0-9])[^0-9]([0-9]{1,2})[^0-9]([0-9]{1,2})$',$fecha,$r)) { // formato YYYY-MM-DD o YYYY/MM/DD
            $fecha = adodb_mktime(0,0,0,$r[2],$r[3],$r[1]);
        }elseif( is_string($fecha) && ereg('^(2[0-9][0-9][0-9])([0-9][0-9])([0-9][0-9])$',$fecha,$r)) { // formato YYYYMMDD
            $fecha = adodb_mktime(0,0,0,$r[2],$r[3],$r[1]);
        }elseif( is_string($fecha) && ereg('^([0-9]{1,2})[^0-9]([0-9]{1,2})[^0-9]([0-9]{1,4})$',$fecha,$r)) { // DD/MM/YYYY
            $fecha = adodb_mktime(0,0,0,$r[2],$r[1],$r[3]);
        }elseif( is_string($fecha) && ereg('^([0-9][0-9])[^0-9]([0-9]{1,2})[^0-9]([0-9][0-9][0-9][0-9])[^0-9]([0-9][0-9])[^0-9]([0-9][0-9])$',$fecha,$r)) { // formato DD-MM-YYYY HH:MM o DD/MM/YYYY HH:MM
                        $fecha = adodb_mktime($r[4],$r[5],0,$r[2],$r[1],$r[3]);
        }elseif( is_string($fecha) && ereg('([0-9]{4})-([0-9]{1,2})-([0-9]{1,2}) ([0-2][0-9]):([0-5][0-9]):([0-5][0-9])\.[0-9]+',$fecha,$r)) { // formato DD-MM-YYYY HH:MM o DD/MM/YYYY HH:MM
                        $fecha = adodb_mktime($r[4],$r[5],$r[6],$r[2],$r[3],$r[1]);
        }elseif($fecha=='NOW') {
            $fecha = time();
        }
        if($fecha!==null && $cvt)
            return date($cvt,$fecha);
        return $fecha;
    }

    function    HTML_options($array, $selected, $raw=false) {
        $buf = '';
        $selected=($raw?$selected:htmlspecialchars($selected));
        foreach($array as $k=>$v)
            $buf .= '<option value="'.($raw?$k:htmlspecialchars($k)).'"'.(($selected==$k)?' selected="selected"':'').'>'.($raw?$v:htmlspecialchars($v)).'</option>';
        return $buf;
    }

    function microtime_float() {
       list($usec, $sec) = explode(" ", microtime());
       return ((float)$usec + (float)$sec);
    }

    function    DEBUGIT($msg, $module='', $timer=null) {
        if(!file_exists(QFORMS_DEBUG_FILE)) return;
        // sálo mantengo unos... 10 megas de log, y despuás borro.
        if(filesize(QFORMS_DEBUG_FILE)>=10485760) fclose(fopen(QFORMS_DEBUG_FILE,'w'));
        if(! ($uid=@$GLOBALS['xoopsUserId']) && @is_object($GLOBALS['xoopsUser']))
            $uid = $GLOBALS['xoopsUser']->vars['uid'];
        if(! ($sig = substr(@session_id(),0,4).substr(@session_id(),-4)) ) $sig="--------";
        if(in_array(substr($module,0,2),array('DB','SQ','sqlquery','XOOPS::queryF')))
            $msg=trim(preg_replace('/\s+/',' ',$msg),"; ");
        if($timer!=null) $msg = sprintf("[%.3f %02d] %s", (LWUtils::microtime_float()-$timer)/10.0, (@$GLOBALS['DEBUGIT_TIMERCOUNTER']++), $msg);
        error_log(sprintf("%s %5.5s %8.8s %5.5s %-20.20s %-25.25s: %s;\n",
            date('Y-m-d,H:i:s'), getmypid(), $sig, @intval($uid), @$_SERVER['SCRIPT_NAME'], $module,
            $msg), 3,QFORMS_DEBUG_FILE);
    }

    function ERROR_ON($cond, $msg, $send_mail=false) {
        if($cond) return LWUtils::FATALERROR($msg, $send_mail);
    }

    function FATALERROR($msg, $send_mail=false) {
        $debug_backtrace='';
        foreach(debug_backtrace() as $idx=>$r) {
            $param = array();
            if(@$r['args']) foreach($r['args'] as $a) $param[]=addcslashes(var_export($a,true),"\0..\37!@\@\177..\377");
            $debug_backtrace .= sprintf(" at %s %s %s%s(%s);\n",
                $r['file'], $r['line'], (@$r['class'].@$r['type']),@ $r['function'], implode(',',$param) );
        }

        LWUtils::DEBUGIT($msg."\n\n$debug_backtrace\n\n".var_export(@$_SERVER,true)."\n\n",'ERROR');

        if(true||$send_mail) {
            mail('nahuel@latinwit.com',"$_SERVER[HTTP_HOST] : ERROR!",
                "\nDate: ".date('Y-m-d H:i:s')."\nREQUEST_URI: $_SERVER[REQUEST_URI]\nmessage: $msg".
                "\n\n$debug_backtrace\n\n".var_export(@$_SERVER,true)."\n\n"
                .((defined('QFORMS_DEBUG_FILE')&&file_exists(QFORMS_DEBUG_FILE))? shell_exec("tail -n100 ".QFORMS_DEBUG_FILE):"-")
                );
        }

        if(file_exists(dirname(dirname(__FILE__)).'/error_message.php')) {
            include(dirname(dirname(__FILE__)).'/error_message.php');
            exit;
        }
        die("<h3>$msg</h3>");
    }

    function    QFORMS_URL($url, $var=null, $val=null, $ch_base=null, $force_nonice_url=false) {
        if(strpos($url,'.php/,')) {
            @list($base, $query) = preg_split('|\.php/,|', $url,2);
            $base .= ".php";
        }else{
            @list($base, $query) = explode('?', $url, 2);
        }
        $query=str_replace(',','=',str_replace('/','&', str_replace('?','&', $query)));

        $vars = array();
        if($query) {
            $query = explode('&', $query);
            foreach($query as $i) {
                @list($k,$v)= explode('=',$i,2);
                if(!trim($k)) continue;
                if(isset($vars[$k])) $vars[$k] .= '&'.$i;
                else $vars[$k] = $i;
            }
        }
        $vars['RvL']            = "RvL=".time();
        if($var) {
            if(is_array($var)) {
                $vset = $var;
                foreach($vset as $var=>$val)
                    if(!is_null($val)) $vars[$var] = "$var=$val"; else unset($vars[$var]);
            }else{
                if(!is_null($val)) $vars[$var] = "$var=$val"; else unset($vars[$var]);
            }
        }
        #printf("b:%s|dn:%s|ch:%s|<br>\n", $base, dirname($base), $ch_base);
        if($ch_base) $base=dirname($base).$ch_base;
        if($base=='/') $base="/index.php";
        $url = "$base?".trim(implode("&",$vars),'&');
        $url = trim($url,'&?');
        if(!empty($GLOBALS['SITE_URL_flag_nice'])&&!$force_nonice_url) {
            $url = str_replace('=',',',str_replace('&','/',$url));
            $url = str_replace('?','/,',$url);
        }
        $url = str_replace('//','/',$url);
        $url = str_replace('http:/','http://',$url);
        return $url;
    }
    function    SITE_URL_PARSE_NICE() {
        if(!empty($GLOBALS['SITE_URL_flag_nice'])) {

            if(strpos($_SERVER['REQUEST_URI'],'.php/,')) {
                @list($base, $query) = preg_split('|\.php/,|', $_SERVER['REQUEST_URI'],2);
            }else{
                @list($base, $query) = explode('?', $_SERVER['REQUEST_URI'], 2);
            }

            $_SERVER['QUERY_STRING']=$query;
            #echo "IN.QS: ",$_SERVER['QUERY_STRING']."<br>\n";
            #echo "IN.RU: ",$_SERVER['REQUEST_URI']."<br>\n";

            $_SERVER['QUERY_STRING']=str_replace(',','=',str_replace('/','&',str_replace('?','&',$_SERVER['QUERY_STRING'])));

            #echo "IN.QS: ",$_SERVER['QUERY_STRING']."<br>\n";

            $query =$_SERVER['QUERY_STRING'];

            $vars = $vars2 = array();
            if($query) {
                $query = explode('&', $query);
                foreach($query as $i) {
                    @list($k,$v)= explode('=',$i,2);
                    $vars[$k] = urldecode($v);
                    #$vars2[$k] = $k.'='.($v);
                    $vars2[$k] =$v;
                }
            }
            $_GET=$vars;
            $_SERVER['REQUEST_URI']  = LWUtils::SITE_URL($_SERVER['REQUEST_URI'],$vars2);
            #$_SERVER['QUERY_STRING'] = implode('&',$vars2);

            #echo "OUT.QS: ",$_SERVER['QUERY_STRING']."<br>\n";
            #echo "OUT.RU: ",$_SERVER['REQUEST_URI']."<br>\n";
            #var_dump($_GET);
            #echo "<hr>\n";
        }
    }

    function    CalcularPaginacion(&$pageNo, $rowsTotal, $rowsPerPage) {
        $pageCount = ceil( $rowsTotal / $rowsPerPage);
        if($pageNo>$pageCount) $pageNo = $pageCount;
        if($pageNo<=0) $pageNo = 1;
        $limit_start = ($pageNo-1)*$rowsPerPage;
        return array($pageCount,$limit_start);
    }
    function    MostrarPaginacion($pageNo, $pageCount, $url=null) {
        $url_base = substr(LWUtils::SITE_URL(LWUtils::SITE_URL($url?$url:$_SERVER['REQUEST_URI'],'pageNo'),'pageNo','1'),0,-1);
        $first  = (($pageNo>1) ? ($url_base.'1') :null);
        $last   = (($pageNo<$pageCount) ? ($url_base.$pageCount) :null);
        $prev   = (($pageNo>1) ? ($url_base.($pageNo-1)) :null);
        $next   = (($pageNo<$pageCount) ? ($url_base.($pageNo+1)) :null);
        if($pageCount>1)
            foreach( range(1,$pageCount) as $p)
                $set_of_pages[$p] = "Página $p";
        else $set_of_pages=array();
        ob_start();
        if( $set_of_pages ) { ?>
            <input value=" &lt;&lt; " name="BackFirst" type="button" onclick="self.location='<?php echo $first; ?>'" <?php if(!$first) echo 'disabled="true"'; ?> />
            <input value=" &lt; " name="Back" type="button"  onclick="self.location='<?php echo $prev; ?>'" <?php if(!$prev) echo 'disabled="true"'; ?> >
            <select value="GoToPage" name="PageNumber" onchange="self.location='<?php echo $url_base; ?>'+this.options[this.selectedIndex].value">
            <?php echo LWUtils::HTML_options($set_of_pages,$pageNo); ?>
            </select>
            <input name="Next" value=" &gt; " type="button" onclick="self.location='<?php echo $next; ?>'" <?php if(!$next) echo 'disabled="true"'; ?> />
            <input name="NextLast" value=" &gt;&gt; " type="button" onclick="self.location='<?php echo $last; ?>'" <?php if(!$last) echo 'disabled="true"'; ?> />
        <?php }/*if( $set_of_pages*/
        $buf=ob_get_contents();
        ob_end_clean();
        return $buf;
    }

    function    MostrarPaginacionLinks($pageNo, $pageCount, $url=null) {
        $url_base = substr(LWUtils::SITE_URL(LWUtils::SITE_URL($url?$url:$_SERVER['REQUEST_URI'],'pageNo'),'pageNo','1'),0,-1);
        $first  = (($pageNo>1) ? ($url_base.'1') :null);
        $last   = (($pageNo<$pageCount) ? ($url_base.$pageCount) :null);
        $prev   = (($pageNo>1) ? ($url_base.($pageNo-1)) :null);
        $next   = (($pageNo<$pageCount) ? ($url_base.($pageNo+1)) :null);
        $setof_pages=range( (($pageNo-10)>0?($pageNo-10):1) , (($pageNo+10)<$pageCount?($pageNo+10):$pageCount) );
        $buf='';
        if( $setof_pages ) {
            #if($first)  $buf.=sprintf('<a href="%s">First</a> ',$first);    else $buf.= 'First ';
            if($prev)   $buf.=sprintf('<a href="%s">&lt;&lt; Prev</a> ',$prev);      else $buf.= '&lt;&lt; Prev ';
            foreach($setof_pages as $idx=>$i)
                $setof_pages[$idx]=(($pageNo==$i)?$i:sprintf('<a href="%s">%s</a>',$url_base.$i,$i));
            $buf.=' &nbsp; '.implode(', ',$setof_pages).' &nbsp; ';
            if($next)   $buf.=sprintf('<a href="%s">Next &gt;&gt;</a> ',$next);      else $buf.= 'Next &gt;&gt;';
            #if($last)   $buf.=sprintf('<a href="%s">Last</a> ',$last);      else $buf.= 'Last ';
        }
        return $buf;
    }
    function    x_get_html_table($a, $id=null, $headers=null, $extra_html=null, $extra_row_html=null) {
        $buf='&nbsp;';
        if(empty($headers))
            $headers = @array_keys(reset($a));
        if($headers) {
            $buf = '<table '.($id?"id=\"$id\"":"").' class="xgettable">';
            $buf .= "<tr>";
            foreach($headers as $name)
                $buf .= '<th>'.htmlspecialchars($name).'</th>';
            $buf .= "</tr>\n";

            foreach($a as $rid=>$rec) {
                $buf .= '<tr'.@$extra_row_html[$rid].'>';
                foreach($rec as $name=>$value) {
                    $html = @$extra_html[$name];
                    if( is_numeric($value) ) {
                        $buf .= '<td align="right" '.$html.'>'.htmlspecialchars($value).'</td>';
                    }elseif( is_array($value) ) {
                        $buf .= '<td '.$html.'>'.implode('',$value).'</td>';
                    }else{
                        $buf .= '<td '.$html.'>'.htmlspecialchars($value).'</td>';
                    }
                }
                $buf .= "</tr>\n";
            }
            $buf .= "</table>\n\n";
        }
        return $buf;
    }
    function    x_get_html_array($a, $id=null) {
        $buf = '<table '.($id?"id=\"$id\"":"").' class="xgettable">';
        $html='';
        foreach($a as $name=>$value) {
            $buf .= '<tr>';
            $buf .= '<th '.$html.'>'.$name.'</th>';
            if( is_numeric($value) ) {
                $buf .= '<td align="right" '.$html.'>'.htmlspecialchars($value).'</td>';
            }elseif( is_array($value) ) {
                $buf .= '<td '.$html.'>'.implode('',$value).'</td>';
            }else{
                $buf .= '<td '.$html.'>'.htmlspecialchars($value).'</td>';
            }
            $buf .= "</tr>\n";
        }
        $buf .= "</table>\n\n";
        return $buf;
    }

    function SQLQuery_mysql($sql, $mode, $start=0, $count=0, $fatal_errors=true, $db=null) {
        #descomentar para activar el soporte mysqli if(function_exists('mysqli_connect')) return LWUtils::SQLQuery_mysqli($sql, $mode, $start, $count, $fatal_errors, $db);
        if(empty($db)&&!empty($GLOBALS['SQLQuery_db'])) $db=&$GLOBALS['SQLQuery_db'];
        switch($mode) {
            case 100:
                if(!is_array($sql)) $sql=explode(';',$sql);
                $db = mysql_connect($sql[0], $sql[1], $sql[2])
                    or LWUtils::FATALERROR("DB (".mysql_errno($db) .") ". mysql_error($db)." : $sql");
                mysql_select_db($sql[3],$db)
                        or LWUtils::FATALERROR("DB (".mysql_errno($db) .") ". mysql_error($db)." : $sql");
                return $GLOBALS['SQLQuery_db']=$db;
                break;
            /**
            * DB-DECLARE, para compatibilidad con, por ejemplo, PEAR.
            * SQLQuery($db->connection,101);
            **/
            case 101:
                return $GLOBALS['SQLQuery_db']=$sql;
                break;
        }
        $sql=trim($sql," \t\n\r;");
        if($start||$count)
            $sql .= " LIMIT $start,$count";

        // profiling...
        $time_start = explode(" ", "".microtime() );
        $time_start = $time_start[1] . substr($time_start[0],1);

        $res = mysql_query($sql, $db);

        // profiling...
        $time_end = explode(" ", "".microtime() );
        $time_end = $time_end[1] . substr($time_end[0],1);

        // debugging
        if(empty($GLOBALS['SQLQuery_query_counter'])) $GLOBALS['SQLQuery_query_counter']=1;
        LWUtils::DEBUGIT($sql,sprintf('DB(#%2.2d %2.2fs M%1.1d)', $GLOBALS['SQLQuery_query_counter']++, $time_end-$time_start, $mode));

        if(!$res) {
            if($fatal_errors) {
                LWUtils::FATALERROR("DB (".mysql_errno($db) .") ". mysql_error($db)." : $sql");
            }else{
                return (object)array(mysql_errno($db),mysql_error($db),$sql);
            }
        }
        $data = array();
        if($res!==TRUE) {
            switch($mode) {
            case 1: if($t=mysql_fetch_array($res, MYSQL_ASSOC)) $data=@reset($t); break;
            case 2: if($t=mysql_fetch_array($res, MYSQL_ASSOC)) $data=$t; break;
            case 3: while( $t = mysql_fetch_array($res, MYSQL_ASSOC) ) $data[]=$t; break;
            case 4: /*NADA ESTE ES EL EXEC */ break;
            case 5: $data=array(); while( $t = mysql_fetch_array($res, MYSQL_ASSOC) ) $data[ reset($t) ] = next($t); break;
            case 6: $data=array(); while( $t = mysql_fetch_array($res, MYSQL_ASSOC) ) $data[ reset($t) ] = $t; break;
            case 7: return $res;
            }
            mysql_free_result($res);
        }
        return $data;
    }

    function SQLQuery($sql, $mode, $start=0, $count=0, $fatal_errors=true, $db=null) {
        if(@$GLOBALS['SQLQuery_default_mode']=='pgsql')
            return LWUtils::SQLQuery_pgsql($sql, $mode, $start, $count, $fatal_errors, $db);
        return LWUtils::SQLQuery_mysql($sql, $mode, $start, $count, $fatal_errors, $db);
    }

    function SQLQuery_pgsql($sql, $mode, $start=0, $count=0, $fatal_errors=true, $db=null) {
        if(empty($db)&&!empty($GLOBALS['SQLQuery_db'])) $db=&$GLOBALS['SQLQuery_db'];
        switch($mode) {
            case 100:
                if(!is_array($sql)) $sql=explode(';',$sql);
				$db = pg_connect("host=$sql[0] port=$sql[4] dbname=$sql[3] user=$sql[1] password=$sql[2]")
                    or die("DB (".pg_last_error($db) .") ". pg_last_error($db)." : $sql");
                return $GLOBALS['SQLQuery_db']=$db;
                break;
        }
        $sql=trim($sql," \t\n\r;");
        if($start||$count)
            $sql .= " LIMIT $count OFFSET $start";

        $time_start = LWUtils::microtime_float();
        $res = pg_query($db,$sql);
        LWUtils::DEBUGIT($sql, 'SQLQuery', $time_start);

        if(!$res) {
        LWUtils::DEBUGIT($sql, 'SQLQuery.ERROR', $time_start);
            if($fatal_errors) {
                die("DB ". pg_last_error($db)." : $sql");
            }else{
                die("DB ". pg_last_error($db)." : $sql");
                return false;
                //return (object)array(mysql_errno($db),mysql_error($db),$sql);
            }
        }
        $data = array();
        if($res!==TRUE) {
            switch($mode) {
            case 1: if($t=pg_fetch_array($res, null, PGSQL_ASSOC)) $data=@reset($t); break;
            case 2: if($t=pg_fetch_array($res, null, PGSQL_ASSOC)) $data=$t; break;
            case 3: while( $t = pg_fetch_array($res, null, PGSQL_ASSOC) ) $data[]=$t; break;
            case 4: /*NADA ESTE ES EL EXEC */ break;
            case 5: $data=array(); while( $t = pg_fetch_array($res, null, PGSQL_ASSOC) ) $data[ reset($t) ] = next($t); break;
            case 6: $data=array(); while( $t = pg_fetch_array($res, null, PGSQL_ASSOC) ) $data[ reset($t) ] = $t; break;
            case 7: return $res;
            }
            pg_free_result($res);
        }
        return $data;
    }

    function SQLQuery_mysqli($sql, $mode, $start=0, $count=0, $fatal_errors=true, $db=null) {
        /* create a connection object which is not connected */
        $db=&$GLOBALS['SQLQuery_mysqli'];

        /* connect to server */

        switch($mode) {
            case 100:
                if(!is_array($sql)) $sql=explode(';',$sql);
                $GLOBALS['SQLQuery_mysqli'] =& new mysqli($sql[0], $sql[1], $sql[2],$sql[3]);
                if(mysqli_connect_errno())
                    LWUtils::FATALERROR("DB (".mysqli_connect_errno() .") ". mysqli_connect_error()." : $sql");
                if (!$GLOBALS['SQLQuery_mysqli']->set_charset("utf8")) {
                    printf("Error loading character set utf8: %s\n", $GLOBALS['SQLQuery_mysqli']->error);
                }

                /* set connection options */
                $GLOBALS['SQLQuery_mysqli']->options(MYSQLI_INIT_COMMAND, "SET AUTOCOMMIT=0");
                $GLOBALS['SQLQuery_mysqli']->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
                return true;
                break;
            /**
            * DB-DECLARE, para compatibilidad con, por ejemplo, PEAR.
            * SQLQuery($db->connection,101);
            **/
            case 101:
                return $GLOBALS['SQLQuery_mysqli']=$sql;
                break;
        }
        $sql=trim($sql," \t\n\r;");
        if($start||$count)
            $sql .= " LIMIT $start,$count";

        // profiling...
        $time_start = explode(" ", "".microtime() );
        $time_start = $time_start[1] . substr($time_start[0],1);

        $res = $GLOBALS['SQLQuery_mysqli']->query($sql);

        // profiling...
        $time_end = explode(" ", "".microtime() );
        $time_end = $time_end[1] . substr($time_end[0],1);

        // debugging
        if(empty($GLOBALS['SQLQuery_query_counter'])) $GLOBALS['SQLQuery_query_counter']=1;
        LWUtils::DEBUGIT($sql,sprintf('DB(#%2.2d %2.2fs M%1.1d)', $GLOBALS['SQLQuery_query_counter']++, $time_end-$time_start, $mode));

        if(!$res) {
            if($fatal_errors) {
                LWUtils::FATALERROR("DB (".$GLOBALS['SQLQuery_mysqli']->errno .") ". $GLOBALS['SQLQuery_mysqli']->error." : $sql");
            }else{
                return (object)array($GLOBALS['SQLQuery_mysqli']->errno,$GLOBALS['SQLQuery_mysqli']->error,$sql);
            }
        }
        /**
         * 1 - traer el primer valor
         * 2 - la primer fila del resultset
         * 3 - array de array assoc
         * 4 - exec
         * 5 - array kv
         */
        $data = array();
        if(is_object($res)) {
            switch($mode) {
            case 1: if($t=$res->fetch_array(MYSQLI_ASSOC)) $data=@reset($t); break;
            case 2: if($t=$res->fetch_array(MYSQLI_ASSOC)) $data=$t; break;
            case 3: while( $t = $res->fetch_array(MYSQLI_ASSOC) ) $data[]=$t; break;
            case 4: /*NADA ESTE ES EL EXEC */ break;
            case 5: $data=array(); while( $t = $res->fetch_array(MYSQLI_ASSOC) ) $data[ reset($t) ] = next($t); break;
            case 6: $data=array(); while( $t = $res->fetch_array(MYSQLI_ASSOC) ) $data[ reset($t) ] = $t; break;
            }
            $res->close();
        }
        return $data;
    }

    /**
    * Funcion rápida para llamar despues de un post
    **/
    function    SITE_REDIRECT($msg, $url, $seconds=1, $with_header=true) {
        global $mosConfig_live_site;
        $with_header=false;
        if(substr($url,0,4)!='http')  $url = "http://".str_replace('//','/',"$_SERVER[HTTP_HOST]/$url");
        $pageTitle='Redirecting...';
        echo <<<__EOT__
<html><head>
<title>Qforms</title>
</head><body>
__EOT__;
        echo "<h3>$msg</h3>";
        echo '<p>(if the page doesn\'t reload '.($seconds>1?" in $seconds seconds":"").', please, click <a href="'.$url.'">here</a>)</p>';
        echo '<script type="text/javascript">window.setTimeout("window.location=\''.$url.'\';",'.(1000*$seconds).');</script>';
        echo '</div></body></html>';
        exit;
    }
    /**
    * NAHUEL 20040930: Utility
    * set: array o cadena de caracteres separados por espacios o coma con los nombres de parametros
    **/
    function    GPC_Collector($set, $from_get=false) {
        $result=array();
        if(!is_array($set)) $set = preg_split('/[,\s]+/',$set);
        foreach($set as $var) {
            if($from_get && isset($_GET[$var])) $val = $_GET[$var];
            elseif(isset($_POST[$var]))         $val = $_POST[$var];
            else                                $val = null;
            if($val!==null && !get_magic_quotes_gpc() && !is_array($val) )
                $val = addslashes($val);
            $result[$var]=$val;
        }
        return $result;
    }

    /**
    * Leo TODO El archivo CSV en memoria y codifico las cadenas.
    * Hago esto para que el práximo paso, csvRead , pueda leer linea a linea el archivo
    * y tener la seguridad de que no haya enteres en cadenas de caracteres. Con esto aseguramos que
    * una linea sea igual a un registro.
    *
    * si $outputfile es null, se devuelve el contenido del archivo, no se graba en disco.
    **/
    function        csvNormalize($inputfile, $outputfile=null, $D=";"){
        if( !file_exists($inputfile) ) return false;
        $file = file_get_contents($inputfile);
        $file = trim(preg_replace_callback('/"(.*)"(?='.$D.'|(?:\r?\n))/smU', create_function('$m',
                'return "\"".urlencode(stripslashes(str_replace(\'""\',\'"\',$m[1])));'), $file));
        if(!$outputfile)
            return $file;
        if( $fp=fopen($outputfile, 'w') ){
            fputs($fp,$file);
            fclose($fp);
            return true;
        }else{
            return false;
        }
    }
    /**
    * $content tiene una o más lineas de CSV, separadas por nueva linea,
    * con campos separados por $D.
    * Devuelvo un array con un array de campos por cada linea.
    **/
    function csvRead( $content, $D = ';', $colnames=array(), $firstline_colnames=false ) {
        $content = str_replace("\r","\n", str_replace("\r\n","\n", $content));
        $data=array();
        if(!is_array($colnames)) $colnames=preg_split('/[,\s]+/',$colnames);
        $counter=0;
        foreach(explode("\n",$content) as $lid=>$line) {
            foreach(explode($D,$line) as $cid=>$col) {
                if(isset($colnames[$cid])) $cid=$colnames[$cid];
                if(substr($col,0,1)=='"') $col=urldecode(substr($col,1));
                elseif(substr($col,0,2)=="\\N") $col = array('NULL');
                if($counter==0 && $firstline_colnames) {
                    $colnames[$cid]=$col;
                }else{
                    $data[$counter][$cid]=$col;
                }
            }
            $counter++;
        }
        return $data;
    }
    /**
    * $content tiene una o más lineas de CSV, separadas por nueva linea,
    * con campos separados por $D.
    * Devuelvo un array con un array de campos por cada linea.
    **/
    function csvWrite( &$array, $D = ';',$exclude_fields=array()) {
        if(!is_array($exclude_fields)) $exclude_fields = preg_split('/[,\s]/', trim($exclude_fields));
        $content = "";
        foreach($array as $idx=>$rec) {
            foreach($rec as $key=>$val) {
                if(in_array($key,$exclude_fields)) continue;
                if(is_numeric($val))
                    $rec[$key]=$val;
                else
                    $rec[$key]=str_replace('"','""',addslashes($val));
            }
            $content .= implode($D,$rec)."\n";
        }
        return $content;
    }
    function csvRow( &$rec, $D = ';', $exclude_fields=array()) {
        foreach($rec as $key=>$val) {
            if(in_array($key,$exclude_fields)) continue;
            if(is_numeric($val))
                $rec[$key]=$val;
            else
                $rec[$key]=str_replace('"','""',addslashes($val));
        }
        return implode($D,$rec);
    }
    function    cutString($s, $max) {
        if(strlen($s)>$max) return substr($s,0,$max-3).'...';
        return $s;
    }

    function    ProfilerPageStart() {
        $t = explode(" ", "".microtime() );
        $GLOBALS['LWUtils::ProfilerPageStart'] = $t = $t[1] . substr($t[0],1);
        register_shutdown_function(create_function('','LWUtils::ProfilerPageStop();'));

        LWUtils::DEBUGIT(@$_SERVER['REQUEST_URI'], sprintf('PREPEND %-15.15s', @$_SERVER['REMOTE_ADDR']));
    }
    function    ProfilerPageCheck($msg=null) {
        $t = explode(" ", "".microtime() );
        $t = $t[1] . substr($t[0],1);
        $t -= @intval($GLOBALS['LWUtils::ProfilerPageStart']);
        LWUtils::DEBUGIT( ($msg?"$msg ":"").@$_SERVER['REQUEST_URI'], sprintf('PREPEND_CHK(%3.2fs)', $t));
    }
    function    ProfilerPageStop() {
        $t = explode(" ", "".microtime() );
        $t = $t[1] . substr($t[0],1);
        $t -= @intval($GLOBALS['LWUtils::ProfilerPageStart']);
        LWUtils::DEBUGIT(@$_SERVER['REQUEST_URI'], sprintf('PREPEND_END(%3.2fs)', $t));
    }

    /**
    * Helper function
    **/
    function    data_subset($data, $subset=null) {
        if(!is_array($subset)) {
            $t=preg_split('/[,\s]+/',$subset);
            $subset=array();
            foreach($t as $i=>$kv) {
                @list($k,$v)=preg_split('/:/',$kv,2);
                $subset[$k] = ($v?$v:$k);
            }
        }
        $result=array();
        foreach($data as $k=>$v) if(!empty($subset[$k])) $result[$subset[$k]]=$v;
        return $result;
    }
    // Lo mismo, pero con un array de arrays (tabla)
    function    data_subset_array($data, $subset=null) {
        foreach($data as $idx=>$rec) {
            $data[$idx]=LWUtils::data_subset($rec,$subset);
        }
        return $data;
    }

    function    displayMessage($msg) {
        if(empty($GLOBALS['LWUtils::displayMessage'])) $GLOBALS['LWUtils::displayMessage']=array();
        $GLOBALS['LWUtils::displayMessage'][] = $msg;
    }
    function    getDisplayedMessages() {
        if(empty($GLOBALS['LWUtils::displayMessage'])) $GLOBALS['LWUtils::displayMessage']=array();
        return $GLOBALS['LWUtils::displayMessage'];
    }

    function dateDiff($interval,$dateTimeBegin,$dateTimeEnd) {
         //Parse about any English textual datetime
         //$dateTimeBegin, $dateTimeEnd

         $dateTimeBegin=strtotime($dateTimeBegin);
         if($dateTimeBegin === -1) {
           return("..begin date Invalid");
         }

         $dateTimeEnd=strtotime($dateTimeEnd);
         if($dateTimeEnd === -1) {
           return("..end date Invalid");
         }

         $dif=$dateTimeEnd - $dateTimeBegin;

         switch($interval) {
           case "s"://seconds
               return($dif);

           case "n"://minutes
               return(floor($dif/60)); //60s=1m

           case "h"://hours
               return(floor($dif/3600)); //3600s=1h

           case "d"://days
               return(floor($dif/86400)); //86400s=1d

           case "ww"://Week
               return(floor($dif/604800)); //604800s=1week=1semana

           case "m": //similar result "m" dateDiff Microsoft
               $monthBegin=(date("Y",$dateTimeBegin)*12)+
                 date("n",$dateTimeBegin);
               $monthEnd=(date("Y",$dateTimeEnd)*12)+
                 date("n",$dateTimeEnd);
               $monthDiff=$monthEnd-$monthBegin;
               return($monthDiff);

           case "yyyy": //similar result "yyyy" dateDiff Microsoft
               return(date("Y",$dateTimeEnd) - date("Y",$dateTimeBegin));

           default:
               return(floor($dif/86400)); //86400s=1d
         }

       }
   function CODE_MARK($msg, $print=false) {
        if($print) echo '<hr>';
        $debug_backtrace='';
        foreach(debug_backtrace() as $idx=>$r) {
            $param = array();
            if(@$r['args']) foreach($r['args'] as $a) $param[]=addcslashes(var_export($a,true),"\0..\37!@\@\177..\377");
            $debug_backtrace .= sprintf(" at %s %s %s%s(%s);\n",
                $r['file'], $r['line'], (@$r['class'].@$r['type']),@ $r['function'], implode(',',$param) );
        }
        LWUtils::DEBUGIT($msg."\n\n$debug_backtrace\n\n".var_export(@$_SERVER,true)."\n\n",'ERROR');
        if($print) echo $msg."<br><pre>\n$debug_backtrace\n</pre>";
        if($print) echo '<hr>';
   }
}// Class


if (!function_exists('file_get_contents')) {
    function file_get_contents($filename, $incpath = false, $resource_context = null) {
        if (false === ($fh = fopen($filename, 'rb', $incpath)) ) {
            user_error('file_get_contents() failed to open stream: No such file or directory',
                E_USER_WARNING);
            return false;
        }
        clearstatcache();
        if ($fsize = @filesize($filename)) {
            $data = fread($fh, $fsize);
        } else {
            $data = '';
            while (!feof($fh)) {
                $data .= fread($fh, 8192);
            }
        }
        fclose($fh);
        return $data;
    }
}
if (!defined('FILE_USE_INCLUDE_PATH')) { define('FILE_USE_INCLUDE_PATH', 1); }
if (!defined('FILE_APPEND')) { define('FILE_APPEND', 8); }

/**
 * Replace file_put_contents()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @link        http://php.net/function.file_put_contents
 * @author      Aidan Lister <aidan@php.net>
 * @version     $Revision: 1.23 $
 * @internal    resource_context is not supported
 * @since       PHP 5
 * @require     PHP 4.0.0 (user_error)
 */
if (!function_exists('file_put_contents')) {
    function file_put_contents($filename, $content, $flags = null, $resource_context = null)
    {
        // If $content is an array, convert it to a string
        if (is_array($content)) {
            $content = implode('', $content);
        }
        // If we don't have a string, throw an error
        if (!is_scalar($content)) {
            user_error('file_put_contents() The 2nd parameter should be either a string or an array',
                E_USER_WARNING);
            return false;
        }
        // Get the length of date to write
        $length = strlen($content);
        // Check what mode we are using
        $mode = ($flags & FILE_APPEND) ?
                    $mode = 'a' :
                    $mode = 'w';
        // Check if we're using the include path
        $use_inc_path = ($flags & FILE_USE_INCLUDE_PATH) ?
                    true :
                    false;
        // Open the file for writing
        if (($fh = @fopen($filename, $mode, $use_inc_path)) === false) {
            user_error('file_put_contents() failed to open stream: Permission denied', E_USER_WARNING);
            return false;
        }
        // Write to the file
        $bytes = 0;
        if (($bytes = @fwrite($fh, $content)) === false) {
            $errormsg = sprintf('file_put_contents() Failed to write %d bytes to %s',
                $length, $filename);
            user_error($errormsg, E_USER_WARNING);
            return false;
        }
        // Close the handle
        @fclose($fh);
        // Check all the data was written
        if ($bytes != $length) {
            $errormsg = sprintf('file_put_contents() Only %d of %d bytes written, possibly out of free disk space.',
                $bytes, $length);
            user_error($errormsg, E_USER_WARNING);
            return false;
        }
        // Return length
        return $bytes;
    }
}
/**
* GetText fake function, con un mecanismo de traducci?n muy simple.
**/
if(!function_exists('TR')) {
    function TR($str) { $args=func_get_args(); return lwutils_simple_translation($str, array_slice($args,1) ); }
}

if ( ! function_exists ( 'mime_content_type' ) ) {
   function mime_content_type ( $f ) {
       return trim ( exec ('file -bi ' . escapeshellarg ( $f ) ) ) ;
   }
}

/*
if( !function_exists('mime_content_type') ) {
    function mime_content_type ( $str ) {

    }
}
*/
function    lwutils_simple_translation($str, $args=array()) {
    if(!$str) return '';

    // En caso de que no est? inicializado el sist de traducci
    if( empty($_GLOBALS['_TransLationStrings']) ) {
        if( @is_readable( QFORMS_PATH.'translation.strings.txt' ) ) {
            $temp=file(QFORMS_PATH.'translation.strings.txt');
            foreach($temp as $idx=>$line) {
                $temp[$idx]=null;
                @list($lang,$key,$trans) = explode('|',trim($line),3);
                if($lang&&$key&&$trans)
                    $_GLOBALS['_TransLationStrings']["$lang|$key"]=$trans;
            }
        }
    }
    if(!defined('QFORMS_LANG')) define('QFORMS_LANG','es');
    $lang=QFORMS_LANG;
    // En caso de que la traducci?n NO exista
    if(empty($_GLOBALS['_TransLationStrings']["$lang|$str"])) {
        if(@is_writable(QFORMS_PATH.'translation.missing.txt')) {
            file_put_contents(QFORMS_PATH.'translation.missing.txt', sprintf("%s|%s|%s\n",
                addcslashes($lang,"\0..\37"), addcslashes($str,"\0..\37"), addcslashes($str,"\0..\37") ) , FILE_APPEND);
        }
        return vsprintf($str,$args);
    }
    return vsprintf($GLOBALS['_TransLationStrings']["$lang|$str"],$args);
}
function    _sorter_numcmp($a,$b) {
    return floatval($a)-floatval($b);
}
function array_table_multisort(&$arr) {
    $sorters=array(); $sidx=0;
    foreach( array_slice(func_get_args(),1) as $idx=>$v) {
        if($v===SORT_ASC) { $sorters[$sidx-1][0]='';
        }elseif($v===SORT_DESC) { $sorters[$sidx-1][0]='-';
        }elseif($v===SORT_NUMERIC) { $sorters[$sidx-1][1]='_sorter_numcmp';
        }else{ $sorters[$sidx++]=array('','',$v); }
    }
    foreach($sorters as $k=>$v) {
        if(!$v[1]) $v[1]='strcmp';
        $sorters[$k] = sprintf("(\$t=%s%s(\$a['%s'],\$b['%s']))", $v[0], $v[1],
            addslashes($v[2]), addslashes($v[2]));
    }
    $sorters=implode('||',$sorters);
    usort($arr, create_function('$a, $b', "$sorters; return \$t;"));
    return($arr);
}
function array_table_multisort_assoc(&$arr) {
    $sorters=array(); $sidx=0;
    foreach( array_slice(func_get_args(),1) as $idx=>$v) {
        if($v===SORT_ASC) { $sorters[$sidx-1][0]='';
        }elseif($v===SORT_DESC) { $sorters[$sidx-1][0]='-';
        }elseif($v===SORT_NUMERIC) { $sorters[$sidx-1][1]='_sorter_numcmp';
        }else{ $sorters[$sidx++]=array('','',$v); }
    }
    foreach($sorters as $k=>$v) {
        if(!$v[1]) $v[1]='strcmp';
        $sorters[$k] = sprintf("(\$t=%s%s(\$a['%s'],\$b['%s']))", $v[0], $v[1],
            addslashes($v[2]), addslashes($v[2]));
    }
    $sorters=implode('||',$sorters);
    uasort($arr, create_function('$a, $b', "$sorters; return \$t;"));
    return($arr);
}

?>
