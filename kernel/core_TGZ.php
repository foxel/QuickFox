<?php

// Simple InUSE Check
if ( !defined('QF_STARTED') )
        die('Hacking attempt');

if ( defined('CORE_TGZ_LOADED') )
        die('Scripting error');

define('CORE_TGZ_LOADED', True);

// ----------------------------------------------------------- \\
//            TGZ/TAR Archive Reader/Writer   (c) LION 2006    \\
// ----------------------------------------------------------- \\

class TGZRead
{
         var $stream, $TARFile;
         var $GZip = false;        // TGZ Flag
         var $buffer = '';         // Read Buffer
         var $block = '';          // Block Buffer (512 bytes)
         var $bpointer = 0;        // InBlock Reading pointer
         var $EOF = False;         // EOF Flag
         var $CurFInfo = Array();  // File header info table

        // Constructor. Creates up the stream
        // Analyzes archive type
        function TGZRead($filename)
        {
                $this->TARFile = $filename;
                if (!file_exists($filename)) return False;
                $fileinf = pathinfo($this->TARFile);
                $ext = strtolower($fileinf['extension']);
                if ($ext=='tgz' && extension_loaded('zlib')) {
                    $this->stream = gzopen($filename, 'rb');
                    $this->GZip = true;
                }
                elseif ($ext=='tar') {
                    $this->stream = fopen($filename, 'rb');
                }
                else
                    return false;

                if ($this->stream)
                    return true;
                else
                    return false;
        }

        function close()
        {
                if ($this->GZip) {
                    gzclose($this->stream);
                }
                else {
                    fclose($this->stream);
                }
        }

        // Reads One block to block buffer
        function ReadBlock()
        {
                if (!$this->stream) return false;
                $this->block='';
                while (strlen($this->buffer)<512 && !$this->EOF) {
                    $need = 512 - strlen($this->buffer);
                    if ($this->GZip) {
                        $this->EOF=gzeof($this->stream);
                        if (!$this->EOF) $this->buffer.=gzread($this->stream,$need);
                    }
                    else {
                        $this->EOF=feof($this->stream);
                        if (!$this->EOF) $this->buffer.=fread($this->stream,$need);
                    }
                }
                $this->block = substr($this->buffer,0,512);
                $this->buffer = substr($this->buffer,512);
                $this->bpointer = 0;
        }

        // Seeks up $count blocks
        function SeekBlocks($count)
        {
                if (!$this->stream) return false;
                For ($i=0; $i<$count; $i++){
                    $this->ReadBlock();
                }
        }

        // Extracts archived file content to external file
        function FExtractCont($filename, $length)
        {
                if (!$this->stream) return false;
                if (empty($filename)) return False;
                $outp = @fopen($filename, 'wb');
                if (!$outp) {
                    $this->CurFInfo['error'].="Output file write Failed\n";
                    return false;
                }
                While ($length>0 && !$this->EOF){
                    $this->ReadBlock();
                    $towrite=($length<512) ? $length : 512;
                    fwrite($outp, substr($this->block,0,$towrite));
                    $length -= 512;
                }
                fclose($outp);
                return True;
        }

        // Extracts archived file content as string data
        function FGetCont($length)
        {
                if (!$this->stream) return false;
                $content='';
                While ($length>0 && !$this->EOF){
                    $this->ReadBlock();
                    $towrite=($length<512) ? $length : 512;
                    $content.=substr($this->block,0,$towrite);
                    $length -= 512;
                }
                return $content;
        }

        // Reinit file pointer and all tha data
        function ResetStream()
        {
                if (!$this->stream) return false;
                if ($this->GZip) {
                    gzseek($this->stream,0);
                }
                else {
                    if (!$this->EOF) $this->buffer.=fseek($this->stream,0);
                }

                $this->EOF=False;
                $this->block='';
                $this->buffer='';
                $this->bpointer=0;
                $this->CurFInfo = Array();
        }

        // InBlock reading function
        function BlockRead($len)
        {
                if (!$this->stream) return false;
                $out = substr($this->block,$this->bpointer,$len);
                $this->bpointer += $len;
                return $out;
        }

