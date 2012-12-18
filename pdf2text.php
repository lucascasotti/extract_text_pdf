<?php

class Pdf2Text
{

    private $_bagHeader     = "";
    private $_bagTrailer    = "";
    private $_bagBody       = "";
    private $_aryObjects    = null;
    private $_fileName      = "";
    private $_fileBuffer    = "";
    private $_fileHandle    = 0;
    private $page           = 0;
    private $encrypt        = false;
    private $errorPage      = false;
    private $obj_pages      = array();
    private $pos_id         = array();
    private $kids           = array();
    private $patternPage    = "/\/Page\W/";
    private $patternKids    = "/\/Kids\W?\[(\s?(\d+\s{0,1}\d+)\s{0,1}R\s?)+\]/";
    private $patternKidsNum = "/(\d+\s{0,1}\d+)\s{0,1}R/";
    private $patternPages   = "/\/Pages\s{0,1}(\d+\s{0,1}\d+)\s{0,1}R/";
    private $patternHeader  = "/^%PDF-(\d+\.\d+)/";
    private $patternObject  = "/^(\d+ \d+) obj\s*(<<.*>>)*(stream)*/";
    private $patternTrailer = "/^(trailer)$/";
    private $patternXref    = "/^(xref)$/";

    public function __construct($filename)
    {
        // Without this setting, trying to parse Windows created PDF's on Mac/Unix
        // or vice versa will not work correctly.
        ini_set('auto_detect_line_endings', true);

        // We are going to suppress errors while executing certain code statements
        // but we still want to preserve any errors for output.
        ini_set('track_errors', true);


        $this->_seenHeader  = false;
        $this->_seenTrailer = false;
    
        $this->_fileName   = $filename;
        $this->_fileBuffer = "";
    
        $this->_fileHandle = @fopen($filename, "r");
        if ($this->_fileHandle) {
            if($this->_getPagesObj()){

                if (1 == preg_match($this->patternHeader, trim($this->_fileBuffer), $matches) 
                    && !$this->_seenHeader)
                    $this->_processPDFHeader($matches);
                
                if(0 != preg_match_all("/(\d+ \d+) obj/", trim($this->_fileBuffer), $matches))
                    $this->_processPDFBody($matches[1]);

                if (1 == preg_match($this->patternTrailer, trim($this->_fileBuffer), $matches) 
                    && !$this->_seenTrailer)
                    $this->_processPDFTrailer($matches);
                
                fclose($this->_fileHandle);
            }
        }
    } 
    
    public function getHeader()       
    {
        return $this->_bagHeader;    
    }
    
    public function getTrailer()      
    { 
        return $this->_bagTrailer;   
    }
    
    public function getBody()     
    { 
        return $this->_bagBody;      
    }

    public function getEncrypt()     
    { 
        return $this->encrypt;      
    }

    public function getErrorPages()     
    { 
        return $this->errorPage;      
    }

    private function _getPagesObj(){
        $v = 1500;
        $i = 2;
        fseek($this->_fileHandle, -$v, SEEK_END);
        $bufferPage = fread($this->_fileHandle, 1500);

        if($this->encrypt = preg_match("/\/Encrypt/", $bufferPage) ? true : false){
            fclose($this->_fileHandle);
            return FALSE;
        }

        while(!preg_match_all($this->patternKids, $bufferPage, $this->kids)){
            if($this->encrypt = preg_match("/\/Encrypt/", $bufferPage) ? true : false || $fim = preg_match("/^%PDF-(\d+\.\d+)$/", $bufferPage)){
                break;
            }
            fseek($this->_fileHandle, -$v*$i, SEEK_END);
            $bufferPage .= fread($this->_fileHandle, $v);    
            $i++;
        }

        if($this->encrypt ||($this->errorPage = $fim == 1 && $this->kids ? true : false)){
            fclose($this->_fileHandle);
            return FALSE;
        }

        foreach ($this->kids[0] as $key => $value) {
            preg_match_all($this->patternKidsNum, $value, $getKidsId);
            $this->kids[$key] = $getKidsId[1];
        }

        $this->pos_id = $this->getPosKidsElements($this->kids, $bufferPage);
        $this->verifyIdKidIsPage($bufferPage);
        
        //Limpa as variaveis
        unset($this->pos_id, $bufferPage, $v, $i, $getKidsId);

        //Volta para o início do arquivo
        fseek($this->_fileHandle, 0);

        //Le a primeira linha do arquivo
        $this->_fileBuffer = fgets($this->_fileHandle);

        //Le o arquivo até o fim e armazena no $bufferPage
        while(!feof($this->_fileHandle)){
            $this->_fileBuffer .= fread($this->_fileHandle, 100);
        }

        $i = 0;
        foreach ($this->kids as $posicao => $kid) {
            if($i == 0){
                $kids[0] = array_merge($this->kids[$posicao],$this->kids[$posicao+1]);
                $i++;
            }else{
                if(array_key_exists($posicao+1, $this->kids)){
                    $kids[0] = array_merge($kids[0],$this->kids[$posicao+1]);
                }
            }
        }
        $this->kids = $kids[0];

        //Limpa as variaveis que não vão mais ser utilizadas
        unset($i, $kid, $posicao, $kids);
        
        //Define a posição dos elementos Page
        $this->pos_id = $this->getPosKidsElements($this->kids, $this->_fileBuffer);

        foreach ($this->pos_id as $key => $pos) {

            $data = substr($this->_fileBuffer, $pos);
            $data = substr($data, strpos($data, '/Contents'));
            preg_match("/[]>]/", $data, $q, PREG_OFFSET_CAPTURE);
            $data = substr($data, strpos($data, '/Contents'), $q[0][1]);

            preg_match_all($this->patternKidsNum, $data, $obj);

            if(array_key_exists($key+1, $this->obj_pages)){
                $this->obj_pages[$key+1] .=  $obj[1];
            }else{
                $this->obj_pages[$key+1] =  $obj[1];
            }
        }

        //Limpa as variaveis que não vão mais ser utilizadas
        unset($data, $key, $pos, $this->pos_id, $obj, $this->kids);

        //Volta para o início do arquivo
        //fseek($this->_fileHandle, 0);

        return TRUE;
    }

