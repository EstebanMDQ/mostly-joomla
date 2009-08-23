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
class   QFormsWidget {
    function    QFormsWidget($params) {
        $this->name         = $params['name']
            or die(QForms::trans('QFormsWidget: missing name'));
        $this->sql_name 		= @$params['sql_name'];
        $this->caption          = (isset($params['caption'])?$params['caption']:ucFirst(str_replace('_',' ',$params['name'])));
        $this->description      = @$params['description'];
        $this->value            = @$params['value'];
        $this->default_value    = @$params['default_value'];
        $this->is_required      = @$params['is_required'];
        $this->is_readonly      = @$params['is_readonly'];
        $this->is_static        = @$params['is_static'];
        $this->is_list_control  = @$params['is_list_control'];
        $this->disabled         = @$params['disabled'];
        $this->is_visible       = (isset($params['is_visible'])?$params['is_visible']:true);
        $this->is_null          = @$params['is_null'];
        $this->static_size      = (isset($params['static_size'])?$params['static_size']:254);
    	$this->css_class	    = @$params['css_class'];
        $this->extra_html       = @$params['extra_html'];
        $this->event_onDisplay  = @$params['event_onDisplay'];
        $this->js_validation    = @$params['js_validation'];
        $this->_TAGS            = explode(' ',@$params['_TAGS']);
        $this->js_getValue      = "(@abm_prefix@Form.elements['@abm_prefix@{$this->name}']?@abm_prefix@Form.elements['@abm_prefix@{$this->name}'].value:null)";
        $this->field_group      = @$params['field_group'];
        $this->field_order      = @$params['field_order'];
        $this->field_listord    = @$params['field_listord'];
        $this->onchange     	= @$params['onchange'];
		$this->text_before_widget = @$params['text_before_widget'];
		$this->text_after_widget  = @$params['text_after_widget'];
    }
    function    Init() {
    }
    function    loadGPC($prefix, $from_get=false) {
        if( $this->is_readonly || $this->is_static )
            return null;
        if($from_get)   $t=@$_GET[$prefix.$this->name];
        else            $t=@$_POST[$prefix.$this->name];
        return ($t&&get_magic_quotes_gpc()?stripslashes($t):$t);
    }
    function    GetDefault() {
        return (strval($this->default_value)?$this->default_value:$this->value);
    }
    function    Validate($value, $zero_repr='0') {
        if(in_array('no_php_validate', $this->_TAGS)) return '';
        $value = strval($value);
        if($this->is_required && (empty($value)&&$value!==$zero_repr) ) {
            return QForms::trans("Required field: \'%s\'", addslashes($this->caption) );
        }
    }
    function    jsValidate() {
        $items=array();
        if( !$this->is_readonly&&!$this->is_static&& $this->is_visible && $this->is_required )
            $items[] = "
        t=document.getElementById('@idOf $this->name@');
        if(t) { t.className=t.className.replace(/errorField/,''); }
        if(@valueOf $this->name@=='' && (!t||!t.disabled) ) {
            errores.push( '".(QForms::trans("Required field: @nameOf $this->name@", addslashes($this->caption)))."\\n');
            if(t){t.className=(t.className?t.className:'')+' errorField';}
        }";
        if($this->js_validation)
            $items[] = $this->js_validation;
        return $items;
    }
    function    ProcessValue(&$value) {
    }
    function    Display($prefix, $base_value, $as_static=false) {
        if( $this->event_onDisplay ) eval($this->event_onDisplay);
        $this->value = $base_value;
        $as_static |= $this->is_readonly || $this->is_static;
        if( $as_static )
            return $this->htmlStatic($prefix);
        return $this->htmlControl($prefix);
    }
    function    htmlControl($prefix) {
    }
    function    htmlStatic($prefix) {
    }
    function    htmlExtras() {
        return array();
    }
    function    htmlHidden($prefix, $base_value) {
        $this->value = $base_value;
		$html = sprintf('<input type="hidden" id="%s" name="%s" value="%s" />',
			$prefix.$this->name, $prefix.$this->name, htmlspecialchars($this->value) );
		return $html;
    }

    /**
    * Static renderer
    **/
    function    Render($classname, $params, $as_static=false, $value=null, $prefix=null, $html_only=true) {
        $obj = new $classname($params);
        $obj->Init();
		if( empty($prefix) && !empty($this->abm->abm_prefix))
			$prefix = $this->abm->abm_prefix;
		elseif(empty($prefix) && empty($this->abm->abm_prefix))
			$prefix = 'xF_';
        $ret = $obj->Display($prefix, $value, $as_static);
        return ($html_only?$ret[1]:$ret);
    }
    /**
    * Static collector
    **/
    function    Collect($classname, $params, $from_get=false, $prefix='xF_') {
        $obj = new $classname($params);
        $obj->Init();
        return $obj->loadGPC($prefix, $from_get);
    }
}

/**
* Widget StaticHTML
**/
class   QFormsWidget_StaticHTML extends QFormsWidget {
    function    QFormsWidget_StaticHTML($params) {
        $this->QFormsWidget($params);
        $this->text         = @$params['text'];
        $this->is_static    = true;
        $this->_TAGS        = ($this->_TAGS?$this->_TAGS:'no_list no_formlist no_export no_print');
        $this->pre_fix      = @$params['pre_fix'];
    }
    function    htmlControl($prefix) {
		$html = ($this->text?$this->text:$this->value);
		if( $this->pre_fix )
		    $html = "<pre>$html</pre>";
        return array($this->css_class, $html);
    }
    function    htmlStatic($prefix) {
		return $this->htmlControl($prefix);
    }
}

/**
* Widget Button
**/
class   QFormsWidget_button extends QFormsWidget {
    function    QFormsWidget_button($params) {
        $this->QFormsWidget($params);
        $this->onclick      = @$params['onclick'];
        $this->text         = @$params['text'];
        $this->do_submit    = @$params['do_submit'];
    	$this->css_class	= ($this->css_class?$this->css_class:'xfABMWidgetButton button');
        $this->show_as_link = @$params['show_as_link'];
    }
    function    htmlControl($prefix) {
        if($this->show_as_link) {
            $url='';
            $r=array();
            $flag_open=false;

            if( preg_match('/\.location\s*=\s*[\'"]([^\'"]+)[\'"]/U', $this->onclick, $r) ) {
                $url=$r[1];
                $this->onclick='';
            }elseif( preg_match('/\.open\s*\(\s*[\'"]([^\'"]+)[\'"]/U', $this->onclick, $r) ) {
                $url=$r[1];
                $flag_open=true;
                $this->onclick='';
            }

            $html = sprintf('<a href="%s" class="%s" title="%s"%s>%s</a>',
                $url, $this->css_class,
                htmlspecialchars($this->description),
                ($flag_open?' target="_new"':'').($this->onclick?" onclick=\"$this->onclick;\"":"")
                , $this->text);
            if($this->disabled)
                $html = $this->text;
        }else{
            $html = sprintf('<input type="%s" name="%s" value="%s"%s title="%s" />',
                ($this->do_submit?'submit':'button'),$prefix.$this->name, htmlspecialchars($this->text),
                ($this->onclick?" onclick=\"$this->onclick\"":"")
                .($this->disabled?" disabled=\"disabled\"":"")
                .($this->css_class?" class=\"$this->css_class\"":""),
                htmlspecialchars($this->description)
                );
        }
        return array($this->css_class, $html);
    }
    function    htmlStatic($prefix) {
		return $this->htmlControl($prefix);
    }
}

/**
* Widget Hidden
**/
class   QFormsWidget_Hidden extends QFormsWidget {
    function    QFormsWidget_Hidden($params) {
        $this->QFormsWidget($params);
        $this->is_visible = false;
    }
    function    htmlControl($prefix) {
        return array(null, $this->htmlHidden($prefix, $this->value));
    }

}

/**
* Widget TextField
**/
class   QFormsWidget_TextField extends QFormsWidget {
    function    QFormsWidget_TextField($params) {
        $this->QFormsWidget($params);
        $this->size 		= intval(isset($params['size'])?$params['size']:0);
        $this->maxlength	= @$params['maxlength'];
		$this->css_class 	= !empty($params['css_class']) ? $params['css_class'] : 'QFormsWidgetTextField textfield';
    }
    function    htmlControl($prefix) {
		$html = sprintf('%s<input type="text" id="%s" name="%s" value="%s"%s />%s',
            $this->text_before_widget,
            $prefix.$this->name, $prefix.$this->name, htmlspecialchars($this->value),
            ($this->css_class?" class=\"$this->css_class\"":"")
            .$this->extra_html
            .($this->size?" size=\"$this->size\"":"")
            .($this->onchange?" onchange=\"$this->onchange\"":"")
            .($this->maxlength?" maxlength=\"$this->maxlength\"":"")
            .($this->disabled?" disabled=\"disabled\"":""),
            $this->text_after_widget
            );
        return array($this->css_class, $html);
    }
    function    htmlStatic($prefix) {
		$html = ($this->value?htmlspecialchars(QForms::_cutstring($this->static_size,$this->value)):'&nbsp;');
        return array($this->css_class, $html);
    }
}

/**
 * WIdget Double textfield, ideal para poner campos dobles como nombre+sufijo, codarea+telefono, etc
 */
class   QFormsWidget_DoubleTextField extends QFormsWidget_TextField {
	function QFormsWidget_DoubleTextField ($params) {
		$this->QFormsWidget_TextField( $params );
		$this->is_suffix = @$params['is_suffix'];
		$this->second_size = @intval($params['second_size'])==0 ? 4 : intval($params['second_size']);
	}
	function    htmlControl($prefix) {
		$value=$this->value;
		$this->value=$value[0];
		$field = parent::htmlControl($prefix);
		$this->size=$this->second_size;
		$name = $this->name;
		$this->name .= '_2';
		$this->value=$value[1];
		$second_field = parent::htmlControl($prefix);
		$this->name=$name;
		$html = $this->is_suffix
			? $field[1] . $second_field[1]
			: $second_field[1] . $field[1];
		$field[1] = $html;
		$this->value=$value;
		return $field;
	}
	function    loadGPC($prefix, $from_get=false) {
        if( $this->is_readonly || $this->is_static )
            return null;
        if($from_get)   $t=array(@$_GET[$prefix.$this->name],@$_GET[$prefix.$this->name.'_2']);
        else            $t=array(@$_POST[$prefix.$this->name],@$_POST[$prefix.$this->name.'_2']);

        return $t;//($t&&get_magic_quotes_gpc()?stripslashes($t):$t);
    }
}


/**
* Widget TextField
**/
class   QFormsWidget_Email extends QFormsWidget_TextField {
    function    QFormsWidget_Email($params) {
        $this->QFormsWidget_TextField($params);
    }
    function    jsValidate() {
        $items=parent::jsValidate();
        if( !$this->is_readonly&&!$this->is_static&& $this->is_visible )
            $items[] = "errores.push( (@valueOf $this->name@ && !(new RegExp('^[a-zA-Z0-9_\\.-]{1,64}@[a-zA-Z0-9_\\.-]{1,64}(\\.[a-zA-Z0-9_\\.-]{1,64}){1,3}\$')).test(@valueOf $this->name@))?'".addslashes(QForms::trans("'%s' is not a valid email address", $this->caption))."\\n':null );";
        return $items;
    }
    function    htmlStatic($prefix) {
		$html = sprintf("<a href=\"mailto:%s\">$this->value</a>",$this->value);
        return array($this->css_class, $html);
    }
}

/**
 * Widget URL
**/
class   QFormsWidget_Url extends QFormsWidget_TextField {
    function    QFormsWidget_Url($params) {
        $this->QFormsWidget_TextField($params);
    }
    function    jsValidate() {
        $items=parent::jsValidate();
        if( !$this->is_readonly&&!$this->is_static&& $this->is_visible )
            $items[] = "errores.push( (@valueOf $this->name@ && !(new RegExp('http:\/\/[A-Za-z0-9\.-]{3,}\.[A-Za-z]{2}')).test(@valueOf $this->name@))?'".addslashes(QForms::trans("'%s' is not a valid url", $this->caption))."\\n':null );";
        return $items;
    }
    function    htmlStatic($prefix) {
		$html = sprintf("<a href=\"%s\" target=\"_blank\">$this->value</a>",$this->value);
        return array($this->css_class, $html);
    }
}

/**
* Widget TextField
**/
class   QFormsWidget_Password extends QFormsWidget_TextField {
    function    QFormsWidget_Password($params) {
        $this->QFormsWidget_TextField($params);
        $this->encode_with	= @$params['encode_with'];
    }
    function    htmlControl($prefix) {
		$html = sprintf('<input type="password" id="%s" name="%s" value="%s"%s />',
            $prefix.$this->name, $prefix.$this->name, htmlspecialchars($this->value),
            ($this->css_class?" class=\"$this->css_class\"":"")
            .($this->size?" size=\"$this->size\"":"")
            .($this->onchange?" onchange=\"$this->onchange\"":"")
            .($this->maxlength?" maxlength=\"$this->maxlength\"":"")
            .($this->disabled?" disabled=\"disabled\"":"")
            );
        return array($this->css_class, $html);
    }
    function    ProcessValue(&$value) {
        if($this->encode_with=='md5'&&$value) $value=md5($value);
    }
}

