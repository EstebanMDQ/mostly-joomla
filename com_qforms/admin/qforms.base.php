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

define('QFORMS_ACTION_LIST',    'list');
define('QFORMS_ACTION_VIEW',    'view');
define('QFORMS_ACTION_INSERT',  'insert');
define('QFORMS_ACTION_UPDATE',  'update');
define('QFORMS_ACTION_DELETE',  'delete');
define('QFORMS_ACTION_LISTFORM','listform');
define('QFORMS_ACTION_EXPORT',  'export');
define('QFORMS_ACTION_PRINT',   'print');
define('QFORMS_ACTION_WIZARD',  'wizard');
define('QFORMS_ACTION_FORM',    'form');
define('QFORMS_ACTION_PREVIEW', 'preview');
define('QFORMS_ACTION_PREVIEWDELETE', 'previewdelete');

define('QFORMS_FILTER_EXACT',   '1');
define('QFORMS_FILTER_LIKE',    '2');
define('QFORMS_FILTER_RANGE',   '3');
define('QFORMS_FILTER_USER',    '4');
define('QFORMS_FILTER_NULL',    '5');
define('QFORMS_FILTER_ISZERO',  '6');
define('QFORMS_FILTER_EXACTSET','7');

define('QFORMS_RENDER_STATIC',  '1');
define('QFORMS_RENDER_CONTROL', '2');
define('QFORMS_RENDER_HIDDEN',  '3');

/**
* Load the widget classes and other helpers.
**/
require_once(QFORMS_PATH.'qforms.widgets.php');
if(!function_exists('adodb_date_test_date')) {
    require_once(QFORMS_PATH.'adodb-time.inc.php');
}

class   QFormsBase {
    /**
    * Define filters, permission, etc.
    **/
    function    Init() {
        $this->currAction   = QFORMS_ACTION_LIST;
        $this->currRecord   = null;

        $this->abm_fields   = array();
        $this->abm_errors   = array();
        $this->abm_data     = array();
        $this->gpc_data     = array();
        $this->abm_filters  = array();
        $this->abm_hiddens  = array();
        $this->abm_sorters  = array();

        $this->abm_title    = '';
        $this->abm_subtitle = '';
        $this->abm_pageNo       = 1;
        $this->abm_pageCount    = 1;
        $this->abm_orderBy      = null;
        $this->abm_rowsPerPage  = 50;
        $this->abm_submitted    = false;
        $this->abm_limit        = array();
        $this->abm_curr_rid     = 0;
        if( !isset($this->abm_prefix) )
        	$this->abm_prefix       = 'xF_';
        #echo $this->abm_prefix;
        $this->perm_list    = true;
        $this->perm_view    = true;
        $this->perm_insert  = true;
        $this->perm_update  = true;
        $this->perm_delete  = true;
        $this->perm_listform= true;
        $this->perm_export  = true;
        $this->perm_print   = true;
        $this->controls_before = true; // define que los controles del formulario esten delante en el modo grilla
        $this->qforms_language  = $GLOBALS['qforms_default_language'] = 'en';
        $this->confirm_reload   = 1; // tiempo en segundos (0-infinito) para recargar. -1 desactiva
        $this->confirm_html     = null;
        $this->sql_engine       = null;
        $this->allow_unchanged_submits = false;

        $this->abm_url          = QForms::URL($_SERVER['REQUEST_URI']);

        $base_url               = QForms::URL($this->abm_url, array(
            $this->abm_prefix.'action' => null,
            $this->abm_prefix.'record' => null,
            ));
        $this->abm_view_url     = "$base_url&{$this->abm_prefix}action=". QFORMS_ACTION_VIEW;
        $this->abm_insert_url   = "$base_url&{$this->abm_prefix}action=". QFORMS_ACTION_INSERT;
        $this->abm_update_url   = "$base_url&{$this->abm_prefix}action=". QFORMS_ACTION_UPDATE;
        $this->abm_delete_url   = "$base_url&{$this->abm_prefix}action=". QFORMS_ACTION_DELETE;
        $this->abm_previewdelete_url   = "$base_url&{$this->abm_prefix}action=". QFORMS_ACTION_PREVIEWDELETE;
        $this->abm_back_url     = null;
        $this->abm_confirm_url  = null;
        $this->html_delete_confirmation = true;

        $this->abm_export_url   = QForms::URL($this->abm_url, array(
            $this->abm_prefix.'action'=>QFORMS_ACTION_EXPORT,
            $this->abm_prefix.'Module'=>basename($_SERVER['SCRIPT_FILENAME'])
            ),null,'qforms.generic.export_print.php');
        $this->abm_print_url    = QForms::URL($this->abm_url, array(
            $this->abm_prefix.'action'=>QFORMS_ACTION_PRINT,
            $this->abm_prefix.'Module'=>basename($_SERVER['SCRIPT_FILENAME'])
             ),null,'qforms.generic.export_print.php');

        $this->abm_listform_url = QForms::URL($this->abm_url,$this->abm_prefix.'action', QFORMS_ACTION_LISTFORM);
        $this->abm_list_url     = QForms::URL($this->abm_url,$this->abm_prefix.'action', QFORMS_ACTION_LIST);

        $this->template_list    = QFORMS_PATH_TEMPLATES.'qforms.template.list.php';
        $this->template_form    = QFORMS_PATH_TEMPLATES.'qforms.template.form.php';
        $this->template_confirm = QFORMS_PATH_TEMPLATES.'qforms.template.confirm.php';
        $this->template_export  = QFORMS_PATH_TEMPLATES.'qforms.template.export.php';
        $this->template_print   = QFORMS_PATH_TEMPLATES.'qforms.template.print.php';

        $this->rows_total       = 0;
        $this->flag_confirm     = false;
        $this->abm_pk_expr      = null;
        $this->wizard_count     = null;
        $this->wizard_step      = null;

        /**
        * GPC: Load action & misc info
        **/
        if( ($t=@strval($_GET[$this->abm_prefix.'action'])) )   $this->currAction = $t;
        if( ($t=@strval($_GET[$this->abm_prefix.'record'])) )   $this->currRecord = $t;
        if( ($t=@strval($_GET[$this->abm_prefix.'backUrl'])) )  $this->abm_back_url = $t;
        if( ($t=@strval($_GET[$this->abm_prefix.'confirmUrl'])))$this->abm_confirm_url = $t;
        if($this->html_delete_confirmation)
        	$this->abm_delete_url   = QForms::URL($this->abm_delete_url,$this->abm_prefix.'backUrl', urlencode($this->abm_back_url));
        $this->abm_submitted = !empty($_POST[$this->abm_prefix.'SubmitData']);
        $this->message = '';
        if( $this->currAction==QFORMS_ACTION_LISTFORM )
        	$this->message = QForms::trans("Remember to click on the save button in order to apply the changes you made");
    }