    private function getPosKidsElements(array $kids, $bufferPage){

        foreach ($kids as $kid) {
            if(is_array($kid)){
                foreach ($kid as $k) {
                    $pregmatch = "/[\n|\r]".$k." obj/";
                    if(preg_match($pregmatch, $bufferPage, $pos, PREG_OFFSET_CAPTURE)){
                        $pos_id[] = $pos[0][1];
                    }
                }
            }else{
                $pregmatch = "/[\n|\r]".$kid." obj/";
                if(preg_match($pregmatch, $bufferPage, $pos, PREG_OFFSET_CAPTURE)){
                    $pos_id[] = $pos[0][1];
                }
            }
        }
        return array_unique($pos_id);
    }

    private function verifyIdKidIsPage($bufferPage){

        $data = substr($bufferPage, $this->pos_id[0]);
        $data = substr($data, 0, strpos($data, 'endobj'));

        if(!preg_match("/\/Contents/", $data)){
            unset($this->kids[0]);
        }
    }

    private function _processPDFHeader($matches)
    {
        //echo "\nPDF Header\n";
        $this->_bagHeader->header  = $matches[0]; // matched line
        $this->_bagHeader->version = $matches[1]; // version part only
        $this->_seenHeader = true;
    }

    private function _processPDFBody(array $matches)
    {
        //echo "\nPDF Body\n";
        $bufferPage = $this->_fileBuffer;

        foreach ($matches as  $key) {

            if(isset($contents)){
                unset($contents);
            }

            $pregmatch = "/[\n|\r]".$key." obj/";

            if(1 == preg_match($pregmatch, $bufferPage, $pos, PREG_OFFSET_CAPTURE)){

                $bufferPage = substr($bufferPage, $pos[0][1]);

                $this->page = "";

                foreach ($this->obj_pages as $pagina => $value) {

                    if(in_array($key, $value))
                        $this->page = $pagina;
                }

                $dictionary   = "";
                $stream       = "";
                $contents     = "";
                $probableText = false;
                
                $pos = null;
                unset($pos);
                if(preg_match("/endobj/", $bufferPage, $pos, PREG_OFFSET_CAPTURE))
                    $contents = substr($bufferPage, 0, $pos[0][1]);

                unset($pos);
                
                //echo "contents:: " . htmlentities($contents) . "\n";
                
                $startIdx = strpos($contents, "<<", 0);
                $stopIdx  = strpos($contents, ">>", $startIdx) + 2;
              
                // Parse out object metadata, if it exists
                if ($startIdx !== false && $stopIdx !== false)
                    $dictionary = substr($contents, $startIdx, $stopIdx - $startIdx);
                
                $startIdx = strpos($contents, "stream", 0) + strlen("stream");
                $stopIdx  = strpos($contents, "endstream", 0);
                
                // Determine if the object contains an embedded stream object
                if ($startIdx !== false && $stopIdx !== false)
                    $stream = substr($contents, $startIdx, $stopIdx - $startIdx);

                // Object does not contain an embedded stream object        
                if ($stream != "") {
                    $contents   = $this->_getStreamData($dictionary, $stream);

                    // This heuristic assumes that if the decoded contents are regular
                    // readable text then within the first 26 characters we would expect
                    // to see at least one space character.
                    if ($contents != "" && 
                        strpos(substr($contents, 0, 26), " ") !== false)
                      $probableText = true;
                    else
                      $probableText = false;
                } else {
                    // Object contains an embedded stream object so we want to eliminate
                    // any non-text containing objects such as images
                    $contents = $this->_getStreamEmbeddedData(
                        substr($contents, strlen($dictionary)), false
                    );
                    $probableText = !($contents === false && $this->_bagTrailer->encrypt === false);
                }
                
                $this->_aryObjects[] = array(
                    "key"          => $key,
                    "page"         => $this->page, 
                    "dictionary"   => $dictionary, 
                    "stream"       => $stream,
                    "contents"     => $contents,
                    "probableText" => $probableText
                );
            
                $this->_bagBody->objects = $this->_aryObjects;
            }
        }
    }