        // Counts CRC for TAR fileheader record
        // We must replace CRC field with 0x20 symbols to count our own CRC
        // So we take $rawRemove field (that must contain already readed CRC field)
        // and sustract all bytes from our CRC adding 0x20 for each one
        function FInfoCRC($rawRemove='')
        {
                $CRC=0;
                for ($i=0; $i<512; $i++)
                    $CRC += ord(substr($this->block,$i,1));
                $remlen=strlen($rawRemove);
                for ($i=0; $i<$remlen; $i++)
                    $CRC += -ord(substr($rawRemove,$i,1))+32;
                return $CRC;
        }

        // Parses TAR fileheader record data and checks it
        function ParseFInfoBlock()
        {
                $this->CurFInfo = Array();
                if (strlen($this->block)<512) return false;

                $this->CurFInfo['error']='';
                $this->CurFInfo['name']=trim($this->BlockRead(100));
                $this->CurFInfo['mode']=octdec(trim($this->BlockRead(8)));
                $this->CurFInfo['uid']=trim($this->BlockRead(8));
                $this->CurFInfo['gid']=trim($this->BlockRead(8));
                $this->CurFInfo['size']=octdec(trim($this->BlockRead(12)));
                $this->CurFInfo['time']=octdec(trim($this->BlockRead(12)));
                $rawCRC = $this->BlockRead(8);
                $myCRC = $this->FInfoCRC($rawCRC);
                $this->CurFInfo['chsum']=octdec(trim($rawCRC));
                $this->CurFInfo['type']=intval(trim($this->BlockRead(1)));
                $this->CurFInfo['linkname']=trim($this->BlockRead(100));
                $this->CurFInfo['magic']=trim($this->BlockRead(8));
                $this->CurFInfo['uname']=trim($this->BlockRead(32));
                $this->CurFInfo['gname']=trim($this->BlockRead(32));
                $this->CurFInfo['devmajor']=trim($this->BlockRead(8));
                $this->CurFInfo['devminor']=trim($this->BlockRead(8));
                $this->CurFInfo['atime']=octdec(trim($this->BlockRead(12)));
                $this->CurFInfo['ctime']=octdec(trim($this->BlockRead(12)));
                $this->CurFInfo['offset']=octdec(trim($this->BlockRead(12)));
                $this->CurFInfo['longnames']=trim($this->BlockRead(4));

                $this->CurFInfo['fblocks']=ceil($this->CurFInfo['size']/512);

                if ($this->CurFInfo['chsum']!=$myCRC) $this->CurFInfo['error'].="Header record CRC Error\n";
                if ($this->CurFInfo['name']=='') $this->EOF=True;

        }

        // Parses trought all archive data, printing or extracting it
        function ExtractArchive($dir='', $replace=False)
        {
                if (!$this->stream) return false;
                $this->ResetStream();
                if (!file_exists($dir) && !empty($dir)) mkdir($dir);
                if (!empty($dir)) $dir.='/';
                $idx=0;
                While (!$this->EOF) {
                    $this->ReadBlock();
                    $this->ParseFInfoBlock();
                    if (!empty($this->CurFInfo['name'])) {
                        $fname=$dir . $this->CurFInfo['name'];
                        Switch ($this->CurFInfo['type'])
                        {
                            case 0:
                                if (!file_exists($fname) || $replace) {
                                    $done = $this->FExtractCont($fname, $this->CurFInfo['size']);
                                    if ($done) {
                                        chmod($fname,$this->CurFInfo['mode']);
                                        touch($fname,$this->CurFInfo['time']);
                                    }
                                    else $this->SeekBlocks($this->CurFInfo['fblocks']);
                                }
                                else $this->SeekBlocks($this->CurFInfo['fblocks']);
                            break;
                            case 5:
                                if(!file_exists($fname))
                                    mkdir($fname);
                                chmod($fname,$this->CurFInfo['mode']);
                            break;
                            default:
                                $this->SeekBlocks($this->CurFInfo['fblocks']);
                        }
                        $idx++;
                    }
                }
        }

