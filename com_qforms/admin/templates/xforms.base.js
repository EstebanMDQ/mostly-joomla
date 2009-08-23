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

function    zebra_tables(id,start,click) {
    var t=null;
    if(!start) start=0;
    if( (t = self.document.getElementById(id)) ) {
        t = t.getElementsByTagName('TR')
        for(var i=start ; i < t.length ; i++) {
            if(!t[i].className) t[i].className=((i%2)?' rowOdd ':' rowEven ')+t[i].className;
            t[i].onmouseover= function(){ this.className = ' rowRuled '+this.className; return false; }
            t[i].onmouseout = function(){ this.className=this.className.replace(/\s*rowRuled\s*/,' '); return false; }
            //if(click) t[i].onclick    = function(evt){  if(!evt) evt=event;
            //    if( evt.target && (evt.target.tagName!='TD'&&evt.target.tagName!='TR') ) return true;
            //    if(this.className.indexOf('rowSelected')>-1)
            //    this.className = this.className.replace(/\s*rowSelected\s*/,' ');
            //    else this.className = ' rowSelected '+this.className;
            //    return true;}
        }
    }
    if(document.forms['xF_FilterForm'] && document.forms['xF_FilterForm'].elements[0] )
        try{ document.forms['xF_FilterForm'].elements[0].focus(); }catch(e) {}

    if(window.cssQuery) {
        var tags = cssQuery("*[title]");
        for(var i=0 ; i < tags.length ; i++ ) {
            tags[i].onmouseover = function() { window.status=this.title; }
        }
    }

    if(window.cssQuery && window.makeNiceTitles) {
        var tags = cssQuery("[title]");
        for(var i=0 ; i < tags.length ; i++) {
            tags[i].setAttribute("nicetitle", tags[i].title);
            //tags[i].setAttribute("title", " ");
            addEvent(tags[i], "mouseover", showNiceTitle);
            addEvent(tags[i], "mouseout", hideNiceTitle);
            addEvent(tags[i], "focus", showNiceTitle);
            addEvent(tags[i], "blur", hideNiceTitle);
        }
    }
    xfABM_InitListAndForm();

    return false;
}

function    tabSheet(tabs,sheets, first_tab) {
    if(!sheets) {
        with(tabs.tabSheet_base) {
            tabSheet_cur.tabSheet_sheet.style.display='none';
            tabSheet_cur.className='';
            tabSheet_cur=tabs;
            tabSheet_cur.tabSheet_sheet.style.display='block';
            tabSheet_cur.className='selected';
        }
        return false;
    }
    if(!first_tab) first_tab=0;
    var counter=0, base=null;
    if(base=tabs=document.getElementById(tabs)) {
        if(tabs=tabs.getElementsByTagName('LI')) {
            if(sheets=document.getElementById(sheets)) {
                if(sheets=sheets.getElementsByTagName('DIV')) {
                    for(var i=0 ; i < sheets.length ; i++) {
                        if(sheets[i].className.substr(0,8)=='tabSheet' && tabs[counter]) {
                            tabs[counter].tabSheet_base = base;
                            tabs[counter].tabSheet_sheet = sheets[i];
                            tabs[counter].onclick = new Function('tabSheet(this)');
                            tabs[counter].tabSheet_sheet.style.display='none';
                            counter++;
                        }
                    }
                    base.tabSheet_cur = tabs[first_tab];
                    if(base.tabSheet_cur.tabSheet_sheet)
                        base.tabSheet_cur.tabSheet_sheet.style.display='block';
                    base.tabSheet_cur.className='selected';
                }
            }
        }
    }
}

function    xforms_form_init() {
    var t=null;
    Event.observe( window, 'load', function() {
        if( (t = self.document.getElementById('XFABMForm')) ) {
            var first=true;
            var availabletypes = ' text textarea password radio checkbox file ';
            for(var i=0 ; i < t.elements.length ; i++) {
                if(t.elements[i]&&t.elements[i].type && (availabletypes.indexOf(' '+t.elements[i].type+' ')>=0) && t.elements[i].disabled!=true ) {
                    t.elements[i].onfocus = function() { if(this.select) this.select(); this.className = this.className.replace(/focused/,'')+' focused'; }
                    t.elements[i].onblur = function() { this.className = this.className.replace(/focused/,''); }
                    if(first) {
                        try{ t.elements[i].focus(); }catch(e) {}
                        first=false;
                    }
                }
            }
        }
        }, false);
	// Notice: The simple theme does not use all options some of them are limited to the advanced theme
    try{
        tinyMCE.init({
            mode : "textareas",
            plugins : "advhr,advimage,advlink,iespell,contextmenu,paste,fullscreen,table",
            theme : "advanced",
            theme_advanced_styles : "Header 1=header1;Header 2=header2;Header 3=header3;Table Row=tableRow1",
            paste_auto_cleanup_on_paste : true,
            paste_convert_headers_to_strong : false,
            paste_strip_class_attributes : "all",
            paste_remove_spans : false,
            paste_remove_styles : false,
            theme_advanced_toolbar_location : "top",
            theme_advanced_toolbar_align : "left",
            theme_advanced_statusbar_location : "bottom",
            auto_reset_designmode: true,
            editor_selector : "mceEditor",
            relative_urls : false
        });
    }catch(e) { }
    xfABM_InitListAndForm();
}

function    xfABM_InitListAndForm() {
    Event.observe(window, 'load', function() {
        var links = cssQuery("a[class~='xfButtonOpenDialog']");
        for(i=0 ; i < links.length ; i++) {
            links[i].onclick=function() {
                top.Gui.dialogUrl('', this.href+'&NFR=1', '', window);
                Event.stop(this);
                if(window.event) window.event.returnValue=false;
                return false; };
        }
        var links = cssQuery("input[name='xF_CancelButton']");
        for(i=0 ; i < links.length ; i++) {
            links[i]._prev_onclick = links[i].onclick
            links[i].onclick=function() {
                if(!top.Gui) { if(this._prev_onclick) this._prev_onclick(); return true; }
                top.Gui.closeLast();
                Event.stop(this);
                if(window.event) window.event.returnValue=false;
                return false; };
        }
    }, false);
}

function    xfABM_ShowHideElt(elt) {
    if(typeof(elt)=='string') elt=document.getElementById(elt);
    if(elt && elt.style ) { elt.style.display=(elt.style.display=='none'?'block':'none'); }
    return false;
}

function xfABM_iFrameHeight(e) {
    var h = 0;
    if ( !document.all ) {
        if( h = document.getElementById(e).contentDocument.height )
            document.getElementById(e).style.height = h + 160 + 'px';
    } else if( document.all ) {
        if( h = document.frames(e).document.body.scrollHeight )
            document.all[e].style.height = h + 120 + 'px';
    }
    if(!window.xfABM_iFrameHeight_counter)window.xfABM_iFrameHeight_counter=0;
    if(h<=1 && window.xfABM_iFrameHeight_counter<2) {
        window.xfABM_iFrameHeight_counter++;
        window.setTimeout('xfABM_iFrameHeight(\''+e+'\')',100);
    }
}

