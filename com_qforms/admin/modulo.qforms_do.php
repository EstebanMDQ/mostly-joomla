<?php
/*
 *    This file is part of QForms
 *
 *    QForms is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    QForms is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with QForms.  If not, see <http://www.gnu.org/licenses/>.
 */
if(!defined('QFORMS_PATH'))  define('QFORMS_PATH', './');
require_once(QFORMS_PATH.'xforms.php');
require_once(SITE_INCLUDE.'modulo.xforms.php');

/**
* Declaro una clase de la que van a ser hijos todos los listados/abms/loquesea de este site (acá pongo valores x defecto para un monton de cosas).
**/
class   ModuleABMS_DO extends ModuleABMS {
    function    Init() {
        parent::Init();
        /**
        * Define options&permission HERE
        **/

        $can_write = securityCheckWrite();

        $this->perm_list        = true;
        $this->perm_view        = true;
        $this->perm_insert      = $can_write;
        $this->perm_update      = $can_write;
        $this->perm_delete      = $can_write;
        $this->perm_listform    = false;
        $this->perm_export      = false;
        $this->perm_print       = false;
        $this->abm_rowsPerPage  = 50;
        $this->template_list    = QFORMS_PATH_TEMPLATES.'xforms.template2.list.php';
        $this->template_form    = QFORMS_PATH_TEMPLATES.'xforms.template2.form.php'; // xforms.template.form.php
        $this->template_confirm = QFORMS_PATH_TEMPLATES.'xforms.template2.confirm.php';

        // tiempo en segundos para recargar al confirmar. -1 desactiva
        $this->confirm_reload   = 0;
        $this->html_delete_confirmation=false;

        $this->sql_engine       = 'mysql';
        $this->confirm_reload   = 1; // tiempo en segundos (0 a infinito) para recargar. -1 desactiva
        $this->xforms_language  = $GLOBALS['xforms_default_language'] = 'en';

        $this->abm_export_url   = QForms::URL($this->abm_url, array(
            'xF_action'=>QFORMS_ACTION_EXPORT,
            'xF_Module'=>basename(empty($this->export_print_callback)?$_SERVER['SCRIPT_FILENAME']:$this->export_print_callback)
            ),null,QFORMS_URI.'xforms.generic.export_print.php');
        $this->abm_print_url    = QForms::URL($this->abm_url, array(
            'xF_action'=>QFORMS_ACTION_PRINT,
            'xF_Module'=>basename(empty($this->export_print_callback)?$_SERVER['SCRIPT_FILENAME']:$this->export_print_callback)
             ),null,QFORMS_URI.'xforms.generic.export_print.php');

        if(empty($this->abm_back_url)&&$this->currAction!=QFORMS_ACTION_LIST&&$this->currAction!=QFORMS_ACTION_LISTFORM)
            $this->abm_back_url=QForms::URL($this->abm_list_url,'xF_backUrl');
    }

    function    Prepare_CB() {
    }

    function    &getValuesForDataObject($do) {
        #if(empty($GLOBALS["ModuleABMS_DO::getValuesForDataObject"][$do])) {
        #    $t =& DataObject::create($do);
        #    $GLOBALS["ModuleABMS_DO::getValuesForDataObject"][$do] = $t->getKeyValue();
        #}
        #return $GLOBALS["ModuleABMS_DO::getValuesForDataObject"][$do];
    }