    /**
    * Helper function
    **/
    function    _widgets_sort(&$a,&$b) {
        $al = sprintf('%s |%4.4d',strtolower($a->field_group), $a->field_order);
        $bl = sprintf('%s |%4.4d',strtolower($b->field_group), $b->field_order);
        if($al == $bl) return 0;
        return (($al > $bl) ? +1 : -1);
    }

    /**
    * Internal initialization
    **/
    function    Prepare() {
        uasort($this->abm_filters,array(&$this,'_widgets_sort'));
        /**
        * GPC: Load filters, pagination, order
        **/
        foreach(array_keys($this->abm_filters) as $name) {
            $this->abm_filters[$name]->abm = &$this;
            $this->abm_filters[$name]->Init();
            if( ($t=$this->abm_filters[$name]->loadGPC('xFF_', true))!==null ) {
                $this->abm_filters[$name]->value = $t;
            }
        }
        if( ($t=@strval($_GET[$this->abm_prefix.'pageNo'])) )       $this->abm_pageNo       = $t;
        if( ($t=@strval($_GET[$this->abm_prefix.'RowsPerPage'])) )  $this->abm_rowsPerPage  = $t;
        if( ($t=@strval($_GET[$this->abm_prefix.'orderBy'])) )      $this->abm_orderBy      = $t;
    }

