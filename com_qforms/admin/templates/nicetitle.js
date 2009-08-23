// NiceTitle
// Original verion by Stuart Langridge (http://www.kryogenix.org/)
// Modified by Kurt Blackwell (http://kurt.blackwell.id.au)

var NICETITLE_X_OFFSET = 15;		// Offset to right of link left
var NICETITLE_X_PADDING = 15;		// Padding between nicetitle and viewport sides
var NICETITLE_Y_PADDING = 15;		// Padding between link and nicetitle
var NICETITLE_MOUSE_DIST = "5em";	// If it can move the title this much closer to the mouse by switching from bottom to top, it will do so.
var NICETITLE_FADE_DELAY = 100;		// Min interval between fade updates (Not IE)
var NICETITLE_FADE_LENGTH = 100;	// Duration of fade in milliseconds

var niceTitleDiv = null;			// Main nice title popup
var niceTitleLink = null;			// Link that created nice title
var niceTitleDummy;					// Dummy object used to find viewport size
var niceTitleFadeTimeout = null;	// Timeout ID used in Mozilla fade
var niceTitleFadeStart;				// When Mozilla fade started
var niceTitleInclPadding;			// true if browser includes padding in offsetWidth
var niceTitleTimeout = null;		// Used to prevent nicetitle loss when crossing link sections

addEvent(window, "load", makeNiceTitles);
addEvent(window, "resize", hideNiceTitle);	// To prevent resizing problems


// Called after window loads.  Iterates through all links, and creates nice
// title for all the have the "title" attribute.
function makeNiceTitles() {
	if (document.links && document.createElement && document.getElementsByTagName
		&& document.body && document.body.appendChild)
	{	// Make a niceTitleDummy element to tell us how big the viewport is (without scrollbars)
		niceTitleDummy = document.createElement("div");
		niceTitleDummy.style.position = "absolute";
		niceTitleDummy.style.left = "-100%";
		niceTitleDummy.style.top = "-100%";
		niceTitleDummy.style.paddingRight = "1px";
		niceTitleDummy.style.width = "1px";
		niceTitleDummy.style.height = NICETITLE_MOUSE_DIST;
		document.body.appendChild(niceTitleDummy);

		niceTitleInclPadding = (niceTitleDummy.offsetWidth <= 1);

		var toolTipHack = navigator.userAgent.indexOf("Opera") >= 0;

		for (var ti=0;ti<document.links.length;ti++) {
			var lnk = document.links[ti];
			if (lnk.title) {
				lnk.setAttribute("nicetitle", lnk.title);
				if (toolTipHack)
					lnk.setAttribute("title", " ");
				else
					lnk.removeAttribute("title");
				addEvent(lnk, "mouseover", showNiceTitle);
				addEvent(lnk, "mouseout", hideNiceTitle);
				addEvent(lnk, "focus", showNiceTitle);
				addEvent(lnk, "blur", hideNiceTitle);
			}
		}
	}
}