    function    getMetaDefinitionForDataObject(&$abm, $dataObjectName) {
        $abm->dataobject = new $dataObjectName();
        $metadata = $abm->dataobject->metadata();

        if(@$metadata['xforms_options']) {
            foreach($metadata['xforms_options'] as $k=>$v) {
                $abm->$k = $v;
            }
        }

        if(method_exists($abm,'Prepare_CB'))
            $abm->Prepare_CB();

        $abm->setof_viewlinks=array();

        $flag_filters=false;

        $field_order=1;
        foreach($metadata['fields'] as $id=>$mdata) {
            if(!$mdata['widget']) $mdata['widget']='Hidden';
            if($mdata['widget']=='date')    $mdata['widget']='DateCustom';
            $class='QFormsWidget_'.$mdata['widget'];
            if(@$mdata['useraccess']=='none') continue;
            if(@$mdata['access']=='none') continue;
            if(class_exists($class)) {
                if(empty($abm->abm_fields[$id])) {
                    $mdata['name']  = $id;
                    $mdata['is_null'] = ($mdata['constraint']=='null');
                    $mdata['is_required'] = ($mdata['constraint']=='required'&&$mdata['access']=='writable');
                    $mdata['is_readonly'] = ($mdata['access']!='writable'&&$mdata['access']!='user');
                    $mdata['is_required'] |= ($mdata['useraccess']=='required');
                    $mdata['is_readonly'] |= ($mdata['useraccess']=='readonly');
                    $tag_active = (strpos(' '.$mdata['_TAGS'].' ',' active_'.$this->currAction.' ')!==false);
                    #if(strpos($mdata['_TAGS'],'listform')===false)
                    #    $mdata['_TAGS'] .= ' no_listform';
                    //var_dump($id,$tag_active,$mdata['field_listord']);echo '<hr>';
                    $mdata['field_listord']=$mdata['list'];
                    $mdata['field_order']=$mdata['form'];
                    if(empty($mdata['field_order']) && !$tag_active )   $mdata['_TAGS'] .= ' no_insert no_update no_delete no_view';
                    if(empty($mdata['field_listord']) && !$tag_active ) $mdata['_TAGS'] .= ' no_list no_listform no_export no_print';
                    if($mdata['reference_to']&&$mdata['reference_to']!=='null') {
                        if(empty($mdata['values_expr']))
                            $mdata['values_expr']=      '$t=new '.$mdata['reference_to'].'(); return $t->getObjectList();';
                        $mdata['quick_expr']= 'if(is_null($this->value)||(string)$this->value==="") return null; $t=new '.$mdata['reference_to'].'(); $t=$t->getObjectList(null, $this->value); return reset($t);';
                        $mdata['values']=array();
                        if(empty($mdata['blank_text'])&& empty($mdata['required']))
                            $mdata['blank_text']=' ';
                    }
                    if($mdata['widget']&&strtolower($mdata['widget'])=='setofitems') {
                        $mdata['picker_set_uid'] = md5("$dataObjectName::$id");
                        $_SESSION['QForms_widget_picker_sets'][$mdata['picker_set_uid']] = $mdata['values_expr'];
                    }

                    if(!empty($mdata['filter'])) {
                        $filter_type =1; $filter_fixed=0;
                        switch($mdata['filter']) {
                        case 'fixed':   $filter_fixed   = 1;break;
                        case 'exact':   $filter_type    = 1;break;
                        case 'like':    $filter_type    = 2;break;
                        case 'iszero':  $filter_type    = QFORMS_FILTER_ISZERO;break;
                        }

                        // no, porque se pierden los filtros manuales.
                        if(isset($_GET['xFF_'.$id])) {
                            //$filter_fixed = 1;
                        }
                        $def1=$mdata;
                        if( $class=='QFormsWidget_textarea')
                            $fclass='QFormsWidget_textfield';
                        else
                            $fclass=$class;
                        if( $filter_type==QFORMS_FILTER_ISZERO ) {
                            $fclass='QFormsWidget_select';
                            $def1['values']=array(1=>'Yes', 0=>'No');
                        }
                        $def1['blank_text']=' ';
                        $def1['is_static'] = false;
                        $def1['is_readonly'] = false;
                        $abm->addFilter(new $fclass($def1), $filter_type, $filter_fixed);

                        if(isset($_GET['xFF_'.$id])) {
                            $mdata['value']=$mdata['default_value']=@$_GET['xFF_'.$id];
                            if($abm->currAction==QFORMS_ACTION_LIST)
                                $mdata['is_readonly']=true;
                            //if(strval($_GET['xFF_'.$id])!=='')
                            //    $mdata['_TAGS'] .= ' no_list';
                        }
                        $flag_filters=true;
                    }
                    if(isset($this->dataobject->$id))
                        $mdata['value']=$this->dataobject->$id;
                    if(@intval($_GET['xFF_'.$id])!==0 ) {
                        $mdata['default_value'] = intval($_GET['xFF_'.$id]);
                    }
                    if(@$mdata['useraccess']=='hidden') continue;
                    $abm->addField(new $class($mdata), (($mdata['access']=='pk')?1:null));
                    $abm->_saved_mdata[$id]=$mdata;
                }
            }else{
                die("Class $class not found");
            }
            $field_order++;
        }

        if($flag_filters) {
            foreach($metadata['fields'] as $id=>&$def) {
                if( $def['access']!='none'&&$def['useraccess']!='none' ) {
                    if( preg_match('/(?<![a-z0-9_])sortable(?![a-z0-9_])/', " $def[_TAGS] ") ) {
                        $abm->abm_sorters[$id] = $def['caption'];
                        $abm->abm_sorters["$id*"] = "$def[caption] (desc)";
                    }
                }
            }
        }
        if($abm->abm_sorters && !$abm->abm_orderBy)
            $abm->abm_orderBy = reset(array_keys($abm->abm_sorters));
    }
    function    dataLoad($from_db=false, $from_defaults=false, $force_defaults=false) {
        if( !$from_db && $from_defaults && $force_defaults ) {
            // Creando...
            $this->dataobject->create();
            $setof_loadables = $this->GetFieldList('loadable');
            if(!$this->abm_data) $this->abm_data = array(0=>array());
            foreach($this->abm_data as $rid=>$rec) {
                foreach(array_keys($this->abm_fields) as $k) {
                    try{
                        $def = $this->dataobject->metadata($k);
                        $v = $this->dataobject->$k;
                        if( in_array($def['type'],array('date','datetime')) && $v=='NOW' )
                            $v=time();
                        $this->abm_data[$rid][$k] = $v;
                    }catch(Exception $e) {
                        // no pasa nada...
                    }
                    if( empty($this->abm_data[$rid][$k]) && !empty($_GET["xFF_$k"])) {
                        $this->abm_data[$rid][$k] = strval($_GET["xFF_$k"]);
                    }
                }
            }
            if(method_exists($this->dataobject, 'event_xforms_create')) {
                $this->dataobject->event_xforms_create();
            }
        }elseif($from_defaults) {
        }
        $retval = parent::dataLoad($from_db, $from_defaults, $force_defaults);
        return $retval;
    }