/**
* Widget TextArea
**/
class   QFormsWidget_TextArea extends QFormsWidget {
    function    QFormsWidget_TextArea($params) {
        $this->QFormsWidget($params);
        $this->rows 		= intval(isset($params['rows'])?$params['rows']:2);
        $this->cols 		= intval(isset($params['cols'])?$params['cols']:0);
		$this->ie6_pre_fix = !empty($params['ie6_pre_fix']) && $params['ie6_pre_fix']==false ? false : true;
		$this->css_class 	= !empty($params['css_class']) ? $params['css_class'] : 'QFormsWidgetTextArea textarea';
    }
    function    htmlControl($prefix) {
		$html = sprintf('<textarea id="%s" name="%s"%s >%s</textarea>',
            $prefix.$this->name, $prefix.$this->name,
            ($this->css_class?" class=\"$this->css_class\"":"")
            .($this->rows?" rows=\"$this->rows\"":"")
            .($this->cols?" cols=\"$this->cols\"":"")
            .($this->onchange?" onchange=\"$this->onchange\"":"")
            .($this->disabled?" disabled=\"disabled\"":""),
            htmlspecialchars($this->value)
            );
        return array($this->css_class, $html);
    }
    function    htmlStatic($prefix) {
        /*
        if( $this->ie6_pre_fix==2 )
			$html = ($this->value? ('<pre>'.htmlspecialchars(QForms::_cutstring($this->static_size, $this->value)).'</pre>'):'&nbsp;');
        elseif( $this->ie6_pre_fix )
			$html = ($this->value? str_replace("\n","<br/>\n",htmlspecialchars(QForms::_cutstring($this->static_size, $this->value))):'&nbsp;');
		else
			$html = ($this->value?htmlspecialchars(QForms::_cutstring($this->static_size,$this->value)):'&nbsp;');
			*/
		//$html = ($this->value? '<div>'.str_replace("\n","<br/>\n",htmlspecialchars(QForms::_cutstring($this->static_size, $this->value))):'&nbsp;').'</div>';
		if( !is_null($this->static_size) )
			$html = ($this->value? str_replace("\n","<br/>\n",htmlspecialchars(QForms::_cutstring($this->static_size, $this->value))):'&nbsp;');
		else
			$html = ($this->value? str_replace("\n","<br/>\n",htmlspecialchars($this->value)):'&nbsp;');
        return array($this->css_class, $html);
    }
}


/**
* Widget Integer
**/
class   QFormsWidget_Integer extends QFormsWidget {
    function    QFormsWidget_Integer($params) {
        $this->QFormsWidget($params);
        $this->size 		= (isset($params['size'])?$params['size']:6);
        $this->max  		= @$params['max'];
        $this->min  		= @$params['min'];
        $this->html_when_zero= @$params['html_when_zero'];
    	$this->css_class	= ($this->css_class?$this->css_class:'xfABMWidgetNumber');
    }
    function    Validate($value) {
        if( $result = parent::Validate($value) ) return $result;
        if( (isset($this->max)&&$value>$this->max) || (isset($this->min)&&$value<$this->min) )
            return QForms::trans("The field  '%s' must range between %s and %s", $this->caption, $this->min, $this->max);
    }
    function    htmlControl($prefix) {
		$html = sprintf('%s<input type="text" id="%s" name="%s" value="%s"%s />%s',
			$this->text_before_widget,
            $prefix.$this->name, $prefix.$this->name, htmlspecialchars($this->value),
            ($this->css_class?" class=\"$this->css_class\"":"")
            .$this->extra_html
            .($this->size?" size=\"$this->size\" maxlength=\"$this->size\"":"")
            .($this->onchange?" onchange=\"$this->onchange\"":"")
            .($this->disabled?" disabled=\"disabled\"":"") ,
            $this->text_after_widget
            );
        return array(null, $html);
    }
    function    htmlStatic($prefix) {
        if(isset($this->html_when_zero) && !$this->value)
            $html=$this->html_when_zero;
        else
            $html = sprintf('%d', $this->value );
        return array($this->css_class, $html);
    }
}

/**
* Widget Numeric
**/
class   QFormsWidget_Numeric extends QFormsWidget {
    function    QFormsWidget_Numeric($params) {
        $this->QFormsWidget($params);
        $this->size 		= (isset($params['size'])?$params['size']:12);
        $this->decimals 	= (isset($params['decimals'])?$params['decimals']:2);
        $this->max  		= @$params['max'];
        $this->min  		= @$params['min'];
        $this->html_when_zero= @$params['html_when_zero'];
    	$this->css_class	= ($this->css_class?$this->css_class:'xfABMWidgetNumber');
        $this->onfocus     	= @$params['onfocus'];
    }
    function    htmlControl($prefix) {
		$html = sprintf('%s<input type="text" id="%s" name="%s" value="%s"%s />%s',
            $this->text_before_widget,
            $prefix.$this->name, $prefix.$this->name, number_format($this->value,$this->decimals,'.',''),
            ($this->css_class?" class=\"$this->css_class\"":"")
            .($this->size?" size=\"$this->size\" maxlength=\"$this->size\"":"")
            .($this->onchange?" onchange=\"$this->onchange\"":"")
            .($this->onfocus?" onfocus=\"$this->onfocus\"":"")
            .($this->disabled?" disabled=\"disabled\"":"") ,
            $this->text_after_widget
            );
        return array(null, $html);
    }
    function    Validate($value) {
        $value = doubleval($value);
        if( $result = parent::Validate($value, (!$value?strval($value):'0.00')) ) return $result;
        if( (isset($this->max)&&$value>$this->max) || (isset($this->min)&&$value<$this->min) )
            return QForms::trans("The field  '%s' must be greater than %s", $this->caption, $this->min);
    }
    function    htmlStatic($prefix) {
        if($this->html_when_zero && !$this->value)
            $html=$this->html_when_zero;
        else
		    $html = number_format($this->value,$this->decimals,'.','');
        return array($this->css_class, $html);
    }
}

/**
* Widget Currency
**/
class   QFormsWidget_Currency extends QFormsWidget_Numeric {
    function    QFormsWidget_Currency($params) {
        $this->QFormsWidget_Numeric($params);
        $this->symbol		= (isset($params['symbol'])?$params['symbol']: QFORMS_DEFAULT_CURRENCY_SYMBOL);
        $this->symbol_before_ammount = (isset($params['symbol_before_ammount'])?$params['symbol_before_ammount']: QFORMS_DEFAULT_SYMBOL_BEFORE_AMMOUNT);
    	$this->css_class	= (($this->css_class=='xfABMWidgetNumber')?'xfABMWidgetNumber xfABMWidgetCurrency':$this->css_class);
        $this->html_when_zero = ' - ';
        if( $this->symbol_before_ammount )
        	$this->text_before_widget = $this->symbol;
        else
        	$this->text_after_widget = $this->symbol;
    }
    function    htmlStatic($prefix) {
        $this->value=doubleval($this->value);
        if( isset($this->html_when_zero) && !$this->value )
            $html=$this->html_when_zero;
        else{
    		if( $this->symbol_before_ammount )
    			$html = $this->symbol.'&nbsp;'.number_format($this->value,$this->decimals,'.','');
    		else
    			$html = number_format($this->value,$this->decimals,'.','').'&nbsp;'.$this->symbol;
        }
        return array( (($this->value<0)?"{$this->css_class}_neg":$this->css_class) , $html);
    }
}


/**
* Widget Items
**/
class   QFormsWidget_SetOfItems extends QFormsWidget {
    function    QFormsWidget_SetOfItems($params) {
        $this->QFormsWidget($params);
        $this->quick_expr   = @$params['quick_expr']; // Expresion para obtener un text unico rapido.
        $this->values_expr  = null; // Expresion para obtener los key=>text totales
        $this->onchange     = @$params['onchange'];
        $this->picker_url   = ((@$params['picker_url'])?$params['picker_url']:QFORMS_WIDGETS_PICKERURL);
        $this->picker_set_uid= @$params['picker_set_uid']; // ID unico para obtener los valores desde el otro lado.
    }
    function    Init() {
        parent::Init();
        $this->js_getValue      = "(@abm_prefix@Form.elements['@abm_prefix@{$this->name}']?@abm_prefix@Form.elements['@abm_prefix@$this->name'].value:null)";
    }
    function    htmlControl($prefix) {
        $html = sprintf('<input type="text" name="%s" id="%s" size="4" value="%s"%s title="%s"/><input type="button" name="%s_button" id="%s_button" value="%s"%s onclick="self.jscode_setofitems_onchange(\'%s\',this.form[\'%s\'])"/>',
            $prefix.$this->name, $prefix.$this->name,
            htmlspecialchars($this->value),
            ($this->disabled?" disabled=\"disabled\"":"")
            .($this->css_class?" class=\"$this->css_class\"":""),
            htmlspecialchars($this->description),
            $prefix.$this->name,
            $prefix.$this->name,
            ($this->value?htmlspecialchars($this->quick_expr?eval($this->quick_expr):''):'?'),
            ($this->disabled?" disabled=\"disabled\"":"")
            .($this->css_class?" class=\"$this->css_class\"":""),
            $this->picker_set_uid,
            $prefix.$this->name
            );
        return array($this->css_class, $html );
    }
    function    htmlStatic($prefix) {
        if($this->quick_expr) {
            $html = htmlspecialchars(eval($this->quick_expr));
        }else{
            if( ($this->value==="0"||$this->value===0) && isset($this->values['0']))
                $html=htmlspecialchars(QForms::_cutstring($this->static_size, @$this->values[$this->value]));
            else
                $html = ($this->value?htmlspecialchars(QForms::_cutstring($this->static_size, @$this->values[$this->value])):'&nbsp;');
        }
        return array($this->css_class, $html);
    }
    function    htmlExtras() {
        $onchange = $this->onchange;
        $u = QForms::URL($_SERVER['REQUEST_URI'], null, null, $this->picker_url);
$jsc = <<<___EOT___
<script>
function	jscode_setofitems_setvalue(field, code, text) {
    if(!code) {
        code=document.getElementById(field).getAttribute('value')
        text=document.getElementById(field+'_button').getAttribute('value')
    }
    document.getElementById(field).value  = code;
    document.getElementById(field+'_button').value = text;
}
function	jscode_setofitems_onchange(uid,elt) {
    var url='$u&W_ID='+escape(uid)+'&W_V='+escape(elt.value)+'&W_F='+escape(elt.id)
    var w = window.open(url,'setofitems_onchange', 'width=640,height=400,status=yes,titlebar=yes,dependant=yes,modal=yes,dialog=yes');
}
</script>
___EOT___;
		$pieces = parent::htmlExtras();
        $pieces['jscode_SetOfItems'] = &$jsc;
        return $pieces;
    }
}

/**
* Widget Select
**/
class   QFormsWidget_Select extends QFormsWidget {
    function    QFormsWidget_Select($params) {
        $this->QFormsWidget($params);
        $this->size 		= (isset($params['size'])?$params['size']:0);
        $this->values  		= @$params['values'];
        $this->values_expr  = @$params['values_expr'];
        $this->onchange     = @$params['onchange'];
        $this->static_size      = (isset($params['static_size'])?$params['static_size']:64);
        $this->blank_text   = @$params['blank_text'];
        $this->css_class	= !empty($params['css_class']) ? $params['css_class'] : 'QFormsWidgetSelect';
        $this->js_getValue      = "((@abm_prefix@Form.elements['@abm_prefix@$this->name']&&@abm_prefix@Form.elements['@abm_prefix@$this->name'].selectedIndex>=0)?@abm_prefix@Form.elements['@abm_prefix@$this->name'].options[@abm_prefix@Form.elements['@abm_prefix@$this->name'].selectedIndex].value:null)";
        $this->quick_expr  = @$params['quick_expr']; // Expresion para obtener un text unico rapido.
    }
    function    Init() {
        parent::Init();
    }
    function    htmlControl($prefix) {
        if(empty($this->values)) {
            if($this->values_expr)
                $this->values = eval($this->values_expr);
            QForms::ERROR_ON( !is_array($this->values), QForms::trans("Error. Select Widget requires values or values_expr")."($this->name)" );
        }
        if( $this->blank_text )
            $this->values = array(""=>$this->blank_text) + $this->values;
        $html = sprintf('<select id="%s" name="%s"%s>',
            $prefix.$this->name, $prefix.$this->name,
            ($this->css_class?" class=\"$this->css_class\"":"")
            .($this->size?" size=\"$this->size\" maxlength=\"$this->size\"":"")
            .($this->onchange?" onchange=\"$this->onchange\" ":"")
            .($this->disabled?" disabled=\"disabled\"":"")
            );
        foreach($this->values as $k=>$v)  {
            $k=strval($k);
            $this->value=strval($this->value);
            if($this->value==='') $this->value=null;
            //if($this->name=='paid_maintfee') { var_dump($this->value,$k, 'x');}
            $selected = (($k===$this->value)?' selected="selected"':'');
            $html .= '<option value="'.htmlspecialchars($k).'"'.$selected.'>'.htmlspecialchars(QForms::_cutstring($this->static_size,$v)).'</option>';
        }
        $html .= '</select>';
        return array($this->css_class, $html);
    }
    function    htmlStatic($prefix) {
        if($this->quick_expr) {
            $html = htmlspecialchars(eval($this->quick_expr));
        }else{
            if(empty($this->values)) {
                if($this->values_expr)
                    $this->values = eval($this->values_expr);
                QForms::ERROR_ON( !is_array($this->values), QForms::trans("Error. Select Widget requires values or values_expr")."($this->name)" );
                if( $this->blank_text )
                    $this->values = array(""=>$this->blank_text) + $this->values;
            }
            if( ($this->value==="0"||$this->value===0) && isset($this->values['0']))
                $html=htmlspecialchars(QForms::_cutstring($this->static_size, @$this->values[$this->value]));
            else
                $html = ($this->value?htmlspecialchars(QForms::_cutstring($this->static_size, @$this->values[$this->value])):'&nbsp;');
        }
        return array($this->css_class, $html);
    }
}


