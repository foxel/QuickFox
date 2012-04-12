<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

Glob_Request('job');

if ($job=='drop_pass') {
$Page_SubTitle = $lang['DROP_PASS_CAPT'];

$fields['script']['value']='drop_pass';
$fields['script']['type']='hidden';

$fields['action']['value']='get_code';
$fields['action']['type']='hidden';

$fields['duserlogin']['type']='text';
$fields['duserlogin']['params']='maxlength=15';
$fields['duserlogin']['capt']=$lang['REG_LOGIN_SHORT'];
$fields['duserlogin']['descr']=$lang['DROP_PASS_LOGIN_MORE'];

if ($QF_Config['use_spcode'] && !$QF_User->uid) {
    $fields['spamcode']['type']='text';
    $fields['spamcode']['params']='maxlength=8';
    $fields['spamcode']['capt']=$lang['SPAM_CODE'].':';
    $fields['spamcode']['descr']='{SPAMIMG}';
}

$fields['submit']['value']=$lang['BTN_ACCEPT'];
$fields['submit']['type']='submit';
$fields['submit']['descr']=$lang['ACT_ACCEPT_MORE'].(($QF_Config['register_need_approve']) ? $lang['ACT_NEED_ADMIN'] : '');

print Vis_Draw_Form($lang['DROP_PASS_CAPT'], 'drppassform', 'index.php', $lang['DROP_PASS_REQUEST'], $fields);

$fields = Array();

$fields['script']['value']='drop_pass';
$fields['script']['type']='hidden';

$fields['action']['value']='use_code';
$fields['action']['type']='hidden';

$fields['duserlogin']['type']='text';
$fields['duserlogin']['params']='maxlength=15';
$fields['duserlogin']['capt']=$lang['REG_LOGIN_SHORT'];
$fields['duserlogin']['descr']=$lang['DROP_PASS_LOGIN_MORE'];

$fields['nuserpass']['type']='doubledpass';
$fields['nuserpass']['params']='maxlength=32';
$fields['nuserpass']['capt']=$lang['REG_PASS_SHORT'];
$fields['nuserpass']['capt2']=$lang['REG_PASS_SHORT_R'];
$fields['nuserpass']['descr']=$lang['REG_PASS_MORE'].'<br />
<b>'.sprintf($lang['MAX_SYMVOLS'],32).'</b>';

$fields['drop_code']['type']='text';
$fields['drop_code']['params']='maxlength=32';
$fields['drop_code']['value']=Get_Request('dcode', 1, 'h', 32);
$fields['drop_code']['capt']=$lang['DROP_PASS_CODE_SHORT'];
$fields['drop_code']['descr']=$lang['DROP_PASS_CODE_MORE'];

$fields['submit']['value']=$lang['BTN_ACCEPT'];
$fields['submit']['type']='submit';
$fields['submit']['descr']=$lang['ACT_ACCEPT_MORE'].(($QF_Config['register_need_approve']) ? $lang['ACT_NEED_ADMIN'] : '');

print Vis_Draw_Form($lang['DROP_PASS_ST2_CAPT'], 'actform', 'index.php', $lang['DROP_PASS_ST2_REQUEST'], $fields);

}
elseif ($job=='activate') {
$Page_SubTitle = $lang['ACT_CAPT_DO'];

$fields['script']['value']='activate';
$fields['script']['type']='hidden';

$fields['auserlogin']['type']='text';
$fields['auserlogin']['params']='maxlength=15';
$fields['auserlogin']['capt']=$lang['REG_LOGIN_SHORT'];
$fields['auserlogin']['descr']=$lang['ACT_LOGIN_MORE'];

$fields['auserpass']['type']='password';
$fields['auserpass']['params']='maxlength=32';
$fields['auserpass']['capt']=$lang['REG_PASS_SHORT'];
$fields['auserpass']['descr']=$lang['ACT_PASS_MORE'];

$fields['activatecode']['type']='text';
$fields['activatecode']['params']='maxlength=32';
$fields['activatecode']['value']=Get_Request('acode', 1, 'h', 32);
$fields['activatecode']['capt']=$lang['ACT_ACODE_SHORT'];
$fields['activatecode']['descr']=$lang['ACT_ACODE_MORE'];

$fields['submit']['value']=$lang['BTN_ACCEPT'];
$fields['submit']['type']='submit';
$fields['submit']['descr']=$lang['ACT_ACCEPT_MORE'].(($QF_Config['register_need_approve']) ? $lang['ACT_NEED_ADMIN'] : '');

print Vis_Draw_Form($lang['ACT_CAPT_DO'], 'actform', 'index.php', $lang['ACT_REQUEST'], $fields);
}