    function    Process() {
        $retval=parent::Process();
        if($this->currAction==QFORMS_ACTION_INSERT && @$this->xforms_edit_after_insert && @$this->tmp_last_insert_id) {
            $this->abm_confirm_url = QForms::URL($this->abm_update_url, 'xF_record', $this->tmp_last_insert_id);
        }
        return $retval;
    }

    function    Prepare() {
        ModuleABMS_DO::getMetaDefinitionForDataObject($this,$this->dataobject);
        parent::Prepare();
    }

    function    DataObject_ProcessRecord(&$rec) {
        foreach($rec as $id=>$v) {
            if(@$this->_saved_mdata[$id]) {
                if( $this->_saved_mdata[$id]['widget']=='select' && $this->_saved_mdata[$id]['is_null'] && empty($v) ) {
                    $v=null;
                }
            }
        }
    }

    /**
    * Helper function ( TODO: SECURITY PROBLEMS )
    **/
    function    DataObject_Filters($data_subset=null) {
        $filter_expr = array();
        $values = array();
        foreach(array_keys($this->abm_filters) as $name)
            $values[ $name ] = $name;
        if($data_subset)
            $values = array_flip(QForms::Data_Subset($values, $data_subset));
        foreach($values as $name=>$sql_name) {
            $type = $this->abm_filters[$name]->abm_filter_type;
            $value = $this->abm_filters[$name]->value;
            if($value || (strval($value)==="0") ) {
                switch($type) {
                case QFORMS_FILTER_EXACT:
                    if( ($setof_values = @explode(",", ereg_replace("[{-}]", "",  $value ))) && count($setof_values)>1 ) {
                        $filter_expr[] = array($sql_name,'=ANY', ($setof_values) );
                    }else{
                        $filter_expr[] = array($sql_name,'=', ($value) );
                    }
                    break;
                case QFORMS_FILTER_LIKE:
                    $filter_expr[] = array($sql_name, ($this->sql_engine=='pgsql')?'ilike':'like',  ($value) );
                    break;
                case QFORMS_FILTER_RANGE:
                    die('NOT IMPLEMENTED: QFORMS_FILTER_RANGE');
                    break;
                case QFORMS_FILTER_NULL:
                    die('NOT IMPLEMENTED: QFORMS_FILTER_NULL');
                    break;
                    if( ($setof_values = @explode(",", ereg_replace("[{-}]", "",  $value ))) && count($setof_values)>1 ) {
                        $filter_expr[] = array($sql_name,'=ANY', ($setof_values) );
                    }else{
                        $filter_expr[] = array($sql_name,'=', ($value) );
                    }
                    break;
                case QFORMS_FILTER_ISZERO:
                    $filter_expr[] = array($sql_name,'=', ($value) );
                    break;
                case QFORMS_FILTER_USER:
                    // No hago nada de nada.
                    break;
                }
            }
        }
        if($filter_expr)
            return $filter_expr;
        return null;
    }