        // Returns indexed Content table for the arcgive
        function GetContent()
        {
                if (!$this->stream) return false;
                $this->ResetStream();
                $Content=Array();
                $idx=0;
                While (!$this->EOF) {
                    $this->ReadBlock();
                    $this->ParseFInfoBlock();
                    if (!empty($this->CurFInfo['name'])) {
                        $Content[$idx++]=$this->CurFInfo;
                        $this->SeekBlocks($this->CurFInfo['fblocks']);
                    }
                }
                return $Content;
        }

        // Returns archived file content by it's index
        function GetFile($FileId, $to_file='')
        {
                if (!$this->stream) return false;
                $this->ResetStream();
                $idx=0;
                While (!$this->EOF) {
                    $this->ReadBlock();
                    $this->ParseFInfoBlock();
                    if (!empty($this->CurFInfo['name'])) {
                        if ($idx==$FileId) {
                            if (strlen($to_file))
                                return $this->FExtractCont($to_file, $this->CurFInfo['size']);
                            else
                                return $this->FGetCont($this->CurFInfo['size']);
                        }
                        else $this->SeekBlocks($this->CurFInfo['fblocks']);
                        $idx++;
                    }
                }
                return false;
        }

}


class TGZWrite
{
         var $stream, $TARFile;
         var $GZip = false;        // TGZ Flag
         var $block = '';          // Block Buffer (512 bytes)
         var $bpointer = 0;        // InBlock Reading pointer
         var $EOF = False;         // EOF Flag
         var $CurFInfo = Array();  // File header info table
         var $error = '';          // Error data string
         var $Working_Dir = '';    // archive root directory
         var $content = Array();   // added data logging
         var $masker = '';         // external files masking function

        // Constructor. Creates up the stream
        // Sets archive type
        // Extension must set the format
        // Use 'tgz' or 'tar' only
        function TGZWrite($filename, $workdir='', $masker='')
        {
                if (function_exists($masker))
                    $this->masker = $masker;

                $this->TARFile = $filename;
                if ($workdir!='' && file_exists($workdir))
                    $this->Working_Dir = $this->DirNameCorrect($workdir);
                else
                    $this->Working_Dir='./';

                $fileinf = pathinfo($this->TARFile);
                $ext = strtolower($fileinf['extension']);
                if ($ext=='tgz' && extension_loaded('zlib')) {
                    $this->stream = gzopen($filename, 'wb9');
                    $this->GZip = true;
                }
                elseif ($ext=='tar') {
                    $this->stream = fopen($filename, 'wb');
                }
                else
                    return false;

                if ($this->stream)
                    return true;
                else
                    return false;
        }

        function Close()
        {                $this->block='';
                $this->WriteBlock();

                if ($this->GZip) {
                    gzclose($this->stream);
                }
                else {
                    fclose($this->stream);
                }
        }

        function GetDirTree($name)
        {                $tree = Array();
                $len=strlen($name);
                for ($i=0;$i<$len;$i++) {
                    if (substr($name,$i,1)=='/')
                        $tree[]=substr($name,0,$i+1);
                }
                return $tree;
        }

        function DirNameCorrect($name)
        {                $len=strlen($name);
                if ($len<=0) return '';
                for ($i=$len-1;$i>=0;$i--) {                    if (substr($name,$i,1)!='/')
                        return substr($name,0,$i+1).'/';
                }
                return '';
        }

        // Writes One block from block buffer
        function WriteBlock()
        {
                if (!$this->stream) return false;
                $len=strlen($this->block);

                if ($len < 512)
                    $this->block.=str_repeat(chr(0),512-$len);
                elseif ($len > 512)
                    $this->block=substr($this->block,0,512);

                if ($this->GZip) {
                    gzwrite($this->stream,$this->block);
                }
                else {
                    fwrite($this->stream,$this->block);
                }
                $this->block='';
                return True;
        }

        // Adds data from file to archive
        function FAddFile($filename, $length)
        {
                if (!$this->stream) return false;
                if (empty($filename)) return False;
                if (is_dir($filename)) return true;
                $inp = @fopen($this->Working_Dir.$filename, 'rb');
                if (!$inp) {
                    $this->error.="Input file read Failed\n";
                    return false;
                }
                While ($length>0 && !feof($inp)){
                    $this->block=fread($inp, 512);
                    $this->WriteBlock();
                    $length -= 512;
                }
                fclose($inp);
                return True;
        }

