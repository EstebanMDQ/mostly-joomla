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
if (!defined('QFORMS_PATH'))
	define('QFORMS_PATH', './');
require_once (QFORMS_PATH . 'qforms.php');

/**
* Declaro una clase de la que van a ser hijos todos los listados/abms/loquesea de este site (acï¿½ pongo valores x defecto para un monton de cosas).
**/
class ModuleABMS extends QForms {

	var $perfiles = array ();

	function Init() {
		parent :: Init();

		//verificacion de permisos
		$this->ERROR_ON(!$this->verificaPermisos(), 'Restricted access');

		$this->qforms_language = $GLOBALS['qforms_default_language'] = 'es';
		/**
		* Define options&permission HERE
		*
		* esta implementación es especial para integrar con el backend
		**/

		if (!isset ($this->skipBreadCrumb))
			$this->skipBreadCrumb = false;
		$this->record_name = ''; // esta propiedad sirve para decirle al abm como armar el boton view
		$this->listLeftActionsCaption = '&nbsp;';
		$this->listRightActionsCaption = '&nbsp;';

		$this->perm_list = true;
		$this->perm_view = true;
		$this->perm_insert = true;
		$this->perm_update = true;
		$this->perm_delete = true;
		$this->perm_listform = false;
		$this->perm_export = false;
		$this->perm_print = false;
		$this->abm_name = ''; // variable para definir como se llama el abm, usado para la clase de las grillas

		$this->show_saveAndNew_button = true;
		$this->showTabs = false;
		$this->abm_rowsPerPage = 50;
		$this->controls_before = false;

		/**
		 * opciones de delete_mode
		 *
		 * direct - borra sin preguntar nada
		 * prompt - borra preguntando en javascript
		 * confirm - borra preguntando en una pagina html
		 */
		$this->delete_mode = 'confirm';

		$this->template_list = QFORMS_PATH_TEMPLATES . 'qforms.simple-template.list.php';
		$this->template_form = QFORMS_PATH_TEMPLATES . 'qforms.simple-template.form.php'; // xforms.template.form.php
		$this->template_confirm = QFORMS_PATH_TEMPLATES . 'qforms.template.confirm.php';

		// tiempo en segundos para recargar al confirmar. -1 desactiva
		$this->confirm_reload = 0;
		$this->html_delete_confirmation = false;

		$this->sql_engine = 'mysql';

		/**
		* para el volver de las submodal
		if(in_array($this->currAction,array(QFORMS_ACTION_VIEW,QFORMS_ACTION_INSERT,QFORMS_ACTION_UPDATE,QFORMS_ACTION_DELETE)))
		    $this->abm_back_url='javascript:void(parent.hidePopWin(false))';
		$this->template_list    = QFORMS_PATH_TEMPLATES.'xforms.template.subModal.php'; // xforms.template.form.php
		**/

		/**
		* Propiedades del módulo como tal, no de las QForms
		**/
		$this->sql_table = null;
		$this->sql_insert_fields = null;
		$this->sql_update_fields = null;
		$this->sql_select = 'SELECT %s FROM %s %s %s';
		$this->sql_select_fields = null;
		$this->sql_select_where = null;

		$this->abm_export_url = QForms :: URL($this->abm_url, array (
			$this->abm_prefix . 'action' => QFORMS_ACTION_EXPORT,
			$this->abm_prefix . 'Module' => basename(empty ($this->export_print_callback) ? $_SERVER['SCRIPT_FILENAME'] : $this->export_print_callback)
		), null, QFORMS_URI .
		'qforms.generic.export_print.php');
		$this->abm_print_url = QForms :: URL($this->abm_url, array (
			$this->abm_prefix . 'action' => QFORMS_ACTION_PRINT,
			$this->abm_prefix . 'Module' => basename(empty ($this->export_print_callback) ? $_SERVER['SCRIPT_FILENAME'] : $this->export_print_callback)
		), null, QFORMS_URI .
		'qforms.generic.export_print.php');

		if (empty ($this->abm_back_url) && $this->currAction != QFORMS_ACTION_LIST)
			$this->abm_back_url = QForms :: URL($this->abm_list_url, $this->abm_prefix . 'backUrl');
	}
	function Process() {

		if ($this->perm_delete && in_array($this->currAction, array (
				QFORMS_ACTION_LISTFORM
			))) {
			$this->addField(new QFormsWidget_Checkbox(array (
				'name' => $this->abm_prefix . 'MassDelete',
				'caption' => 'Delete?',
				'description' => '',
				'is_null' => false,
				'is_required' => false,
				'is_static' => false,
				'_TAGS' => '',
				'field_group' => '',
				'css_class' => $this->abm_prefix . 'MassDelete'
			)));
		}

		parent :: Process();
		if (empty ($this->abm_name) && !empty ($this->table))
			$this->abm_name = 'QForms_' . $this->table;
	}