    /**
    * TODO: COMMENT
    **/
    function    dataLoad($from_db=false, $from_defaults=false, $force_defaults=false) {
        if($from_db) {
            if($this->currRecord) {
                $this->abm_data     = array($this->data_select(false, $this->currRecord));
            }else{
                $this->rows_total       = $this->data_select(true);
                $this->abm_pageCount    = ceil( $this->rows_total / $this->abm_rowsPerPage);
                if($this->abm_pageNo<1)
                    $this->abm_pageNo=1;
                if($this->abm_pageNo>$this->abm_pageCount)
                    $this->abm_pageNo=$this->abm_pageCount;
                $this->abm_limit    = array(
                    (($this->abm_pageNo>0)?(($this->abm_pageNo-1)*$this->abm_rowsPerPage):0),
                    $this->abm_rowsPerPage );
                if($this->rows_total)
                    $this->abm_data     = $this->data_select();
            }
        }
        if($from_defaults) {
            if($force_defaults && empty($this->abm_data))
                $this->abm_data[0]=array();
            foreach($this->abm_data as $rid=>$rec) {
                foreach(array_keys($this->abm_fields) as $k) {
                	if( !isset($rec[$k]) && !is_null($t=$this->abm_fields[$k]->GetDefault() ) ) {
                        $this->abm_data[$rid][$k] = $t;
                    }
                }
            }
        }
    }

    /**
    * Read the name...
    **/
    function    Validate() {
        foreach($this->gpc_data as $rid=>$rec) {
            foreach($this->GetFieldList( 'visible' ) as $name) {
                if( ($t=$this->abm_fields[$name]->Validate( $this->getFieldValue($name,$rid) )) )
                    $this->abm_errors[] = $t;
            }
            $this->ValidateRec($this->getData($rid), $this->getDataDiff($rid), $this->getPK($rid,$rec), $rid);
        }
        return empty($this->abm_errors);
    }

    /**
    * GPC: Load fields data.
    **/
    function    loadGPC() {
        $dirty=false;
        foreach($this->abm_data as $rid=>$rec) {
            $this->gpc_data[$rid]=array();
            foreach(array_keys($this->abm_fields) as $k) {
            	//printf("<li>loadGPC: %s|%s</li>", $k, $this->abm_fields[$k]->loadGPC( $this->getWidgetName($k,$rid) ));
                if( ($t=$this->abm_fields[$k]->loadGPC( $this->getWidgetName($k,$rid) ))!==null ) {
                    $this->gpc_data[$rid][$k] = $t;
                    $dirty = true;
                }
            }
        }

        /**
        * This nifty little piece of code might look a bit complicated...
        **/
        $this->abm_result_data      = array();
        $this->abm_result_dataDiff  = array();

        foreach($this->gpc_data as $rid=>$rec) {
            $this->abm_result_data[$rid] = array();
            foreach(array_keys($this->abm_fields) as $name) {
                if(!$this->abm_fields[$name]->is_static) {
                    $value=array('lasdhkq7812#!@#*34y78134 asdfS ]X ;sdkhhgh');
                    if( isset($rec[$name]) ) {
                        $value = $rec[$name];
                        if(empty($value) && $this->abm_fields[$name]->is_null) $value=null;
                        //printf("<li>POSTED rid=%s name=%s data=%s value=%s</li>\n", $rid, $name, @$this->abm_data[$rid][$name], @$value, @$this->abm_result_data[$rid][$name]);
                    }else{
                        //printf("<li>DEFAULT rid=%s name=%s data=%s value=%s</li>\n", $rid, $name, @$this->abm_data[$rid][$name], @$value, @$this->abm_result_data[$rid][$name]);
                        $value = @$this->abm_data[$rid][$name];
                    }

                    if($value!==array('lasdhkq7812#!@#*34y78134 asdfS ]X ;sdkhhgh')) {
                        if(empty($value) && $this->abm_fields[$name]->is_null) $value=null;
                        $this->abm_fields[$name]->ProcessValue($value);
                        @$this->abm_result_data[$rid][$name] = $value;
                        @$this->abm_result_dataDiff[$rid][$name] = $value;

                        //printf("<li>Change rid=%s name=%s data=%s value=%s</li>\n", $rid, $name, @$this->abm_data[$rid][$name], @$value, @$this->abm_result_data[$rid][$name]);
                    }else{
                        //printf("<li>.... WRONG(?) rid=%s name=%s data=%s value=%s</li>\n", $rid, $name, @$this->abm_data[$rid][$name], @$value, @$this->abm_result_data[$rid][$name]);
                    }
                }
            }
        }
/** Modo anterior, tratando de detectar cambios... no funciona muy bien
        foreach($this->gpc_data as $rid=>$rec) {
            $this->abm_result_data[$rid] = array();
            foreach(array_keys($this->abm_fields) as $name) {
                if(!$this->abm_fields[$name]->is_static) {
                    $value=array('lasdhkq7812#!@#*34y78134 asdfS ]X ;sdkhhgh');
                    $flag_changed=false;
					//printf("<li>Checking: |%s|%s|%s|</li>\n", $rid, $name, $this->abm_data[$rid][$name]);
                    if( isset($rec[$name]) ) {
                        $value = $rec[$name];
                        if(empty($value) && $this->abm_fields[$name]->is_null)
                            $value=null;
                        //printf("<li>Changed: |%s|%s|%s|%s|</li>\n", $rid, $name, $this->abm_data[$rid][$name], $value);
                        $flag_changed = (!isset($this->abm_data[$rid][$name])&&!is_null(@$this->abm_data[$rid][$name]) || @$this->abm_data[$rid][$name]!==$value);
                    }else{
                        $value = @$this->abm_data[$rid][$name];
                    }
                    if($value!==array('lasdhkq7812#!@#*34y78134 asdfS ]X ;sdkhhgh')) {
                        if(empty($value) && $this->abm_fields[$name]->is_null)
                            $value=null;
                        $this->abm_fields[$name]->ProcessValue($value);
                        @$this->abm_result_data[$rid][$name] = $value;
                        if($flag_changed) {
                            @$this->abm_result_dataDiff[$rid][$name] = $value;
                            //printf("<li>Diff: |%s|%s|%s|%s|%s|</li>\n", $rid, $name, $this->abm_data[$rid][$name], $value, $this->abm_result_data[$rid][$name]);
                        }
                    }
                }
            }
        }
*/
        return $dirty;
    }