        // Adds data to archive
        function FAddCont($content)
        {
                if (!$this->stream) return false;
                $length=strlen($content);
                $pos=0;
                While ($pos<$length){
                    $this->block=substr($content,$pos,512);
                    $this->WriteBlock();
                    $pos += 512;
                }
                return $content;
        }

        // InBlock writing function
        function BlockWrite($inp, $len)
        {
                if (!$this->stream) return false;
                if ($len>strlen($inp))
                    $inp.=str_repeat(chr(0),$len-strlen($inp));
                $this->block.=substr($inp,0,$len);
        }

        // Counts CRC for TAR fileheader record
        // We must replace CRC field with 0x20 symbols to count our own CRC
        // So we take $rawRemove field (that must contain already readed CRC field)
        // and sustract all bytes from our CRC adding 0x20 for each one
        function FInfoCRC($header)
        {
                $header['chsum']=str_repeat(' ',8);
                $CRC=0;
                foreach ($header as $value) {
                    $len=strlen($value);
                    for ($i=0;$i<$len;$i++)
                        $CRC += ord(substr($value,$i,1));
                }
                return $CRC;
        }

        // Generate TAR fileheader record data for file
        // DataType:
        // 0 - File From Disk
        // 1 - File From String
        // 2 - Make New Dir
        function CreateFInfoBlock($filename, $chmod='', $datatype=0, $length=0)
        {
                switch ($datatype)
                {
                case 2:    // MKDir
	                if (!preg_match('#^[0-7]{1,3}$#',$chmod))
	                    $chmod = '755';
                    $filename = $this->DirNameCorrect($filename);

	                $size=0;

	                $time=decoct($timer->time);

	                $type='5';
	            break;
                case 1:     // String
	                if ($length<=0) return False;

	                if (!preg_match('#^[0-7]{1,3}$#',$chmod))
	                    $chmod = '644';

	                $size=decoct($length);

	                $time=decoct($timer->time);

	                $type='0';
	            break;
	            default:                    if (!file_exists($this->Working_Dir.$filename)) return false;

                    $isdir=is_dir($this->Working_Dir.$filename);
                    if ($isdir)
                        $filename = $this->DirNameCorrect($filename);

	                if (!preg_match('#^[0-7]{1,3}$#',$chmod))	                    $chmod = decoct(fileperms($this->Working_Dir.$filename));

	                $size=($isdir) ? '0' : decoct(filesize($this->Working_Dir.$filename));

	                $time=decoct(filemtime($this->Working_Dir.$filename));

	                $type=($isdir) ? '5' : '0';
                }

	            $chmod=str_repeat('0',8-2-strlen($chmod)).$chmod.' '.chr(0);
	            $size=str_repeat('0',12-1-strlen($size)).$size.' ';
	            $time=str_repeat('0',12-1-strlen($time)).$time.' ';

                $header=Array();
                $header['name']=$filename;
                $header['mode']=$chmod;
                $header['uid']='';
                $header['gid']='';
                $header['size']=$size;
                $header['time']=$time;
                $header['chsum']=str_repeat(' ',8);
                $header['type']=$type;
                $header['linkname']='';
                $header['magic']='ustar  ';
                $header['uname']='';
                $header['gname']='';
                $header['devmajor']='';
                $header['devminor']='';
                $header['atime']='';
                $header['ctime']='';
                $header['offset']='';
                $header['longnames']='';

                $CRC=decoct($this->FInfoCRC($header));
                $CRC=str_repeat('0',8-1-strlen($CRC)).$CRC.' ';
                $header['chsum']=$CRC;

                $this->block='';
                $this->BlockWrite($header['name'],100);
                $this->BlockWrite($header['mode'],8);
                $this->BlockWrite($header['uid'],8);
                $this->BlockWrite($header['gid'],8);
                $this->BlockWrite($header['size'],12);
                $this->BlockWrite($header['time'],12);
                $this->BlockWrite($header['chsum'],8);
                $this->BlockWrite($header['type'],1);
                $this->BlockWrite($header['linkname'],100);
                $this->BlockWrite($header['magic'],8);
                $this->BlockWrite($header['uname'],32);
                $this->BlockWrite($header['gname'],32);
                $this->BlockWrite($header['devmajor'],8);
                $this->BlockWrite($header['devminor'],8);
                $this->BlockWrite($header['atime'],12);
                $this->BlockWrite($header['ctime'],12);
                $this->BlockWrite($header['offset'],12);
                $this->BlockWrite($header['longnames'],4);

        }