/**
* Widget Select
**/
class   QFormsWidget_SelectMany extends QFormsWidget {
    function    QFormsWidget_SelectMany($params) {
        $this->QFormsWidget($params);
        $this->size 		= (isset($params['size'])?$params['size']:0);
        $this->values  		= @$params['values'];
        $this->values_expr  = @$params['values_expr'];
        $this->onchange     = @$params['onchange'];
        $this->blank_text   = @$params['blank_text'];
        $this->css_class	= !empty($params['css_class']) ? $params['css_class'] : 'QFormsWidgetSelectList';
        $this->js_getValue      = "((@abm_prefix@Form.elements['@abm_prefix@$this->name']&&@abm_prefix@Form.elements['@abm_prefix@$this->name'].selectedIndex>=0)?@abm_prefix@Form.elements['@abm_prefix@$this->name'].options[@abm_prefix@Form.elements['@abm_prefix@$this->name'].selectedIndex].value:null)";
        $this->quick_expr  = @$params['quick_expr']; // Expresion para obtener un text unico rapido.
    }
    function    Init() {
        parent::Init();
    }
    function    htmlControl($prefix) {
        if(empty($this->values)) {
            if($this->values_expr)
                $this->values = eval($this->values_expr);
            QForms::ERROR_ON( !is_array($this->values), QForms::trans("Error. Select Widget requires values or values_expr")."($this->name)" );
            if( $this->blank_text )
                $this->values = array(""=>$this->blank_text) + $this->values;
        }
        $html = sprintf('<select id="%s" name="%s"%s>',
            $prefix.$this->name, $prefix.$this->name,
            ($this->css_class?" class=\"$this->css_class\"":"")
            .($this->size?" size=\"$this->size\" maxlength=\"$this->size\"":"")
            .($this->onchange?" onchange=\"$this->onchange\" ":"")
            .($this->disabled?" disabled=\"disabled\"":"")
            );
        foreach($this->values as $k=>$v)  {
            $k = strval($k);
            $this->value = strval($this->value);
            if($this->value==='') $this->value=null;
            $selected = (($k===$this->value)?' selected="selected"':'');
            $html .= '<option value="'.htmlspecialchars($k).'"'.$selected.'>'.htmlspecialchars($v).'</option>';
        }
        $html .= '</select>';
        return array($this->css_class, $html);
    }
    function    htmlStatic($prefix) {
        if($this->quick_expr) {
            $html = htmlspecialchars(eval($this->quick_expr));
        }else{
            if(empty($this->values)) {
                if($this->values_expr)
                    $this->values = eval($this->values_expr);
                QForms::ERROR_ON( !is_array($this->values), QForms::trans("Error. Select Widget requires values or values_expr")."($this->name)" );
                if( $this->blank_text )
                    $this->values = array(""=>$this->blank_text) + $this->values;
            }
            if( ($this->value==="0"||$this->value===0) && isset($this->values['0']))
                $html=htmlspecialchars(QForms::_cutstring($this->static_size, @$this->values[$this->value]));
            else
                $html = ($this->value?htmlspecialchars(QForms::_cutstring($this->static_size, @$this->values[$this->value])):'&nbsp;');
        }
        return array($this->css_class, $html);
    }
}

/**
* Widget CheckBox
**/
class   QFormsWidget_CheckBox extends QFormsWidget {
    function    QFormsWidget_CheckBox($params) {
        $this->QFormsWidget($params);
        $this->checked_value    = (isset($params['checked_value'])?$params['checked_value']: 1 );
        $this->unchecked_value  = (isset($params['unchecked_value'])?$params['unchecked_value']: 0 );
        $this->css_class	= !empty($params['css_class']) ? $params['css_class'] : 'QFormsWidgetCheckbox';
        $this->onclick          = @$params['onclick'];
        $this->is_required      = false;
    }
    function    htmlControl($prefix) {
        $checked = (!strcmp($this->value,$this->checked_value)?' checked=""':'');
        $v = htmlspecialchars($this->checked_value);
        $u = htmlspecialchars($this->unchecked_value);
		$html = sprintf('<input type="hidden" name="%s" value="%s" /><input type="checkbox" id="%s" name="%s" value="%s"%s />',
            $prefix.$this->name, $u, $prefix.$this->name, $prefix.$this->name, $v,
            $checked.($this->css_class?" class=\"$this->css_class\"":"").($this->onclick?" onclick=\"$this->onclick\"":"")
            .($this->disabled?" disabled=\"disabled\"":"")
            );
        return array($this->css_class, $html);
    }
    function    htmlStatic($prefix) {
 	    $html = (!strcmp($this->value,$this->checked_value)?QForms::trans("Yes"):QForms::trans("No"));
        return array($this->css_class, $html);
    }
}

/**
* Widget CheckBoxSN
**/
class   QFormsWidget_CheckBoxSN extends QFormsWidget_CheckBox {
    function    QFormsWidget_CheckBoxSN($params) {
        $this->QFormsWidget_CheckBox($params);
        $this->checked_value    = 'S';
        $this->unchecked_value  = 'N';
    }
}

/**
* Widget CheckBoxSiNo
**/
class   QFormsWidget_CheckBoxSiNo extends QFormsWidget_CheckBox {
    function    QFormsWidget_CheckBoxSiNo($params) {
        $this->QFormsWidget_CheckBox($params);
        $this->checked_value    = 'SI';
        $this->unchecked_value  = 'NO';
    }
}


/**
* Widget CheckBoxSet
**/
class   QFormsWidget_CheckBoxSet extends QFormsWidget {
    function    QFormsWidget_CheckBoxSet($params) {
        $this->QFormsWidget($params);
        $this->size 		  = (isset($params['size'])?$params['size']:0);
        $this->values  		  = @$params['values'];
        $this->values_expr    = @$params['values_expr'];
        $this->separator      	= (!empty($params['separator']) ? $params['separator'] : ';');
    	$this->css_class	  = ($this->css_class?$this->css_class:'QFormsWidgetCheckbox');
		$this->default_checked	= (!empty($params['default_checked']) && $params['default_checked'] ? true : false);
    }
    function    Init() {
        parent::Init();
        if($this->values_expr) {
            $this->values = eval($this->values_expr);
        }
		if( $this->default_checked && empty($this->value) ){
			foreach( $this->values as $k => $v ){
				$this->value[] = $k;
			}
			if( !empty($this->value) && is_array($this->value) )
				$this->value = implode($this->separator,$this->value);
		}
        QForms::ERROR_ON( !is_array($this->values), QForms::trans("Error. CheckBoxSet Widget requires values or values_expr") );
    }
    function    htmlControl($prefix) {
        $html = '<ul class="'.$this->css_class.'" >';
        foreach($this->values as $key=>$text) {
            $checked = (strpos("  ".$this->separator.$this->separator.$this->value.$this->separator, $this->separator.$key.$this->separator)?' checked=""':'');
            $key = htmlspecialchars($key);
            $html .= sprintf('<li><input type="checkbox" id="%s%s" name="%s[]" value="%s"%s /> <label for="%s%s">%s</label></li>',
                $prefix.$this->name, $key,
                $prefix.$this->name, $key,
                $checked
                .($this->disabled?" disabled=\"disabled\"":""),
                $prefix.$this->name, $key,
                $text
                );
        }
		$html .= '</ul>';
        return array($this->css_class, $html);
    }
    function    loadGPC($prefix, $from_get=false) {
        if( $this->is_readonly || $this->is_static )
            return null;
        if($from_get)
            $t=@$_GET[$prefix.$this->name];
        else
            $t=@$_POST[$prefix.$this->name];
        if(is_array($t)) $t=implode($this->separator,$t);
        return strval($t&&get_magic_quotes_gpc()?stripslashes($t):$t);
    }
    function    htmlStatic($prefix) {
        $html = array();
        foreach($this->values as $key=>$text)
            if(strpos("  ".$this->separator.$this->separator.$this->value.$this->separator, $this->separator.$key.$this->separator))
                $html[] = htmlspecialchars($text);
		$html = ($this->value?implode(',',$html):'&nbsp;');
        return array($this->css_class, $html);
    }
}



/**
* Widget Select
**/
class   QFormsWidget_DoubleListBox extends QFormsWidget {
    function    QFormsWidget_DoubleListBox($params) {
        $this->QFormsWidget($params);
        $this->size 		= (isset($params['size'])?$params['size']:5);
        $this->values_orig	= @$params['values_orig'];
        $this->orig_expr	= @$params['orig_expr'];
        $this->values_dest	= @$params['values_dest'];
        $this->css_class	= !empty($params['css_class']) ? $params['css_class'] : 'select_double';
        $this->dest_expr	= @$params['dest_expr'];
        $this->caption_orig	= @$params['caption_orig'];
        $this->caption_dest	= @$params['caption_dest'];
		$this->addItemOnce = !empty($params['addItemOnce']) ? $params['addItemOnce'] : true;
		$this->jsHookOnAdd = @$params['jsHookOnAdd'];
		$this->jsHookOnRmv = @$params['jsHookOnRmv'];

        /* $_html = <<<___EOT___
<table border="0" cellspacing="0" cellpadding="3" align="left">
  <caption>@caption@</caption>
  <tr>
	<td align="center">@caption_orig@</td><td>&nbsp;</td>
	<td align="center">@caption_dest@</td><td>&nbsp;</td>
	<td rowspan="4">@description@</td>
  </tr>
  <tr>
	<td rowspan="3"><input type="hidden" name="@fieldname@" value="@value@" /><select name="@fieldname@_orig" size="@size@" multiple="1">@origin@</select></td>
	<td><input type="button" value="&nbsp;>&nbsp;" onclick="qform_dlbox(this.form,'@fieldname@','add')" class="xfABMWidgetButton"></td>
	<td rowspan="3"><select name="@fieldname@_dest" size="@size@" multiple="1">@destination@</select></td>
	<td><input type="button" value="^" onClick="qform_dlbox(this.form,'@fieldname@','up')" class="xfABMWidgetButton"></td>
  </tr>
  <tr><td><br></td><td>&nbsp;</td></tr>
  <tr>
	<td><input type="button" value="&nbsp;<&nbsp;" onClick="qform_dlbox(this.form,'@fieldname@','rmv')" class="xfABMWidgetButton" /></td>
	<td><input type="button" value="v" onClick="qform_dlbox(this.form,'@fieldname@','dn')" class="xfABMWidgetButton"></td>
  </tr>
</table>
___EOT___; */

		$_html = <<<__EOT__
<div class="not_added">
	<div class="title">@caption_orig@</div>
	<input type="hidden" name="@fieldname@" value="@value@" />
	<select name="@fieldname@_orig" size="@size@">@origin@</select>
</div>
<div class="middle">
	<!-- input type="button" title="@title_addall@" class="button all" value="►►" name="" onclick="qform_dlbox(this.form,'@fieldname@','addall')"/ -->
	<input type="button" title="@title_add@" class="button" value="►" name="" onclick="qform_dlbox(this.form,'@fieldname@','add')"/>
	<input type="button" title="@title_rmv@" class="button" value="◄" name="" onclick="qform_dlbox(this.form,'@fieldname@','rmv')"/>
	<!-- input type="button" title="@title_rmvall@" class="button all" value="◄◄" name="" onclick="qform_dlbox(this.form,'@fieldname@','rmvall')"/ -->
</div>
<div class="added">
	<div class="title">@caption_dest@</div>
	<select name="@fieldname@_dest" size="@size@">@destination@</select>
</div>
<div class="sort">
	<input type="button" title="@title_up@" class="button" value="▲" name="" onclick="qform_dlbox(this.form,'@fieldname@','up')"/>
	<input type="button" title="@title_dn@" class="button" value="▼" name="" onclick="qform_dlbox(this.form,'@fieldname@','dn')"/>
</div>
__EOT__;
		$this->_html = isset($params['_html']) ? $params['_html'] : $_html;
    }
    function    Init() {
        parent::Init();
        if($this->orig_expr)
            $this->values_orig = eval($this->orig_expr);
        if($this->orig_expr)
            $this->values_dest = eval($this->dest_expr);
    }
    function    htmlControl($prefix) {
		if( !empty($this->value) && !is_array($this->value) ) {
			$this->value=explode(';', $this->value);
		}
		if( empty($this->values_dest) && !empty($this->value) ) {
			foreach($this->value as $v )
				if( !empty($this->values_orig[$v]) ) {
					$this->values_dest[$v] = $this->values_orig[$v];
					unset($this->values_orig[$v]);
				}else{
					$this->values_dest[$v] = $v;
				}
		}
		$orig='';
		for(reset($this->values_orig) ; list($k,$v) = each($this->values_orig) ; ) {
			$orig .= sprintf("<option value=\"%s\">%s</option>\n", htmlspecialchars($k), htmlspecialchars($v) );
		}
		$dest='';
		$a = array();
		foreach ( $this->values_dest as $k =>$v ) {
			$dest .= sprintf("<option value=\"%s\">%s</option>\n", htmlspecialchars($k), htmlspecialchars($v) );
			$a[] = $k;
		}

		$this->value = implode(';',$a);
		$html = QForms::x_replacer($this->_html, '@@', array(
			'fieldname'		=> $prefix.$this->name,
			'origin'		=> $orig,
			'destination'	=> $dest,
			'size'			=> $this->size,
			'caption'		=> $this->caption,
			'caption_orig'	=> $this->caption_orig,
			'caption_dest'	=> $this->caption_dest,
			'description'	=> $this->description,
			'value'			=> $this->value,
			'title_addall'  => QForms :: trans('Add all the items'),
			'title_add'  	=> QForms :: trans('Add all the selected item'),
			'title_rmv'  	=> QForms :: trans('Remove de selected item'),
			'title_rmvall'  => QForms :: trans('Remove all the items'),
			'title_up'  	=> QForms :: trans('Move the selected item up'),
			'title_dn'  	=> QForms :: trans('Move the selected item down'),
			));
		$html .= '<script type="text/javascript" >';
		if( $this->addItemOnce )
			$html .= 'window.doubleList_props[\''.$prefix.$this->name.'\'] = { \'addItemOnce\': true };';
		else
			$html .= 'window.doubleList_props[\''.$prefix.$this->name.'\'] = { \'addItemOnce\': false };';
		if( !empty($this->jsHookOnAdd) ) {
			$html .= 'window.doubleList_hook_add[\''.$prefix.$this->name.'\'] = '.$this->jsHookOnAdd.';';
		}
		if( !empty($this->jsHookOnRmv) ) {
			$html .= 'window.doubleList_hook_rmv[\''.$prefix.$this->name.'\'] = '.$this->jsHookOnRmv.';';
		}
		$html .= '</script>';
        return array($this->css_class, $html);
    }
    function    htmlStatic($prefix) {
		$html = '';
		if( !empty($this->values_dest) ) {
			$html = '<ul>';
			foreach( $this->values_dest as $k=>$v )
				$html .= "<li>$v</li>\n";
			$html .= '</ul>';
		}
        return array($this->css_class, $html);
    }