    /**
    * Data validation & processing
    **/
    function    Process() {
        /**
        * Initialize widgets & prepare the PK expression
        **/
        uasort($this->abm_fields, array(&$this,'_widgets_sort'));

        $exprs  = array();
        foreach(array_keys($this->abm_fields) as $name) {
            $this->abm_fields[$name]->abm = &$this;
            $this->abm_fields[$name]->Init();
            if($this->abm_fields[$name]->abm_pk_order) {
                $exprs[] = "'$name'=>\$rec['$name']";
            }
        }
        if($exprs) {
            $this->abm_pk_expr = 'array('.implode(",",$exprs).')';
            /**
            * In case of a 'complex' (more than one field) PK, decode it
            **/
            if(count($exprs)>1) {
                $currPk = explode('|', $this->currRecord);
                $this->currRecord=array();
                $order=0;
                foreach(array_keys($this->abm_fields) as $k) {
                    if($t=$this->abm_fields[$k]->abm_pk_order) {
                        $this->currRecord[$k]=stripcslashes(@$currPk[$order++]);
                    }
                }
            }
        }

        /**
        * Execute the corresponding action
        **/
        $data=false;
        switch($this->currAction) {
        case QFORMS_ACTION_INSERT:
            $this->ERROR_ON( !$this->perm_insert, QForms::trans("Access right error"));
            $this->dataLoad(false,true, true);

            #HACK DATAOBJECTS:
            if($this->abm_submitted && $this->loadGPC() && $this->Validate()) {
                $this->flag_confirm = $this->data_insert($this->getData());
            }

            #if($this->abm_submitted && $this->loadGPC() && $this->Validate() && (($data=$this->getData())||$this->allow_unchanged_submits) && $this->data_insert( $data ) ) {
            #    $this->flag_confirm = true;
            #}elseif(is_null(@$data)) {
            #    $this->abm_errors[] = QForms::trans("No changes to perform");
            #}
            break;
        case QFORMS_ACTION_UPDATE:
            $this->ERROR_ON( !$this->perm_update, QForms::trans("Access right error") );
            $this->ERROR_ON( !$this->currRecord,  QForms::trans("Missing record") );
            $this->dataLoad(true,true);

            #HACK DATAOBJECTS:
            if($this->abm_submitted && $this->loadGPC() && $this->Validate()) {
                $this->flag_confirm = $this->data_update( $this->currRecord, $this->getData() );
            }
            #if($this->abm_submitted && $this->loadGPC() && $this->Validate() && (($data=$this->getDataDiff())||$this->allow_unchanged_submits) && $this->data_update( $this->currRecord, $data ) ) {
            #    $this->flag_confirm = true;
            #}elseif(is_null(@$data)) {
            #    $this->abm_errors[] = QForms::trans("No changes to perform");
            #}
            break;
        case QFORMS_ACTION_DELETE:
            $this->ERROR_ON( !$this->perm_delete, QForms::trans("Access right error") );
            $this->ERROR_ON( !$this->currRecord,  QForms::trans("Missing record") );
            $this->dataLoad(true,false);

            if(!$this->abm_back_url) $this->abm_back_url=$this->abm_list_url;
            if(!$this->abm_confirm_url) $this->abm_confirm_url=$this->abm_list_url;

            if( !$this->html_delete_confirmation || $this->abm_submitted) {
            	if($this->html_delete_confirmation)
            		$this->abm_list_url = $this->abm_confirm_url;
                if( $this->data_delete( $this->currRecord ) )
                    $this->flag_confirm = true;
            }
            break;
        case QFORMS_ACTION_VIEW:
            $this->ERROR_ON( !$this->perm_view, QForms::trans("Access right error") );
            $this->ERROR_ON( !$this->currRecord,  QForms::trans("Missing record") );
            $this->dataLoad(true, true);
            break;
        case QFORMS_ACTION_PREVIEWDELETE:
            $this->ERROR_ON( !$this->perm_delete, QForms::trans("Access right error") );
            $this->ERROR_ON( !$this->perm_view, QForms::trans("Access right error") );
            $this->ERROR_ON( !$this->currRecord,  QForms::trans("Missing record") );
            $this->dataLoad(true, true);
            break;
        case QFORMS_ACTION_FORM:
            $this->ERROR_ON( !$this->perm_update, QForms::trans("Access right error") );
            $this->dataLoad(true,true);
            if($this->abm_submitted && $this->loadGPC() && $this->Validate() && ($data=$this->getDataDiff()) ) {
                $this->data_update( $this->currRecord, $data );
                $this->flag_confirm = true;
            }elseif(empty($this->allow_empty_changes)&&is_null(@$data)) {
                $this->abm_errors[] = QForms::trans("No changes to perform");
            }else{
                // for forms, always try to fetch the data
                if($this->loadGPC())
                    $this->Validate();
            }
            break;
        case QFORMS_ACTION_PREVIEW:
            $this->ERROR_ON( !$this->perm_view, QForms::trans("Access right error") );
            $this->ERROR_ON( !$this->currRecord,  QForms::trans("Missing record") );
            $this->dataLoad(true,true);
            if( $this->loadGPC() && $this->Validate() && ($data=$this->getDataDiff()) ) {
                $this->data_preview( $this->currRecord, $data );
            }else{
                $this->abm_errors[] = QForms::trans("Fatal");
            }
            break;
        case QFORMS_ACTION_LISTFORM:
            $this->ERROR_ON( !$this->perm_listform, QForms::trans("Access right error") );
            $this->currRecord = null;
            $this->dataLoad(true, false);

            if($this->abm_submitted && $this->loadGPC() && $this->Validate() && ($alldata = $this->getDataDiff(null)) ) {
                $allok=true;
                foreach($alldata as $rid=>$data) {
                    $allok = $allok && $this->data_update( $this->getPK($rid), $data , $rid);
                }
                if($allok)
                $this->flag_confirm = true;
            }
            break;
        case QFORMS_ACTION_WIZARD:
            $this->dataLoad(true,true);
            $this->loadGPC();
            $wizard_next = !empty($_POST[$this->abm_prefix.'WizardNext']);
            $wizard_prev = !empty($_POST[$this->abm_prefix.'WizardPrev']);
            unset($_POST[$this->abm_prefix.'WizardNext']);
            unset($_POST[$this->abm_prefix.'WizardPrev']);
            if($wizard_next && $this->wizard_step<$this->wizard_count) {
                if($this->Validate()) {
                    $this->WizardData($this->getData());
                    $this->wizard_step++;
                    return true;
                }
            }elseif($wizard_prev && $this->wizard_step>1){
                $this->WizardData($this->getData());
                $this->wizard_step--;
                return true;
            }elseif( $this->abm_submitted && $this->Validate() ) {
                $this->WizardData($this->getData());
                $this->data_update( $this->currRecord, $this->WizardData() );
                $this->flag_confirm = true;
                return false;
            }
            break;
        default:
            $this->ERROR_ON( !$this->perm_list, QForms::trans("Access right error") );
            $this->currRecord = null;
            $this->dataLoad(true, true);
        }
        return $this->flag_confirm;
    }