	/**
	* Ejecuta query con la api de joomla
	* juan cruz
	**/
	function joomlaSQLQuery($sql) {
		$db = JFactory :: getDBO();
		$db->setQuery($sql);
		$db->query();
	}

	function verificaPermisos() {

		$result = false;
		if (!empty ($this->perfiles)) {
			//si hay permisos para verificar
			$user = & JFactory :: getUser();
			if (!$user->block) {
				//tambien se podria utilizar el id del permiso [gid] => 25 para Super Administrator
				if (in_array($user->usertype, $this->perfiles)) {
					$result = true;
				}
			}
		} else {
			$result = true;
		}
		return $result;
	}

	/**
	* Data Select
	**/
	function data_select($do_count = false, $pk = null) {
		$where = $this->sql_select_where;
		// Obtengo el ORDER BY
		$order = (($t = $this->SQL_Order()) ? " ORDER BY $t" : "");
		// Obtengo el WHERE (de los filtros seteados en las QFORMS, si los hay)
		if ($t = $this->SQL_Filters())
			$where = ($where ? "$where AND $t" : "WHERE $t");
		// Obtengo el WHERE (de la primary key, si la hay)
		if ($pk && ($t = $this->SQL_PrimaryKey($pk)))
			$where = ($where ? "$where AND $t" : "WHERE $t");
		// Ejecuto alguno de los 3 selects posibles (count, getOne, getAll)

		$db = JFactory :: getDBO();

		if ($do_count) {
			$db->setQuery(sprintf($this->sql_select, "COUNT(*)", $this->sql_table, $where, ""));
			return $db->loadResult();

		}
		elseif ($pk) {
			$db->setQuery(sprintf($this->sql_select, $this->sql_select_fields, $this->sql_table, $where, ""));
			$rec = $db->loadAssoc();
			$this->ERROR_ON(empty ($rec), QForms :: trans("Record not found"));
			return !empty ($rec) ? $rec : array ();
		} else {
			#printf($this->sql_select,$this->sql_select_fields,$this->sql_table , $where, $order);
			$limit = !empty ($this->abm_limit) ? " LIMIT {$this->abm_limit[0]}, {$this->abm_limit[1]}" : '';
			$sql = sprintf($this->sql_select, $this->sql_select_fields, $this->sql_table, $where, $order . $limit);
			$db->setQuery($sql);
			$list = $db->loadAssocList();
			return !empty ($list) ? $list : array ();
		}
	}

	function validate_record($pk, $data, $mode) {
		return true;
	}

	/**
	* Ejecuto un INSERT en la DB
	**/
	function data_insert($data) {
		if ($this->validate_record(null, $data, 'insert')) {
			if ($this->sql_insert_fields)
				$data = QForms :: Data_subset($data, $this->sql_insert_fields);
			if ($sql = QForms :: SQL_Insert($this->sql_table, $data)) {
				if (function_exists('QFORMS_AUDIT_HOOK'))
					QFORMS_AUDIT_HOOK('INSERT', $this->sql_table, null, $data, $sql, null, @ $this->abm_result_dataDiff[$this->abm_curr_rid]);
				//QForms::SQLQuery($sql,4);
				//juan cruz            ;
				$this->joomlaSQLQuery($sql);
				return true;
			}
		}
		return false;
	}

	/**
	* Ejecuto un UPDATE en la DB
	**/
	function data_update($pk, $data) {
		if ($this->validate_record($pk, $data, 'update')) {
			if (@ $data[$this->abm_prefix . 'MassDelete'])
				return $this->data_delete($pk);
			unset ($data[$this->abm_prefix . 'MassDelete']);
			if ($this->sql_update_fields)
				$data = QForms :: Data_subset($data, $this->sql_update_fields);
			$cond = $this->SQL_PrimaryKey($pk);
			if ($sql = QForms :: SQL_Update($this->sql_table, $cond, $data)) {
				if (function_exists('QFORMS_AUDIT_HOOK'))
					QFORMS_AUDIT_HOOK('UPDATE', $this->sql_table, $pk, $data, $sql, $this->abm_data[$this->abm_curr_rid], @ $this->abm_result_dataDiff[$this->abm_curr_rid]);
				//QForms::SQLQuery($sql,4);
				//juan cruz
				echo $sql;
				$this->joomlaSQLQuery($sql);
				return true;
			}
		}
		return false;
	}

	/**
	* Ejecuto un DELETE en la DB
	**/
	function data_delete($pk) {
		if ($this->validate_record($pk, null, 'delete')) {
			$where = $this->SQL_PrimaryKey($pk);
			//QForms::SQLQuery($sql="DELETE FROM $this->sql_table WHERE $where", 4);
			//juan cruz
			$this->joomlaSQLQuery($sql = "DELETE FROM $this->sql_table WHERE $where");
			$null = null;
			if (function_exists('QFORMS_AUDIT_HOOK'))
				QFORMS_AUDIT_HOOK('INSERT', $this->sql_table, $pk, $null, $sql, $this->abm_data[$this->abm_curr_rid], null, null);
			return true;
		}
		return false;
	}

