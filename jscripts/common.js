// PreInit

isDOM=document.getElementById //DOM1 browser (MSIE 5+, Netscape 6, Opera 5+)
isMSIE=document.all && document.all.item //Microsoft Internet Explorer 4+
isNetscape4=document.layers //Netscape 4.*
isOpera=window.opera //Opera
isOpera5=isOpera && isDOM //Opera 5+
isMSIE5=isDOM && isMSIE && !isOpera //MSIE 5+
isMozilla=isNetscape6=isDOM && !isMSIE && !isOpera

var xmlhttp = false;
var _ms_xmlHttpVersion = "";

// Get element by id

function qf_getbyid(id)
{
        itm = null;

        if (document.getElementById)
        {
                itm = document.getElementById(id);
        }
        else if (document.all)
        {
                itm = document.all[id];
        }
        else if (document.layers)
        {
                itm = document.layers[id];
        }

        return itm;
}

// Show/hide

function toggleview(id)
{
        if ( ! id ) return;

        if ( itm = qf_getbyid(id) )
        {
                if (itm.style.display == "none")
                {
                        qf_show_div(itm);
                }
                else
                {
                        qf_hide_div(itm);
                }
        }
}

// Set DIV ID to hide

function qf_hide_div(itm)
{
        if ( ! itm ) return;

        itm.style.display = "none";
}

// Set DIV ID to show

function qf_show_div(itm)
{
        if ( ! itm ) return;

        itm.style.display = "";
}

// Set DIV ID to hide

function qf_hide_div_id(id)
{
        if ( itm = qf_getbyid(id) ) {
		itm.style.display = "none";
	}
}

// Set DIV ID to show

function qf_show_div_id(id)
{
        if ( itm = qf_getbyid(id) ) itm.style.display = "";

}

function getAbsolutePos(itm)
{
   var r = { x: itm.offsetLeft, y: itm.offsetTop };
   if (itm.offsetParent)
   {
       var tmp = getAbsolutePos(itm.offsetParent);
       r.x += tmp.x;
       r.y += tmp.y;
   }
   return r;
}


function qf_show_div_mouse(id, evt)
{
  mousex = evt.clientX;
  mousey = evt.clientY;
  pagexoff = 0;
  pageyoff = 0;
  if(isMSIE5){
    pagexoff = document.documentElement.scrollLeft;
    pageyoff = document.documentElement.scrollTop;
  }
  else{
    pagexoff = window.pageXOffset;
    pageyoff = window.pageYOffset;
  }
  ;
  if (obj = qf_getbyid(id)) {
    if (isNetscape4)
      stl = obj;
    else
      stl = obj.style;

	  c_width = obj.offsetWidth;
	  oCanvas = document.getElementsByTagName(
	      (document.compatMode && document.compatMode == 'CSS1Compat') ? 'HTML' : 'BODY'
      )[0];
	  w_width = oCanvas.clientWidth ? oCanvas.clientWidth + oCanvas.scrollLeft : window.innerWidth + window.pageXOffset;


  	if(stl){
        if (mousex + pagexoff + c_width > w_width)
            stl.left = w_width - c_width + 'px';
        else
    	    stl.left = (mousex+pagexoff) + 'px';

	    stl.top = (mousey+pageyoff) + 20 + 'px';

    	stl.display = '';
	}
  }
  return true;
}

function createXMLHttp() {
    var aVersions = [ "MSXML2.XMLHttp.7.0",
        "MSXML2.XMLHttp.6.0",
        "MSXML2.XMLHttp.5.0",
        "MSXML2.XMLHttp.4.0","MSXML2.XMLHttp.3.0",
        "MSXML2.XMLHttp","Microsoft.XMLHttp" ];

    if (_ms_xmlHttpVersion != "")
      return new ActiveXObject(_ms_xmlHttpVersion);

    for (var i = 0; i < aVersions.length; i++) {
      try {
          var oXmlHttp = new ActiveXObject(aVersions[i]);
	  xmlHttpVersionUsing = aVersions[i];
	  _ms_xmlHttpVersion = aVersions[i];
          return oXmlHttp;
      } catch (oError) {
        //Do nothing
      }
  }
  _ms_xmlHttpVersion = "";
  throw new Error("MSXML is not installed or ActiveX objects execution is disabled in your browser settings.");
}

function xmlhttp_reconnect()

{

	if( xmlhttp )

	{

		delete xmlhttp;

	}



	xmlhttp = false;



	if( window.XMLHttpRequest )

	{

		xmlhttp = new XMLHttpRequest();

		xmlHttpVersionUsing = "XMLHttpRequest (embedded)";
	}

	else {
          if ( window.ActiveXObject ) {
	    try {
              xmlhttp = createXMLHttp();
	    } catch (e) {
	      alert(e.message);
	    }
	  } else {
	    alert("AJAX assumed that ActiveX support should be turned on in your browser");
	  }
	}
};