    /**
    * TODO: COMMENT
    **/
    function    GetFilterList( $type ) {
        $set=array();
        switch($type) {
        case 'visible':
            foreach(array_keys($this->abm_filters) as $name)
                if($this->abm_filters[$name]->is_visible && !$this->abm_filters[$name]->abm_filter_fixed)
                    $set[] = $name;
            break;
        case 'hidden':
            foreach(array_keys($this->abm_filters) as $name)
                if(!$this->abm_filters[$name]->is_visible || $this->abm_filters[$name]->abm_filter_fixed)
                    $set[] = $name;
            break;
        }
        return $set;
    }

    /**
    * TODO: COMMENT
    **/
    function    GetSortList() {
        $set    = $this->abm_sorters;
        foreach(array_keys($this->abm_fields) as $name) {
            $r=array();

            if( preg_match('/(?<![a-z0-9_])sortable(:[^\s]+)?(?![a-z0-9_])/', implode(' ',$this->abm_fields[$name]->_TAGS),$r) ) {
                $fname              = htmlspecialchars((!empty($r[1]))?substr($r[1],1):$name);
                $caption            = $this->abm_fields[$name]->caption;
                $set[ $fname ]      = $caption;
                $set[ "$fname*" ]   = "$caption (desc)";
            }
        }
        return $set;
    }
    /**
    * TODO: COMMENT
    **/
    function    GetFieldList( $type ) {
        $set    = array();
        $tags = array('no_'.$this->currAction);

        foreach(array_keys($this->abm_fields) as $name) {
            if( array_intersect($this->abm_fields[$name]->_TAGS,array('static_'.$this->currAction)) )
                $this->abm_fields[$name]->is_static=true;
        }

        switch($type) {
        case 'loadable':
            foreach(array_keys($this->abm_fields) as $name) {
                if($this->abm_fields[$name]->is_static && !array_intersect($this->abm_fields[$name]->_TAGS,$tags) )
                    $set[] = $name;
            }
            break;
        case 'writable':
            foreach(array_keys($this->abm_fields) as $name) {
                if(!$this->abm_fields[$name]->is_static && !$this->abm_fields[$name]->is_readonly && !array_intersect($this->abm_fields[$name]->_TAGS,$tags) )
                    $set[] = $name;
            }
            break;
        case 'visible':
            foreach(array_keys($this->abm_fields) as $name) {
                if($this->abm_fields[$name]->is_visible && !array_intersect($this->abm_fields[$name]->_TAGS,$tags))
                    $set[] = $name;
            }
            break;
        case 'hidden':
            foreach(array_keys($this->abm_fields) as $name)
                if(!$this->abm_fields[$name]->is_visible)
                    $set[] = $name;
            break;
        default:
            foreach(array_keys($this->abm_fields) as $name)
                $set[] = $name;
            break;
        }
        return $set;
    }

