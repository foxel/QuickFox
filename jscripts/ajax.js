function CheckAjax() {

   xmlhttp_reconnect();

   if (xmlhttp)
   {
	xmlhttp.onreadystatechange = function()
	{
          if(xmlhttp.readyState == 4 && xmlhttp.status == 200) {
            qf_getbyid('aj_check').innerHTML = xmlhttp.responseText;
	   // disableFormCtrls('form_reply_' + _IR.comment_id, false);
          } else if(xmlhttp.readyState == 4 && xmlhttp.status != 200) {
	   // disableFormCtrls('form_reply_' + _IR.comment_id, false);
	  }
        }


	xmlhttp.open('GET', 'index.php?sr=AJAX', true);
	xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;charset=windows-1251');
	xmlhttp.send('');
   }
}
