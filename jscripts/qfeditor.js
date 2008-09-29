// QFCode control

window.resizeTo('650','500');
window.moveTo(screen.width/2-325,screen.height/2-250);
document.onClose = function()
{
        opener.focus();
};

// Startup variables
var imageTag = false;
var theSelection = false;

// Sender Form Name

// Check for Browser & Platform for PC & IE specific bits
// More details from: http://www.mozilla.org/docs/web-developer/sniffer/browser_type.html
var clientPC = navigator.userAgent.toLowerCase(); // Get client info
var clientVer = parseInt(navigator.appVersion); // Get browser version

var is_ie = ((clientPC.indexOf("msie") != -1) && (clientPC.indexOf("opera") == -1));
var is_nav = ((clientPC.indexOf('mozilla')!=-1) && (clientPC.indexOf('spoofer')==-1)
                && (clientPC.indexOf('compatible') == -1) && (clientPC.indexOf('opera')==-1)
                && (clientPC.indexOf('webtv')==-1) && (clientPC.indexOf('hotjava')==-1));
var is_moz = 0;

var is_win = ((clientPC.indexOf("win")!=-1) || (clientPC.indexOf("16bit") != -1));
var is_mac = (clientPC.indexOf("mac")!=-1);

// Helpline messages
b_help = "{L_EDITOR_TAGHINT_BOLD}";
i_help = "{L_EDITOR_TAGHINT_ITL}";
u_help = "{L_EDITOR_TAGHINT_UNDR}";
q_help = "{L_EDITOR_TAGHINT_QUOTE}";
c_help = "{L_EDITOR_TAGHINT_CODE}";
h_help = "{L_EDITOR_TAGHINT_HIDE}";
p_help = "{L_EDITOR_TAGHINT_IMG}";
w_help = "{L_EDITOR_TAGHINT_URL}";
a_help = "{L_EDITOR_TAGHINT_CLOSE}";
s_help = "{L_EDITOR_TAGHINT_COLOR}";
f_help = "{L_EDITOR_TAGHINT_SIZE}";

// Define the QFCode tags
qfcode = new Array();
qftags = new Array('[b]','[/b]','[i]','[/i]','[u]','[/u]','[quote]','[/quote]','[code]','[/code]','[list]','[/list]','[hide]','[/hide]','[img]','[/img]','[url]','[/url]');
imageTag = false;

// Shows the help messages in the helpline window
function helpline(help) {
        document.post.helpbox.value = eval(help + "_help");
}


// Replacement for arrayname.length property
function getarraysize(thearray) {
        for (i = 0; i < thearray.length; i++) {
                if ((thearray[i] == "undefined") || (thearray[i] == "") || (thearray[i] == null))
                        return i;
                }
        return thearray.length;
}

// Replacement for arrayname.push(value) not implemented in IE until version 5.5
// Appends element to the array
function arraypush(thearray,value) {
        thearray[ getarraysize(thearray) ] = value;
}

// Replacement for arrayname.pop() not implemented in IE until version 5.5
// Removes and returns the last element of an array
function arraypop(thearray) {
        thearraysize = getarraysize(thearray);
        retval = thearray[thearraysize - 1];
        delete thearray[thearraysize - 1];
        return retval;
}


function checkForm() {

        formErrors = false;

        if (document.post.message.value.length < 2) {
                formErrors = "{L_ERR_NO_MESS}";
        }

        if (formErrors) {
                alert(formErrors);
        } else {
                qfstyle(-1);
                //formObj.submit.disabled = true;
                if (parmess!=0) parmess.value = document.post.message.value;
                opener.focus();
                window.close();
        }
        return false;
}

function emoticon(text) {
        var txtarea = document.post.message;
        text = ' ' + text + ' ';
        if (txtarea.createTextRange && txtarea.caretPos) {
                var caretPos = txtarea.caretPos;
                caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? caretPos.text + text + ' ' : caretPos.text + text;
                txtarea.focus();
        } else {
                txtarea.value  += text;
                txtarea.focus();
        }
}