else {
print '<script language="JavaScript" type="text/javascript">
errnologin = "'.$lang['ERR_NO_LOGIN'].'";
errneuslogin = "'.$lang['ERR_LOGIN_INCORRECT'].'";
errnopass = "'.$lang['ERR_NO_PASS'].'";
errnoemail = "'.$lang['ERR_NO_EMAIL'].'";
errpasseq = "'.$lang['ERR_DIF_PASS'].'";


function checkForm(form) {

        formErrors = "";

        if (form.nuserlogin.value.length < 3) {
                formErrors += errnologin + "\n";
        }

        if (!form.nuserlogin.value.match( /'.UNAME_MASK.'/i )) {
                formErrors += errneuslogin + "\n";
        }

        if (form.nuserpass1.value.length < 3) {
                formErrors += errnopass + "\n";
        }

        if (form.nuserpass2.value != form.nuserpass1.value) {
                formErrors += errpasseq + "\n";
        }

        if (!form.nemail.value.match( /'.EMAIL_MASK.'/i ))
        {
                formErrors += errnoemail + "\n";
        }

        if (formErrors) {
                alert(formErrors);
                return false;
        } else {
                formObj.submit.disabled = true;
                return true;
        }
}
</script>';

$Page_SubTitle = $lang['CAPT_REG_NEW'];

unset($fields);
$fields['script']['value']='register';
$fields['script']['type']='hidden';

$fields['nuserlogin']['type']='text';
$fields['nuserlogin']['params']='maxlength=15';
$fields['nuserlogin']['capt']=$lang['REG_LOGIN_SHORT'];
$fields['nuserlogin']['descr']=$lang['REG_LOGIN_MORE'].'<br />
<b>'.sprintf($lang['MAX_SYMVOLS'],15).'</b>';

$fields['nuserpass']['type']='doubledpass';
$fields['nuserpass']['params']='maxlength=32';
$fields['nuserpass']['capt']=$lang['REG_PASS_SHORT'];
$fields['nuserpass']['capt2']=$lang['REG_PASS_SHORT_R'];
$fields['nuserpass']['descr']=$lang['REG_PASS_MORE'].'<br />
<b>'.sprintf($lang['MAX_SYMVOLS'],32).'</b>';

$fields['nemail']['type']='text';
$fields['nemail']['params']='maxlength=32';
$fields['nemail']['capt']=$lang['REG_EMAIL_SHORT'];
$fields['nemail']['descr']=$lang['REG_EMAIL_MORE'].'<br />
<b>'.sprintf($lang['MAX_SYMVOLS'],32).'</b>';

$fields['nuinfo']['type']='textarea';
$fields['nuinfo']['capt']=$lang['REG_INFO'];
$fields['nuinfo']['descr']=(($QF_Config['register_need_approve']) ? '<span class="red">'.$lang['REG_INFO_MORE'].'</span>' : '');

if ($QF_Config['use_spcode'] && !$QF_User->uid) {
    $fields['spamcode']['type']='text';
    $fields['spamcode']['params']='maxlength=8';
    $fields['spamcode']['capt']=$lang['SPAM_CODE'].':';
    $fields['spamcode']['descr']='{SPAMIMG}';
}

$fields['submit']['value']=$lang['BTN_ACCEPT'];
$fields['submit']['type']='submit';
$fields['submit']['descr']=$lang['REG_ACCEPT_MORE'].'
'.(($QF_Config['register_need_approve']) ? '<span class="red">'.$lang['REG_NEED_ADMIN'].'</span>' : '');

print Vis_Draw_Form($lang['CAPT_REG_NEW'], 'regform', 'index.php', $lang['REG_REQUEST'], $fields, '', '', 'onsubmit="return checkForm(this)"');

}
?>
</center>