    /**
    * TODO: COMMENT
    **/
    function    RenderField( $name, $rid, $mode=QFORMS_RENDER_CONTROL ) {
        $this->abm_curr_rid = $rid;
        $prefix = $this->getWidgetName($name,$rid);
        $value  = $this->getFieldValue($name,$rid);

        if($mode==QFORMS_RENDER_HIDDEN) {
            $class = null;
            $html = $this->abm_fields[$name]->htmlHidden( $prefix , $value );
        }else{
        	if($mode==QFORMS_RENDER_STATIC && !empty($this->abm_fields[$name]->is_list_control) )
        		$mode=QFORMS_RENDER_CONTROL;

            list($class,$html) = $this->abm_fields[$name]->Display( $prefix , $value, ($mode==QFORMS_RENDER_STATIC) );
        }
        $t=(array)$this->abm_fields[$name];
        $t['caption'       ] = $this->abm_fields[$name]->caption;
        $t['html'          ] = $html;
        $t['id'            ] = $prefix.$name;
        $t['class'         ] = $class;
        $t['value'         ] = $value;
        $t['_TAGS'         ] = @$this->abm_fields[$name]->_TAGS;
		$t['type'		   ] = substr(strtolower(get_class($this->abm_fields[$name])),13);
        $t['description'   ] = $this->abm_fields[$name]->description;
        $t['group'         ] = $this->abm_fields[$name]->field_group;
        return $t;
    }
    /**
    * TODO: COMMENT
    **/
    function    RenderFilter( $name, $mode=QFORMS_RENDER_CONTROL ) {
        $this->abm_filters[$name]->abm = &$this;
        $value  = $this->abm_filters[$name]->value;
        if($mode==QFORMS_RENDER_HIDDEN) {
            $class = null;
            $html = $this->abm_filters[$name]->htmlHidden( 'xFF_' , $value );
        }else{
            list($class,$html) = $this->abm_filters[$name]->Display( 'xFF_' , $value, ($mode==QFORMS_RENDER_STATIC) );
        }
        return array(
            'caption'   => htmlspecialchars($this->abm_filters[$name]->caption),
            'html'      => $html,
            'class'     => $class,
            'value'     => $value,
            '_TAGS'         => $this->abm_filters[$name]->_TAGS,
			'type'			 => substr(strtolower(get_class($this->abm_filters[$name])),13),
            'description'   => $this->abm_filters[$name]->description,
            'group'         => $this->abm_filters[$name]->field_group
            );
    }

