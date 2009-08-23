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

if(!defined('QFORMS_PATH'))                 define('QFORMS_PATH', './');
if(!defined('QFORMS_URI'))                  define('QFORMS_URI',  './');

if(!defined('QFORMS_WIDGETS_IMAGEMGRURL'))  define('QFORMS_WIDGETS_IMAGEMGRURL',    './qforms/ImageManager/');
if(!defined('QFORMS_WIDGETS_HTMLURL'))      define('QFORMS_WIDGETS_HTMLURL',        './qforms/htmlarea/');
if(!defined('QFORMS_WIDGETS_HTMLIMGURL'))   define('QFORMS_WIDGETS_HTMLIMGURL',     '/imagenes/');
if(!defined('QFORMS_WIDGETS_HTMLIMGPATH'))  define('QFORMS_WIDGETS_HTMLIMGPATH',    '/imagenes/');
if(!defined('QFORMS_WIDGETS_PICKERURL'))    define('QFORMS_WIDGETS_PICKERURL',      QFORMS_URI.'qforms.widget_picker.php');
if(!defined('QFORMS_PATH_TEMPLATES'))		define('QFORMS_PATH_TEMPLATES',         QFORMS_PATH.'templates/');
if(!defined('QFORMS_URI_TEMPLATES'))		define('QFORMS_URI_TEMPLATES',          QFORMS_URI.'templates/');

/**
 * Loads base class
 */
require_once(QFORMS_PATH.'qforms.base.php');

class   QForms extends QFormsBase {
    /**
    * Fix a most annoying behaviour with html forms and the GET method.
    * It also stores the fixed filter values.
    **/
    function    htmlGetExtraParams() {
        $buf=array();
        foreach($_GET as $k=>$v) {
            if( (substr($k,0,strlen($this->abm_prefix))!=$this->abm_prefix && substr($k,0,4)!='xFF_') || $k==$this->abm_prefix.'backUrl')
                $buf[] = sprintf('<input type="hidden" name="%s" value="%s" />',
                    htmlspecialchars($k), htmlspecialchars($v) );
        }
        foreach($this->GetFilterList( 'hidden' ) as $name) {
            if($t=$this->RenderFilter($name, QFORMS_RENDER_HIDDEN)) {
                $buf[] = $t['html'];
            }
        }
        return implode('',$buf);
    }

