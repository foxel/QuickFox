function for_preview(frm_name) {

    xmlhttp_reconnect();

    par_msg = document.forms[frm_name].message;
    if (par_msg==null)
        par_msg = document.forms[frm_name].t_descr;
    prv_butt = document.forms[frm_name].preview;

    if (xmlhttp && par_msg!=null)
    {
    xmlhttp.onreadystatechange = function()
    	{
            if (xmlhttp.readyState == 4) {
                if (xmlhttp.status == 200) {
                    if (prv = qf_getbyid(frm_name + '_preview')) {
                        prv.innerHTML = xmlhttp.responseText;
                        if (links = prv.getElementsByTagName('a'))
                            for (var i in links) {
                                links[i].onclick = function (e) {alert(this.href); if (!e) window.event.cancelBubble = true; else e.stopPropagation(); return false;};
                            }
                        prv.style.display = "";
                        par_msg.style.display = "none";
                    }
                }
                if (prv_butt) {
                    prv_butt.disabled = true;
                    prv_butt.value = '{L_BTN_PREVIEW}';
                }
            }
        }

        if (prv_butt) {
            prv_butt.disabled = true;
            prv_butt.value = '{L_WAITASEC}';
        }
        var req = "class=forum&job=preview&message=" + encodeURIComponent(par_msg.value);
        xmlhttp.open('POST', 'index.php?sr=AJAX', true);
        xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xmlhttp.send(req);
    }
}

function for_hidepreview(frm_name) {

    par_msg = document.forms[frm_name].message;
    if (par_msg==null)
        par_msg = document.forms[frm_name].t_descr;
    prv_butt = document.forms[frm_name].preview;

    if (par_msg!=null)
    {
        if (prv = qf_getbyid(frm_name + '_preview')) {
            prv.style.display = "none";
            par_msg.style.display = "";
            //par_msg.focus();
        }
        if (prv_butt) {
            prv_butt.disabled = false;
            prv_butt.value = '{L_BTN_PREVIEW}';
        }
    }
}

function for_attmouseclick(e, obj) {

    if (e.button == 1 && !obj.myhref) {
        obj.myhref = obj.href;
        obj.href+='&download=1';
        window.setTimeout(function() {for_atthrefclear(obj);}, 500);
    }
}

function for_atthrefclear(obj) {

    if (obj.myhref) {
        obj.href = obj.myhref;
        obj.myhref = false;
    }
}

var QF_STORE = function() {
    if (!window.localStorage)
        return {
            store:   function(obj) {return false;},  
            restore: function(obj) {return false;},
            clear:   function(obj) { return false;}
        };

    function getElementKey(obj) {
        var key = obj.name;
        if (obj.form && obj.form.name)
            key += '.'+obj.form.name;
        return key;
    }
    
    function elementStore(obj) {
        if (typeof obj.value == 'undefined' || (obj.value && obj.value.length < 50))
            return;
        var val = obj.value;
        var key = getElementKey(obj);
        window.localStorage.setItem(key, val);
    }

    function elementRestore(obj) {
        if (obj.value)
            return;
        var key = getElementKey(obj);
        var val = window.localStorage.getItem(key);
        if (val && val.length > 50)
            obj.value = val;
    }

    function elementClear(obj) {
        var key = getElementKey(obj);
        window.localStorage.removeItem(key);
    }

    function formClear(obj) {
        for (var i = obj.elements.length - 1; i >= 0; i--)
            elementClear(obj.elements[i]);
    }

    function formRestore(obj) {
        for (var i = obj.elements.length - 1; i >= 0; i--)
            elementRestore(obj.elements[i]);
    }

    function formStore(obj) {
        for (var i = obj.elements.length - 1; i >= 0; i--)
            elementStore(obj.elements[i]);
    }

    window.addEventListener('load', function() {
        for (var i = document.forms.length - 1; i >= 0; i--) {
            formRestore(document.forms[i]); 
            document.forms[i].addEventListener('submit', function () { QF_STORE.clear(this) });
        }
    });
    
    return {
        store: function(obj) {
            if (!obj || !obj.tagName)
                return;
            if (obj.tagName == 'FORM')
                return formStore(obj);
            else
                return elementStore(obj);
        },
        clear: function(obj) {
            if (!obj || !obj.tagName)
                return;
            if (obj.tagName == 'FORM')
                return formClear(obj);
            else
                return elementClear(obj);
        },
        restore: function(obj) {
            if (!obj || !obj.tagName)
                return;
            if (obj.tagName == 'FORM')
                return formRestore(obj);
            else
                return elementRestore(obj);
        }
    }
}();