        // Writes data to archive from a string
        // In the archive there will be a file named [$filename]
        function AddStringData($inp, $filename, $chmod='', $dchmod='')
        {
                if (!$this->stream) return false;
                if ($filename=='') return False;
                if (in_array($filename,$this->content)) return True;
                $tree = $this->GetDirTree($filename);
                foreach ($tree as $dir)
                    if (!in_array($dir,$this->content)) {
                        $this->CreateFInfoBlock($dir, $dchmod, 2);
                        $this->WriteBlock();
                        $this->content[]=$dir;
                    }
                $this->CreateFInfoBlock($filename, $chmod, 1, strlen($inp));
                $this->content[]=$filename;
                if ($this->WriteBlock())
                    return $this->FAddCont($inp);
                else
                    return False;
        }

        // Writes data to archive from a file
        function AddFileData($filename, $chmod='', $dchmod='')
        {
                if (!$this->stream) return false;
                if ($filename=='') return False;

                if (!file_exists($this->Working_Dir.$filename))
                    return False;
                $tree = $this->GetDirTree($filename);
                foreach ($tree as $dir)
                    if (!in_array($dir, $this->content)) {
                        $this->CreateFInfoBlock($dir, $dchmod);
                        $this->WriteBlock();
                        $this->content[]=$dir;
                    }

                if (in_array($filename,$this->content)) return True;

                $masker=$this->masker;
                if ($masker)
                    if (function_exists($masker))
                        if (!$masker($this->Working_Dir.$filename))
                            return true;

                $this->CreateFInfoBlock($filename, $chmod);
                $this->content[]=$filename;
                if ($this->WriteBlock())
                    return $this->FAddFile($filename, filesize($this->Working_Dir.$filename));
                else
                    return False;
        }

        // Add New directiory into archive
        function AddDir($filename, $chmod='')
        {
                if (!$this->stream) return false;
                if ($filename=='') return False;
                $tree = $this->GetDirTree($filename);
                foreach ($tree as $dir)
                    if (!in_array($dir,$this->content)) {
                        $this->CreateFInfoBlock($dir, $chmod, 2);
                        $this->WriteBlock();
                        $this->content[]=$dir;
                    }
                return True;
        }

        function ArchiveDir($dir, $mask='', $dchmod='', $fchmod='', $subdirs=True, $masker='')
        {
            $mask=strtolower($mask);
            $dir=$this->DirNameCorrect($dir);
            $odir=opendir($this->Working_Dir.$dir);
            if ($odir) {                $this->AddFileData($dir, $dchmod, $dchmod);
                while ($file=readdir($odir)) if ($file!='.' && $file!='..') {                    if (is_dir($this->Working_Dir.$dir.$file)) {                        if ($subdirs)
                            $this->ArchiveDir($dir.$file, $mask, $dchmod, $fchmod, $subdirs);
                    }
                    else {                        $finf=pathinfo($file);
                        if (!isset($finf['extension']))
                            $this->AddFileData($dir.$file, $fchmod, $dchmod);
                        elseif ($this->CheckMask($mask, $finf['extension']))
                            $this->AddFileData($dir.$file, $fchmod, $dchmod);
                    }

                }
                closedir($odir);
            }
        }

        function CheckMask($mask, $ext)
        {            if ($mask=='')
                return True;
            elseif ($ext=='')
                return False;
            elseif (substr_count(' '.$mask.' ', ' '.strtolower($ext).' '))
                return True;
            else
                return False;
        }
}
?>