    private function _processPDFTrailer($matches)
    {
        //echo "\nPDF Trailer\n";
        
        $contents = $this->_readToEndOfBlock("/^(%%EOF)$/");
        
        $startIdx = strpos($contents, "<<", 0);
        $stopIdx  = strpos($contents, ">>", 0) + strlen(">>");
        
        $this->_bagTrailer->dictionary = substr(
            $contents, $startIdx, $stopIdx - $startIdx
        );
    
        $patternId = "/\/ID\s{0,1}\[\s{0,1}<(\d|\w+)>\s{0,1}<(\d|\w+)>\s{0,1}\]/";
    
        if (1 == preg_match($patternId, $this->_bagTrailer->dictionary, $matches)) {
            $this->_bagTrailer->id1 = $matches[1];
            $this->_bagTrailer->id2 = $matches[2];
        }
    
        $patternRoot   = "/\/Root\s{0,1}(\d+\s{0,1}\d+)\s{0,1}R/";
    
        if (1 == preg_match($patternRoot, $this->_bagTrailer->dictionary, $matches))
            $this->_bagTrailer->root = $matches[1];
    
        $patternInfo   = "/\/Info\s{0,1}(\d+\s{0,1}\d+)\s{0,1}R/";
    
        if (1 == preg_match($patternInfo, $this->_bagTrailer->dictionary, $matches))
            $this->_bagTrailer->info = $matches[1];
        
        $patternSize   = "/\/Size\s{0,1}(\d+)\s{0,1}/";
        
        if (1 == preg_match($patternSize, $this->_bagTrailer->dictionary, $matches))
            $this->_bagTrailer->size = $matches[1];

        $patternPrev   = "/\/Prev\s{0,1}(\d+)\s{0,1}/";
        
        if (1 == preg_match($patternPrev, $this->_bagTrailer->dictionary, $matches))
            $this->_bagTrailer->prev = $matches[1];

        $patternEncrypt = "/\/Encrypt/";
        
        if (1 == preg_match($patternEncrypt, $contents, $matches))
            $this->_bagTrailer->encrypt = true;

        $patternStartXref = "/startxref\s*(\d+)\s*%%EOF/";
        
        if (1 == preg_match($patternStartXref, $contents, $matches))
            $this->_bagTrailer->startXref = $matches[1];

        $this->_seenTrailer = true;
    }