	function htmlListButtonsLeft($rid, $rec, $set = array ()) {
		$currPK = $this->getPK($rid, $rec, true);
		$setof_buttons = array ();

		//Render($classname, $params, $as_static=false, $value=null, $prefix='xF_', $html_only=true) {
		if (!empty ($this->record_name) && $this->perm_update) {
			$text = $this->getFieldValue($this->record_name);
			$setof_buttons[] = QFormsWidget :: Render('QFormsWidget_button', array (
				'name' => 'ViewButton',
				'text' => $text,
				'onclick' => "self.location='$this->abm_update_url&{$this->abm_prefix}record=$currPK&{$this->abm_prefix}confirmUrl=" . urlencode($_SERVER['REQUEST_URI']) . "'",
				'css_class' => 'f_textfield',
				'show_as_link' => true,
				
			), false, null, $this->abm_prefix);
		}
		elseif (!empty ($this->record_name) && $this->perm_view) {
			$text = $this->getFieldValue($this->record_name);
			$setof_buttons[] = QFormsWidget :: Render('QFormsWidget_button', array (
				'name' => 'ViewButton',
				'text' => $text,
				'onclick' => "self.location='$this->abm_view_url&{$this->abm_prefix}record=$currPK&{$this->abm_prefix}confirmUrl=" . urlencode($_SERVER['REQUEST_URI']) . "'",
				'css_class' => 'f_textfield',
				'show_as_link' => true,
				
			), false, null, $this->abm_prefix);
		}
		return implode('', $setof_buttons);
	}
	/**
	* Returns the row buttons (view/edit) according to permission
	**/
	function htmlListButtonsRight($rid, $rec, $set = array ()) {
		$currPK = $this->getPK($rid, $rec, true);
		$setof_buttons = array ();
		if (empty ($this->record_name) && (!isset ($set['view']) || $set['view']) && $this->perm_view) {
			$setof_buttons[] = QFormsWidget :: Render('QFormsWidget_button', array (
				'name' => 'ViewButton',
				'text' => QForms :: trans('View'),
				'onclick' => "self.location='$this->abm_view_url&{$this->abm_prefix}record=$currPK&{$this->abm_prefix}confirmUrl=" . urlencode($_SERVER['REQUEST_URI']) . "'",
				'css_class' => 'view',
				'show_as_link' => true,
				
			), false, null, $this->abm_prefix);
		}
		if (empty ($this->record_name) && (!isset ($set['update']) || $set['update']) && $this->perm_update) {
			$setof_buttons[] = QFormsWidget :: Render('QFormsWidget_button', array (
				'name' => 'EditButton',
				'text' => QForms :: trans('Modify'),
				'description' => QForms :: trans('Modify'),
				'onclick' => "self.location='$this->abm_update_url&{$this->abm_prefix}record=$currPK&{$this->abm_prefix}confirmUrl=" . urlencode($_SERVER['REQUEST_URI']) . "'",
				'css_class' => 'edit',
				'show_as_link' => true,
				
			), false, null, $this->abm_prefix);
		}
		if ((!isset ($set['delete']) || $set['delete']) && $this->perm_delete) {
			switch ($this->delete_mode) {
				case 'prompt' :
					$setof_buttons[] = QFormsWidget :: Render('QFormsWidget_button', array (
						'name' => 'DeleteButton',
						'text' => QForms :: trans('Delete'),
						'description' => QForms :: trans('Delete'),
						'onclick' => "if(confirm('" . QForms :: trans('Do you really want to delete this record?') . "')) self.location='$this->abm_delete_url&{$this->abm_prefix}record=$currPK'",
						'css_class' => 'delete',
						'show_as_link' => true,
						
					), false, null, $this->abm_prefix);
					break;
				case 'confirm' :
					$setof_buttons[] = QFormsWidget :: Render('QFormsWidget_button', array (
						'name' => 'DeleteButton',
						'text' => QForms :: trans('Delete...'),
						'description' => QForms :: trans('Delete...'),
						'onclick' => "self.location='$this->abm_previewdelete_url&{$this->abm_prefix}record=$currPK'",
						'css_class' => 'delete',
						'show_as_link' => true,
						
					), false, null, $this->abm_prefix);
					break;
				case 'direct' :
					$setof_buttons[] = QFormsWidget :: Render('QFormsWidget_button', array (
						'name' => 'DeleteButton',
						'text' => QForms :: trans('Direct Delete'),
						'description' => QForms :: trans('Direct Delete'),
						'onclick' => "self.location='$this->abm_delete_url&{$this->abm_prefix}record=$currPK'",
						'css_class' => 'delete',
						'show_as_link' => true,
						
					), false, null, $this->abm_prefix);
					break;
			}
		}
		return implode('', $setof_buttons);
	}
	/**
	* Returns the form buttons (save/cancel/view/delete) according to permission
	**/
	function htmlFormActions($set = array ()) {
		$buttons = '';
		$currPK = $this->getPK(0, null, true);
		$is_view = ($this->perm_view && $this->currAction == QFORMS_ACTION_VIEW);
		$is_delete = ($this->perm_delete && $this->currAction == QFORMS_ACTION_DELETE);
		$is_insert = ($this->perm_insert && $this->currAction == QFORMS_ACTION_INSERT);
		$is_update = ($this->perm_update && $this->currAction == QFORMS_ACTION_UPDATE);

		if ($is_insert || $is_update) {
			if ((!isset ($set['cancel']) || $set['cancel']) && !empty ($this->abm_back_url)) {
				$buttons .= ' ' . QFormsWidget :: Render('QFormsWidget_button', array (
					'name' => 'CancelButton',
					'text' => QForms :: trans('Cancel'),
					'onclick' => "self.location='$this->abm_back_url'",
					#'css_class' => 'xfButtonBase xfButtonCancel'
					
				), false, null, $this->abm_prefix);
			}
			$buttons .= QFormsWidget :: Render('QFormsWidget_button', array (
				'name' => 'SubmitData',
				'text' => QForms :: trans('Save'),
				'do_submit' => true,
				#'css_class' => 'xfButtonBase xfButtonSave'
				
			), false, null, $this->abm_prefix);

			if ($this->perm_insert && $this->show_saveAndNew_button) {
				$buttons .= ' ' . QFormsWidget :: Render('QFormsWidget_button', array (
					'name' => 'SubmitData',
					'text' => QForms :: trans('Save & New'),
					'do_submit' => true,
					#'css_class' => '',
					'onclick' => "this.form.action='" . QForms :: URL($this->abm_url, "{$this->abm_prefix}confirmUrl", urlencode($this->abm_insert_url)) . "'; return true;",
					
				), false, null, $this->abm_prefix);
			}

			/*if( (!isset($set['delete'])||$set['delete']) && $is_update && $this->perm_delete) {
			    $buttons .= ' '.QFormsWidget::Render( 'QFormsWidget_button', array(
			        'name'      => 'DeleteButton',
			        'text'      => QForms::trans('Delete'),
			        'onclick'   => "if(confirm('".QForms::trans('Do you really want to delete this record?')."')) self.location='$this->abm_delete_url&{$this->abm_prefix}record=$currPK'",
			        #'css_class' => 'xfButtonBase xfButtonDelete'
			        ), false);
			}*/
		}
		elseif ($is_view || $is_delete) {
			if ($is_delete) {
				$buttons .= ' ' . QFormsWidget :: Render('QFormsWidget_button', array (
					'name' => 'SubmitData',
					'text' => QForms :: trans('Confirm'),
					'do_submit' => true,
					#'css_class' => 'xfButtonBase xfButtonDelete'
					
				), false, null, $this->abm_prefix);
			}
			/*if( (!isset($set['delete'])||$set['delete']) && $this->perm_delete && $is_view) {
			    $buttons .= QFormsWidget::Render( 'QFormsWidget_button', array(
			        'name'      => 'DeleteButton',
			        'text'      => QForms::trans('Delete'),
			        'onclick'   => "if(confirm('".QForms::trans('Do you really want to delete this record?')."')) self.location=('$this->abm_delete_url&{$this->abm_prefix}record=$currPK')",
			        #'css_class' => 'xfButtonBase xfButtonDelete'
			        ), false);
			}*/
			if ((!isset ($set['update']) || $set['update']) && $is_view && $this->perm_update) {
				$buttons .= ' ' . QFormsWidget :: Render('QFormsWidget_button', array (
					'name' => 'UpdateButton',
					'text' => QForms :: trans('Edit'),
					'onclick' => "self.location='$this->abm_update_url&{$this->abm_prefix}record=$currPK'",
					#'css_class' => 'xfButtonBase xfButtonUpdate'
					
				), false, null, $this->abm_prefix);
			}
			if ((!isset ($set['cancel']) || $set['cancel']) && !empty ($this->abm_back_url)) {
				$buttons .= ' ' . QFormsWidget :: Render('QFormsWidget_button', array (
					'name' => 'CancelButton',
					'text' => QForms :: trans($is_view ? 'Back to list' : 'Cancel'),
					'onclick' => "self.location='$this->abm_back_url'",
					#'css_class' => 'xfButtonBase xfButtonCancel'
					
				), false, null, $this->abm_prefix);
			}
		}
		if ($this->perm_delete && $this->perm_view && $this->currAction == QFORMS_ACTION_PREVIEWDELETE) {
			$buttons .= QFormsWidget :: Render('QFormsWidget_button', array (
				'name' => 'DeleteButton',
				'text' => QForms :: trans('Delete'),
				'onclick' => "self.location='$this->abm_delete_url&{$this->abm_prefix}record=$currPK'",
				#'css_class' => 'xfButtonBase xfButtonDelete'
				
			), false, null, $this->abm_prefix);
			$buttons .= ' ' . QFormsWidget :: Render('QFormsWidget_button', array (
				'name' => 'CancelButton',
				'text' => QForms :: trans($is_view ? 'Back to list' : 'Cancel'),
				'onclick' => "self.location='$this->abm_back_url'",
				#'css_class' => 'xfButtonBase xfButtonCancel'
				
			), false, null, $this->abm_prefix);
		}

		return $buttons;
	}

