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
        xmlhttp.open('POST', 'index.php?sr=AJAX&'+req, true);
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