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


/*-------------------------------GLOBAL VARIABLES------------------------------------*/

var detect = navigator.userAgent.toLowerCase();
var OS,browser,version,total,thestring;

/*-----------------------------------------------------------------------------------------------*/

//Browser detect script origionally created by Peter Paul Koch at http://www.quirksmode.org/

function getBrowserInfo() {
	if (checkIt('konqueror')) {
		window.realBrowser = "Konqueror";
		OS = "Linux";
	}
	else if (checkIt('safari')) window.realBrowser 	    = "Safari"
	else if (checkIt('omniweb')) window.realBrowser 	= "OmniWeb"
	else if (checkIt('opera')) window.realBrowser 		= "Opera"
	else if (checkIt('webtv')) window.realBrowser 		= "WebTV";
	else if (checkIt('icab')) window.realBrowser 		= "iCab"
	else if (checkIt('msie')) window.realBrowser 		= "Internet Explorer"
	else if (!checkIt('compatible')) {
		window.realBrowser = "Netscape Navigator"
		version = detect.charAt(8);
	}
	else window.realBrowser = "An unknown browser";

	if (!version) version = detect.charAt(place + thestring.length);

	if (!OS) {
		if (checkIt('linux')) OS 		= "Linux";
		else if (checkIt('x11')) OS 	= "Unix";
		else if (checkIt('mac')) OS 	= "Mac"
		else if (checkIt('win')) OS 	= "Windows"
		else OS 								= "an unknown operating system";
	}
}

function checkIt(string) {
	place = detect.indexOf(string) + 1;
	thestring = string;
	return place;
}

/*-----------------------------------------------------------------------------------------------*/