function qffontstyle(qfopen, qfclose) {
        var txtarea = document.post.message;

        if ((clientVer >= 4) && is_ie && is_win) {
                theSelection = document.selection.createRange().text;
                if (!theSelection) {
                        txtarea.value += qfopen + qfclose;
                        txtarea.focus();
                        return;
                }
                document.selection.createRange().text = qfopen + theSelection + qfclose;
                txtarea.focus();
                return;
        }
        else if (txtarea.selectionEnd && (txtarea.selectionEnd - txtarea.selectionStart > 0))
        {
                mozWrap(txtarea, qfopen, qfclose);
                return;
        }
        else
        {
                txtarea.value += qfopen + qfclose;
                txtarea.focus();
        }
        storeCaret(txtarea);
}


function qfstyle(qfnumber) {
        var txtarea = document.post.message;

        txtarea.focus();
        donotinsert = false;
        theSelection = false;
        qflast = 0;

        if (qfnumber == -1) { // Close all open tags & default button names
                while (qfcode[0]) {
                        butnumber = arraypop(qfcode) - 1;
                        txtarea.value += qftags[butnumber + 1];
                        buttext = eval('document.post.addqfcode' + butnumber + '.value');
                        eval('document.post.addqfcode' + butnumber + '.value ="' + buttext.substr(0,(buttext.length - 1)) + '"');
                }
                imageTag = false; // All tags are closed including image tags :D
                txtarea.focus();
                return;
        }

        if ((clientVer >= 4) && is_ie && is_win)
        {
                theSelection = document.selection.createRange().text; // Get text selection
                if (theSelection) {
                        // Add tags around selection
                        document.selection.createRange().text = qftags[qfnumber] + theSelection + qftags[qfnumber+1];
                        txtarea.focus();
                        theSelection = '';
                        return;
                }
        }
        else if (txtarea.selectionEnd && (txtarea.selectionEnd - txtarea.selectionStart > 0))
        {
                mozWrap(txtarea, qftags[qfnumber], qftags[qfnumber+1]);
                return;
        }

        // Find last occurance of an open tag the same as the one just clicked
        for (i = 0; i < qfcode.length; i++) {
                if (qfcode[i] == qfnumber+1) {
                        qflast = i;
                        donotinsert = true;
                }
        }

        if (donotinsert) {                // Close all open tags up to the one just clicked & default button names
                while (qfcode[qflast]) {
                                butnumber = arraypop(qfcode) - 1;
                                txtarea.value += qftags[butnumber + 1];
                                buttext = eval('document.post.addqfcode' + butnumber + '.value');
                                eval('document.post.addqfcode' + butnumber + '.value ="' + buttext.substr(0,(buttext.length - 1)) + '"');
                                imageTag = false;
                        }
                        txtarea.focus();
                        return;
        } else { // Open tags

                if (imageTag && (qfnumber != 14)) {                // Close image tag before adding another
                        txtarea.value += qftags[15];
                        lastValue = arraypop(qfcode) - 1;        // Remove the close image tag from the list
                        document.post.addqfcode14.value = "Img";        // Return button back to normal state
                        imageTag = false;
                }

                // Open tag
                txtarea.value += qftags[qfnumber];
                if ((qfnumber == 14) && (imageTag == false)) imageTag = 1; // Check to stop additional tags after an unclosed image tag
                arraypush(qfcode,qfnumber+1);
                eval('document.post.addqfcode'+qfnumber+'.value += "*"');
                txtarea.focus();
                return;
        }
        storeCaret(txtarea);
}

// From http://www.massless.org/mozedit/
function mozWrap(txtarea, open, close)
{
        var selLength = txtarea.textLength;
        var selStart = txtarea.selectionStart;
        var selEnd = txtarea.selectionEnd;
        if (selEnd == 1 || selEnd == 2)
                selEnd = selLength;

        var s1 = (txtarea.value).substring(0,selStart);
        var s2 = (txtarea.value).substring(selStart, selEnd)
        var s3 = (txtarea.value).substring(selEnd, selLength);
        txtarea.value = s1 + open + s2 + close + s3;
        return;
}

// Insert at Claret position. Code from
// http://www.faqts.com/knowledge_base/view.phtml/aid/1052/fid/130
function storeCaret(textEl) {
        if (textEl.createTextRange) textEl.caretPos = document.selection.createRange().duplicate();
}

//-->
