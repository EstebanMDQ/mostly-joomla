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
var jsQuickForm = {
    init: function(formName,formValues) {
        var form=((typeof(formName)=='string')?document.getElementById(formName):formName);
        if(form) {
            var t=null;
            for(var i=0 ; i<form.elements.length ; i++ ) {
                if( form.elements[i].type=='select-one' && form.elements[i].getAttribute('setvalue') && form.elements[i].selectedIndex<=0) {
                    for(var j=0 ; j < form.elements[i].options.length ; j++ ) {
                        var v=form.elements[i].options[j].value?form.elements[i].options[j].value:form.elements[i].options[j].text;
                        if( v == form.elements[i].getAttribute('setvalue') ) {
                            form.elements[i].selectedIndex=j;
                            break;
                        }
                    }
                }
                if( formValues && (t=formValues[ form.elements[i].name ]) ) {
                    this.getSetValue(form.elements[i], t );
                }
            }
            if(form.elements[0] && form.elements[0].focus && form.elements[0].type!='hidden') {
                form.elements[0].focus();
            }
        }
    },
    submit: function(form, callback_fn, ignore_required, error_fn) {
        if(form) {
            var t=null, required_missing='';
            if(!ignore_required) {
                for(var i=0 ; i<form.elements.length ; i++ ) {
                    if( form.elements[i].name && form.elements[i].className.match(/required/) ) {
                        var t=this.getSetValue(form.elements[i]);
                        if(!form.elements[i].style.border_prev) form.elements[i].style.border_prev=form.elements[i].style.border;
                        if(t) {
                            form.elements[i].style.border='2px inset white';
                        }else{
                            form.elements[i].style.borderColor='red';
                            required_missing += form.elements[i].name;
                        }
                    }
                }
            }
            var errorString='';
            if(required_missing)    errorString = 'Los campos marcados en rojo requieren un valor.\n';
            if(callback_fn) errorString += callback_fn(form);
            if(errorString) {
                if(error_fn) { error_fn(errorString); } else{ alert(errorString); }
                return false;
            }
            return true;
        }
    },
    getSetValue: function(elt, value) {
        var ret=null;
        if(!elt||!elt.type) return null;
        switch(elt.type) {
        case 'text': case 'password': case 'hidden': case 'textarea':
            if(undefined!=value)
                elt.value=value;
            ret= elt.value;
            break;
        case 'select-one':
            if(undefined!=value) {
                for(var i=0 ; i < elt.options.length ; i++) {
                    if( elt.options[i].value == value ) {
                        elt.selectedIndex=i;
                        break;
                    }
                }
            }else if(elt.selectedIndex>=0) {
                ret= elt.options[elt.selectedIndex].value?elt.options[elt.selectedIndex].value:elt.options[elt.selectedIndex].text;
            }
            break;
        case 'select-multiple':
            ret=[];
            for(var i=0 ; i < elt.options.length ; i++) {
                if(undefined!=value)
                    elt.options[i].selected = (elt.options[i].value == value);
                if(elt.options[i].value == value) ret.push(value);
            }
            ret=ret.join(';');
            break;
        case 'checkbox': case 'radio':
            ret=[];
            if( !elt.length ) elt=[elt];
            for(var i=0 ; i < elt.length ; i++) {
                if(undefined!=value)
                    elt[i].checked = (elt[i].value == value);
                if(elt.options[i].value == value) ret.push(value);
            }
            ret=ret.join(';');
            break;
        }
        return ret;
    }
};

function    loadHTMLFragment(uri, id, callback) {
    var A=null;
    try {
        A=new ActiveXObject("Msxml2.XMLHTTP");
    } catch (e) {
        try {
            A=new ActiveXObject("Microsoft.XMLHTTP");
        } catch (oc) {
            A=null;
        }
    }
    if(!A && typeof XMLHttpRequest != "undefined")
        A = new XMLHttpRequest();
    if (A) {
        if(!callback) {
            callback=function(html, id) {
                var t=null;
                if(id && (t=document.getElementById(id)))
                    t.innerHTML=html;
                };
        }
        //A.loadHTMLFragment_id       = id;
        //A.loadHTMLFragment_callback = callback;
        if(uri.indexOf("?")==-1) uri+='?RvL='+new Date().getTime();
        else    uri+='&RvL='+new Date().getTime();
        A.open('GET', uri, true);
        A.onreadystatechange = function() {
            if (A.readyState != 4)  return;
            if(A.status==200)   callback(A.responseText, id);
            else                alert('loadHTMLFragment: ERROR: '+A.status);
        }
        var post_data=null;
        A.send(post_data);
        delete A;
    }else{
        alert('FATAL: no XMLHttpRequest object found.');
    }
}