// Pops up a nice title for the link.  This
function showNiceTitle(e) {
	var lnk = getNodeWithTag(getEventTarget(e), "a");
	//if (!lnk) return;
    if (!lnk) lnk=getEventTarget(e);

	// Prevent duplications with mouseover and focus, and kill others if something went wrong
	if (niceTitleLink == lnk)
	{
		if (niceTitleTimeout)
			window.clearTimeout(niceTitleTimeout);
		return;
	}
	hideNiceTitleNow(e);

	// Create the nice title.  The inner parts are <span>s so we can better
	// manipulate them and find their preferred width.
	var d = document.createElement("div");
	d.className = "nicetitle";
	d.innerHTML = "<span class=text>" + lnk.getAttribute("nicetitle")
		+ "</span><br /><span class=href></span>";

	// Add nice title to document so we can find sizes (but hide it off side)
	d.style.top = "-1000em";
	d.style.left = "-1000em";
	document.body.appendChild(d);

	if (d.filters)
	{	// IE fade with filters
		d.style.display = "none";
		d.style.filter = "progid:DXImageTransform.Microsoft.Fade(duration=" + (NICETITLE_FADE_LENGTH / 1000)
			+ ") progid:DXImageTransform.Microsoft.GradientWipe(duration=" + (NICETITLE_FADE_LENGTH / 2000) + ")";
		//	+ ") progid:DXImageTransform.Microsoft.RandomBars(duration=" + (NICETITLE_FADE_LENGTH / 2000) + ", orientation=vertical)";
		//	+ ") progid:DXImageTransform.Microsoft.Stretch(duration=" + (NICETITLE_FADE_LENGTH / 2000) + ")";

		d.filters[0].Apply();
		d.filters[1].Apply();
		d.style.display = "block";
		window.setTimeout("fadeForIE()", 1);
	}
	else if (d.style.MozOpacity !== undefined)
	{	// Mozilla fade in
		d.style.MozOpacity = 0;
		niceTitleFadeStart = new Date();
		if (niceTitleFadeTimeout)
			window.clearTimeout(niceTitleFadeTimeout);
		niceTitleFadeTimeout = window.setTimeout("fadeForMozilla()", NICETITLE_FADE_DELAY);
	}

	// Set best width for NiceTitle
	var newWidth = d.offsetWidth;
	var padding = 0;
	var pat = d.childNodes[0];
	var pad = d.childNodes[2];
	if (niceTitleInclPadding) // Assume same padding on either side
		padding = Math.max(pad.offsetLeft, pat.offsetLeft)*2;
	newWidth = Math.max(newWidth, Math.max(pat.offsetWidth, pad.offsetWidth) + padding);
	d.style.width = newWidth + "px";

	// Get padding that is not included in offsetWidth calculation
	padding = d.offsetWidth - newWidth;

	// Position NiceTitle
	var dim = getNodeDims(lnk);
	var mouse = getMousePos(e);
	//var dx = (mouse.active ? mouse.x : dim.left) + NICETITLE_X_OFFSET;
	var dx = dim.left + NICETITLE_X_OFFSET;
	var dy = dim.bottom + NICETITLE_Y_PADDING;
	var view = getViewport();

	// Align horizontally
	if ((d.offsetWidth + NICETITLE_X_PADDING*2) > view.width)
	{	// Too wide for viewport.  Just use all possible space
		d.style.width = (view.width - NICETITLE_X_PADDING*2 - padding) + "px";
		d.style.left = NICETITLE_X_PADDING + "px";
		d.style.right = NICETITLE_X_PADDING + "px";
	}
	else if ((dx + d.offsetWidth + NICETITLE_X_OFFSET) > view.right)
	{	// Pin to  right side
		d.style.left = "auto";
		d.style.right = NICETITLE_X_PADDING + "px";
	}
	else
	{	// Pin to left side
		d.style.left = dx + "px";
	}

	// Align vertically
	if ((dy + d.offsetHeight > view.bottom)
		|| (mouse.active && (mouse.y - dim.top + niceTitleDummy.offsetHeight < dy - mouse.y)
			&& (dim.top - NICETITLE_Y_PADDING - d.offsetHeight > view.top)))
	{	// Not enough room below link || can move it above link, closer to the mouse
		d.style.top = (dy - d.offsetHeight - dim.height - NICETITLE_Y_PADDING*2) + "px";
	}
	else
	{	// Enough room below link.  Move above.
		d.style.top = dy + "px";
	}

	niceTitleDiv = d;
	niceTitleLink = lnk;
}


function fadeForIE()
{	// HACK: Called by timeout to stop mysterious scrollbar popping in IE
	if (niceTitleDiv)
	{
		niceTitleDiv.filters[0].Play();
		niceTitleDiv.filters[1].Play();
	}
}


function fadeForMozilla()
{	// Use -moz-opacity to fade nicetitle
	if (!niceTitleDiv)
	{	// No title?  End timeout
		niceTitleFadeTimeout = null;
		return;
	}

	var diff = (new Date() - niceTitleFadeStart);

	if (diff >= NICETITLE_FADE_LENGTH)
	{	// End fade.  Don"t make it exactly 1.0 so it won"t flick
		niceTitleDiv.style.MozOpacity = 0.9999;
		niceTitleFadeTimeout = null;
	}
	else
	{	// Continue fade
		niceTitleDiv.style.MozOpacity = diff / NICETITLE_FADE_LENGTH;
	}

	window.setTimeout("fadeForMozilla()", NICETITLE_FADE_DELAY);
}


function hideNiceTitleNow() {
	// Destroy nice title if it exists, and the kill event was generated by the same link that owns it
	if (niceTitleDiv) {
		window.clearTimeout(niceTitleTimeout);
		document.body.removeChild(niceTitleDiv);
		niceTitleDiv = null;
		niceTitleLink = null;
		niceTitleTimeout = null;
	}
}


function hideNiceTitle(e) {
	// Destroy nice title in a millisec so
	if (niceTitleDiv) {
		if (window.setTimeout)
			niceTitleTimeout = window.setTimeout("hideNiceTitleNow()", 1);
		else
			hideNiceTitleNow();
	}
}