	function getAddButton() {
		return QFormsWidget :: Render('QFormsWidget_button', array (
			'name' => 'NewButton',
			'text' => QForms :: trans('Add New'),
			'onclick' => "self.location='$this->abm_insert_url'; return event.returnValue=true;",
			#'css_class' => 'xfButtonBase xfButtonInsert xfButtonOpenDialog',
			'description' => 'Agregar un nuevo registro a esta tabla.',
			'show_as_link' => false,
			
		), false, null, $this->abm_prefix);
	}

	/**
	* Returns the List/ListForm top buttons (New/View as list/listform/Export/print) according to permission
	**/
	function htmlListTopActions() {
		$buttons = '';
		if (!empty ($this->perm_insert)) {
			$buttons .= $this->getAddButton();
		}
		if (!empty ($this->perm_listform) && !empty ($this->perm_list)) {
			if ($this->currAction == QFORMS_ACTION_LISTFORM) {
				$buttons .= QFormsWidget :: Render('QFormsWidget_button', array (
					'name' => 'ListFormButton',
					'text' => QForms :: trans('List'),
					'onclick' => "self.location='$this->abm_list_url'; return event.returnValue=true;",
					#'css_class' => 'xfButtonBase xfButtonListForm',
					'show_as_link' => false,
					
				), false, null, $this->abm_prefix);
			} else {
				$buttons .= QFormsWidget :: Render('QFormsWidget_button', array (
					'name' => 'ListFormButton',
					'text' => QForms :: trans('Editable list'),
					'onclick' => "self.location='$this->abm_listform_url'; return event.returnValue=true;",
					#'css_class' => 'xfButtonBase xfButtonListForm',
					'description' => 'Permite editar algunos campos de los registros de este listado, sin tener que ir al formulario individual.',
					'show_as_link' => false,
					
				), false, null, $this->abm_prefix);
			}
		}
		if (!empty ($this->perm_export)) {
			$buttons .= QFormsWidget :: Render('QFormsWidget_button', array (
				'name' => 'ExportButton',
				'text' => QForms :: trans('Export'),
				'onclick' => "self.open('$this->abm_export_url'); return event.returnValue=true;",
				#'css_class' => 'xfButtonBase xfButtonExport',
				'show_as_link' => false,
				'description' => 'Descarga un archivo MS Excel conteniendo los datos de todas estas pï¿½ginas.',
				
			), false, null, $this->abm_prefix);
		}
		if (!empty ($this->perm_print)) {
			$buttons .= QFormsWidget :: Render('QFormsWidget_button', array (
				'name' => 'PrintButton',
				'text' => QForms :: trans('Print'),
				'onclick' => "window.print();",
				#'css_class' => 'xfButtonBase xfButtonPrint',
				'show_as_link' => false,
				'description' => 'Abre una pï¿½gina web apta para imprimir los datos de todas estas pï¿½ginas.',
				
			), false, null, $this->abm_prefix);
		}
		return $buttons;
	}

