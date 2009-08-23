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

require_once(QFORMS_PATH.'xforms.php');

class   QFormsConfiguration extends QForms {
    function    Run() {
        $this->Init();
        $this->Prepare();
        $this->Process();
    }
    function    Init() {
        parent::Init();
        /**
        * Define options&permission HERE
        **/
        $this->abm_title        = 'Global Configuration';
        $this->perm_view        = false;
        $this->perm_insert      = false;
        $this->perm_update      = true;
        $this->perm_delete      = false;
        $this->perm_export      = false;
        $this->perm_print       = false;
        $this->perm_list        = false;
        $this->perm_listform    = false;
        $this->currAction       = QFORMS_ACTION_UPDATE;
        $this->currRecord       = 'dummy';
        $this->confirm_reload   = 1; // tiempo en segundos (0-infinito) para recargar. -1 desactiva
        $this->abm_confirm_url  = $_SERVER['REQUEST_URI'];
        $this->abm_rowsPerPage  = 50;
        $this->sql_engine       = 'mysql';
        $this->configuration_tablename = 'configuration';
        $this->template_form    = QFORMS_PATH_TEMPLATES.'xforms.template.tabbedform.php'; // xforms.template.form.php
    }
    function    Process() {
        $this->cfg_currGroup    = @strval($_GET['xcfg_group']);
        $this->cfg_assocdata[ ]  = array();
        $this->cfg_assocextras[ ]  = array();

//        $this->cfg_groups = array(); //QForms::SQLQuery("SELECT DISTINCT grupo, grupo as grupo2 FROM configuration WHERE not (grupo like '\_%') ORDER BY grupo",5);
        $this->cfg_groups = array(''=>'')+QForms::SQLQuery("SELECT DISTINCT group_name, group_name as grupo2 FROM $this->configuration_tablename WHERE not (group_name like ' %') ORDER BY group_name",5);
        //if(empty($this->cfg_currGroup)) $this->cfg_currGroup=reset(array_keys($this->cfg_groups));

        $where=($this->cfg_currGroup?"WHERE group_name='".($this->cfg_currGroup)."'":"");
        $cfg_data = array();
        if($this->show_without_group||$this->cfg_currGroup)
            $cfg_data = QForms::SQLQuery($s="SELECT name, $this->configuration_tablename.* FROM $this->configuration_tablename $where ORDER BY name",6);

        foreach($cfg_data as $rec) {
            switch(strtolower($rec['type'])) {
            case 'int': case 'integer': case 'entero':
                $this->addField(new QFormsWidget_Integer(array(
                    'name'          => $rec['name'],
                    'caption'  		=> $rec['name'],
                    'description'   => $rec['description'],
                    )));
                break;
            case 'array':
                $this->addField(new QFormsWidget_TextArea(array(
                    'name'          => $rec['name'],
                    'caption'  		=> $rec['name'],
                    'description'   => $rec['description'],
                    'rows'          => 5,
                    'cols'          => 40,
                    )));
                break;
            case 'textarea':
                $this->addField(new QFormsWidget_TextArea(array(
                    'name'          => $rec['name'],
                    'caption'  		=> $rec['name'],
                    'description'   => $rec['description'],
                    'rows'          => 5,
                    'cols'          => 40,
                    )));
                break;
            case 'html':
                $this->addField(new QFormsWidget_HTMLEditor(array(
                    'name'          => $rec['name'],
                    'caption'  		=> $rec['name'],
                    'description'   => $rec['description'],
                    'rows'          => 5,
                    'cols'          => 40,
                    'editor_url'        => QFORMS_URI.'tinymce/xforms_editor.php',
                    'editor_inline'     => QFORMS_PATH.'tinymce/xforms_editor.inline.php',
                    )));
                break;
            case 'checkboxset':
            	list($setof_values, $setof_selected) = explode('|',$rec['value'],2);
                $this->addField(new QFormsWidget_CheckBoxSet(array(
                    'name'          => $rec['name'],
                    'caption'  		=> $rec['name'],
                    'description'   => $rec['description'],
                    'values'   		=> (function_exists($setof_values)?call_user_func($setof_values):$GLOBALS[$setof_values]),
                    )));
                 $this->cfg_assocextras[ $rec['name'] ] = $setof_values;
                 $rec['value'] = $setof_selected;
                break;
            default:
                $this->addField(new QFormsWidget_TextField(array(
                    'name'          => $rec['name'],
                    'caption'  		=> $rec['name'],
                    'description'   => $rec['description'],
                    )));
            }
            $this->cfg_assocdata[ $rec['name'] ]  = $rec['value'];
            $this->cfg_assoctypes[ $rec['name'] ] = $rec['type'];
        }
        parent::Process();
    }
    function    data_select($do_count=false, $pk=null) {
        return $this->cfg_assocdata;
    }
    function    data_update($pk, $data, $rid=null) {
        foreach($data as $name=>$value) {
            $type       = $this->cfg_assoctypes[ $name ];
            $orig_value = $this->cfg_assocdata[$name];
            switch($type) {
            case 'array':
                break;
            case 'checkboxset':
            	$value = $this->cfg_assocextras[$name].'|'.$value;
                break;
            }
            // If the value has changed, we store it.
            if( $orig_value !== $value ) {
                QForms::SQLQuery(sprintf("UPDATE $this->configuration_tablename SET value='%s' WHERE name='%s'",
                    addslashes($value), addslashes($name)
                    ),4);
            }
        }
        return true;
    }
    function    data_delete($pk) {
        return false;
    }
    function    evtBeforeData() {
        printf('<center><b>Group: </b><select name="xcfg_group" onchange="self.location=\'%s&xcfg_group=\'+this.options[this.selectedIndex].value">%s</select></center><br/>',
            QForms::URL($_SERVER['REQUEST_URI'],'xcfg_group'),
            QForms::HTML_options($this->cfg_groups, $this->cfg_currGroup));

        if($this->cfg_currGroup=='mensajes') {
            echo "Recuerde que en todos los mensajes que sean un asunto o texto de mensaje, puede usar variables type @nombrecompleto@, @nombre_usuario@ y @clave@ en el texto";
        }
    }

    function    ProcessConfiguration($data) {
        $result=array();
        foreach($data as $rec) {
            $name   = $rec['name'];
            $type   = $rec['type'];
            $value  = $rec['value'];
            switch($type) {
            case 'array':
                $result[$name]=array();
                foreach( preg_split('/\r?\n/',trim($value)) as $kv ) {
                    @list($k,$v) = explode(': ',$kv,2);
                    $result[$name][$k] = $v;
                }
                break;
            case 'checkboxset':
            	list($setof_values, $value) = explode('|',$value,2);
                $result[$name]=array();
                foreach( preg_split('/;/',trim($value)) as $k ) {
                    $result[$name][$k] = $k;
                }
                break;
            default:
                $result[$name]=$value;
            }
        }
        return $result;
    }
}

/**

DROP TABLE IF EXISTS `configuration`;
CREATE TABLE `configuration` (
  `name` varchar(255) NOT NULL default '',
  `value` text NOT NULL,
  `type` varchar(12) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `group_name` varchar(15) default NULL,
  PRIMARY KEY  (`name`)
);

INSERT INTO bw_configuration (name,value,type,description,group_name) VALUES ('','','','','');

**/

?>