    function    htmlExtras() {
$jsc = <<<___EOT___
<script>
window.doubleList_hook_add = {};
window.doubleList_hook_rmv = {};
window.doubleList_props = {};

function qform_dlbox_cloner( o ) {
	return new Option( o.text, o.value );
}
function	qform_dlbox(form, field, action) {
	var orig = form.elements[field+'_orig'];
	var dest = form.elements[field+'_dest'];
	if(action=='up') {
		if(dest.selectedIndex>0) {
			var i = dest.selectedIndex
			var t = qform_dlbox_cloner( dest.options[ i-1 ] )
			dest.options[ i-1 ] = qform_dlbox_cloner(dest.options[ i ])
			dest.options[ i ] = t
			dest.selectedIndex = i-1
		}
	}else if(action=='dn') {
		if(dest.selectedIndex>-1 && dest.selectedIndex < dest.options.length-1) {
			var i = dest.selectedIndex
			var t = qform_dlbox_cloner( dest.options[ i+1 ] )
			dest.options[ i+1 ] = qform_dlbox_cloner(dest.options[ i ])
			dest.options[ i ] = t
			dest.selectedIndex = i+1
		}
	}else if(action=='add') {
		if(orig.selectedIndex>-1) {
            var d = orig.selectedIndex;
			if( window.doubleList_hook_add && window.doubleList_hook_add[field] )
				var o = window.doubleList_hook_add[field](orig.options[ orig.selectedIndex ]);
			else
				var o = orig.options[ orig.selectedIndex ];
			if( o!=false ){
				dest.options[ dest.options.length ] = qform_dlbox_cloner( o );
				if( window.doubleList_props && window.doubleList_props[field].addItemOnce && orig.options[orig.selectedIndex].value!='_new' ) {
					orig.options[ orig.selectedIndex ] = null;
					if( d<orig.options.length )
						orig.selectedIndex = d;
					else
						orig.selectedIndex = orig.options.length-1;
				} /* if( add_item_once ) */
			}
		}
	}else if(action=='rmv') {
		if(dest.selectedIndex>-1) {
            var d = dest.selectedIndex;
			if( window.doubleList_hook_rmv && window.doubleList_hook_rmv[field] )
				var o = window.doubleList_hook_rmv[field]( dest.options[ dest.selectedIndex ] );
			else
				var o = dest.options[ dest.selectedIndex ];
			if( o!=false ){
				if( window.doubleList_props && window.doubleList_props[field].addItemOnce ) {
					orig.options[ orig.options.length ] = qform_dlbox_cloner( o );
				} /* if( add_item_once ) */
				dest.options[ dest.selectedIndex ] = null;
				if( d<dest.options.length )
					dest.selectedIndex = d;
				else
					dest.selectedIndex = dest.options.length-1;
			}
		}
	}else if(action=='addall') {
		for(var i=0 ; i < orig.options.length ; i++ )
			dest.options[dest.options.length] = qform_dlbox_cloner(orig.options[i])
		for(var i=orig.options.length-1 ; i>-1 ; i-- ) orig.options[i] = null
	}else if(action=='rmvall') {
		for(var i=0 ; i < dest.options.length ; i++ )
			orig.options[orig.options.length] = qform_dlbox_cloner(dest.options[i])
		for(var i=dest.options.length-1 ; i>-1 ; i-- ) dest.options[i] = null
	}

	var r = ''
	for(var i=0 ; i < dest.options.length ; i++ )
		r += dest.options[i].value + ';'
	form.elements[field].value = r
}
</script>
___EOT___;
		$pieces = parent::htmlExtras();
        $pieces['jscode_DoubleListBox'] = &$jsc;
        return $pieces;
    }
}

/**
* Widget Lookup
**/
class   QFormsWidget_Lookup extends QFormsWidget_Select {
    function    QFormsWidget_Lookup($params) {
        $this->QFormsWidget_Select($params);
        $this->onchange    = null; // no onchange for lookup fields
        $this->css_class	= !empty($params['css_class']) ? $params['css_class'] : 'lookup';
        $this->js_getValue      = "((@abm_prefix@Form.elements['@abm_prefix@$this->name']&&@abm_prefix@Form.elements['@abm_prefix@$this->name'].selectedIndex>=0)?@abm_prefix@Form.elements['@abm_prefix@$this->name'].options[@abm_prefix@Form.elements['@abm_prefix@$this->name'].selectedIndex].value:null)";
    }
    function    htmlControl($prefix) {
        // SIEMPRE ordeno los valores.
        asort($this->values);
        $html = parent::htmlControl($prefix);

        $html = sprintf('<input type="text" onkeyup="xflookup_item(this,this.form.%s,event)"><br />',
            $prefix.$this->name,$prefix.$this->name,
            ($this->css_class?" class=\"$this->css_class\"":"")
            .($this->size?" size=\"$this->size\" size=\"$this->size\"":"")
            ) . $html[1];
        return array($this->css_class, $html);
    }
    function    htmlExtras() {
$jsc = <<<___EOT___
<script>
function xflookup_bs(sel,val,b,e) {
	if(b>e) return -1;
	var m = Math.round((b+e)/2);
	var t = sel.options[m].text.toLowerCase().substr(0,val.length);
	if(val < t)			return xflookup_bs(sel,val,b,m-1)
	else if(val > t)	return xflookup_bs(sel,val,m+1,e)
	else				return m
}
function xflookup_item(text,sel,e) {
	if(sel.xfl_searching) return;
	sel.xfl_searching = true
	var pos = 0;
	var v = text.value.toLowerCase()
	if(v) {
		if((pos=xflookup_bs(sel,v,0,sel.options.length-1)) >-1)
			sel.selectedIndex=pos;
		else
			text.value = v.substr(0,text.value.length-1)
	}
	sel.xfl_searching = false
}
</script>
___EOT___;
		$pieces = parent::htmlExtras();
        $pieces['jscode_Lookup'] = &$jsc;
        return $pieces;
    }
}

/**
* Widget TextArea
**/
class   QFormsWidget_HTMLEditor extends QFormsWidget {
    function    QFormsWidget_HTMLEditor($params) {
        $this->QFormsWidget($params);
        $this->rows 		= intval(isset($params['rows'])?$params['rows']:0);
        $this->cols 		= intval(isset($params['cols'])?$params['cols']:0);
        $this->maxlength	= @$params['maxlength'];
        $this->editor_url	      = ((@$params['editor_url'])?$params['editor_url']:QFORMS_WIDGETS_HTMLURL.'xforms_editor.php');
        $this->css_class	= !empty($params['css_class']) ? $params['css_class'] : 'htmleditor';
        $this->editor_inline      = @$params['editor_inline'];
        $this->editor_images_url  = ((@$params['editor_images_url'])?$params['editor_images_url']:QFORMS_WIDGETS_HTMLIMGURL);
        $this->editor_images_path = ((@$params['editor_images_path'])?$params['editor_images_path']:QFORMS_WIDGETS_HTMLIMGPATH);
    }
    function    htmlControl($prefix) {
        // Guardo estos parï¿½metros para que estï¿½n disponibles para el editor y el plugin de imï¿½genes.
        $_SESSION['QFormsWidget_HTMLEditor_cfg']=array(
            dirname($this->editor_url).'/', $this->editor_images_url, $this->editor_images_path );
        if(empty($this->editor_inline)) {
            $html = sprintf('<input type="hidden" name="%s" id="%s" value="%s" />
            <textarea name="%sPreview" id="%sPreview"%s disabled="disabled">%s</textarea>
            <input type="button" value="Add Text" onclick="window.open(\'%s&xfabm_field=%s\',\'QFormsWidgetHTMLEditor\')" class="xfABMWidgetButton" />',
                $prefix.$this->name, $prefix.$this->name, htmlspecialchars($this->value),
                $prefix.$this->name, $prefix.$this->name,
                ($this->css_class?" class=\"$this->css_class\"":"")
                .($this->rows?" rows=\"$this->rows\"":"")
                .($this->cols?" cols=\"$this->cols\"":""),
                htmlspecialchars($this->value),
                QForms::URL($this->editor_url),
                $prefix.$this->name
                );
        }else{
            ob_start();
            include($this->editor_inline);
            $html = ob_get_contents();
            ob_end_clean();
        }
        return array($this->css_class, $html);
    }
    function    htmlStatic($prefix) {
		$html = ($this->value?htmlspecialchars(strip_tags(QForms::_cutstring($this->static_size,$this->value))):'&nbsp;');
        return array($this->css_class, $html);
    }
}

/**
* Widget Date ANTERIOR, sin opciï¿½n a hora.

class   QFormsWidget_Date extends QFormsWidget {
    function    QFormsWidget_Date($params) {
        $this->QFormsWidget($params);
        $n 						= date('Y',time());
        $this->year_range       = (isset($params['year_range'])?$params['year_range']: array($n-5,$n+5) );
        $this->control_format   = (isset($params['control_format'])?$params['control_format']:'d/m/Y');
        $this->static_format    = (isset($params['static_format'])?$params['static_format']:'d/m/Y');
        $this->result_format    = (isset($params['result_format'])?$params['result_format']:'Y-m-d');
        $this->calendar_url     = (isset($params['calendar_url'])?$params['calendar_url']:QFORMS_URI.'xforms.date.calendar.js');
        $this->blank_text       = @$params['blank_text'];
    }
    function    htmlControl($prefix) {
        $this->value = QFormsWidget_Date::x_qDate($this->value);
        if($this->calendar_url)
            $html = $this->_renderCal($prefix.$this->name, '', $this->value );
        else
            $html = $this->_renderCombo($prefix.$this->name, '', $this->value );
        return array($this->css_class, $html);
    }
    function    htmlStatic($prefix) {
        $this->value = QFormsWidget_Date::x_qDate($this->value);
		$html = ($this->value?date($this->static_format, $this->value):'&nbsp;');
        return array($this->css_class, $html);
    }
    function    loadGPC($prefix, $from_get=false) {
        if( $this->is_readonly || $this->is_static )
            return null;
        if($from_get)   $value=@$_GET[$prefix.$this->name];
        else            $value=@$_POST[$prefix.$this->name];
        if($this->result_format)
            $value = QFormsWidget_Date::x_qDate($value, $this->result_format);
        return $value;
    }
    function    htmlExtras() {
        $set = array();
        if($this->calendar_url)
            $set['xfdatecal_calendar_code'] = '<script type="text/javascript" src="'.$this->calendar_url.'"></script>';
        return $set;
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
        }elseif($fecha=='NOW') {
            $fecha = time();
        }
        if($fecha!==null && $cvt)
            return date($cvt,$fecha);
        return $fecha;
    }
    function _renderCombo($name, $suffix, $default_date ) {
        $default_date   = QFormsWidget_Date::x_qDate($default_date);
        $pname          = $name.$suffix;
        $array_days     = range(1,31);
        $array_months   = array(1=>'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
        $array_years    = range($this->year_range[0], $this->year_range[1]);

        list($cyear,$cmonth,$cday) = ($default_date
            ?explode(' ',date('Y m d',$default_date),3)
            :array(null,null,null));

        $blank='';
        if($this->blank_text!==null) $blank='<option>'.htmlspecialchars($this->blank_text).'</option>';

        $bd = '<select name="'.$pname.'[]">'.$blank;
        foreach($array_days as $day)
            $bd .= sprintf('<option value="%s"%s>%s</option>', $day, (($cday==$day)?' selected="selected"':''), $day);
        $bd .= '</select>';
        $bm = '<select name="'.$pname.'[]">'.$blank;
        foreach($array_months as $i=>$month)
            $bm .= sprintf('<option value="%s"%s>%s</option>', $i, (($cmonth==$i)?' selected="selected"':''), $month);
        $bm .= '</select>';
        $by = '<select name="'.$pname.'[]">'.$blank;
        foreach($array_years as $year)
            $by .= sprintf('<option value="%s"%s>%s</option>', $year, (($cyear==$year)?' selected="selected"':''), $year);
        $by .= '</select>';

        $buf='';
        foreach(explode('/',$this->control_format) as $e ) {
            switch(strtolower($e)) {
            case 'd': $buf .= $bd; break;
            case 'm': $buf .= $bm; break;
            case 'y': $buf .= $by; break;
            }
        }
        return $buf;
    }
    function _renderCal($name, $suffix, $default_date ) {
        $default_date   = QFormsWidget_Date::x_qDate($default_date, $this->control_format);
        $pname          = $name.$suffix;
        $code = "if(!window._flag_jscal) { window._flag_jscal=true; self.qform_showCal('$pname', '-', this.form.$pname.name, this.form.name, event); }";
        return sprintf('<input type="text" name="%s" size="10" maxlength="10" value="%s"%s />'.
            '<input type="button" value="..." onclick="%s" class="xfABMWidgetButton" /><div id="qfcal_%s" style="position:absolute; left:0px;"></div>',
            $pname, $default_date,
            ($this->css_class?" class=\"$this->css_class\"":"")
            .($this->disabled?" disabled=\"disabled\"":""),
            $code, $pname
            );
    }
}
**/