    /**
    * TODO: COMMENT
    **/
    function    addField($obj, $pk_order=0) {
    	//echo $obj->name;
        #$this->ERROR_ON(isset($this->abm_fields[$obj->name]), QForms::trans("Error. Duplicated field").': '.$obj->name );
        $obj->abm_pk_order = $pk_order;
        $obj->abm = & $this;
        if(is_null($obj->field_order)) $obj->field_order=count($this->abm_fields);
        if(is_null($obj->field_listord)) $obj->field_listord=count($this->abm_fields);
        $this->abm_fields[$obj->name] = $obj;
    }

    /**
    * TODO: COMMENT
    **/
    function    addFilter($obj, $filter_type, $fixed_filter=false) {
        #$this->ERROR_ON(isset($this->abm_filters[$obj->name]), QForms::trans("Error. Duplicated filter") );
        $obj->abm_filter_type   = $filter_type;
        $obj->abm_filter_fixed  = $fixed_filter;
        if(is_null($obj->field_order)) $obj->field_order=count($this->abm_filters);
        $this->abm_filters[$obj->name] = $obj;
    }

    /**
    * TODO: COMMENT
    **/
    function    addHidden($name, $value) {
        $this->abm_hiddens[$name] = $value;
    }

    /**
    * TODO: COMMENT
    **/
    function    getFieldValue($name, $row=null) {
        if($row===null) $row=$this->abm_curr_rid;
        return (isset($this->abm_result_data[$row][$name])
            ?$this->abm_result_data[$row][$name]
            :@$this->abm_data[$row][$name]);
    }

    /**
    * TODO: COMMENT
    **/
    function    getFilterValue($name) {
        return $this->abm_filters[$name]->value;
    }

    /**
    * TODO: COMMENT
    **/
    function    getWidgetName($name, $rid=null) {
        return $this->abm_prefix.($rid?"_{$rid}_":"");
    }

    /**
    * TODO: COMMENT
    **/
    function    getPK($rid, $rec=null, $encoded=false) {
        $pk = array();
        if(!$rec) $rec = @$this->abm_data[$rid];
        if($rec) {
            $pk = @eval("return $this->abm_pk_expr;");
            if(count($pk)==1) $pk = reset($pk);
        }
        if($encoded) {
            if(is_array($pk)){
                foreach($pk as $k=>$v) $pk[$k] = addcslashes($v,'|');
                $pk = urlencode(implode('|',$pk));
            }else{
                $pk = urlencode($pk);
            }
        }
        return $pk;
    }

    /**
    * TODO: COMMENT
    **/
    function    getPKNames($pk) {
        foreach(array_keys($this->abm_fields) as $name) {
            $this->abm_fields[$name]->abm = &$this;
            $this->abm_fields[$name]->Init();
            if($this->abm_fields[$name]->abm_pk_order) {
                return array($name=>$pk);
            }
        }
        return array();
    }