    function    data_onRecord(&$rec) {
    }

    /**
    * Data Select
    **/
    function    data_select($do_count=false, $pk=null) {
        $setof_filters=$this->DataObject_Filters();
        $setof_orders = (trim($this->abm_orderBy)?preg_split('/\||;/',$this->abm_orderBy):null);
        if($do_count) {
            return $this->dataobject->count($setof_filters);
        }elseif($pk) {
            $this->dataobject->load($pk);
            $data = $this->dataobject->toAssoc();
            $this->data_onRecord($data);
            return $data;
        }else{
            $setof_objects = $this->dataobject->select($setof_filters, $setof_orders, array($this->abm_limit[0], $this->abm_limit[1]) );
            $data=array();
            foreach($setof_objects as $obj) {
                // Me siento sucio por esto! ESTO:
                $obj->id;
                // Carga el valor del objeto...

                $data[] = $obj->toAssoc();
            }
            foreach($data as $k=>$dummy) $this->data_onRecord($data[$k]);
            return $data;
        }
    }

    /**
    * Ejecuto un INSERT en la DB
    **/
    function    data_insert($data) {
        $this->DataObject_ProcessRecord($data);
        $this->dataobject->create();
        # agregado esto porque $data tiene todas las rows, incluyendo las que no se tocaron.
        $setof_writables = $this->GetFieldList('writable');
        foreach($data as $k=>$v) {
            if( in_array($k,$setof_writables) ) {
                $def=$this->dataobject->metadata($k);
                if($def['constraint']=='null' && strlen(strval($v))==0 )
                    $v=null;
                $this->dataobject->$k=$v;
            }
        }
        try{
            $this->dataobject->save();
            if( $this->tmp_last_insert_id = $this->dataobject->id )
                return true;
        }catch(Exception $e) {
            $this->abm_errors[]= $e->getMessage();
        }
        return empty($this->abm_errors);
    }

    /**
    * Ejecuto un UPDATE en la DB
    **/
    function    data_update($pk, $data) {
        $this->DataObject_ProcessRecord($data);
        if(@$data['xF_MassDelete'])
            return $this->data_delete($pk);
        unset($data['xF_MassDelete']);
        # agregado esto porque $data tiene todas las rows, incluyendo las que no se tocaron.
        $setof_writables = $this->GetFieldList('writable');
        $this->dataobject->load($pk);
        foreach($data as $k=>$v) {
            if( in_array($k,$setof_writables) ) {
                $def = $this->dataobject->metadata($k);
                if($def['constraint']=='null' && strlen(strval($v))==0 )
                    $v=null;
                $this->dataobject->$k=$v;
            }
        }
        try{
            $this->dataobject->save();
            return true;
        }catch(Exception $e) {
            $this->abm_errors[]= $e->getMessage();
        }
        return empty($this->abm_errors);
    }

