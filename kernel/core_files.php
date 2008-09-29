<?php
// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

if ( defined('CORE_FILES_LOADED') )
        die('Scripting error');

define('CORE_FILES_LOADED', True);

class DownLoadFile{
  var $FileName;
  var $RealName;
  var $mime='';
  var $size;
  var $time;
  var $start;
  var $NeedRange;

  function DownLoadFile($FileName, $RealName='', $mime=''){ // �����������
    global $mimes;

    $this->FileName= $FileName;
    if (empty($RealName)) $this->RealName= $FileName;
    else $this->RealName= $RealName;

    if (empty($mime))
    {        $info=pathinfo($this->RealName);
        $mime=$mimes[strtolower($info['extension'])]['type'];
        if (!$mime) $mime='application/octet-stream';
    }

    $this->mime=$mime;

    $range = getenv("HTTP_RANGE");
    if(!Empty($range)){
      $this->NeedRange=true;
      $this->start=intval(substr($range,6)); //�������� bytes
    }else{
      $this->NeedRange=false;
      $this->start=0;
    }
    $this->size=-1; // -1 ������� ��� ����� ���
    if(file_exists($this->FileName)){
      $this->size=filesize($this->FileName);
      $this->time=date("D, d M Y H:i:s ", filemtime($this->FileName))."GMT";
    }

  }



// private

  function outHeaderCommon(){
    $info=pathinfo($this->RealName);
    header('Content-Transfer-Encoding: binary');
    header('Content-Type: '.$this->mime.'; charset=windows-1251');
    header('Content-Disposition: attachment; filename="'.$info['basename'].'"');
    //header('Title: '.$info['basename']);
    header('Last-modified: '.$this->time);
    header('Content-Length: '.($this->size - $this->start));
    header('Accept-Ranges: bytes');
  }

  function outHeaderForRange(){
    global $_SERVER;
         header($_SERVER["SERVER_PROTOCOL"] . ' 206 Partial Content');
         header('Content-Range: bytes '.$this->start."-".($this->size-1)."/".$this->size);
  }

  function outContent(){
    if($handle = fopen($this->FileName, 'rb')){
        $this->CalcStatistics();
        fseek($handle, $this->start);
        fpassthru($handle);
        fclose($handle);
    }else{
        $this->out403();
    }
  }


// overload

 function CalcStatistics(){
   // ���������� � ����� ����� ���������� �����
   //  ������ � �� ��� � ��������� ���� �� �����
   // ����������� � ����������� ������
 }

  function outContentType(){
    header("Content-Type: application/force-download"); //���������� ������ ��������� ����
  }

  function out404(){
     header("HTTP/1.0 404 Not Found");  // ���� ����������
    echo "404 Not Found!";
    exit;

  }

  function out403(){
    header("HTTP/1.0 403 Forbidden");  // ���� ���������, ��� � ��� 404
    echo "403 Forbidden!";
    exit;
  }



// public

  function out(){
    if($this->size>0){
       $this->outHeaderCommon();
      // $this->outContentType();
       if($this->NeedRange) $this->outHeaderForRange();
       $this->outContent();
    }else{
      $this->out404();
    }
  }


}


//Load mime types list
$result=$QF_DBase->sql_doselect('{DBKEY}mime');
// � ����� ��������� ���������� ������� � ������ $mimes
if (!empty($result)) {    while ( $cmime = $QF_DBase->sql_fetchrow($result))
        $mimes[$cmime['ext']] = $cmime;
    $QF_DBase->sql_freeresult($result);
}

?>