/**
* Widget Date
**/
class   QFormsWidget_Date extends QFormsWidget {
    function    QFormsWidget_Date($params) {
        $this->QFormsWidget($params);
        $n 						= date('Y',time());
        $this->year_range       = (isset($params['year_range'])?$params['year_range']: array($n-5,$n+5) );
        $this->control_format   = (isset($params['control_format'])?$params['control_format']:'d-m-Y');
        $this->static_format    = (isset($params['static_format'])?$params['static_format']:'d-m-Y');
        $this->result_format    = (isset($params['result_format'])?$params['result_format']:'Y-m-d');
        $this->calendar_url     = (isset($params['calendar_url'])?$params['calendar_url']:QFORMS_URI.'jscalendar/');
		$this->showTime 		 	= isset($params['showTime']) ? $params['showTime'] : false;
		$this->singleClick 		= isset($params['singleClick']) ?$params['singleClick'] : true;
        $this->blank_text       = @$params['blank_text'];
    	$this->css_class	    = ($this->css_class?$this->css_class:'xfABMWidgetDate date');
    	$this->readonly_textfield = !empty($params['readonly_textfield']) ? $params['readonly_textfield'] : false;
    }
    function    htmlControl($prefix) {
        $this->value = QFormsWidget_Date::x_qDate($this->value);
        if($this->calendar_url)
            $html = $this->_renderCal($prefix.$this->name, '', $this->value );
        else
            $html = $this->_renderCombo($prefix.$this->name, '', $this->value );
        return array($this->css_class, $html);
    }
    function    htmlStatic($prefix) {
        $this->value = QFormsWidget_Date::x_qDate($this->value,$this->static_format);
		$html = ($this->value?$this->value:'&nbsp;');
        return array($this->css_class, $html);
    }
    function    loadGPC($prefix, $from_get=false) {
        if( $this->is_readonly || $this->is_static )
            return null;
        if($from_get)   $value=@$_GET[$prefix.$this->name];
        else            $value=@$_POST[$prefix.$this->name];
        if(isset($value)) {
            if($this->result_format)
                $value = QFormsWidget_Date::x_qDate($value, $this->result_format);
        }
        return $value;
    }
    function    x_qDate($fecha, $cvt=null) {

        $qd_array_meses = array(
            'es' => array( 1=>"Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre" ),
            'en' => array( 1=>"January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December" ),
            );
        $qd_array_dias= array(
            'es'=> array( "Domingo", "Lunes", "Martes", "Miercoles", "Jueves", "Viernes", "Sï¿½bado"),
            'en'=> array( "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"),
            );
        $r=array();
        if(is_array($fecha)) {
            if(count($fecha)==3 && $fecha[0])
                $fecha = adodb_mktime(0,0,0, $fecha[1], $fecha[0], $fecha[2]);
            elseif(count($fecha)==2 && $fecha[0])
                $fecha = adodb_mktime(0,0,0, $fecha[0], 1, $fecha[1]);
            else
                $fecha = null;

        }elseif( is_string($fecha) && (empty($fecha) || substr($fecha,0,10)=='0000-00-00') ) {
            $fecha = '';
        }elseif( is_string($fecha) && ereg('^([0-9][0-9][0-9][0-9])[^0-9]([0-9]{1,2})[^0-9]([0-9][0-9])[^0-9]([0-9][0-9])[^0-9]([0-9][0-9])[^0-9]?([0-9][0-9])?',$fecha,$r)) { // formato YYYY-MM-DD HH:MM:SS-n Postgress...
            $fecha = adodb_mktime($r[4],$r[5],$r[6],$r[2],$r[3],$r[1]);
        }elseif( is_string($fecha) && ereg('^([0-9][0-9][0-9][0-9])[^0-9]([0-9]{1,2})[^0-9]([0-9]{1,2})$',$fecha,$r)) { // formato YYYY-MM-DD o YYYY/MM/DD
            $fecha = adodb_mktime(0,0,0,$r[2],$r[3],$r[1]);
        }elseif( is_string($fecha) && ereg('^(2[0-9][0-9][0-9])([0-9][0-9])([0-9][0-9])$',$fecha,$r)) { // formato YYYYMMDD
            $fecha = adodb_mktime(0,0,0,$r[2],$r[3],$r[1]);
        }elseif( is_string($fecha) && ereg('^([0-9]{1,2})[^0-9]([0-9]{1,2})[^0-9]([0-9]{1,4})$',$fecha,$r)) { // DD/MM/YYYY
            $fecha = adodb_mktime(0,0,0,$r[2],$r[1],$r[3]);
        }elseif( is_string($fecha) && ereg('^([0-9][0-9])[^0-9]([0-9]{1,2})[^0-9]([0-9][0-9][0-9][0-9])[^0-9]([0-9][0-9])[^0-9]([0-9][0-9])$',$fecha,$r)) { // formato DD-MM-YYYY HH:MM o DD/MM/YYYY HH:MM
			$fecha = adodb_mktime($r[4],$r[5],0,$r[2],$r[1],$r[3]);
        }elseif($fecha=='NOW') {
            $fecha = time();
        }

        if($fecha && $cvt) {
            if($cvt=='TEXTUAL') {
                return $qd_array_dias['es'][date('w',$fecha)]. " ". date('j',$fecha)." de ".$qd_array_meses['es'][date('n',$fecha)]." de ".date('Y',$fecha);
            }
            return date( $cvt, $fecha );
        }
        return $fecha;
    }

    function _renderCombo($name, $suffix, $default_date ) {
        $default_date   = QFormsWidget_Date::x_qDate($default_date);
        $pname          = $name.$suffix;
        $array_days     = range(1,31);
        $array_months   = array(1=>'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
        $array_years    = range($this->year_range[0], $this->year_range[1]);

        list($cyear,$cmonth,$cday) = ($default_date
            ?explode(' ',date('Y m d',$default_date),3)
            :array(null,null,null));

        $blank='';
        if($this->blank_text!==null) $blank='<option>'.htmlspecialchars($this->blank_text).'</option>';

        $bd = '<select name="'.$pname.'[]">'.$blank;
        foreach($array_days as $day)
            $bd .= sprintf('<option value="%s"%s>%s</option>', $day, (($cday==$day)?' selected="selected"':''), $day);
        $bd .= '</select>';
        $bm = '<select name="'.$pname.'[]">'.$blank;
        foreach($array_months as $i=>$month)
            $bm .= sprintf('<option value="%s"%s>%s</option>', $i, (($cmonth==$i)?' selected="selected"':''), $month);
        $bm .= '</select>';
        $by = '<select name="'.$pname.'[]">'.$blank;
        foreach($array_years as $year)
            $by .= sprintf('<option value="%s"%s>%s</option>', $year, (($cyear==$year)?' selected="selected"':''), $year);
        $by .= '</select>';

        $buf='';
        foreach(explode('/',$this->control_format) as $e ) {
            switch(strtolower($e)) {
            case 'd': $buf .= $bd; break;
            case 'm': $buf .= $bm; break;
            case 'y': $buf .= $by; break;
            }
        }
        return $buf;
    }
    function _renderCal($name, $suffix, $default_date ) {
        $default_date   = QFormsWidget_Date::x_qDate($default_date, $this->control_format);
        $pname          = $name.$suffix;
		$widgetvalue	= !empty($this->value) ? $this->x_qDate($this->value, $this->control_format) : '';
        $widget_control_format = preg_replace('/([A-Za-z])/','%\1',$this->control_format);
        $widget_control_format = str_replace('%i','%M', $widget_control_format);
		return sprintf('
			<input type="text" class="widget-date" name="%s" id="%s" %s size="%s" value="%s" />',
			$pname,
			$pname,
			$this->readonly_textfield ? ' readonly="" ' : '',
			$this->showTime?16:10,
			$widgetvalue
			);
    }
}

/**
* Widget DateRange
**/
class   QFormsWidget_DateRange extends QFormsWidget_Date {
    function    QFormsWidget_DateRange($params) {
        $this->QFormsWidget_Date($params);
    }
    function    Init() {
        parent::Init();
        $this->_normalize_dates($this->value);
        QForms::ERROR_ON( !is_array($this->year_range), QForms::trans("Error. DateRange Widget requires year_range as array") );
    }
    function    htmlControl($prefix) {
        $this->_normalize_dates($this->value);
        if($this->calendar_url) {
            $html_from  = $this->_renderCal($prefix.$this->name, '_f', $this->value[0]);
            $html_to    = $this->_renderCal($prefix.$this->name, '_t', $this->value[1]);
        }else{
            $html_from  = $this->_renderCombo($prefix.$this->name, '_f', $this->value[0]);
            $html_to    = $this->_renderCombo($prefix.$this->name, '_t', $this->value[1]);
        }
        $html = sprintf('<span class="from">%s%s</span><span class="to">%s%s</span>',
        		QForms :: trans('From'),
        		$html_from,
        		QForms :: trans('To'),
        		$html_to
        		);
        return array($this->css_class, "<span class=\"from\">From $html_from</span><span class=\"to\">To $html_to</span>");
    }
    function    htmlStatic($prefix) {
        if( is_array($this->value) && count($this->value)==2 ){
        	$value1 = QFormsWidget_Date::x_qDate($this->value[0]);
        	$value2 = QFormsWidget_Date::x_qDate($this->value[1]);
        	$html = sprintf('<span class="from">%s %s</span><span class="to">%s %s</span>',
        		QForms :: trans('From'),
        		($value1 ? date($this->static_format, $value1):'&nbsp;'),
        		QForms :: trans('To'),
        		($value2 ? date($this->static_format, $value2):'&nbsp;')
        		);
        } else {
        	$html = '';
        }

        #$this->value = QFormsWidget_Date::x_qDate($this->value);
		#$html = ($this->value?date($this->static_format, $this->value):'&nbsp;');
		//"<span class=\"from\">From $html_from</span><span class=\"to\">To $html_to</span>";
        return array($this->css_class, $html);
    }
    function    loadGPC($prefix, $from_get=false) {
        if( $this->is_readonly || $this->is_static )
            return null;
        if($from_get) {
            $f=@$_GET[$prefix.$this->name."_f"];
            $t=@$_GET[$prefix.$this->name."_t"];
        }else{
            $f=@$_POST[$prefix.$this->name."_f"];
            $t=@$_POST[$prefix.$this->name."_t"];
        }
        $value = array($f,$t);
        $this->_normalize_dates($value);
        return $value;
    }
    function    _normalize_dates(&$value) {
        if(!is_array($value)) $value=array(null,null);
        $value[0] = ($value[0]?QFormsWidget_Date::x_qDate( $value[0], $this->result_format):null);
        $value[1] = ($value[1]?QFormsWidget_Date::x_qDate( $value[1], $this->result_format):null);
    }
}

/**
* Widget QFormsWidget_LookupLinkedList
**/
class   QFormsWidget_LookupLinkedList extends QFormsWidget {
    function    QFormsWidget_LookupLinkedList($params) {
        $this->QFormsWidget($params);
        $this->size 		= (isset($params['size'])?$params['size']:5);
        $this->values_orig	= @$params['values_orig'];
        $this->orig_expr	= @$params['orig_expr'];
        $this->values_dest	= @$params['values_dest'];
        $this->dest_expr	= @$params['dest_expr'];
        $this->caption_orig	= @$params['caption_orig'];
        $this->caption_dest	= @$params['caption_dest'];

        $_html = <<<___EOT___
	<div style=" float: left;">
	<input type="hidden" name="@fieldname@" value="@value@" />
	@lookup@
	<select name="@fieldname@_orig">
	@origin@</select>
	</div>
	<div style=" float: left; padding: 3px;">
	<input type="button" value="&nbsp;<&nbsp;" onClick="xforms_lookuplinkedbox(this.form,'@fieldname@','rmv')" class="xfABMWidgetButton" /><br/>
	<input type="button" value="&nbsp;>&nbsp;" onclick="xforms_lookuplinkedbox(this.form,'@fieldname@','add')" class="xfABMWidgetButton">
	</div>
	<div style=" float: none;">
	<select name="@fieldname@_dest" size="@size@" multiple="1">@destination@</select>
	</div>
___EOT___;
		$this->_html = isset($params['_html']) ? $params['_html'] : $_html;
    }
    function    Init() {
        parent::Init();
        if($this->orig_expr)
            $this->values_orig = eval($this->orig_expr);
        if($this->orig_expr)
            $this->values_dest = eval($this->dest_expr);
		@asort($this->values_orig);
    }
    function    htmlControl($prefix) {
		$orig='';
		for(reset($this->values_orig) ; list($k,$v) = each($this->values_orig) ; )
			$orig .= sprintf("<option value=\"%s\">%s</option>\n", htmlspecialchars($k), htmlspecialchars($v) );
		$dest='';
		$a = array();
		for(reset($this->values_dest) ; list($k,$v) = each($this->values_dest) ; ) {
			$dest .= sprintf("<option value=\"%s\">%s</option>\n", htmlspecialchars($k), htmlspecialchars($v) );
			$a[] = $k;
		}
        $lookup = sprintf('<input type="text" size="8" onkeyup="xflookuplinkedbox_item(this,this.form.%s,event)"><br />',
            $this->name.'_orig',$this->name.'_orig',
            ($this->css_class?" class=\"$this->css_class\"":"")
            .($this->size?" size=\"$this->size\" size=\"$this->size\"":"")
            );
		$this->value = implode(';',$a);
		$html = QForms::x_replacer($this->_html, '@@', array(
			'lookup'		=> $lookup,
			'fieldname'		=> $this->name,
			'origin'		=> $orig,
			'destination'	=> $dest,
			'size'			=> $this->size,
			'caption'		=> $this->caption,
			'caption_orig'	=> $this->caption_orig,
			'caption_dest'	=> $this->caption_dest,
			'description'	=> $this->description,
			'value'			=> $this->value
			));
        return array($this->css_class, $html);
    }
    function    htmlStatic($prefix) {
		$html = ($this->value?htmlspecialchars(QForms::_cutstring($this->static_size, @$this->values[$this->value])):'&nbsp;');
        return array($this->css_class, $html);
    }