    /**
    * Ejecuto un DELETE en la DB
    **/
    function    data_delete($pk) {
        try{
            $this->dataobject->load($pk);
            if($this->dataobject->delete($pk))
                return true;
        }catch(Exception $e) {
            $this->abm_errors[]= $e->getMessage();
        }
        return empty($this->abm_errors);
    }

    /**
    * Returns the row buttons (view/edit) according to permission
    **/
    function    htmlListButtons($rid, $rec, $set=array() ) {
        $currPK = $this->getPK($rid, $rec, true);

        $setof_buttons=array();
        $this->dataobject->load($currPK);
        if( (!isset($set['view'])||$set['view']) && $this->perm_view) {
            $setof_buttons[] = QFormsWidget::Render( 'QFormsWidget_button', array(
                'name'      => 'ViewButton',
                'caption'   => 'Name',
                'text'      => $this->dataobject->getRecordName(),
                'onclick'   => "self.location='$this->abm_view_url&xF_record=$currPK'",
                'css_class' => 'xfButtonBase xfButtonView xfButtonOpenDialog',
                'show_as_link' =>  true,
                ), false);
        }
        /*
        if( (!isset($set['update'])||$set['update']) && $this->perm_update) {
            $setof_buttons[] = QFormsWidget::Render( 'QFormsWidget_button', array(
                'name'      => 'EditButton',
                'text'      => QForms::trans('Modify'),
                'onclick'   => "self.location='$this->abm_update_url&xF_record=$currPK'",
                'css_class' => 'xfButtonBase xfButtonUpdate xfButtonOpenDialog',
                'show_as_link' =>  true,
                ), false);
        }
        if( (!isset($set['delete'])||$set['delete']) && $this->perm_delete) {
            $setof_buttons[] = QFormsWidget::Render( 'QFormsWidget_button', array(
                'name'      => 'DeleteButton',
                'text'      => QForms::trans('Delete'),
                'onclick'   => "if(confirm('".QForms::trans('Do you really want to delete this record?')."')) self.location=('$this->abm_delete_url&xF_record=$currPK')",
                'css_class' => 'xfButtonBase xfButtonDelete xfButtonOpenDialog',
                'show_as_link' =>  true,
                ), false);
        }
        */
        return implode(' | ',$setof_buttons);
    }
    /**
    * Returns the form buttons (save/cancel/view/delete) according to permission
    **/
    function    htmlFormActions( $set=array() ) {
        $buttons='';
        $currPK = $this->getPK(0, null, true);
        $is_view    = ($this->perm_view&&$this->currAction==QFORMS_ACTION_VIEW);
        $is_delete  = ($this->perm_delete&&$this->currAction==QFORMS_ACTION_DELETE);
        $is_insert  = ($this->perm_insert&&$this->currAction==QFORMS_ACTION_INSERT);
        $is_update  = ($this->perm_update&&$this->currAction==QFORMS_ACTION_UPDATE);

        if($is_insert||$is_update) {
            $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                'name'      => 'SubmitData',
                'text'      => QForms::trans('Save'),
                'do_submit' => true,
                'css_class' => 'xfButtonBase xfButtonSave'
                ), false);
            /*
            if( $this->perm_insert) {
                $buttons .= ' '.QFormsWidget::Render( 'QFormsWidget_button', array(
                    'name'      => 'SubmitData',
                    'text'      => QForms::trans('Save & New'),
                    'do_submit' => true,
                    'css_class' => 'xfButtonBase xfButtonSave',
                    'onclick'   => "this.form.action='".QForms::URL($this->abm_url,'xF_backUrl',urlencode($this->abm_insert_url))."'; return true;",
                    ), false);
            }
            */