    private function _getStreamData($header, $data)
    {
        // Non-text stream objects such as images
        if (strpos($header, "/Device", 0) !== false ||
            strpos($header, "/Image", 0) !== false ||
            strpos($header, "/Metadata", 0) !== false ||
            strpos($header, "/ColorSpace", 0) !== false) 
            return false; // "** Device|Image|Metadata **"

        // Encodings we are not able to parse yet   
        if (strpos($header, "/ASCIIHexDecode", 0) !== false ||
            strpos($header, "/ASCII85Decode", 0) !== false ||
            strpos($header, "/LZWDecode", 0) !== false ||
            strpos($header, "/RunLengthDecode", 0) !== false ||
            strpos($header, "/CCITTFaxDecode", 0) !== false ||
            strpos($header, "/DCTDecode", 0) !== false)
            return false; // "** Unhandled Encoding **"
                        
        // Filter out PDF hint tables
        if (1 == preg_match("/\/[LS]\s{0,1}\d+/", $header, $matches)) {
            return false; //"** HINT TABLE **";
        }
        
        // Filter out font program information
        if (1 == preg_match("/\/Length[123]\s{0,1}\d+/", $header, $matches) ||
            1 == preg_match("/\/Subtype\/Type1C/", $header, $matches) ||
            1 == preg_match("/\/Subtype\/CIDFontType0C/", $header, $matches))
            return false; //"** FONT PROGRAM **";  

        // PDF is encrypted
        if ($this->encrypt == true)
            return false; //"** ENCRYPTED **";

        if (strpos($header, "/FlateDecode", 0) === false) {
            // Stream is plain text
            return $this->_getStreamEmbeddedData($data, false);
        } else {
            // Stream is compressed text using zlib (i.e. FlateDecode) 
            $startPos = 1;  // Assume one line stream tag separator
            $endPos = 1;    // Assume one line endstream tag separator
            $length = 0;

            // Check for Carriage Return + Line Feed after stream tag
            if (substr($data, 0, 1) == "\r" && substr($data, 1, 1) == "\n")
                $startPos = 2;

            // Check for Carriage Return + Line Feed before endstream tag
            if (substr($data, -2, 1) == "\r" && substr($data, -1, 1) == "\n")
                $endPos = 2;    

            // If the length is an indirect object reference that has a format
            // like "/Length # # R" where # # is the key of the referenced
            // object whose contents contain the stream length value. In this 
            // case we will just take the shortcut evaluation of the length using
            // stream/endstream positions and removing the leading separators. NOTE:
            // there does not appear to be trailing separators used here so we are
            // ignoring the endpos calculation in the length.
            if (1 == preg_match("/\/Length\s{0,1}\d+\s{0,1}\d+\s{0,1}R/", $header, $matches)) {
                $length = strlen($data) - $startPos;
            } else {
                // A direct length value is stored in the header and has a format
                // like: "/Length #"
                preg_match("/\/Length\s{0,1}(\d+)/", $header, $matches);
                $length = $matches[1];
            }

            $php_errormsg = "";
            $contents = @gzuncompress(substr($data, $startPos, $length));

            if ($php_errormsg != "") {
                echo "Warning: " . htmlentities($php_errormsg) . " in " 
                    . htmlentities(__FILE__) . " near line " 
                    . htmlentities(__LINE__) . "\n";
                //echo "DEBUG: Header  : " . htmlentities($header) . "\n";
                //echo "DEBUG: Length  : " . htmlentities($length) . "\n";
                //echo "DEBUG: StartPos: " . htmlentities($startPos) . "\n";
                //echo "DEBUG: EndPos  : " . htmlentities($endPos) . "\n";
            }

            //echo "DEBUG:Uncompressed Contents: " . htmlentities($contents) . "\n\n";

            return $this->_getStreamEmbeddedData($contents, true);
        }
    }

    private function _getStreamEmbeddedData($data, $wasCompressed = true)
    {
        $char      = "";
        $paren     = false;
        $results   = "";
        $tjFollows = false;

        // Parentheses are used to delimit text streams, but those text
        // streams may also contain embedded parentheses. We want to
        // replace the embedded parenteses with brackets so our match
        // expression is not affected.
        $data = str_replace("\(", "[", $data);
        $data = str_replace("\)", "]", $data);

        //echo "DEBUG: Stream: " . htmlentities($data) . "\n";

        $reg = "/(\([^()]+\))/im";

        if (0 < preg_match_all($reg, $data, $matches)) {
            foreach ($matches[0] as $entry) {
                //echo "DEBUG: Match: " . htmlentities($entry) . "\n";
                $results .= substr($entry, 1, -1);
            }
        }

        //echo "DEBUG:Stream Embedded Data: {{" . htmlentities($results) . "}}\n\n";

        return $results;
    }
}


class PDFtext
{

    protected $file_name;
    protected $return = array();

    public function __construct(){}

    public function setPdf($file_name)
    {
        $this->file_name = (string)$file_name;
    }


    public function getPdf()
    {
        $object = new Pdf2Text($this->file_name);

        if($object->getErrorPages() || $object->getEncrypt()){
            return false;
        }else{
            $body = $object->getBody();

            $i = 0;
            foreach ($body->objects as $obj)
            {
                if(($obj['probableText']) && ($obj['contents'] != "" && !is_null($obj['contents']) && $obj['page'])){
                    if($obj['page'] == $i && array_key_exists($obj['page'], $this->return)){
                        $this->return[$obj['page']] .= $obj['contents'];
                    }else{
                        $this->return[$obj['page']] = $obj['contents'];
                        $i = $obj['page'];
                    }
                }
            }
            return $this->return;
        }
    }
}