    /**
    * Returns the row buttons (view/edit) according to permission
    **/
    function    htmlListButtons_submodal($rid, $rec, $set=array() ) {
        $buttons='';
        $currPK = $this->getPK($rid, $rec, true);
        if( (!isset($set['view'])||$set['view']) && $this->perm_view) {
            $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                'name'      => 'ViewButton',
                'text'      => QForms::trans('View'),
                'onclick'   => "showPopWin('$this->abm_view_url&{$this->abm_prefix}record=$currPK',800,420,null);",
                'css_class' => 'xfButtonBase xfButtonView'
                ), false);
        }
        if( (!isset($set['update'])||$set['update']) && $this->perm_update) {
            $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                'name'      => 'EditButton',
                'text'      => QForms::trans('Edit'),
                'onclick'   => "showPopWin('$this->abm_update_url&{$this->abm_prefix}record=$currPK',800,420,null);",
                'css_class' => 'xfButtonBase xfButtonUpdate'
                ), false);
        }
        return $buttons;
    }

    /**
    * Returns the row buttons (view/edit) according to permission
    **/
    function    htmlListButtons($rid, $rec, $set=array() ) {
        $buttons='';
        $currPK = $this->getPK($rid, $rec, true);
        if( (!isset($set['view'])||$set['view']) && $this->perm_view) {
            $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                'name'      => 'ViewButton',
                'text'      => QForms::trans('View'),
                'onclick'   => "self.location='$this->abm_view_url&{$this->abm_prefix}record=$currPK'",
                'css_class' => 'xfButtonBase xfButtonView xfButtonOpenDialog',
                'show_as_link' =>  true,
                ), false);
        }
        if( (!isset($set['update'])||$set['update']) && $this->perm_update) {
            $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                'name'      => 'EditButton',
                'text'      => QForms::trans('Edit'),
                'onclick'   => "self.location='$this->abm_update_url&{$this->abm_prefix}record=$currPK'",
                'css_class' => 'xfButtonBase xfButtonUpdate xfButtonOpenDialog',
                'show_as_link' =>  true,
                ), false);
        }
        return $buttons;
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

            if( (!isset($set['delete'])||$set['delete']) && $is_update && $this->perm_delete) {
                $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                    'name'      => 'DeleteButton',
                    'text'      => QForms::trans('Delete'),
                    'onclick'   => "if(confirm('".QForms::trans('Do you really want to delete this record?')."')) self.location='$this->abm_delete_url&{$this->abm_prefix}record=$currPK'",
                    'css_class' => 'xfButtonBase xfButtonDelete'
                    ), false);
            }
            if( (!isset($set['view'])||$set['view']) && $is_update && $this->perm_view) {
                $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                    'name'      => 'ViewButton',
                    'text'      => QForms::trans('View'),
                    'onclick'   => "if(confirm('".QForms::trans('If you perform this action, all the changes you made will be lost. Are you sure?')."')) self.location='$this->abm_view_url&{$this->abm_prefix}record=$currPK'",
                    'css_class' => 'xfButtonBase xfButtonView'
                    ), false);
            }
            if( (!isset($set['cancel'])||$set['cancel']) && !empty($this->abm_back_url)) {
                $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                    'name'      => 'CancelButton',
                    'text'      => QForms::trans('Close'),
                    'onclick'   => "self.location='$this->abm_back_url'",
                    'css_class' => 'xfButtonBase xfButtonCancel'
                    ), false);
            }
        }elseif($is_view||$is_delete) {
            if($is_delete) {
                $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                    'name'      => 'SubmitData',
                    'text'      => QForms::trans('Delete'),
                    'do_submit' => true,
                    'css_class' => 'xfButtonBase xfButtonDelete'
                    ), false);
            }
            if( (!isset($set['update'])||$set['update']) && $is_view&&$this->perm_update) {
                $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                    'name'      => 'UpdateButton',
                    'text'      => QForms::trans('Edit'),
                    'onclick'   => "self.location='$this->abm_update_url&{$this->abm_prefix}record=$currPK'",
                    'css_class' => 'xfButtonBase xfButtonUpdate'
                    ), false);
            }
            if( (!isset($set['cancel'])||$set['cancel']) && !empty($this->abm_back_url)) {
                $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                    'name'      => 'CancelButton',
                    'text'      => QForms::trans('Close'),
                    'onclick'   => "self.location='$this->abm_back_url'",
                    'css_class' => 'xfButtonBase xfButtonCancel'
                    ), false);
            }



        }

        return $buttons;
    }


    /**
    * Returns the wizard buttons (prev/next/cancel/finish)
    **/
    function    htmlWizardActions( $set=array() ) {
        $buttons='';
        if( (!isset($set['cancel'])||$set['cancel']) && !empty($this->abm_back_url)) {
            $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                'name'      => 'CancelButton',
                'text'      => QForms::trans('Close'),
                'onclick'   => "if(confirm('".QForms::trans('Are you sure?')."')) self.location='$this->abm_back_url'",
                'css_class' => 'xfButtonBase xfButtonCancel'
                ), false);
            $buttons .= '&nbsp; &nbsp; &nbsp; ';
        }
        $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
            'name'      => 'WizardPrev',
            'text'      => QForms::trans('<<'),
            'do_submit' => true,
            'onclick'   => "self.{$this->abm_prefix}Validate=null",
            'disabled'  => ($this->wizard_step<=1),
            'css_class' => 'xfButtonBase xfButtonPrev'
            ), false);
        $buttons .= ' ';
        $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
            'name'      => 'WizardNext',
            'text'      => QForms::trans('>>'),
            'do_submit' => true,
            'disabled'  => ($this->wizard_step>=$this->wizard_count),
            'css_class' => 'xfButtonBase xfButtonNext'
            ), false);

        $buttons .= '&nbsp; &nbsp; &nbsp; ';
        $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
            'name'      => 'SubmitData',
            'text'      => QForms::trans('Finish'),
            'disabled'  => ($this->wizard_step<$this->wizard_count),
            'do_submit' => true,
            'css_class' => 'xfButtonBase xfButtonFinish'
            ), false);

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
                'show_as_link' =>  true,
                ), false);
        }
        if(!empty($this->perm_listform) && !empty($this->perm_list) ) {
            if( $this->currAction==QFORMS_ACTION_LISTFORM ) {
                $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                    'name'      => 'ListFormButton',
                    'text'      => QForms::trans('List'),
                    'onclick'   => "self.location='$this->abm_list_url'; return event.returnValue=true;",
                    'css_class' => 'xfButtonBase xfButtonListForm',
                    'show_as_link' =>  true,
                    ), false);
            }else{
                $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                    'name'      => 'ListFormButton',
                    'text'      => QForms::trans('Editable list'),
                    'onclick'   => "self.location='$this->abm_listform_url'; return event.returnValue=true;",
                    'css_class' => 'xfButtonBase xfButtonListForm',
                    'description' => 'Permite editar algunos campos de los registros de este listado, sin tener que ir al formulario individual.',
                    'show_as_link' =>  true,
                    ), false);
            }
        }
        if(!empty($this->perm_export)) {
            $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                'name'      => 'ExportButton',
                'text'      => QForms::trans('Export'),
                'onclick'   => "self.open('$this->abm_export_url'); return event.returnValue=true;",
                'css_class' => 'xfButtonBase xfButtonExport',
                'show_as_link' =>  true,
                'description' => 'Descarga un archivo MS Excel conteniendo los datos de todas estas páginas.',
                ), false);
        }
        if(!empty($this->perm_print)) {
            $buttons .=  QFormsWidget::Render( 'QFormsWidget_button', array(
                'name'      => 'PrintButton',
                'text'      => QForms::trans('Print'),
                'onclick'   => "self.open('$this->abm_print_url'); return event.returnValue=true;",
                'css_class' => 'xfButtonBase xfButtonPrint',
                'show_as_link' =>  true,
                'description' => 'Abre una página web apta para imprimir los datos de todas estas páginas.',
                ), false);
        }
        return $buttons;
    }

    /**
    * Returns the List/ListForm bottom buttons (save/cancel) according to permission
    **/
    function    htmlListBottomActions() {
        $buttons = '';
        if($this->perm_listform && $this->currAction==QFORMS_ACTION_LISTFORM) {
            $do_submit = true;
            $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                'name'      => 'SubmitData',
                'text'      => QForms::trans('Save'),
                'onclick'   => "self.document.forms['xF_MainForm'].submit();"
                ), false);
        }
        if(!empty($this->abm_back_url)) {
            $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
                'name'      => 'CancelButton',
                'text'      => QForms::trans('Back'),
                'onclick'   => "self.location='$this->abm_back_url'",
                #'css_class' => 'xfButtonBase xfButtonCancel',
                ), false);
        }
        return $buttons;
    }

    /**
    * BLA BLA BLA
    **/
    function    htmlFormExtras() {
        $set = array();
        foreach(array_keys($this->abm_filters) as $k) {
            foreach($this->abm_filters[$k]->htmlExtras() as $uid=>$html)
                $set[$uid] = $html;
        }
        foreach(array_keys($this->abm_fields) as $k) {
            foreach($this->abm_fields[$k]->htmlExtras() as $uid=>$html)
                $set[$uid] = $html;
        }
        return implode("\n",$set);
    }

    /**
    * The name says it all: Display everything
    **/
    function    Display() {
        /**
        * Execute the corresponding action
        **/
        switch($this->currAction) {
        case QFORMS_ACTION_INSERT:
            if(isset($this->abm_subtitle)) $this->abm_subtitle = QForms::trans('New');
            include($this->flag_confirm?$this->template_confirm:$this->template_form);
            break;
        case QFORMS_ACTION_UPDATE:
            if(isset($this->abm_subtitle)) $this->abm_subtitle = QForms::trans('Edit');
            include($this->flag_confirm?$this->template_confirm:$this->template_form);
            break;
        case QFORMS_ACTION_DELETE:
            if(isset($this->abm_subtitle)) $this->abm_subtitle = QForms::trans('Delete');
            include($this->flag_confirm?$this->template_confirm:$this->template_form);
            break;
        case QFORMS_ACTION_VIEW:
            if(isset($this->abm_subtitle)) $this->abm_subtitle = QForms::trans('View');
            include($this->template_form);
            break;
        case QFORMS_ACTION_PREVIEWDELETE:
            if(isset($this->abm_subtitle)) $this->abm_subtitle = QForms::trans('Delete');
            include($this->template_form);
            break;
        case QFORMS_ACTION_PREVIEW:
            if(isset($this->abm_subtitle)) $this->abm_subtitle = QForms::trans('View');
            include($this->template_form);
            break;
        case QFORMS_ACTION_EXPORT:
            if(isset($this->abm_subtitle)) $this->abm_subtitle = QForms::trans('Exporting');
            include($this->template_export);
            break;
        case QFORMS_ACTION_PRINT:
            if(isset($this->abm_subtitle)) $this->abm_subtitle = QForms::trans('Printing');
            include($this->template_print);
            break;
        case QFORMS_ACTION_LISTFORM:
            if(!isset($this->abm_subtitle)) $this->abm_subtitle = QForms::trans('Editing');
            if(empty($this->abm_confirm_url)) $this->abm_confirm_url=$this->abm_list_url;
            include($this->flag_confirm?$this->template_confirm:$this->template_list);
            break;
        case QFORMS_ACTION_WIZARD:
            if(!isset($this->abm_subtitle)) $this->abm_subtitle = QForms::trans('step %s of %s',$this->wizard_step,$this->wizard_count);
            include($this->flag_confirm?$this->template_confirm:$this->template_form);
            break;
        default:
            include($this->template_list);
            #echo $this->template_list;
        }
    }

    /**
    * Returns the Confirmation buttons (go to list, redo) according to permission
    **/
    function    htmlConfirmActions() {
    }

    /**
    * TODO: COMMENT
    **/
    function    ERROR_ON($cond,$msg) {
        if($cond) die($msg);
    }

    /**
    * Helper function
    **/
	function _cutstring($max, $str) {
		if($max && strlen($str)>$max && $max!=-1 )
			return substr($str,0,$max-3)."...";
		return $str;
	}


    /**
    * Helper function
    **/
    function x_replacer($text, $separators, $values) {
        $searches   = array_keys($values);
        $replaces   = array_values($values);
        foreach($searches as $i=>$search)
            $searches[$i] = $separators[0]. $search .$separators[1];
        return str_replace($searches,$replaces,$text);
    }

    /**
    * Helper function: URL processor
    **/
    function    URL($url, $var=null, $val=null, $ch_base=null) {
        @list($base, $query) = explode('?', $url,2);
        $vars = array();
        if($query) {
            $query = explode('&', $query);
            foreach($query as $i) {
                @list($k,$v)= explode('=',$i,2);
                if(isset($vars[$k])) $vars[$k] .= '&'.$i;
                else $vars[$k] = $i;
            }
        }
        $vars['RvL']            = "RvL=".time();
        if($var) {
            if(is_array($var)) {
                $vset = $var;
                foreach($vset as $var=>$val)
                    if($val) $vars[$var] = "$var=$val"; else unset($vars[$var]);
            }else{
                if($val) $vars[$var] = "$var=$val"; else unset($vars[$var]);
            }
        }
        if($ch_base) $base=$ch_base;
        $url = "$base?".implode("&",$vars);
        return $url;
    }

    /**
    * Helper function
    **/
    function    HTML_options($array, $selected, $raw=false) {
        $buf = '';
        $selected=($raw?$selected:htmlspecialchars($selected));
        foreach($array as $k=>$v)
            $buf .= '<option value="'.($raw?$k:htmlspecialchars($k)).'"'.(($selected==$k)?' selected="selected"':'').'>'.($raw?$v:htmlspecialchars($v)).'</option>';
        return $buf;
    }

    /**
    * Helper function
    **/
    function    sameKeyValue($array) {
        $buf = array();
        foreach($array as $v) $buf[$v]=$v;
        return $buf;
    }

    /**
    * Helper function
    **/
    function    trans($str) {

        $args = func_get_args();

        // Si existe la función "_" NO uso el esquema de traducción este.
        #$args[] = 'backend';
        #if(function_exists('advanced_translator')) return call_user_func_array('advanced_translator',$args);

        unset($args[0]);

        switch($GLOBALS['qforms_default_language']) {
        case 'es':
            $lang_strings = array(
                '<<' => '<<',
                '>>' => '>>',
                'Access right error' => 'Error de acceso',
                'Are you sure?' => 'Seguro?',
                'Cancel' => 'Cancelar',
                'Close' => 'Cerrar',
                'Delete' => 'Borrar',
                'Edit' => 'Editar',
                'Editable list'=> 'Modificar página',
                'Editing'=> 'Modificando',
                'Error. CheckBoxSet Widget requires values or values_expr'
                    => 'Error. CheckBoxSet Widget requiere values o values_expr',
                'Error. DateRange Widget requires year_range as array'
                    => 'Error. DateRange Widget requiere year_range como un array',
                'Error. Duplicated field'
                    => 'Error. Campo duplicado',
                'Error. Duplicated filter'
                    => 'Error. Filtro duplicado',
                'Error. Select Widget requires values or values_expr'
                    => 'Error. Select Widget requiere values o values_expr',
                'Export' => 'Exportar',
                'Export all' => 'Exportar todo',
                'Exporting' => 'Exportando',
                'Fatal' => 'Fatal',
                'Finish' => 'Terminar',
                'Go Back' => 'Volver',
                'If you perform this action, all the changes you made will be lost. Are you sure?'
                    => 'Si modificó algún campo, los datos no serán guardados. Está seguro?',
                'List' => 'Listado',
                'Modify' => 'Editar',
                'Missing record' => 'Falta registro',
                'New' => 'Crear Nuevo',
                'Add New' => 'Crear Nuevo',
                'No' => 'No',
                'Yes' => 'Sí',
                'No changes to perform' => 'No hay cambios a realizar',
                'Print' => 'Imprimir',
                'Print all' => 'Imprimir todo',
                'Printing' => 'Imprimiendo',
                'Required field: \'%s\'' => 'Campo requerido: \'%s\'',
                'Save' => 'Guardar',
                '\'%s\' is not a valid email address'
                    => '\'%s\' no es una dirección de correo válida',
                'step %s of %s' => 'Paso %s de %s',
                'The field  \'%s\' must range between %s and %s'
                    => 'El campo \'%s\' debe estar comprendido entre %s y %s',
                'View' => 'Ver',
                'QFormsWidget: missing name' => 'QFormsWidget: Falta nombre',
                'Apply' => 'Aplicar',
                'Clear' => 'Limpiar',
                'Showing' => 'Mostrando',
                'Order by' => 'Ordenar por',
                'records' => 'registros',
                'Page' => 'Página',
                'If you have modified records and wish to wiew the changes in this list, press the button:'=>
                'Si modificó registros y desea ver los cambios reflejados en este listado, presione el botón:',
                'Refresh' => 'Refrescar',
                'Showing' => 'Mostrando',
                'records in'=>'registros en',
                'pages'=>'páginas',
                'If the page does not refresh automatically in'=>'Si ve que la página no recarga automáticamente en',
                'If this page doesn\'t reload in a few seconds, <br />Please click <a href="%s">Here</a>' => 'Si la página no se recarga en unos segundos, <br />Por favor presione <a href="%s">Aquí</a>',
                'seconds'=>'segundos',
                'Please press'=>'Por favor presione',
                'Here'=>'Aquí',
                'The operation has been successful.'=>'La operación ha sido ejecutada satisfactoriamente.',
                'Finish' => 'Fin'	 ,
                'If you have changed any field, all the changes you made will be lost. Are you sure?'=>'Si modificó algún campo, los datos no serán guardados. Está seguro?',
                'Do you really want to delete this record?' => '¿Realmente desea borrar este registro?',
                'Querying' => 'Mostrando',
                'Double click on a row to edit' => 'Haga doble click en cualquier línea para modificar el registro',
                'Please apply some filters' => 'Por favor, utilice algunos de los filtros disponibles y presione el botón "Aplicar".',
                'Filters & Options' => 'Filtros y Opciones',
                'of' => 'de',
                'qform_action_update' => 'Editar',
                'qform_action_insert' => 'Nuevo',
                'Home' => 'Inicio',
                'qform_action_previewdelete' => 'Borrar',
                'Back to list' => 'Volver a la lista',
                'qform_action_view' => 'Ver',
                'Insert Image' => 'Insertar Imagen',
                '<strong>Record %s</strong> to <strong>%s</strong> from <strong>%s</strong> Records' => '<strong>Registro %s</strong> a <strong>%s</strong> de <strong>%s</strong> Registros',
				'Save & New' => 'Guardar y Crear Nuevo',
				'Back' => 'Volver',
				'No records available' => 'No hay registros',

            );
            if(isset($lang_strings[$str])) return vsprintf($lang_strings[$str], $args );
            break;
        default:
            return vsprintf($str, $args );
        }
        @error_log("'$str' => '$str',\n", 3, '/tmp/qforms.log');
        return "## ".vsprintf($str, $args )." ##";
    }

    /**
    * Helper function
    **/
    function    Data_Subset($data, $subset=null) {
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

    /**
    * Helper function ( TODO: SECURITY PROBLEMS )
    **/
    function    SQL_Filters( $data_subset=null  ) {
        $filter_expr = array();
        $values = array();
        foreach($this->abm_filters as $name=>$filter ) {
            $values[ $name ] = !empty($filter->sql_name)
            	? $filter->sql_name
            	: $name;
        }
        if($data_subset)
            $values = array_flip(QForms::Data_Subset($values, $data_subset));
        foreach($values as $name=>$sql_name) {
            $type = $this->abm_filters[$name]->abm_filter_type;
            $value = $this->abm_filters[$name]->value;
            if($value || (strval($value)==="0") ) {
                switch($type) {
                case QFORMS_FILTER_EXACT:
                    $filter_expr[] = "$sql_name = '".addslashes($value)."'";
                    break;
                case QFORMS_FILTER_LIKE:
                    $filter_expr[] = "$sql_name ".(($this->sql_engine=='pgsql')?'ilike':'like')." '%".addslashes($value)."%'";
                    break;
                case QFORMS_FILTER_RANGE:
                    if($value[0])
                        $filter_expr[] = " ($sql_name >= '".addslashes($value[0])."')";
                    if($value[1])
                        $filter_expr[] = " ($sql_name <= '".addslashes($value[1])."')";
                    break;
                case QFORMS_FILTER_NULL:
                    if($value)
                        $filter_expr[] = "$sql_name IS NOT NULL";
                    else
                        $filter_expr[] = "$sql_name IS NULL";
                    break;
                case QFORMS_FILTER_ISZERO:
                    if($value)
                        $filter_expr[] = "$sql_name != 0";
                    else
                        $filter_expr[] = "$sql_name == 0";
                    break;
                case QFORMS_FILTER_USER:
                    if( !empty($this->abm_filters[$name]->eventOnFilter) && ($t=eval($this->abm_filters[$name]->eventOnFilter)) )
                    	$filter_expr[] = $t;
                    break;
                }
            }
        }
        if($filter_expr)
            return '('.implode(') AND (',$filter_expr).')';
        return '';
    }
    function    SQL_UserFilters() {
        $user_filters=array();
        $values = array();
        foreach(array_keys($this->abm_filters) as $name)
            $values[ $name ] = $name;
        foreach($values as $name=>$sql_name) {
            $type = $this->abm_filters[$name]->abm_filter_type;
            if($type==QFORMS_FILTER_USER) {
                $value = $this->abm_filters[$name]->value;
                if( $value||(strval($value)==="0")  )
                    $user_filters[$name]=$value;
            }
        }
        return $user_filters;
    }

    /**
    * Helper function ( TODO: SECURITY PROBLEMS )
    **/
    function    SQL_Order($aliases=array()) {
        if($aliases) {
            if(!is_array($aliases)) {
                $t=preg_split('/[,\s]+/',$aliases);
                $subset=array();
                foreach($t as $i=>$kv) {
                    @list($k,$v)=preg_split('/:/',$kv,2);
                    $subset[$k] = ($v?$v:$k);
                }
            }
            $aliases=$subset;
        }
        $order_expr=array();
        if($this->abm_orderBy) {
            foreach(explode('|',$this->abm_orderBy) as $order) {
                if(substr($order,-1)=='*') {
                    $ascdesc =' DESC';
                    $order=substr($order,0,-1);
                }else{
                    $ascdesc =' ASC';
                }
                if(!empty($aliases[$order])) $order=$aliases[$order];
                $order_expr[]="$order $ascdesc";
            }
        }
        if($order_expr)
            return implode(',',$order_expr);
        return '';
    }

    /**
    * Helper function ( TODO: SECURITY PROBLEMS? NO )
    **/
    function    SQL_PrimaryKey($currPk) {
        $order=0;
        if(is_array($currPk))
            $cv = reset($currPk);
        foreach(array_keys($this->abm_fields) as $name) {
            if($this->abm_fields[$name]->abm_pk_order) {
                $fieldname = !empty($this->abm_fields[$name]->sql_name)
                	? $this->abm_fields[$name]->sql_name
                	: $name;
                if(is_array($currPk)) {
                    $exprs[] = $fieldname ."='".addslashes($cv)."'";
                    $cv=next($currPk);
                }else{
                    $exprs[] = $fieldname ."='".addslashes($currPk)."'";
                    break;
                }
            }
        }
        return implode(' AND ',$exprs);
    }

    /**
    * Helper function
    **/
    function    SQL_Function($value) {
        return array('SQL',$value);
    }

    /**
    * Helper function
    * $table = nombre de tabla
    * $data = array KV con los datos
    * $noQuote = campos que no se deben encerrar entre comillas , separados por comas
    * $skip = campos que tiene que ignorar en el array
    **/
    function    SQL_Insert($table, $data, $noQuote='', $skip='') {
        if(!is_array($noQuote)) $noQuote=preg_split('/[,\s]+/',$noQuote);
        if(!is_array($skip)) $skip=preg_split('/[,\s]+/',$skip);
        $fields=$values=array();
        foreach($data as $k=>$v) {
            if(in_array($k,$skip)) continue;

            if(is_array($v)) $v=implode(' ',$v);
            elseif(is_null($v)) $v= "NULL";
            elseif(!in_array($k,$noQuote)) $v= "'".addslashes($v)."'";
            $fields[]=$k;
            $values[]=$v;
        }
        if($fields)
            return "INSERT INTO $table (".implode(',',$fields).") VALUES (".implode(',',$values).")";
        return null;
    }

    /**
    * Helper function
    **/
    function    SQL_Update($table, $keyExpr, $data, $noQuote='',$skip='') {
        if(!is_array($noQuote)) $noQuote=preg_split('/[,\s]+/',$noQuote);
        if(!is_array($skip)) $skip=preg_split('/[,\s]+/',$skip);
        $fvs=array();
        foreach($data as $k=>$v) {
            if(in_array($k,$skip)) continue;

            if(is_array($v)) $v=implode(' ',$v);
            elseif(is_null($v)) $v= "NULL";
            elseif(!in_array($k,$noQuote)) $v= "'".addslashes($v)."'";
            $fvs[$k] = " $k=$v";
        }

        if($fvs)
            return "UPDATE $table SET ".implode(',',$fvs)." WHERE $keyExpr";
        return null;
    }

    function    SQL_excecute_SelectInsertUpdate($table, $where, $rec, $noQuote='', $noInsert='', $noUpdate='' ) {
        if(QForms::SQLQuery("SELECT 1 FROM $table WHERE $where",1)) {
            return QForms::SQLQuery(QForms::SQL_Update($table, $where, $rec, $noQuote, $noUpdate),4);
        }else{
            return QForms::SQLQuery(QForms::SQL_Insert($table, $rec, $noQuote, $noInsert),4);
        }
    }

    /**
    * Helper function
    **/
    function    SQLQuery($sql, $mode, $lstart=null, $lcount=null) {
        return LwUtils::SQLQuery($sql, $mode, $lstart, $lcount);
    }

    /**
    * Helper function
    **/
    function    GeneralConfirm($msg, $url, $timeout=-1) {
        echo '<h3>'.htmlspecialchars($msg).'</h3>';
        if($timeout>-1) {
            echo '<script type="text/javascript">self.setTimeout("self.location=\''.$url.'\'",'.($timeout*1000).');</script>';
            echo '<div style="text-align:center; background-color: #EBEBEB; border-top: 1px solid #FFFFFF; border-left: 1px solid #FFFFFF; border-right: 1px solid #AAAAAA; border-bottom: 1px solid #AAAAAA; font-weight : bold;">';
            echo '<p>Si ve que la página no recarga automáticamente en '.$timeout.' segundos, <br />Por favor presione <a href="'.$url.'">Aquí</a></p>';
            echo '</div>';
        }
    }
    function    evtBeforeTable() {}
    function    evtAfterTable() {}
    function    evtBeforeData() {}
    function    evtAfterData() {}
    function    evtAfterLastRow() {}
    function    evtBeforeHeaders() {}



    /**
    * Helper function
    **/
    function    PHP_Filters( $data_subset=array() ) {
        $filter_expr = array();
        $values = array();
        foreach(array_keys($this->abm_filters) as $name)
            $values[ $name ] = $name;
        if($data_subset)
            $values = array_flip(QForms::Data_Subset($values, $data_subset));
        foreach($values as $name=>$sql_name) {
            $type = $this->abm_filters[$name]->abm_filter_type;
            $value = $this->abm_filters[$name]->value;
            if(@$_GET['D']) {
                printf("<li>%s: %s", $name, var_export($value,true) );
            }
            if($value || (strval($value)==="0") ) {
                switch($type) {
                case QFORMS_FILTER_EXACT:
                    $filter_expr[] = "\$rec['$sql_name'] == '".addslashes($value)."'";
                    break;
                case QFORMS_FILTER_LIKE:
                    $filter_expr[] = "preg_match('/^.*".str_replace('%','.*',preg_quote(addslashes($value))).".*/',\$rec['$sql_name'])";
                    break;
                case QFORMS_FILTER_RANGE:
                    /* TODO */
                    $a=array();
                    if(is_array($value)) {
                        if($value[0]||(strval($value[0])==="0") )
                            $a[]= "\$rec['$sql_name'] >= '".addslashes($value[0])."'";
                        if($value[1]||(strval($value[1])==="0") )
                            $a[]= "\$rec['$sql_name'] <= '".addslashes($value[1])."'";
                        if($t=implode(' && ',$a))
                            $filter_expr[]=$t;
                    }
                    break;
                case QFORMS_FILTER_NULL:
                    if($value)
                        $filter_expr[] = "!is_null(\$rec['$sql_name'])";
                    else
                        $filter_expr[] = "is_null(\$rec['$sql_name'])";
                    break;
                case QFORMS_FILTER_USER:
                    // No hago nada de nada.
                    break;
                }
            }
        }
        if($filter_expr)
            return '('.implode(') && (',$filter_expr).')';
        return 'true';
    }

    /**
    * Helper function
    **/
    function    PHP_Order() {
        $ascdesc=null;
        if($this->abm_orderBy) {
            foreach(explode('|',$this->abm_orderBy) as $order) {
                if(substr($order,-1)=='*') {
                    $ascdesc = SORT_DESC;
                    $order=substr($order,0,-1);
                }else{
                    $ascdesc = SORT_ASC;
                }
                break;
            }
        }
        if($ascdesc)
            return array($order,$ascdesc);
        return '';
    }

    /**
    * Helper function ( TODO: SECURITY PROBLEMS? NO )
    **/
    function    PHP_PrimaryKey($currPk) {
        $order=0;
        if(is_array($currPk))
            $cv = reset($currPk);
        foreach(array_keys($this->abm_fields) as $name) {
            if($this->abm_fields[$name]->abm_pk_order) {
                if(is_array($currPk)) {
                    $exprs[] = "(\$rec['$name'] == '".addslashes($cv)."')";
                    $cv=next($currPk);
        }else{
                    $exprs[] = "(\$rec['$name'] == '".addslashes($currPk)."')";
                    break;
                }
        }
    }
        return implode(' && ',$exprs);
    }
    function    getKVArray($data, $k=null,$v=null) {
        $temp=array();
        if($data && $data[0]) {
            $fl=array_keys($data[0]);
            if(empty($k)) $k=reset($fl);
            if(empty($v)) $v=next($fl);
            foreach($data as $idx=>$rec) {
                $data[$idx]=null;
                if(is_array($v)) {
                    $t=''; foreach($v as $v1) $t .=' '. $rec[$v1];
                    $temp[ @$rec[$k] ] = $t;
                }else{
                    $temp[ @$rec[$k] ] = $rec[$v];
                }
            }
        }
        return $temp;
    }
}

function include_jquery() {
	if( defined('JQUERY_INCLUDED') || !defined('JQUERY_URI') )
		return false;
	echo '<script type="text/javascript" charset="UTF-8" src="'.JQUERY_URI.'jquery-1.2.6.js"></script>';
	echo '<script type="text/javascript" charset="UTF-8" src="'.JQUERY_URI.'jquery.curvycorners.source.js"></script>';
	echo '<script type="text/javascript" charset="UTF-8" src="'.JQUERY_URI.'thickbox.js"></script>';
	echo '<script type="text/javascript" charset="UTF-8" src="'.JQUERY_URI.'ui/jquery.ui.all.js"></script>';
	echo '<link rel="stylesheet" charset="UTF-8" href="'.JQUERY_URI.'themes/flora/flora.all.css" />';
	echo '<link rel="stylesheet" href="'.JQUERY_URI.'thickbox.css" type="text/css" media="screen" />';
	?>
<script type="text/javascript" >
jQuery.noConflict();
jQuery(document).ready(function(){
	jQuery('.round-corners').corner();
	jQuery('#toggle-list-control-pannel').click(function(){
		jQuery('#list-control-pannel').toggle('slow');
	});
});
</script>
	<?php
}


?>