            if( (!isset($set['delete'])||$set['delete']) && $is_update && $this->perm_delete) {
                $buttons .= ' &nbsp; &nbsp; &nbsp; '.QFormsWidget::Render( 'QFormsWidget_button', array(
                    'name'      => 'DeleteButton',
                    'text'      => QForms::trans('Delete'),
                    'onclick'   => "if(confirm('".QForms::trans('Do you really want to delete this record?')."')) self.location='$this->abm_delete_url&xF_record=$currPK'",
                    'css_class' => 'xfButtonBase xfButtonDelete'
                    ), false).' &nbsp; &nbsp; &nbsp; ';
            }

            if( (!isset($set['cancel'])||$set['cancel']) && !empty($this->abm_back_url)) {
                $buttons .= ' '.QFormsWidget::Render( 'QFormsWidget_button', array(
                    'name'      => 'CancelButton',
                    'text'      => QForms::trans('Cancel'),
                    'onclick'   => "self.location='$this->abm_back_url'",
                    'css_class' => 'xfButtonBase xfButtonCancel'
                    ), false);
            }
        }elseif($is_view||$is_delete) {
            if($is_delete) {
                $buttons .= ' '.QFormsWidget::Render( 'QFormsWidget_button', array(
                    'name'      => 'SubmitData',
                    'text'      => QForms::trans('Confirm'),
                    'do_submit' => true,
                    'css_class' => 'xfButtonBase xfButtonDelete'
                    ), false);
            }
            if( (!isset($set['update'])||$set['update']) && $is_view&&$this->perm_update) {
                $buttons .= ' '.QFormsWidget::Render( 'QFormsWidget_button', array(
                    'name'      => 'UpdateButton',
                    'text'      => QForms::trans('Edit'),
                    'onclick'   => "self.location='$this->abm_update_url&xF_record=$currPK'",
                    'css_class' => 'xfButtonBase xfButtonUpdate'
                    ), false);
            }

            if( (!isset($set['delete'])||$set['delete']) && $is_view && $this->perm_delete) {
                $buttons .= ' &nbsp; &nbsp; &nbsp; '.QFormsWidget::Render( 'QFormsWidget_button', array(
                    'name'      => 'DeleteButton',
                    'text'      => QForms::trans('Delete'),
                    'onclick'   => "if(confirm('".QForms::trans('Do you really want to delete this record?')."')) self.location='$this->abm_delete_url&xF_record=$currPK'",
                    'css_class' => 'xfButtonBase xfButtonDelete'
                    ), false).' &nbsp; &nbsp; &nbsp; ';
            }
            if( (!isset($set['update'])||$set['update']) && $is_view&&$this->perm_insert) {
                $buttons .= ' '.QFormsWidget::Render( 'QFormsWidget_button', array(
                    'name'      => 'NewButton',
                    'text'      => QForms::trans('Add New'),
                    'onclick'   => "self.location='$this->abm_insert_url'; return event.returnValue=true;",
                    'css_class' => 'xfButtonBase xfButtonInsert xfButtonOpenDialog',
                    'show_as_link' =>  false,
                    ), false);
            }
            if( (!isset($set['cancel'])||$set['cancel']) && !empty($this->abm_back_url) && $this->perm_list ) {
                $buttons .= ' '.QFormsWidget::Render( 'QFormsWidget_button', array(
                    'name'      => 'CancelButton',
                    'text'      => QForms::trans('List'),
                    'onclick'   => "self.location='$this->abm_back_url'",
                    'css_class' => 'xfButtonBase xfButtonCancel'
                    ), false);
            }
        }
        return $buttons;
    }

    /**
    * Returns the List/ListForm top buttons (New/View as list/listform/Export/print) according to permission
    **/
    function    htmlListTopActions() {
        $buttons = '';
        if(!empty($this->perm_insert)) {
            $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                'name'      => 'NewButton',
                'text'      => QForms::trans('Add New'),
                'onclick'   => "self.location='$this->abm_insert_url'; return event.returnValue=true;",
                'css_class' => 'xfButtonBase xfButtonInsert xfButtonOpenDialog',
                'description' => 'Agregar un nuevo registro a esta tabla.',
                'show_as_link' =>  false,
                ), false);
        }
        if(!empty($this->perm_listform) && !empty($this->perm_list) ) {
            if( $this->currAction==QFORMS_ACTION_LISTFORM ) {
                $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                    'name'      => 'ListFormButton',
                    'text'      => QForms::trans('List'),
                    'onclick'   => "self.location='$this->abm_list_url'; return event.returnValue=true;",
                    'css_class' => 'xfButtonBase xfButtonListForm',
                    'show_as_link' =>  false,
                    ), false);
            }else{
                $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                    'name'      => 'ListFormButton',
                    'text'      => QForms::trans('Editable list'),
                    'onclick'   => "self.location='$this->abm_listform_url'; return event.returnValue=true;",
                    'css_class' => 'xfButtonBase xfButtonListForm',
                    'description' => 'Permite editar algunos campos de los registros de este listado, sin tener que ir al formulario individual.',
                    'show_as_link' =>  false,
                    ), false);
            }
        }
        if(!empty($this->perm_export)) {
            $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                'name'      => 'ExportButton',
                'text'      => QForms::trans('Export EXCEL'),
                'onclick'   => "self.open('$this->abm_export_url'); return event.returnValue=true;",
                'css_class' => 'xfButtonBase xfButtonExport',
                'show_as_link' =>  false,
                'description' => 'Descarga un archivo MS Excel conteniendo los datos de todas estas páginas.',
                ), false);
        }
        if(!empty($this->perm_print)) {
            $buttons .=  QFormsWidget::Render( 'QFormsWidget_button', array(
                'name'      => 'PrintButton',
                'text'      => QForms::trans('Print'),
                'onclick'   => "self.open('$this->abm_print_url'); return event.returnValue=true;",
                'css_class' => 'xfButtonBase xfButtonPrint',
                'show_as_link' =>  false,
                'description' => 'Abre una página web apta para imprimir los datos de todas estas páginas.',
                ), false);
        }
        return $buttons;
    }
}