function getMousePos(event) {
	var m;
	if (window.event && (window.event.clientX !== undefined) && (document.body.scrollLeft !== undefined))
		// IE
		m = { x:(window.event.clientX + document.body.scrollLeft), y:(window.event.clientY + document.body.scrollTop) };
	else if ((event.clientX !== undefined) && (window.scrollX !== undefined))
		// Mozilla/Netscape
		m = { x:(event.clientX + window.scrollX), y:(event.clientY + window.scrollY) };
	else if ((event.clientX !== undefined) && (window.pageXOffset !== undefined))
		// One last Netscape method
		m = { x:(event.clientX + window.pageXOffset), y:(event.clientY + window.pageYOffset) };
	else
		// Report position of what caused the event
		m = getNodePos(getEventTarget(event));

	// Return whether mouse actively caused this event
	m.active = ((window.event && window.event.type.indexOf("mouse") >= 0) || (event.type && event.type.indexOf("mouse") >= 0));

	return m;
}


// Add an eventListener to browsers that can do it somehow.
// Original: Scott Andrew
// Modified to put attachEvent as perference to fix Opera
function addEvent(obj, evType, fn){
	if (obj.attachEvent){
		return obj.attachEvent("on" + evType, fn);
	} else if (obj.addEventListener){
		obj.addEventListener(evType, fn, true);
		return true;
	} else {
		return false;
	}
}


// Ascends the nodes to find the one with the specified tag
function getNodeWithTag(el, tag) {
	// toLowerCase is for Gecko bug, supposed to be uppercase
	tag = tag.toLowerCase();
	while ((el != null) && ((el.nodeType != 1) || (el.tagName.toLowerCase() != tag)))
		el = el.parentNode;
	return el;
}


// This will get the position of the specified element.  For text, this will return the
// position of the first character, and not the top left bounds if the text wraps.
function getNodePos(el)
{
	if (!el) return { x:0, y:0 };
	var pos = { x:el.offsetLeft, y:el.offsetTop }
	for (var parent = el.offsetParent; parent != null; parent = parent.offsetParent)
	{
		pos.x += parent.offsetLeft;
		pos.y += parent.offsetTop;
	}
	return pos;
}


// Finds the top, bottom and total height of an element and it's nodes.
// The left position is given, but it's only the x of the top node.
// The horizontal dims can't be reliably calculated because of wrapping text.
function getNodeDims(el) {
	function recurse(el, dim) {
		for (var num = 0; num < el.childNodes.length; num++)
		{	// Recurse through children
			var child = el.childNodes[num];
			if (!isNaN(child.offsetHeight))
			{	// See if child will push dims out
				var p = getNodePos(child);
				if (dim.top > p.y) dim.top = p.y;
				p.y += child.offsetHeight;
				if (dim.bottom < p.y) dim.bottom = p.y;
			}
			recurse(child, dim);
		}
	}

	// Set initial bounds
	var p = getNodePos(el);
	var dim = { left:p.x, top:p.y, bottom:(p.y + el.offsetHeight) };
	recurse(el, dim);
	dim.height = dim.bottom - dim.top;

	return dim;
}


function getEventTarget(event)
{
	if (window.event && window.event.srcElement)
		return window.event.srcElement;
	else if (event && event.target)
		return event.target;
	return null;
}


function getViewport()
{
	var view;
	if (window.pageXOffset !== undefined)
		view = { left:window.pageXOffset, top:window.pageYOffset };
	else if (document.body.scrollLeft !== undefined)
		view = { left:document.body.scrollLeft, top:document.body.scrollTop };
	else if (window.scrollX !== undefined)
		view = { left:window.scrollX, top:window.scrollY };
	else
		view = null;

	if (view)
	{	// We know where the screen is
		// niceTitleDummy and/or document.body might be screen height or doc height.
		// Pick the smallest, and cross our fingers.
		view.right = view.left + Math.min(document.body.offsetWidth, -niceTitleDummy.offsetLeft);
		if (window.innerWidth)
			view.right = Math.min(view.right, view.left + window.innerWidth);
		view.bottom = view.top + Math.min(document.body.offsetHeight, -niceTitleDummy.offsetTop);
		if (window.innerHeight)
			view.bottom = Math.min(view.bottom, view.top + window.innerHeight);
	}
	else
	{	// We don't know where we are
		view = { left: 0, top: 0, right: document.body.offsetWidth, bottom: Number.POSITIVE_INFINITY}
	}
	view.width = view.right - view.left;
	view.height = view.bottom - view.top;

	return view;
}