    function    htmlExtras() {
$jsc = <<<___EOT___
<script>
function xflookuplinkedbox_bs(sel,val,b,e) {
	if(b>e) return -1;
	var m = Math.round((b+e)/2);
	var t = sel.options[m].text.toLowerCase().substr(0,val.length);
	if(val < t)			return xflookuplinkedbox_bs(sel,val,b,m-1)
	else if(val > t)	return xflookuplinkedbox_bs(sel,val,m+1,e)
	else				return m
}
function xflookuplinkedbox_item(text,sel,e) {
	if(sel.xfl_searching) return;
	sel.xfl_searching = true
	var pos = 0;
	var v = text.value.toLowerCase()
	if(v) {
		if((pos=xflookuplinkedbox_bs(sel,v,0,sel.options.length-1)) >-1)
			sel.selectedIndex=pos;
		else
			text.value = v.substr(0,text.value.length-1)
	}
	sel.xfl_searching = false
}
function xforms_lookuplinkedbox_cloner( o ) {
	return new Option( o.text, o.value );
}
function	xforms_lookuplinkedbox(form, field, action) {
	var orig = form.elements[field+'_orig'];
	var dest = form.elements[field+'_dest'];
	if(action=='up') {
		if(dest.selectedIndex>0) {
			var i = dest.selectedIndex
			var t = xforms_lookuplinkedbox_cloner( dest.options[ i-1 ] )
			dest.options[ i-1 ] = xforms_lookuplinkedbox_cloner(dest.options[ i ])
			dest.options[ i ] = t
			dest.selectedIndex = i-1
		}
	}else if(action=='dn') {
		if(dest.selectedIndex>-1 && dest.selectedIndex < dest.options.length-1) {
			var i = dest.selectedIndex
			var t = qform_lookuplinkxforms_lookuplinkedbox_cloneredbox_cloner( dest.options[ i+1 ] )
			dest.options[ i+1 ] = xforms_lookuplinkedbox_cloner(dest.options[ i ])
			dest.options[ i ] = t
			dest.selectedIndex = i+1
		}
	}else if(action=='add') {
		if(orig.selectedIndex>-1) {
			insertOk = true;
			for( i=0 ; i<dest.options.length ; i++){
				if( dest.options[i].value == orig.options[orig.selectedIndex].value )
					insertOk = false;
			}
			if( insertOk ){
				var d = orig.selectedIndex;
				dest.options[ dest.options.length ] = xforms_lookuplinkedbox_cloner( orig.options[ orig.selectedIndex ] )
			}
		}
	}else if(action=='rmv') {
		if(dest.selectedIndex>-1) {
            var d = dest.selectedIndex;
			// orig.options[ orig.options.length ] = xforms_lookuplinkedbox_cloner( dest.options[ dest.selectedIndex ] )
			dest.options[ dest.selectedIndex ] = null
            dest.selectedIndex = d;
		}
	}else if(action=='addall') {
		for(var i=0 ; i < orig.options.length ; i++ )
			dest.options[dest.options.length] = cloner(orig.options[i])
		for(var i=orig.options.length-1 ; i>-1 ; i-- ) orig.options[i] = null
	}else if(action=='rmvall') {
		for(var i=0 ; i < dest.options.length ; i++ )
			orig.options[orig.options.length] = cloner(dest.options[i])
		for(var i=dest.options.length-1 ; i>-1 ; i-- ) dest.options[i] = null
	}

	var r = ''
	for(var i=0 ; i < dest.options.length ; i++ )
		r += dest.options[i].value + ';'
	form.elements[field].value = r
}
</script>
___EOT___;
		$pieces = parent::htmlExtras();
        $pieces['jscode_LookupLinkedList'] = &$jsc;
        return $pieces;
    }
}


/**
* Widget Select
**/
class   QFormsWidget_RadioButtons extends QFormsWidget {
    function    QFormsWidget_RadioButtons($params) {
        $this->QFormsWidget($params);
        $this->size 		= (isset($params['size'])?$params['size']:0);
        $this->values  		= @$params['values'];
        $this->values_expr  = @$params['values_expr'];
        $this->onclick      = @$params['onclick'];
        $this->blank_text   = @$params['blank_text'];
        $this->js_getValue  = "jscode_RadioButtons(@abm_prefix@Form.elements['@abm_prefix@$this->name'])";
        $this->horizontal   = @$params['horizontal'];
    }
    function    Init() {
        parent::Init();
        if($this->values_expr) {
            $this->values = eval($this->values_expr);
        }
        QForms::ERROR_ON( !is_array($this->values), QForms::trans("Error. RadioButtons Widget requires values or values_expr") );
        if( $this->blank_text ) {
            $this->values = array(""=>$this->blank_text) + $this->values;
        }
    }
    function    htmlControl($prefix) {
        $html='';
        foreach($this->values as $k=>$v)  {
            $k=strval($k);
            $this->value=strval($this->value);
            if($this->value==='') $this->value=null;

            $selected = (($k===$this->value)?' checked="checked"':'');

        	$onclick = ($this->onclick? " onclick=\"$this->onclick\"":'');
        	$disabled = ($this->disabled?" disabled=\"disabled\"":"");
            $html .= '<input type="radio" name="'.($prefix.$this->name).'" value="'.htmlspecialchars($k).'"'.$selected.$onclick.$disabled.' />'.htmlspecialchars($v) . ($this->horizontal?"&nbsp;\n":"<br />\n");
        }
        return array($this->css_class, $html);
    }
    function    htmlStatic($prefix) {

		$html = (isset($this->value)?htmlspecialchars( @$this->values[$this->value] ):'&nbsp;');
        return array($this->css_class, $html);
    }
    function    htmlExtras() {
$jsc = <<<___EOT___
<script>
function	jscode_RadioButtons(elt) {
	var csel='';
	for(var i=0 ; i<elt.length ; i++) {
		if(elt[i].checked) {
			csel=elt[i].value;
		}
	}
	return csel;
}
</script>
___EOT___;
		$pieces = parent::htmlExtras();
        $pieces['jscode_RadioButtons'] = &$jsc;
        return $pieces;
    }
}

/**
* Widget File upload.
**/
class   QFormsWidget_FileUpload extends QFormsWidget {
    function    QFormsWidget_FileUpload($params) {
        $this->QFormsWidget($params);
        $this->dest_path        = (isset($params['dest_path'])?$params['dest_path']:'/tmp/');
        $this->accept           = @$params['accept'];
        $this->size             = @$params['size'];
        $this->can_delete       = @$params['can_delete'];
        $this->onclick          = @$params['onclick'];
        $this->is_required      = @$params['is_required'];
        $this->overwrite        = true; //@$params['overwrite'];
        $this->save_with_full_path = !empty($params['save_with_full_path']) ? $params['save_with_full_path'] : false ;
    }
    function    htmlControl($prefix) {
        $name=$prefix.$this->name;
        $static = $this->htmlStatic($prefix);
		$html = sprintf('<input type="file" name="%s" size="%s" %s />%s',
			$name, ($this->size?$this->size:''),
			($this->accept? "accept=\"$this->accept\"":'').($this->disabled?" disabled=\"disabled\"":"").($this->onclick?" onclick=\"$this->onclick\"":""),
			(($this->can_delete&&$this->value)?("<br /><input type=\"checkbox\" name=\"{$name}_rmv_prevfile\" value=\"1\" checked=\"\" />".$static[0]):'')
			);
        return array($this->css_class, $html);
    }
    function    htmlStatic($prefix) {
		if($this->value)
			$html = sprintf('Archivo(%s)',$this->value);
    	else
    		$html = "Archivo()";
    	return array($this->css_class, $html);
    }
    function    loadGPC($prefix, $from_get=false) {
        if( $this->is_readonly || $this->is_static )
            return null;

        $src_filename=@$_FILES[$prefix.$this->name]['tmp_name'];
		if( !empty($src_filename) ) {
			$dest_filename = $this->dest_path . basename($_FILES[$prefix.$this->name]['name']);
			if( move_uploaded_file($src_filename, $dest_filename) ) {
				chmod($dest_filename, 0777);
				if( $this->save_with_full_path )
					return $dest_filename;
				else
					return str_replace($this->dest_path, '', $dest_filename);
			}
		}elseif( @$_POST[$prefix.$this->name."_rmv_prevfile"] ) {
			// Si no está seteado.
            return '';
		}
		return null;
    }
}

/**
* Widget Image upload.
**/
class   QFormsWidget_ImageUpload extends QFormsWidget_FileUpload {
    function    QFormsWidget_ImageUpload($params) {
        $this->QFormsWidget_FileUpload($params);
        $this->accept           = (isset($params['accept'])?$params['accept']:'image/*');
        $this->show_preview     = @$params['show_preview'];
        $this->show_image       = @$params['show_image'];
        $this->imgUrl			= @$params['imgUrl'];
        $this->max_width		= !empty($params['max_width']) && intval($params['max_width'])>0 ? intval($params['max_width']) : 100;
        $this->is_required      = false;
    }
    function    htmlControl($prefix) {
        $name=$prefix.$this->name;
        $static = $this->htmlStatic($prefix);
		$html = $static[1].sprintf('%s<input type="file" name="%s" size="%s" %s onchange="document.getElementById(\''.$name.'Prev\').src=this.value" />',
			(($this->can_delete&&$this->value)?("<input type=\"checkbox\" name=\"{$name}_rmv_prevfile\" value=\"1\" />".QForms::trans('check to delete')):''),
			$name, ($this->size?$this->size:''),
			($this->accept? "accept=\"$this->accept\"":'').($this->disabled?" disabled=\"disabled\"":"").($this->onclick?" onclick=\"$this->onclick\"":"") );
        #if( $this->show_image && $this->value )
        #	$html .= '<br/><img src="'.$this->imgUrl.$this->value.'" id="'.$name.'Prev" name="'.$name.'Prev" />';
        return array($this->css_class, $html);
    }
    function    htmlStatic($prefix) {
		if($this->value) {
			if( $this->show_image )
				$html = '<img src="'.$this->imgUrl.$this->value.'" width="'.$this->max_width.'" />';
			else
				$html = sprintf('Archivo(%s)',$this->value);
		} else
    		$html = "Archivo()";
    	return array($this->css_class, $html);
    }
}

/**
* Widget Image upload/manager... IMPORTANTE: no se puede usar en el mismo abm junto con FileManager
**/
class   QFormsWidget_ImageManager extends QFormsWidget {
    function    QFormsWidget_ImageManager($params) {
        $this->QFormsWidget($params);
        $this->size             = @$params['size'];
        $this->onclick          = @$params['onclick'];
        $this->editor_url	      = ((@$params['editor_url'])?$params['editor_url']:QFORMS_WIDGETS_IMAGEMGRURL);
        $this->editor_images_url  = ((@$params['editor_images_url'])?$params['editor_images_url']:QFORMS_WIDGETS_HTMLIMGURL);
        $this->editor_images_path = ((@$params['editor_images_path'])?$params['editor_images_path']:QFORMS_WIDGETS_HTMLIMGPATH);
    }
    function    htmlControl($prefix) {
        $name=$prefix.$this->name;

        // Guardo estos parámetros para que estï¿½n disponibles para el editor y el plugin de imï¿½genes.
        if(empty($_SESSION['QFormsWidget_ImageManager_cfg'])) $_SESSION['QFormsWidget_ImageManager_cfg']=array();
        $_SESSION['QFormsWidget_ImageManager_cfg'][$name]=array(
            dirname($this->editor_url).'/',
            $this->editor_images_url,
            $this->editor_images_path,true, QForms :: trans('Insert Image') );

		$html = sprintf('<input type="hidden" id="%s" name="%s" size="%s" %s value="%s" />',
			$name, $name, ($this->size?$this->size:''),
			($this->disabled?" disabled=\"disabled\"":"").($this->onclick?" onclick=\"$this->onclick\"":"").
			($this->onchange?" onchange=\"$this->onchange\"":""),
            $this->value
			);
        $img = ($this->value?$this->editor_images_url.$this->value:'about:blank');
		//value="'.qforms :: trans('Admin Images').'"
        $html .= '
<br/><img width="150" src="'.$img.'" id="'.$name.'Prev" name="'.$name.'Prev" />
<input type="button" class="add_it" onclick="window.open(\''.$this->editor_url.'manager.php?F='.$name.'\',\'\',\'titlebar=yes,status=yes,scrollbars=yes\')" />
<script type="text/javascript">jscode_ImageFileManagerSel_data[\''.$name.'\']=[/^'.preg_quote($this->editor_images_url,'/').'/,\''.$this->editor_images_url.'\',\''.addslashes($this->onchange).'\']; </script>
';
        return array($this->css_class, $html);
    }
    function    htmlStatic($prefix) {
		if($this->value) {
			if( !empty($this->editor_images_url) )
				$html = '<br/><img src="'.$this->editor_images_url.$this->value.'" width="100" />';
			else
				$html = sprintf('Archivo(%s)',$this->value);
		} else
    		$html = "Archivo()";
    	return array($this->css_class, $html);
    }
    function    htmlExtras() {
$jsc = <<<___EOT___
<script>
var jscode_ImageFileManagerSel_data={};
function	jscode_ImageFileManagerSel(fname,value) {
    var field=null;
    if(value && (field=document.getElementById(fname))) {
        if( !jscode_ImageFileManagerSel_data[fname] ) { alert('ERROR'); return; }
        var re=jscode_ImageFileManagerSel_data[fname];
        field.value=value.replace(re[0],'');
        var prev=value;
        if(field=document.getElementById(fname+'Prev')) field.src=prev;
        if(re[2]) eval(re[2]);
    }
}
</script>
___EOT___;
		$pieces = parent::htmlExtras();
        $pieces['jscode_ImageFileManagerSel'] = &$jsc;
        return $pieces;
    }
}

/**
* Widget File upload/manager... IMPORTANTE: no se puede usar en el mismo abm junto con ImageManager
**/
class   QFormsWidget_FileManager extends QFormsWidget {
    function    QFormsWidget_FileManager($params) {
        $this->QFormsWidget($params);
        $this->size             = @$params['size'];
        $this->onclick          = @$params['onclick'];
        $this->editor_url	      = ((@$params['editor_url'])?$params['editor_url']:QFORMS_WIDGETS_IMAGEMGRURL);
        $this->editor_images_url  = ((@$params['editor_images_url'])?$params['editor_images_url']:QFORMS_WIDGETS_HTMLIMGURL);
        $this->editor_images_path = ((@$params['editor_images_path'])?$params['editor_images_path']:QFORMS_WIDGETS_HTMLIMGPATH);
    }
    function    htmlControl($prefix) {
        $name=$prefix.$this->name;

        // Guardo estos parï¿½metros para que estï¿½n disponibles para el editor y el plugin de imï¿½genes.
        if(empty($_SESSION['QFormsWidget_ImageManager_cfg'])) $_SESSION['QFormsWidget_ImageManager_cfg']=array();
        $_SESSION['QFormsWidget_ImageManager_cfg'][$name]=array(
            dirname($this->editor_url).'/',
            $this->editor_images_url,
            $this->editor_images_path, false ,'Subir/Seleccionar archivos');

		$html = sprintf('<input type="text" id="%s" name="%s" size="%s" %s value="%s" />',
			$name, $name, ($this->size?$this->size:''),
			($this->disabled?" disabled=\"disabled\"":"").($this->onclick?" onclick=\"$this->onclick\"":"").
			($this->onchange?" onchange=\"$this->onchange\"":""),
			$this->value
			);
        $html .= '
<input type="button" value="..." onclick="window.open(\''.$this->editor_url.'manager.php?F='.$name.'~'.session_name().'~'.session_id().'\',\'\',\'titlebar=yes,status=yes,scrollbars=yes\')" />
<script type="text/javascript">jscode_ImageFileManagerSel_data[\''.$name.'\']=[/^'.preg_quote($this->editor_images_url,'/').'/,\''.$this->editor_images_url.'\',\''.addslashes($this->onchange).'\']; </script>
';

        return array($this->css_class, $html);
    }
    function    htmlStatic($prefix) {
		if($this->value)
			return sprintf('Archivo(%s)',$this->value);
    	else
    		return "Archivo()";
    }
    function    htmlExtras() {
$jsc = <<<___EOT___
<script>
var jscode_ImageFileManagerSel_data={};
function	jscode_ImageFileManagerSel(fname,value) {
    var field=null;
    if(value && (field=document.getElementById(fname))) {
        if( !jscode_ImageFileManagerSel_data[fname] ) { alert('ERROR'); return; }
        var re=jscode_ImageFileManagerSel_data[fname];
        field.value=value.replace(re[0],'');
        var prev=value;
        if(field=document.getElementById(fname+'Prev')) field.src=prev;
        if(re[2]) eval(re[2]);
    }
}
</script>
___EOT___;
		$pieces = parent::htmlExtras();
        $pieces['jscode_ImageFileManagerSel'] = &$jsc;
        return $pieces;
    }
}


/**
* Widget Button, behaviour ... half a CheckBox
**/
class   QFormsWidget_ButtonAsCheckBox extends QFormsWidget_CheckBox {
    function    QFormsWidget_ButtonAsCheckBox($params) {
        $this->QFormsWidget_CheckBox($params);
        $this->type				= 'ButtonAsCheckBox';
        $this->checked_value    = (isset($params['checked_value'])?$params['checked_value']: 1 );
        //$this->unchecked_value  = (isset($params['unchecked_value'])?$params['unchecked_value']: 0 );
        $this->text         	= @$params['text'];
        $this->onclick          = @$params['onclick'];
        $this->is_required      = false;
    }
    function    htmlControl($prefix) {
        //$checked = (!strcmp($this->value,$this->checked_value)?' checked=""':'');
        $v = htmlspecialchars($this->checked_value);
        //$u = htmlspecialchars($this->unchecked_value);

		$html = sprintf('<input type="button" name="%sHandler" value="%s" onclick="this.form.%s.value=%s;%s"%s /><input type="hidden" name="%s" value="" />',
            $prefix.$this->name, $this->text, $prefix.$this->name, $v, $this->onclick,
            ($this->css_class?" class=\"$this->css_class\"":"").($this->disabled?" disabled=\"disabled\"":""),
            $prefix.$this->name
            );
        return array($this->css_class, $html);
    }
}
/**
* Widget Button, behaviour ... half a CheckBox
**/
class   QFormsWidget_CustomHTMLEditor extends QFormsWidget_HTMLEditor {
    function    QFormsWidget_CustomHTMLEditor($params) {
        if(!isset($params['editor_url']))
            $params['editor_url']   = QFORMS_URI.'tinymce/xforms_editor.php';
        if(!isset($params['editor_inline']))
            $params['editor_inline']   = QFORMS_PATH.'tinymce/xforms_editor.inline.php';
        if(!isset($params['rows']))
            $params['rows']   = 8;
        $this->QFormsWidget_HTMLEditor($params);
    }
}


/**
*   same than FileUpload but doesn't proccess the files
**/

class  QFormsWidget_fileUploadInInsert extends QFormsWidget_FileUpload{
	function    loadGPC($prefix, $from_get=false){
		return false;
	}
}


/**
 *	QFormsWidget_DataList
 *	widget para tener una lista de textos variados
 */
class   QFormsWidget_DataList extends QFormsWidget {