class   QFormsWidget_DateCustom extends QFormsWidget_Date {
    function    QFormsWidget_DateCustom($params) {
        return $this->QFormsWidget_Date($params);
    }
    function    htmlControl($prefix) {
        list($css,$html)=parent::htmlControl($prefix);
        $d=date('d/m/Y');
        $html.='[ <a href="javascript:void(setNow(\''.$prefix.$this->name.'\'))">'.$d.'</a> ]';
        return array($css,$html);
    }
    function    htmlExtras() {
        $set = parent::htmlExtras();
        $set['xfdatecal_calendar_code'] .= '<script type="text/javascript">function setNow(id) { document.getElementById(id).value=\'\'+(new Date()).getDate()+\'/\'+(1+(new Date()).getMonth())+\'/\'+(new Date()).getFullYear(); }</script>';
      return $set;
    }
}

class   QFormsWidget_DateTime extends QFormsWidget_Date {
    function    QFormsWidget_DateTime($params) {
        $params['control_format']   = ('d/m/Y H:i');
        $params['static_format']    = ('d/m/Y H:i');
        $params['result_format']    = ('Y-m-d H:i');
        return $this->QFormsWidget_Date($params);
    }
    function    htmlControl($prefix) {
        list($css,$html)=parent::htmlControl($prefix);
        $d=date('d/m/Y');
        $html.='[ <a href="javascript:void(setNow(\''.$prefix.$this->name.'\'))">'.$d.'</a> ]';
        return array($css,$html);
    }
    function    htmlExtras() {
        $set = parent::htmlExtras();
        $set['xfdatecal_calendar_code'] .= '<script type="text/javascript">function setNow(id) { document.getElementById(id).value=\'\'+(new Date()).getDate()+\'/\'+(1+(new Date()).getMonth())+\'/\'+(new Date()).getFullYear(); }</script>';
      return $set;
    }
}

?>