	function completeActivateButtonUrl(& $button) {
		$pk = $this->getFieldValue('id');
		$url = str_replace('@pk@', $pk, $button->ajaxUrlRequest);
		$button->onclick = "ajaxActivateItem(this, '$url')";
		#$button->css_class = 'notactive';
	}
}

/**
* Declaro una clase de la que van a ser hijos todos los listados/abms/loquesea de este site (acï¿½ pongo valores x defecto para un monton de cosas).
**/
class ModuleList extends QForms {
	function Init() {
		parent :: Init();
		/**
		* Define options&permission HERE
		**/
		$this->perm_list = true;
		$this->perm_view = true;
		$this->perm_insert = false;
		$this->perm_update = false;
		$this->perm_delete = false;
		$this->perm_listform = false;
		$this->perm_export = true;
		$this->perm_print = true;
		$this->abm_rowsPerPage = 50;
		$this->template_list = QFORMS_PATH_TEMPLATES . 'qforms.template.list.php';
		$this->template_form = QFORMS_PATH_TEMPLATES . 'qforms.template.form.php'; // qforms.template.form.php
		$this->template_confirm = QFORMS_PATH_TEMPLATES . 'qforms.template.confirm.php';

		// tiempo en segundos para recargar al confirmar. -1 desactiva
		$this->confirm_reload = 0;
		$this->html_delete_confirmation = false;

		$this->sql_engine = 'mysql';
		$this->confirm_reload = 1; // tiempo en segundos (0 a infinito) para recargar. -1 desactiva
		$this->qforms_language = $GLOBALS['qforms_default_language'] = 'es';

		/**
		* para el volver de las submodal
		if(in_array($this->currAction,array(QFORMS_ACTION_VIEW,QFORMS_ACTION_INSERT,QFORMS_ACTION_UPDATE,QFORMS_ACTION_DELETE)))
		    $this->abm_back_url='javascript:void(parent.hidePopWin(false))';
		$this->template_list    = QFORMS_PATH_TEMPLATES.'xforms.template.subModal.php'; // xforms.template.form.php
		**/

		/**
		* Propiedades del mï¿½dulo como tal, no de las QForms
		**/
		$this->sql_table = null;
		$this->sql_insert_fields = null;
		$this->sql_update_fields = null;
		$this->sql_select = null;
		$this->sql_select_fields = null;
		$this->sql_select_where = null;

		$this->abm_export_url = QForms :: URL($this->abm_url, array (
			$this->abm_prefix . 'action' => QFORMS_ACTION_EXPORT,
			$this->abm_prefix . 'Module' => basename(empty ($this->export_print_callback) ? $_SERVER['SCRIPT_FILENAME'] : $this->export_print_callback)
		), null, 'qforms/qforms.generic.export_print.php');
		$this->abm_print_url = QForms :: URL($this->abm_url, array (
			$this->abm_prefix . 'action' => QFORMS_ACTION_PRINT,
			$this->abm_prefix . 'Module' => basename(empty ($this->export_print_callback) ? $_SERVER['SCRIPT_FILENAME'] : $this->export_print_callback)
		), null, QFORMS_URI .
		'qforms.generic.export_print.php');
	}