	function QFormsWidget_DataList ( $params ) {
		$this->QFormsWidget( $params );
		$this->separator = !empty($params['separator']) ? $params['separator'] : '|';
		$this->jsRegExp = !empty($params['jsRegExp']) ? $params['jsRegExp'] : '';
	}

	function htmlControl($prefix='') {
		#DEBUG 		$this->value = array(1=>'hola',2=>'chau',3=>'wow!');
		if( !is_array($this->value) )
			$this->value=explode($this->separator,$this->value);

		# var_dump($this->value);
		$name = $prefix.$this->name;
		$s = sprintf('<input type="hidden" name="%1$s" id="%1$s" value="%2$s"/>' .
					 '<input type="hidden" name="%1$s_jsRegExp" id="%1$s_jsRegExp" value="%3$s"/>' .
					 '<ul id="ul_%1$s">',
			$name,implode($this->separator,$this->value), $this->jsRegExp );
		foreach($this->value as $k=>$v){
			if( !empty($v) )
				$s .= sprintf('<li>%s<input onClick="qfw_datalist_borrar_url(event)" type="button" title="Borrar" class="delete" value="" ></li>', $v);
		}
		$h = '';
		$s .= '</ul>';
		$s .= sprintf('<div class="new_data_item"><input name="%1$s_newitem" id="%1$s_newitem" type="text" class="textfield" />
							<input onClick="qfw_datalist_add_url(\'%1$s\');" type="button" title="%2$s" class="add_it" value="" />
						</div>', $name, QForms :: trans('Agregar este enlace!'));
		$s .= '
<script type="text/javascript">
window.DataList.'.$name.'={separator: \''.$this->separator.'\'};
</script>';
		return array( $this->css_class,$s);
	}

	function	htmlStatic($prefix='') {
		if( !is_array($this->value) )
			$this->value=explode($this->separator,$this->value);

		$html = '<ul>';
		foreach($this->value as $k=>$v){
			if( !empty($v) )
				$html .= sprintf('<li>%s</li>', $v);
		}
		$html .= '</ul>';
		return array( $this->css_class,$html);
	}

	function    htmlExtras() {
$jsc = <<<___EOT___
<script type="text/javascript">
window.DataList = {};
function qfw_datalist_add_url(name){
	extraURL = \$(name+'_newitem');
	jsRegExp =\$F(name+'_jsRegExp');
	r = new RegExp(jsRegExp,'i');
	if (extraURL.value == '' && (jsRegExp=='' || extraURL.match(r)) ){
		alert('Error!');
		return false;
	}
	ul  = \$('ul_'+name);
	var a=document.createElement('input');
	a.setAttribute('type','button');
	a.className = 'delete';
	// esto para que agregue el evento en cualquier browser o IE
	if (a.addEventListener){
	  	a.addEventListener('click', qfw_datalist_borrar_url, false);
	} else if (a.attachEvent){
	  	a.attachEvent('onclick', qfw_datalist_borrar_url );
	}
	li = document.createElement("li");
	li.innerHTML = extraURL.value;
	li.appendChild(a);
	ul.appendChild(li);
	h = \$(name);
	h.value += window.DataList[name].separator+extraURL.value;

	extraURL.value='';
	extraURL.focus();
}

function qfw_datalist_borrar_url(w){
	name = w.target.parentNode.parentNode.id.replace(/^ul_/,'');
	var ulList = document.getElementById('ul_'+name);
	if (ulList && w && w.target) {
		ulList.removeChild(w.target.parentNode);
	}
	else
		if (ulList && w && w.srcElement) {
			ulList.removeChild(w.srcElement.parentNode);
		}
	h = \$(name);
	h.value = '';
	children = ulList.getElementsByTagName('li');
	for (var ii = 0; ii < children.length; ii++) {
		h.value += children[ii].innerHTML.replace(/\<.*\>/i, '')+'|';
	}
}

</script>
___EOT___;
		$pieces = parent::htmlExtras();
        $pieces['jscode_DataList'] = &$jsc;
        return $pieces;
    }

}






/**
* Widget File Upload,
**/

class   QFormsWidget_FileUploadExt extends QFormsWidget {
    function    QFormsWidget_FileUploadExt($params) {
        $this->QFormsWidget($params);
        $this->dest_path        = (isset($params['dest_path'])?$params['dest_path']:'/tmp/');
        $this->base_url         = @$params['base_url'];
        $this->accept           = @$params['accept'];
        $this->valid_mimes 		= @$params['valid_mimes'];
        $this->resizeImageTo	= @$params['resizeImageTo']; // datos de ancho y alto en un array, para hacer resize de la imagen
        $this->maxsize			= @$params['maxsize'];
        $this->show_preview		= !empty($params['show_preview']) ? $params['show_preview'] : false;
        $this->can_delete       = !empty($params['can_delete']) ? $params['can_delete'] : true;
        $this->is_required      = @$params['is_required'];
        $this->new_name			= @$params['new_name'];
        $this->maxWidth			= !empty($params['maxWidth']) && intval($params['maxWidth'])>0 ? intval($params['maxWidth']) : 100;
        $this->maxHeight		= !empty($params['maxHeight']) && intval($params['maxHeight'])>0 ? intval($params['maxWidth']) : 100;
        $this->overwrite        = true; //@$params['overwrite'];
        $this->save_with_full_path = !empty($params['save_with_full_path']) ? $params['save_with_full_path'] : false ;

    }
    function    htmlControl($prefix) {
        $name=$prefix.$this->name;
        $static = $this->htmlStatic($prefix);
		$deleteControl = $this->can_delete && !empty($this->value)
			? '<input onclick="FileUploadExt_delete(this,\''.$name.'\')" value="" type="button" class="delete" title="'.QForms::trans('Delete').'" /><input type="hidden" name="'.$name.'_rmv_prevfile" id="'.$name.'_rmv_prevfile" value="" />' : '';
		$html = '';
		if( !empty($this->value) )
			$html .= '<div class="current_file">'.$static[1].$deleteControl.'<div class="clear"></div></div>';
		$html .= sprintf('<div class="new_file" id="%1$s_new_file" %4$s >%3$s<input type="file" name="%1$s" id="%1$s" %2$s class="file" /></div>',
				$name,
				!empty($this->accept) ? "accept=\"$this->accept\" " : '',
				!empty($this->maxsize) ? '<input type="hidden" name="MAX_FILE_SIZE" value="'.$this->maxsize.'" />' : '',
				$this->value!='' ? ' style="display:none;"' : ''
				);
		/*$html .= !empty($this->description)
			? '<div class="hint">'.$this->description.'</div>' : '';*/

        return array($this->css_class, $html);
    }
    function    htmlStatic($prefix) {
		/*$preview = sprintf('<a href="$"><img src="style/img/layout/blank.gif" width="100" height="80" align="absbottom" /></a>',
			$file_url,
			$width
			);*/
		if( !empty($this->value) && file_exists($this->dest_path.$this->value) ) {
			$preview = $this->value;
			if( $this->show_preview && in_array(strtolower(substr($this->value,-3)), array('jpg','gif','png','bmp')) || strtolower(substr($this->value,-4))=='jpeg' ) {
				$info = getimagesize($this->dest_path.$this->value);
				$ratioX = $this->maxWidth/$info[0];
				$ratioY = $this->maxHeight/$info[1];
				if( $ratioX>=1 && $ratioY>=1 )
					$widthheight = $info[2];
				elseif( $ratioX>$ratioY )
					$widthheight = ' width="'.(ceil($info[0]*$ratioY)).'" height="'.(ceil($info[1]*$ratioY)).'"';
				else
					$widthheight = ' width="'.(ceil($info[0]*$ratioX)).'" height="'.(ceil($info[1]*$ratioX)).'"';
				$preview = sprintf('<img src="%1$s" %2$s align="absbottom" />',
					$this->base_url.$this->value ,
					$widthheight
					);
			}
			$html = sprintf('<a href="%1$s%2$s">%3$s</a>',$this->base_url,$this->value, $preview);
		} else
    		$html = "";
    	return array($this->css_class, $html);
    }
    function    loadGPC($prefix, $from_get=false) {
        if( $this->is_readonly || $this->is_static )
            return null;

        $src_filename=@$_FILES[$prefix.$this->name]['tmp_name'];
		if( !empty($src_filename) ) {
			if( !empty($this->maxsize) && $_FILES[$prefix.$this->name]['size']>$this->maxsize ) {
				return null;
			}
			/*
			 * para inverstigar despues !
			if( !empty($this->accept) ) {
				$accept = preg_split('/[,\s*]/',$this->accept);
				$finfo = finfo_open(FILEINFO_MIME);
				$mime = finfo_file($finfo ,$src_filename);
				if( !in_array($mime, $accept) )
					die('incorrect mime type '.$mime); // return null;
			} */

			#die($_FILES[$prefix.$this->name]['type']);
			if( !empty($this->valid_mimes) ) {
				$this->valid_mimes = preg_split('/[,;]\s*/', $this->valid_mimes);
				#var_dump($this->valid_mimes, $_FILES[$prefix.$this->name]['type'], !in_array($_FILES[$prefix.$this->name]['type'], $this->valid_mimes)); die();
				if( !in_array($_FILES[$prefix.$this->name]['type'], $this->valid_mimes) ){
					$this->abm->abm_errors[] = QForms::trans('Ivalid mime type %s',$_FILES[$prefix.$this->name]['type']);
					return false;
				}

			}
			$newname = !empty($this->new_name) ? $this->new_name : basename($_FILES[$prefix.$this->name]['name']);
			$dest_filename = $this->dest_path . $newname;
			if( move_uploaded_file($src_filename, $dest_filename) ) {
				chmod($dest_filename, 0777);
				if( $this->resizeImageTo )
					$this->imageResize($dest_filename, $_FILES[$prefix.$this->name]['type'], $this->resizeImageTo);
				if( $this->save_with_full_path )
					return $dest_filename;
				else
					return str_replace($this->dest_path, '', $dest_filename);
			}
		}elseif( !empty($_POST[$prefix.$this->name."_rmv_prevfile"]) ) {
			// Si no está seteado.
            return '';
		}
		return null;
    }

	function	imageResize($img, $mime, $newsize) {
		if( !function_exists('imagecreatefromjpeg') )
			return '';
		if( file_exists($img)) {
			$size = getimagesize($img);

			//calculate ratio
			if( $size[0] > $newsize[0] & $size[1] <= $newsize[1]){
				$ratio = $newsize[0] / $size[0];
			}
			elseif( $size[1] > $newsize[1] & $size[0] <= $newsize[0] ){
				$ratio = $newsize[1] / $size[1];
			}
			elseif( $size[0] > $newsize[0] & $size[1] > $newsize[1] ){
				$ratio1 = $newsize[0] / $size[0];
				$ratio2 = $newsize[1] / $size[1];
				$ratio = ($ratio1 > $ratio2)? $ratio1:$ratio2;
			}
			else{
				$ratio = 1;
			}

			$new_width = ceil($size[0] * $ratio);
			$new_height = ceil($size[1] * $ratio);

		}

		switch( $mime ) {
			case 'image/jpeg':
				$image = @ imagecreatefromjpeg($img);
				break;
			case 'image/gif':
				$image = @ imagecreatefromgif($img);
				break;
			case 'image/png':
				$image = @ imagecreatefrompng($img);
				break;
		}

		if ($image){
			// Resample
			$image_p = imagecreatetruecolor($new_width, $new_height);
			imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $size[0], $size[1]);
			switch( $mime ) {
				default:
				case 'image/jpeg':
					imagejpeg($image_p, $img, 85);
					break;
				case 'image/gif':
					$nombre_archivo = $this->folderimages_cachefolder.$img;
					imagegif($image_p, $img);
					break;
			}
		}
	}

	function    htmlExtras() {
$jsc = <<<___EOT___
<script type="text/javascript">
function FileUploadExt_delete(b, name) {
	var h =$(name+'_rmv_prevfile');
	h.value=1;
	b.parentNode.style.display = 'none';
	document.getElementById(name+'_new_file').style.display='block';
}
</script>
___EOT___;
		$pieces = parent::htmlExtras();
        $pieces['jscode_FileUploadExt'] = &$jsc;
        return $pieces;
    }


}






/**
* Coordenadas Google Map Widget
**/
class   QFormsWidget_gmap extends QFormsWidget {
    function    QFormsWidget_gmap($params) {
        $this->QFormsWidget($params);
        $this->text          = @$params['text'];
        $this->latwidget     = @$params['latwidget'];
        $this->longwidget    = @$params['longwidget'];
        $this->addresswidget = @$params['addresswidget'];
        $this->key           = @$params['key'];
        $this->prefix           = @$params['prefix'];
    }