    /**
    * TODO: COMMENT
    **/
    function    getData($rid=0) {
        if($rid!==null) return @$this->abm_result_data[$rid];
        return $this->abm_result_data;
    }

    /**
    * TODO: COMMENT
    **/
    function    getDataDiff($rid=0) {
        if($rid!==null) return @$this->abm_result_dataDiff[$rid];
        return $this->abm_result_dataDiff;
    }

    /**
    * TODO: COMMENT
    **/
    function    WizardData($set=null, $clean=false) {
        if($clean||empty($_SESSION[$this->abm_prefix."WSESS $this->abm_title data"]))
            $_SESSION[$this->abm_prefix."WSESS $this->abm_title data"]=array();
        if($set)
            $_SESSION[$this->abm_prefix."WSESS $this->abm_title data"] = array_merge(@$_SESSION[$this->abm_prefix."WSESS $this->abm_title data"],$set);
        return @$_SESSION[$this->abm_prefix."WSESS $this->abm_title data"];
    }

    function    ValidateRec($data, $dataDiff, $pk=null, $rid=null) {
        return empty($this->abm_errors);
    }
    function    ValidateRecJS_callback($m) {
        switch($m[1]) {
        case 'idOf': return $this->abm_prefix.$this->abm_fields[$m[2]]->name;
        case 'valueOf': return str_replace('@abm_prefix@', $this->abm_prefix, $this->abm_fields[$m[2]]->js_getValue);
        case 'nameOf':  return addslashes ( ($this->abm_fields[$m[2]]->field_group?"(".$this->abm_fields[$m[2]]->field_group.") ":"").$this->abm_fields[$m[2]]->caption );
        case 'fieldExists':  return $this->abm_fields[$m[2]]->caption;
        }
        return null;
    }
    function    ValidateRecJS( $items=array() ) {
        $visible_fields     = $this->GetFieldList( 'visible' );
        foreach($visible_fields as $name) {
            if($t=$this->abm_fields[$name]->jsValidate()) {
                $items=array_merge($items,$t);
            }
        }
        $items =  preg_replace_callback('/@(\w+) (\w+)@/', array(&$this,'ValidateRecJS_callback'),
            implode(";\n",$items) );
        //$items =  preg_replace_callback('/@(nameOf) (\w+)@/',
        //    create_function('$m', 'return $this->abm_fields[$m[1]]->caption;'),
        //    $items );
        if($items)
        $items = "
<script type=\"text/javascript\" >
function xF_Value(elt,name) {var t=null; if(!name) name=elt.name; if(t=elt.form.elements[name]) { if(t.options) return t.options[t.selectedIndex].value; else return t.value; } }
function xF_Validate(xF_Form) {
   	var errores=[];

$items

	var errorMsg='';
	for(var i=0 ; i<errores.length ; i++)
		if(errores[i]) errorMsg+=''+errores[i];
	if(errorMsg) {
		if(t=document.getElementById('xfABMErrors'))
			t.innerHTML=errorMsg.replace(/\\r?\\n/,'<br>');
			alert(errorMsg); return false;
		}
	return true;
}
</script>";
        return $items;
    }

    /**
    * Main loop. This is the method one has to override.
    **/
    function    Run() {
        $this->Init();

        /**
        * Define filters, permission and options HERE
        **/

        $this->Prepare();

        /**
        * Define columns HERE
        **/

        $this->Process();

        /**
        * Usually Display is called elsewhere...
        **/
    }

    /**
    * TODO: COMMENT
    **/
    function    data_select($do_count=false, $pk=null) {
        if($do_count) {
            return 0;
        }elseif($pk) {
            return array();
        }else{
            return array();
        }
    }

    /**
    * TODO: COMMENT
    **/
    function    data_insert($data) {
        echo "<p>data_insert(".var_export($data,true).");</p>\n";
    }

    /**
    * TODO: COMMENT
    **/
    function    data_update($pk, $data) {
        echo "<p>data_update(".var_export($pk,true).",".var_export($data,true).");</p>\n";
    }

    /**
    * TODO: COMMENT
    **/
    function    data_delete($pk) {
        echo "<p>data_delete(".var_export($pk,true).");</p>\n";
    }

    /**
    * TODO: COMMENT
    **/
    function    data_preview($pk, $data) {
        echo "<p>data_preview(".var_export($pk,true).",".var_export($data,true).");</p>\n";
    }

}

?>