	/**
	* Data Select
	**/
	function data_select($do_count = false, $pk = null) {
		$where = $this->sql_select_where;
		// Obtengo el ORDER BY
		$order = (($t = $this->SQL_Order()) ? " ORDER BY $t" : "");
		// Obtengo el WHERE (de los filtros seteados en las QFORMS, si los hay)
		if ($t = $this->SQL_Filters())
			$where = ($where ? "$where AND $t" : "WHERE $t");
		// Obtengo el WHERE (de la primary key, si la hay)
		if ($pk && ($t = $this->SQL_PrimaryKey($pk)))
			$where = ($where ? "$where AND $t" : "WHERE $t");
		// Ejecuto alguno de los 3 selects posibles (count, getOne, getAll)
		if ($do_count) {
			//echo sprintf($this->sql_select,"COUNT(*)", $where,"");
			return QForms :: SQLQuery(sprintf($this->sql_select, "COUNT(*)", $where, ""), 1);
		}
		elseif ($pk) {
			//echo sprintf($this->sql_select,$this->sql_select_fields, $where, "");
			$rec = QForms :: SQLQuery(sprintf($this->sql_select, $this->sql_select_fields, $where, ""), 2);
			$this->ERROR_ON(empty ($rec), QForms :: trans("Record not found"));
			return $rec;
		} else {
			//echo sprintf($this->sql_select,$this->sql_select_fields, $where, $order);
			return QForms :: SQLQuery(sprintf($this->sql_select, $this->sql_select_fields, $where, $order), 3, $this->abm_limit[0], $this->abm_limit[1]);
		}
	}

	/**
	* Ejecuto un INSERT en la DB
	**/
	function data_insert($data) {
		return false;
	}
	/**
	* Ejecuto un UPDATE en la DB
	**/
	function data_update($pk, $data) {
		return false;
	}
	/**
	* Ejecuto un DELETE en la DB
	**/
	function data_delete($pk) {
		return true;
	}
}

