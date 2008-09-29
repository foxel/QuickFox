var mchat_upd_cb = function ()
{
    if (xmlhttp.readyState == 4)
    {
        if (xmlhttp.status == 200 && (chater = qf_getbyid('minichater')))
        {
            chater.innerHTML = xmlhttp.responseText;
        }

        window.clearTimeout(mchat_q_id);
        mchat_q_id = window.setTimeout(mchat_upd, 30000);
    }
}

function mchat_upd()
{

    xmlhttp_reconnect();
    if (xmlhttp)
    {
        xmlhttp.onreadystatechange = mchat_upd_cb;

        var req = 'class=mchat';
        xmlhttp.open('POST', 'index.php?sr=AJAX', true);
        xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xmlhttp.send(req);
    }
    else
        alert('Mini Chat AJAX Error');
}

function mchat_send()
{

    xmlhttp_reconnect();
    var msg_ = qf_getbyid('mchat_msg');
    if (xmlhttp && msg_ && msg_.value && msg_.value.length)
    {
        xmlhttp.onreadystatechange = mchat_upd_cb;

        var req = 'class=mchat&newmess=' + encodeURIComponent(msg_.value);
        var msg_lv = qf_getbyid('mchat_lv')
        if (msg_lv && (msg_lv.selectedIndex > -1))
            req += '&messlevel=' + msg_lv.options[msg_lv.selectedIndex].value;
        xmlhttp.open('POST', 'index.php?sr=AJAX', true);
        xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xmlhttp.send(req);
        msg_.value = '';
    }
}

function mchat_quote(str)
{    var msg_ = qf_getbyid('mchat_msg');
    if (msg_)
    {
        msg_.value = msg_.value + str + ' ';
        msg_.focus();
    }

}

var mchat_q_id = window.setTimeout(mchat_upd, 75000);