    function    htmlControl($prefix) {

    	// $html .= "<input alt=\"#TB_inline?height=400&width=563&inlineId=searchmap\" title=\"add a caption to title attribute / or leave blank\" class=\"thickbox\" type=\"button\" value=\"Search\" />";
    	 $html .= "<div id=\"searchmap\" style=\"width: 550px; height: 370px;\">";
    	 $html .= "<div id=\"map_canvas\" style=\"width: 400x; height: 280px; \"></div>";
    	 $html .= "<table><tr><th>Lat.</th><td>";
    	 $html .= $this->htmlInput($prefix,$prefix.$this->latwidget.'_gmap');
         $html .= "</td></tr><tr><th>Long.</th><td>";
    	 $html .= $this->htmlInput($prefix,$prefix.$this->longwidget.'_gmap');
         $html .= sprintf('<input type="%s" name="%s" value="%s" onclick="showLatLong()" title="%s" />',
					        ($this->do_submit?'submit':'button'),
					        $prefix.$this->name.'_latlong',
					         htmlspecialchars($this->text),
					        htmlspecialchars($this->description)
					     );
		 $html .= "</td></tr><tr><th>Address</th><td>";
    	 $html .= $this->htmlInput($prefix,$prefix.$this->addresswidget.'_gmap');
         $html .= sprintf('<input type="%s" name="%s" value="%s" onclick="showAddress(document.getElementById(\''.$prefix.$this->addresswidget.'_gmap\').value)" title="%s" />',
					        ($this->do_submit?'submit':'button'),
					        $prefix.$this->name.'_address',
					         htmlspecialchars($this->text),
					        htmlspecialchars($this->description)
					     );
	     $html .= "</td></tr>";
		 $html .= "</table>";
		 $html .= "</div>";
         return array(null, $html);
    }

    function    htmlInput($prefix,$name) {
		$html = sprintf('%s<input type="text" id="%s" name="%s" value="%s"%s />%s',
            $this->text_before_widget,
            $name, $name, htmlspecialchars($this->value),
            ($this->css_class?" class=\"$this->css_class\"":"")
            .$this->extra_html
            .($this->size?" size=\"$this->size\"":"")
            .($this->onchange?" onchange=\"$this->onchange\"":"")
            .($this->maxlength?" maxlength=\"$this->maxlength\"":"")
            .($this->disabled?" disabled=\"disabled\"":""),
            $this->text_after_widget
            );
        return $html;
    }

    function    htmlStatic($prefix) {
		return array(null, 'Gmap Location');
    }

	function    htmlExtras() {

$jsc = " <script type=\"text/javascript\" src=\"http://www.google.com/jsapi?key=".$this->key."\"></script>";
$jsc .= "<script type=\"text/javascript\">";
$jsc .= "var latitud = \"".$this->prefix.$this->latwidget."\";";
$jsc .= "var longitud = \"".$this->prefix.$this->longwidget."\";";
$jsc .= "var direccion = \"".$this->prefix.$this->addresswidget."\";";
$jsc .= "var blatitud = \"".$this->prefix.$this->latwidget.'_gmap'."\";";
$jsc .= "var blongitud = \"".$this->prefix.$this->longwidget.'_gmap'."\";";
$jsc .= "var bdireccion = \"".$this->prefix.$this->addresswidget.'_gmap'."\";";
$jsc .= <<<___EOT___

	var map = null;
	var geocoder = null;
	var marker = null;
	var ctrlBLatitud = null;
	var ctrlBLongitud = null;
	var ctrlBDireccion = null;
	var ctrlLatitud = null;
	var ctrlLongitud = null;
	var ctrlDireccion = null;

  google.load("maps", "2",{"other_params":"sensor=true"});

  function initialize() {

  	ctrlBLatitud = document.getElementById(blatitud) ;
  	ctrlBLongitud = document.getElementById(blongitud) ;
  	ctrlBDireccion = document.getElementById(bdireccion) ;
  	ctrlLatitud = document.getElementById(latitud) ;
  	ctrlLongitud = document.getElementById(longitud) ;
  	ctrlDireccion = document.getElementById(direccion) ;

    map = new google.maps.Map2(document.getElementById("map_canvas"));
    var center = new GLatLng(ctrlLatitud.value , ctrlLongitud.value );
    map.setCenter(center, 13);
    map.addControl(new GLargeMapControl());
    map.addControl(new GMapTypeControl());
    map.addControl(new GOverviewMapControl());


	map.setCenter(center, 13);

	// Create our "tiny" marker icon
	var blueIcon = new GIcon(G_DEFAULT_ICON);
	blueIcon.image = "http://www.google.com/intl/en_us/mapfiles/ms/micons/blue-dot.png";
	markerOptions = { icon:blueIcon , draggable: true };


	marker = new GMarker(center, markerOptions);
    ctrlBLatitud.value = marker.getLatLng().lat();
    ctrlBLongitud.value = marker.getLatLng().lng();
    ctrlBDireccion.value = ctrlDireccion.value;

    initializeEvents();

	map.addOverlay(marker);

    geocoder = new GClientGeocoder();
  }
  google.setOnLoadCallback(initialize);


	function initializeEvents(){
		GEvent.addListener(marker, "drag", function() {
	              ctrlBLatitud.value = marker.getLatLng().lat();
	              ctrlBLongitud.value = marker.getLatLng().lng();
	              ctrlLatitud.value = marker.getLatLng().lat();
	              ctrlLongitud.value = marker.getLatLng().lng();
		  });

		ctrlBLatitud.onchange = function(){ctrlLatitud.value = ctrlBLatitud.value;}

		ctrlBLongitud.onchange = function() {ctrlLongitud.value = ctrlBLongitud.value;}

		ctrlBDireccion.onchange = function() {ctrlDireccion.value = ctrlBDireccion.value;}
	}

    function showAddress(address) {
      if (geocoder) {
        geocoder.getLatLng(
          address,
          function(point) {
            if (!point) {
              alert(address + " not found");
            } else {
              map.setCenter(point, 13);
              marker.setLatLng(point);
              marker.enableDragging();
              ctrlBLatitud.value = point.lat();
              ctrlBLongitud.value = point.lng();
              ctrlLatitud.value = point.lat();
              ctrlLongitud.value = point.lng();
            }
          }
        );
      }
    }


   function showLatLong(){
		var center = new GLatLng(ctrlLatitud.value , ctrlLongitud.value );
		map.setCenter(center);
		marker.setLatLng(center);
		marker.enableDragging();
   }
</script>

___EOT___;
		$pieces = parent::htmlExtras();
        $pieces['jscode_GmapSearch'] = &$jsc;
        return $pieces;
    }

}




?>