/**
* Declaro una clase de la que van a ser hijos todos los listados/abms/loquesea de este site (acï¿½ pongo valores x defecto para un monton de cosas).
**/
class ModuleSimpleForm extends QForms {
	function Init() {
		parent :: Init();
		/**
		* Define options&permission HERE
		**/
		$this->perm_list = false;
		$this->perm_view = false;
		$this->perm_insert = false;
		$this->perm_update = true;
		$this->perm_delete = false;
		$this->perm_listform = false;
		$this->perm_export = false;
		$this->perm_print = false;
		$this->abm_rowsPerPage = 50;
		$this->template_list = QFORMS_PATH_TEMPLATES . 'qforms.template.list.php';
		$this->template_form = QFORMS_PATH_TEMPLATES . 'qforms.template.tabbedform.php'; // qforms.template.form.php
		$this->template_confirm = QFORMS_PATH_TEMPLATES . 'qforms.template.confirm.php';

		// tiempo en segundos para recargar al confirmar. -1 desactiva
		$this->confirm_reload = 0;
		$this->html_delete_confirmation = false;

		$this->sql_engine = 'mysql';
		$this->confirm_reload = 1; // tiempo en segundos (0 a infinito) para recargar. -1 desactiva
		$this->qforms_language = $GLOBALS['qforms_default_language'] = 'es';

		unset ($this->abm_subtitle);

		$this->currAction = QFORMS_ACTION_UPDATE;
		$this->currRecord = 1;
		$this->form_data = array (
			'_really_dummy field' => null
		);
	}

	/**
	* Returns the form buttons (save/cancel/view/delete) according to permission
	**/
	function htmlFormActions($set = array ()) {
		$buttons = '';
		$currPK = $this->getPK(0, null, true);
		$is_update = ($this->perm_update && $this->currAction == QFORMS_ACTION_UPDATE);

		if ($is_update) {
			$buttons .= QFormsWidget :: Render('QFormsWidget_button', array (
				'name' => 'SubmitData',
				'text' => QForms :: trans('Submit'),
				'do_submit' => true,
				# 'css_class' => 'xfButtonBase xfButtonSave button'
				
			), false);

			if ((!isset ($set['cancel']) || $set['cancel']) && !empty ($this->abm_back_url)) {
				$buttons .= QFormsWidget :: Render('QFormsWidget_button', array (
					'name' => 'CancelButton',
					'text' => QForms :: trans('Cancel'),
					'onclick' => "self.location='$this->abm_back_url'",
					# 'css_class' => 'xfButtonBase xfButtonCancel button'
					
				), false);
			}
		}
		return $buttons;
	}

	function data_select($do_count = false, $pk = null) {
		return $this->form_data;
	}

	/**
	* Ejecuto un INSERT en la DB
	**/
	function data_insert($data) {
		return false;
	}

	/**
	* Ejecuto un UPDATE en la DB
	**/
	function data_update($pk, $data) {
		$cond = $this->SQL_PrimaryKey($pk);
		if ($sql = QForms :: SQL_Update($this->sql_table, $cond, $data)) {
			QForms :: SQLQuery($sql, 4);
			return true;
		}
		return false;
	}

	/**
	* Ejecuto un DELETE en la DB
	**/
	function data_delete($pk) {
		return false;
	}
}

class ModuleWizard extends QForms {
	function Init() {
		$tmp_step = @ $this->wizard_step;
		if (!$tmp_step)
			$tmp_step = @ intval($_GET[$this->abm_prefix . 'wizardStep']);
		$_SERVER['REQUEST_URI'] = QForms :: URL($_SERVER['REQUEST_URI'], $this->abm_prefix . 'wizardStep', $tmp_step);
		$this->qforms_language = $GLOBALS['qforms_default_language'] = 'es';
		parent :: Init();

		/**
		* Define options&permission HERE
		**/
		$this->abm_title = 'Wizard';
		$this->perm_update = true;
		$this->perm_view = false;
		$this->perm_insert = false;
		$this->perm_delete = false;
		$this->perm_export = false;
		$this->perm_print = false;
		$this->perm_list = false;
		$this->perm_listform = false;
		$this->currAction = QFORMS_ACTION_WIZARD;
		$this->template_form = QFORMS_PATH_TEMPLATES . 'qforms.template.wizard.php';
		$this->currRecord = 'dummy';
		$this->abm_confirm_url = QForms :: URL($_SERVER['REQUEST_URI'], $this->abm_prefix . 'wizardStep');
		$this->confirm_reload = -1; // tiempo en segundos (0-infinito) para recargar. -1 desactiva
		$this->sql_engine = 'mysql';
		$this->wizard_count = 1;
		$this->wizard_step = $tmp_step;
		if (!$tmp_step)
			$this->WizardData(null, true);
	}

	function Run() {
		$this->Init();
		$this->Prepare();
		if (!$this->wizard_step)
			$this->wizard_step = 1;
		$this->WizardStep($this->wizard_step);
		if ($this->Process()) {
			$this->Init();
			$this->WizardStep($this->wizard_step);
			$this->Process();
		}
		$this->abm_subtitle = "Step $this->wizard_step of $this->wizard_count";
	}

	function WizardStep($step) {
	}

	function data_select($do_count = false, $pk = null) {
		if ($do_count)
			return 1;
		return $this->WizardData();
	}
	function data_update($pk, $cdata, $rid = null) {
		return true;
	}
}