if(!top.Gui) {
    top.Gui = {
        dialogStack: [],
        topWindow: top,
        overlay: null,
        init: function() {
            getBrowserInfo();
            top.Gui.topBody     = top.Gui.topWindow.document.getElementsByTagName('body')[0];
            top.Gui.overlay     = top.Gui.topWindow.document.createElement('div');
            top.Gui.loadingImg  = top.Gui.topWindow.document.createElement('img');
            top.Gui.loadingImg.src='loading.gif';
            top.Gui.loadingImg.className='guidialogLoading';
            if( window.realBrowser != 'Internet Explorer' )
                top.Gui.loadingImg.style.position='fixed';
            top.Gui.overlay.id	= 'overlay';
            top.Gui.topBody.appendChild(top.Gui.overlay);
            top.Gui.topBody.appendChild(top.Gui.loadingImg);

            top.Gui._initIFrame = function() {
                if(top.Gui._initCounter<=0) {
                    try{ top.Gui.tempFrame.contentWindow.close = function() { top.Gui.closeLast(); } }catch(e) {}
                    try{
                        var body=top.Gui.tempFrame.contentWindow.document.getElementsByTagName('body');
                        if(body && body[0]) body[0].className +=' inDialog';
                    }catch(e){}
                    top.Gui.tempBorder.style.display= 'block';
                    top.Gui.loadingImg.style.display='none';
                    top.Gui._initCounter++;
                }
            }
            top.Gui.dialogCreate(0);
            top.Gui.dialogCreate(1);
        },
        dialogUrl: function(title, url, className, w) {
            top.Gui.tempContent=null; top.Gui.tempUrl=url;
            var frame = this.dialogOpen(title, className);
            top.Gui._initCounter=0;
            top.Gui.tempFrame.src = url;
            top.Gui.tempFrame.opener = w;
            return frame;
        },
        dialog: function(title, content, className) {
            top.Gui.tempContent=content;
            return this.dialogOpen(title, function() {
                    if(!top.Gui.flag_initialized) {
                        top.Gui.tempFrame.contentWindow.document.body.innerHTML = top.Gui.tempContent;
                        top.Gui.flag_initialized=true;
                        try{ top.Gui.tempFrame.contentWindow.close = function() { top.Gui.closeLast(); } }catch(e) {}
                    }
                }, className);
        },
        dialogCreate: function(idx, className) {
            var frame		= top.Gui.topWindow.document.getElementById('guidialogFrame-'+idx)
            if(!frame) {
                frame		     = top.Gui.topWindow.document.createElement('div');
                frame.id		= 'guidialogFrame-'+idx;
                frame.className = 'guidialogFrame'+(className?(' '+className):'');
                frame.innerHTML	= '<div class="guidialogTitle"><span id="guidialogTitle-'+idx+'"></span>&nbsp; <a href="#" onclick="window.top.Gui.closeLast(); return event.returnValue=false;">[X]</a></div><iframe id="guidialog-'+idx+'" class="guidialogContent" scrolling="yes" frameborder="no" src="about:blank">IFRAMES REQUIRED</iframe><div class="guidialogTitle">&nbsp;</div>';
                top.Gui.topBody.appendChild(frame);
                top.Gui.tempFrame= top.Gui.topWindow.document.getElementById('guidialog-'+idx)
                Event.observe( top.Gui.tempFrame, 'load', top.Gui._initIFrame, false);
                if(idx) {
                    frame.style.left= ''+(50+10*idx)+'px';
                    frame.style.top= ''+(30+2*idx)+'px';
                }
            }
            top.Gui.tempTitle   = top.Gui.topWindow.document.getElementById('guidialogTitle-'+idx)
            top.Gui.tempBorder  = top.Gui.topWindow.document.getElementById('guidialogFrame-'+idx)
            top.Gui.tempFrame   = top.Gui.topWindow.document.getElementById('guidialog-'+idx)
            return frame;
        },
        dialogOpen: function(title, className) {
            if (window.realBrowser== 'Internet Explorer'){
                this.getScroll();
                this.prepareIE('100%', 'hidden');
                this.setScroll(0,0);
                this.hideSelects('hidden');
            }

            var idx = top.Gui.dialogStack.length;
            top.Gui._initCounter=0;
            top.Gui.dialogStack[idx] = this.dialogCreate(idx, className);

            top.Gui.tempTitle.innerHTML     = title;
            top.Gui.overlay.style.display   = 'block';
            top.Gui.loadingImg.style.display='block';

            return top.Gui.tempFrame.contentWindow;
        },

        closeLast: function() {
            var removeelt=null;

            // Find the last opened element and hide it.
            if(top.Gui.dialogStack.length) {
                var idx= top.Gui.dialogStack.length-1;
                removeelt = top.Gui.dialogStack.pop();
                removeelt.style.display='none';
                top.Gui.topWindow.document.getElementById('guidialog-'+idx).src='about:blank'
            }else{
                return false;
            }

            // If this is the last dialog, remove the overlay.
            if(!top.Gui.dialogStack.length) {
                top.Gui.overlay.style.display = 'none';
                if (window.realBrowser== "Internet Explorer"){
                    this.setScroll(0,this.yPos);
                    this.prepareIE("auto", "auto");
                    this.hideSelects("visible");
                }
            }
            return true;
        },
        // Ie requires height to 100% and overflow hidden or else you can scroll down past the lightbox
        prepareIE: function(height, overflow){
            var bod = top.Gui.topWindow.document.getElementsByTagName('body')[0];
            bod.style.height = height;
            bod.style.overflow = overflow;

            var htm = top.Gui.topWindow.document.getElementsByTagName('html')[0];
            htm.style.height = height;
            htm.style.overflow = overflow;
        },

        // In IE, select elements hover on top of the lightbox
        hideSelects: function(visibility){
            selects = top.Gui.topWindow.document.getElementsByTagName('select');
            for(i = 0; i < selects.length; i++) {
                selects[i].style.visibility = visibility;
            }
        },

        // Taken from lightbox implementation found at http://www.huddletogether.com/projects/lightbox/
        getScroll: function(){
            if (top.Gui.topWindow.pageYOffset) {
                this.yPos = top.Gui.topWindow.pageYOffset;
            } else if (top.Gui.topWindow.document.documentElement && top.Gui.topWindow.document.documentElement.scrollTop){
                this.yPos = top.Gui.topWindow.document.documentElement.scrollTop;
            } else if (top.Gui.topWindow.document.body) {
                this.yPos = top.Gui.topWindow.document.body.scrollTop;
            }
        },

        setScroll: function(x, y){
            top.Gui.topWindow.scrollTo(x, y);
        }
    }
}