class ModuleABMS_Memory extends ModuleABMS {
	function Init() {
		parent :: Init();

		$this->memory_data = array ();
	}

	function Process() {
		$t = array_keys($this->abm_fields);
		$this->testdata_pk = reset($t);
		parent :: Process();
	}

	/**
	    * Data Select
	**/
	function data_select($do_count = false, $pk = null) {
		if ($pk) {
			foreach ($this->memory_data as $rec)
				if ($rec[$this->testdata_pk] == $pk)
					return $rec;
			return array ();
		} else {
			$where = $this->PHP_Filters();
			$order = $this->PHP_Order();
			$result = array ();
			foreach ($this->memory_data as $rec)
				if (eval ("return $where;"))
					$result[] = $rec;
			if ($order && $result) {
				$_R = array ();
				foreach ($result as $i => $r)
					$_R[$i] = $r[$order[0]];
				array_multisort($_R, $order[1], $result);
			}
			if ($do_count) {
				return count($result);
			} else {
				return $result;
			}
		}
	}
	function data_insert($data) {
		return false;
	}
	function data_update($pk, $data) {
		return false;
	}
	function data_delete($pk) {
		return false;
	}
}

class QFormsWidget_GenericYesNo extends QFormsWidget_CheckBox {
	function QFormsWidget_GenericYesNo($params) {
		return $this->QFormsWidget_CheckBox($params);
	}
}

class ReportBase extends ModuleABMS {
	function ReportBase() {
		$this->Init();
		$this->abm_rowsPerPage = 100000;
		$this->perm_list = true;
		$this->perm_view = false;
		$this->perm_insert = false;
		$this->perm_update = false;
		$this->perm_delete = false;
		$this->perm_listform = false;
		$this->perm_export = false;
		$this->perm_print = false;

		$this->sql_table = null;
		$this->sql_insert_fields = null;
		$this->sql_update_fields = null;
		$this->sql_select = ''; // SELECT %s FROM tabla %s %s
		$this->sql_select_fields = null;
		$this->sql_select_where = null;

		$this->FilterToShow = false;
		$this->showWarning = false;

		$this->quick_def = array (); // array que contiene campos de texto como widget, name, caption en ese orden, los widget pueden no llevar el prefijo QFormsWidget_
	}

	function Run() {

		$this->hookBeforePrepare();
		$this->Prepare();

		if (is_array($this->quick_def)) {
			foreach ($this->quick_def as $def) {
				$def = preg_split('/[,;|]\s*/', $def, 3);
				$class = null;
				if (class_exists('QFormsWidget_' . $def[0]))
					$class = 'QFormsWidget_' . trim($def[0]);
				elseif (class_exists($def[0])) $class = trim($def[0]);
				$def[1] = trim($def[1]);
				$def[2] = trim($def[2]);
				if (!empty ($class) && !empty ($def[1])) {
					$this->addField(new $class (array (
						'name' => trim($def[1]),
						'caption' => !empty ($def[2]) ? QForms :: trans($def[2]) : QForms :: trans($def[1]),
						'is_readonly' => true,
						
					)));
				}
			}
		}

		$this->hookBeforeProcess();
		$this->Process();
	}

	function data_insert($data) {
	}
	function data_update($pk, $data) {
	}
	function data_delete($pk) {
	}

	function hookBeforeProcess() {
	}
	function hookBeforePrepare() {
	}

	function evtBeforeTable() {
		if ($this->FilterToShow && $this->showWarning)
			echo '<div class="comment_warning">' . QForms :: trans('Aplique filtros para ver el reporte') . '</div>';
	}
	function data_select($do_count = false, $pk = null) {
		if (empty ($this->sql_select)) {
			if ($do_count)
				return 0;
			else
				return array ();
		}
		$where = $this->sql_select_where;
		// Obtengo el ORDER BY
		$order = (($t = $this->SQL_Order()) ? " ORDER BY $t" : "");
		// Obtengo el WHERE (de los filtros seteados en las QFORMS, si los hay)
		if ($t = $this->SQL_Filters())
			$where = ($where ? "$where AND $t" : "WHERE $t");
		// Obtengo el WHERE (de la primary key, si la hay)
		if ($this->FilterToShow && empty ($where)) {
			$this->showWarning = true;
			if ($do_count)
				return 0;
			else
				return array ();
		}
		if ($do_count) {
			$sql = sprintf($this->sql_select, "COUNT(*)", $where, "");
			$r = QForms :: SQLQuery($sql, 1);
			if (empty ($r))
				$r = 0;
		} else {
			$sql = sprintf($this->sql_select, $this->sql_select_fields, $where, $order);
			$r = QForms :: SQLQuery($sql, 3, $this->abm_limit[0], $this->abm_limit[1]);
		}
		#DEBUG echo "$sql<hr />";
		return $r;
	}

}
?>
