<?php
/**
 * Copyright 2009, Thomas Chester
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package Pdf2Text
 * @author Thomas Chester
 * @link https://launchpad.net/pdf2text Pdf2Text Project
 * @copyright Copyright 2009, Thomas Chester
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @version 1.1.0
 */

/**
 * Interface describing the types of data and metadata that
 * are available from the pdf2text object.
 * @access public
 * @package Pdf2Text
 * @author Thomas Chester
 */
interface TChester_iPDFInfo
{
    /**
     * The document's title.
     * @return string
     * @access public
     */
    public function getTitle();

    /**
     * The name of the person who created the document.
     * @return string
     * @access public
     */
    public function getAuthor();

    /**
     * The subject of the document.
     * @return string
     * @access public
     */
    public function getSubject();

    /**
     * Keywords associated with the document.
     * @return string
     * @access public
     */
    public function getKeywords();

    /**
     * The name of the application that originally created the document
     * before it was converted to PDF format.
     * @return string
     * @access public
     */
    public function getCreator();

    /**
     * The name of the application that converted the original document
     * into PDF format.
     * @return string
     * @access public
     */
    public function getProducer();

    /**
     * The human-readable date and time when the PDF was created.
     * @return string
     * @access public
     */
    public function getCreationDate();

    /**
     * The human-readable date and time of the most recent modification.
     * @return string
     * @access public
     */
    public function getModDate();

    /**
     * The textual contents of the PDF file.
     * @return string
     * @access public
     */
    public function getContents();
}

/**
 * Interface describing the types of structural data that
 * is available from the pdf2text object.
 * @access public
 * @package Pdf2Text
 * @author Thomas Chester
 */
interface TChester_iPDFStructure
{
    /**
     * Retrieves the parsed Header section of the PDF file.
     * @return string
     * @access public
     */
    public function getHeader();

    /**
     * Retrieves the parsed Trailer section of the PDF file.
     * @return string
     * @access public
     */
    public function getTrailer();

    /**
     * Retrieves the parsed Body section of the PDF file.
     * @return string
     * @access public
     */
    public function getBody();

    /**
     * Retrieves the parsed Cross-Reference section of the PDF file.
     * @return string
     * @access public
     */
    public function getXref();
}

/**
 * Class wrapper around an array of key/value pairs
 * Here is an example:
 * <code>
 * <?php
 *     $bag = new TChester_StructureBag(); // Creates new bag
 *     $bag->title = "My Title";           // Key/value "title" ==> "My Title"
 *     $title = $bag->title;               // Get "My Title" using "title" key
 *     $name = $bag->name;                 // Error - key does not exist
 * ?>
 * </code>
 * @access public
 * @package Pdf2Text
 * @author Thomas Chester
 */
class TChester_StructureBag
{
    /**
     * Internal stored is implemented as key/value pairs
     * @var array
     * @access private
     */
    private $_data = array();
    
    /**
     * Default constructor
     * @access public
     */
    public function __construct()
    {
        // Reserved for future use.
    }
    
    /**
     * Add a key/value pair to the collection, if the
     * key already exists, its value will be overwritten.
     * @param string $name Key to associate with value
     * @param mixed $value Value to store
     * @return void
     * @access public
     */
    public function __set($name, $value)
    {
        $this->_data[$name] = $value;
    }
    
    /**
     * Retrieves a value for a specified key, if the key
     * does not exist an error will be triggered.
     * @param $name Key to retrieve value for
     * @return mixed Value associated with key or null if error occurred
     * @access public
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->_data)) {
            return $this->_data[$name];
        }
        
        // An undefined array key will trigger an error
        $trace = debug_backtrace();
        trigger_error(
            "Undefined property via __get(): " . $name .
            " in " . $trace[0]['file'] . " on line " .
            $trace[0]['line'],
            E_USER_NOTICE
        );
        
        return null;
    }
    
}

/**
 * Class to extract text contents and metadata out of a document that was
 * created using the Adobe Portable Document Format.
 *
 * WARNING: The PDF 1.4 specification allows incremental updates
 * to the PDF which could result in appearance of multiple body, cross-
 * reference, and trailer sections. This class has not been tested using
 * such a PDF file and the output of the public interfaces is unknown. It
 * is assumed this incremental updating is the exception
 * rather than the norm.
 * 
 * NOTE: Encryption of PDF's is not supported, content and metadata will
 * not be available.
 *
 * Here is an example showing how to get the contents and metadata:
 * <code>
 *    $object   = new TChester_Pdf2Text("document1.pdf");
 *    if ($trailer->encrypt === false) {
 *        $contents = $object->getContent();
 *        $title    = $object->getTitle();
 *        $author   = $object->getAuthor();
 *        $subject  = $object->getSubject();
 *        $keywords = $object->getKeywords();
 *        $creator  = $object->getCreator();
 *        $producer = $object->getProducer();
 *        $created  = $object->getCreationDate();
 *        $modified = $object->getModDate();
 *    }
 * </code> 
 * @link http://www.adobe.com/devnet/pdf/pdf_reference.html PDF Reference
 * @access public
 * @package Pdf2Text
 * @author Thomas Chester
 */
class TChester_Pdf2Text implements TChester_iPDFInfo, TChester_iPDFStructure
{
    /**
     * The PDF title from the metadata
     * @var string
     * @access private
     */
    private $_title        = "";

    /**
     * The PDF author from the metadata
     * @var string
     * @access private
     */
    private $_author       = "";

    /**
     * The PDF subject from the metadata
     * @var string
     * @access private
     */
    private $_subject      = "";

    /**
     * The PDF keywords from the metadata
     * @var string
     * @access private
     */
    private $_keywords     = "";

    /**
     * The PDF keywords from the metadata of Apple generated PDF's
     * @var string
     * @access private
     */
    private $_aaplKeywords = "";

    /**
     * The PDF creator from the metadata
     * @var string
     * @access private
     */
    private $_creator      = "";

    /**
     * The PDF producer from the metadata
     * @var string
     * @access private
     */
    private $_producer     = "";

    /**
     * The PDF creation date from the metadata
     * @var string
     * @access private
     */
    private $_creationDate = "";

    /**
     * The PDF last modification date title from the metadata
     * @var string
     * @access private
     */
    private $_modDate      = "";

    /**
     * The PDF text content from the body section
     * @var string
     * @access private
     */
    private $_contents     = "";
    
    /**
     * The contents of the PDF header section
     * @var TChester_StructureBag
     * @access private
     */
    private $_bagHeader    = "";

    /**
     * The contents of the PDF trailer section
     * @var TChester_StructureBag
     * @access private
     */
    private $_bagTrailer   = "";

    /**
     * The contents of the PDF body section
     * @var TChester_StructureBag
     * @access private
     */
    private $_bagBody      = "";

    /**
     * The contents of the PDF cross reference section
     * @var TChester_StructureBag
     * @access private
     */
    private $_bagXref      = "";

    /**
     * Array of parsed objects from the PDF body. Each object is an
     * array with the following keys: 'key', 'dictionary', 'stream'
     * 'contents', 'probableText'.
     * @var array
     * @access private
     */
    private $_aryObjects   = null;

    /**
     * Array of parsed info objects from the PDF trailer. The PDF
     * specification defines the following as keys: 'Size', 'Prev',
     * 'Root', 'Encrypt', 'Info', 'ID'. 'ID' is actually treated as
     * 'ID1' and 'ID2' in the code.
     * @var array
     * @access private
     */
    private $_aryInfoKeys  = null;

    /**
     * The PDF file being parsed
     * @var string
     * @access private
     */
    private $_fileName     = "";

    /**
     * The current line read from the PDF file
     * @var integer
     * @access private
     */
    private $_fileLine     = 0;

    /**
     * Contains the current line read from the input
     * @var string
     * @access private
     * @uses _readLine() populated through this function
     */
    private $_fileBuffer   = "";

    /**
     * File handle used by PHP file processing functions
     * @var integer
     * @access private
     */
    private $_fileHandle   = 0;

     /**
     * File handle used by PHP file processing functions
     * @var integer
     * @access private
     */
    private $page   = 0;

    /**
     * File handle used by PHP file processing functions
     * @var integer
     * @access private
     */
    private $fileLength   = 0;

    /**
     * File handle used by PHP file processing functions
     * @var boolean
     * @access private
     */
    private $encrypt   = false;

    /**
     * File handle used by PHP file processing functions
     * @var boolean
     * @access private
     */
    private $errorPage   = false;

    /**
     * File handle used by PHP file processing functions
     * @var array
     * @access private
     */
    private $obj_pages   = array();

    /**
     * File handle used by PHP file processing functions
     * @var array
     * @access private
     */
    private $pos_id   = array();

    /**
     * File handle used by PHP file processing functions
     * @var array
     * @access private
     */
    private $kids  = array();

        private $patternPage           = "/\/Page\W/";
        private $patternKids           = "/\/Kids\W?\[(\s?(\d+\s{0,1}\d+)\s{0,1}R\s?)+\]/";
        private $patternKidsNum        = "/(\d+\s{0,1}\d+)\s{0,1}R/";
        private $patternPages          = "/\/Pages\s{0,1}(\d+\s{0,1}\d+)\s{0,1}R/";


    /**
     * Class constructor
     * @param string $filename Name and path of PDF file
     * @access public
     */
    public function __construct($filename)
    {
        // Without this setting, trying to parse Windows created PDF's on Mac/Unix
        // or vice versa will not work correctly.
        ini_set('auto_detect_line_endings', true);

        // We are going to suppress errors while executing certain code statements
        // but we still want to preserve any errors for output.
        ini_set('track_errors', true);

        $this->_bagHeader  = new TChester_StructureBag();
        $this->_bagTrailer = new TChester_StructureBag();
        $this->_bagBody    = new TChester_StructureBag();
        $this->_bagXref    = new TChester_StructureBag();
        
        $this->_bagHeader->type    = "header";
        $this->_bagTrailer->type   = "trailer";
        $this->_bagBody->type      = "body";
        $this->_bagXref->type      = "xref";
        
        $this->_aryObjects   = array();
        $this->_aryInfoKeys  = array();
        
        $this->_bagTrailer->size      = 0;
        $this->_bagTrailer->prev      = 0;
        $this->_bagTrailer->root      = "";
        $this->_bagTrailer->encrypt   = "";
        $this->_bagTrailer->info      = "";
        $this->_bagTrailer->id1       = "";
        $this->_bagTrailer->id2       = "";
        $this->_bagTrailer->startXref = 0;
        $this->_bagTrailer->encrypted = false;
        $this->_bagTrailer->eof       = "%%EOF";
        
        $patternHeader  = "/^%PDF-(\d+\.\d+)$/";
        $patternObject  = "/^(\d+ \d+) obj\s*(<<.*>>)*(stream)*/";
        $patternTrailer = "/^(trailer)$/";
        $patternXref    = "/^(xref)$/";
    
        $this->_seenHeader  = false;
        $this->_seenTrailer = false;
    
        $this->_fileName   = $filename;
        $this->_fileLine   = 0;
        $this->_fileBuffer = "";
    
        $this->_fileHandle = @fopen($filename, "r");
        if ($this->_fileHandle) {
            if($this->_getPagesObj()){
                while (!feof($this->_fileHandle)) {
                    $this->_readLine();
                
                    if (1 == preg_match($patternHeader, trim($this->_fileBuffer), $matches) 
                        && !$this->_seenHeader)
                        $this->_processPDFHeader($matches);
                    
                    if (1 == preg_match($patternObject, trim($this->_fileBuffer), $matches))    
                       $this->_processPDFBody($matches);
                    
                    if (1 == preg_match($patternXref, trim($this->_fileBuffer), $matches))    
                        $this->_processPDFXref($matches);

                    if (1 == preg_match($patternTrailer, trim($this->_fileBuffer), $matches) 
                        && !$this->_seenTrailer)
                        $this->_processPDFTrailer($matches);
                    
                }
                fclose($this->_fileHandle);
                
                $this->_processPDFInfoBlock();
                $this->_processContents();
            }
        }
    } 

    private function _getPagesObj(){
        $v = 1500;
        $i = 2;
        fseek($this->_fileHandle, -$v, SEEK_END);
        $bufferPage = fread($this->_fileHandle, 1500);

        if($this->encrypt = preg_match("/\/Encrypt/", $bufferPage) ? true : false){
            var_dump("FUDEU ENTRO AQUI 2");
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
            var_dump("FUDEU ENTRO AQUI");
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
        $bufferPage = fgets($this->_fileHandle);

        //Le o arquivo até o fim e armazena no $bufferPage
        while(!feof($this->_fileHandle)){
            $bufferPage .= fread($this->_fileHandle, 100);
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
        $this->pos_id = $this->getPosKidsElements($this->kids, $bufferPage);

        foreach ($this->pos_id as $key => $pos) {

            $data = substr($bufferPage, $pos);
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
        unset($data, $key, $pos, $this->pos_id, $obj, $this->kids, $bufferPage);

       //Volta para o início do arquivo
        fseek($this->_fileHandle, 0);

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

    /**
     * Supports the iPDFInfo interface
     * @return string
     * @see TChester_iPDFInfo::getTitle()
     * @access public
     */
    public function getTitle()        
    { 
        return $this->_title;        
    }
    
    /**
     * Supports the iPDFInfo interface
     * @return string
     * @see TChester_iPDFInfo::getAuthor()
     * @access public
     */
    public function getAuthor()       
    { 
        return $this->_author;       
    }
    
    /**
     * Supports the iPDFInfo interface
     * @return string
     * @see TChester_iPDFInfo::getSubject()
     * @access public
     */
    public function getSubject()      
    { 
        return $this->_subject;      
    }
    
    /**
     * Supports the iPDFInfo interface
     * @return string
     * @see TChester_iPDFInfo::getKeywords()
     * @access public
     */
    public function getKeywords()     
    { 
        return $this->_keywords;     
    }
    
    /**
     * Supports the iPDFInfo interface
     * @return string
     * @see TChester_iPDFInfo::getCreator()
     * @access public
     */
    public function getCreator()      
    { 
        return $this->_creator;      
    }
    
    /**
     * Supports the iPDFInfo interface
     * @return string
     * @see TChester_iPDFInfo::getProducer()
     * @access public
     */
    public function getProducer()     
    { 
        return $this->_producer;     
    }
    
    /**
     * Supports the iPDFInfo interface
     * @return string
     * @see TChester_iPDFInfo::getCreationDate()
     * @access public
     */
    public function getCreationDate() 
    { 
        return $this->_creationDate; 
    }
    
    /**
     * Supports the iPDFInfo interface
     * @return string
     * @see TChester_iPDFInfo::getModDate()
     * @access public
     */
    public function getModDate()      
    { 
        return $this->_modDate;      
    }
    
    /**
     * Supports the iPDFInfo interface
     * @return string
     * @see TChester_iPDFInfo::getContents()
     * @access public
     */
    public function getContents()     
    { 
        return $this->_contents;     
    }
    
    /**
     * Supports the iPDFStructure interface
     * @return string
     * @see TChester_iPDFStructure::getHeader()
     * @access public
     */
    public function getHeader()       
    {
        return $this->_bagHeader;    
    }
    
    /**
     * Supports the iPDFStructure interface
     * @return string
     * @see TChester_iPDFStructure::getTrailer()
     * @access public
     */
    public function getTrailer()      
    { 
        return $this->_bagTrailer;   
    }
    
    /**
     * Supports the iPDFStructure interface
     * @return string
     * @see TChester_iPDFStructure::getBody()
     * @access public
     */
    public function getBody()     
    { 
        return $this->_bagBody;      
    }

    /**
     * Supports the iPDFStructure interface
     * @return string
     * @access public
     */
    public function getEncrypt()     
    { 
        return $this->encrypt;      
    }

    /**
     * Supports the iPDFStructure interface
     * @return string
     * @see TChester_iPDFStructure::getBody()
     * @access public
     */
    public function getErrorPages()     
    { 
        return $this->errorPage;      
    }
    
    /**
     * Supports the iPDFStructure interface
     * @return string
     * @see TChester_iPDFStructure::getXref()
     * @access public
     */
    public function getXref()         
    { 
        return $this->_bagXref;      
    }

    /**
     * Processes the PDF file header, which consists of a single
     * line with a format like: %PDF?#.#
     * @return void
     * @access private
     */
    private function _processPDFHeader($matches)
    {
        //echo "\nPDF Header\n";
        $this->_bagHeader->header  = $matches[0]; // matched line
        $this->_bagHeader->version = $matches[1]; // version part only
        $this->_seenHeader = true;
    }

    /**
     * Processes the PDF file body, which consists of a series
     * of object blocks like: obj ... endobj. The 'obj' tag may
     * contain metadata contained between '<<' and '>>' delimiters.
     * Also the object may contain an embedded stream object
     * which will be delimited by 'stream' and 'endstream' tags.
     * @return void
     * @access private
     */
    private function _processPDFBody($matches)
    {
        //echo "\nPDF Body\n";

        $key          = $matches[1];
        $dictionary   = "";
        $stream       = "";
        $contents     = "";
        $probableText = false;
        
        $contents = $this->_readToEndOfBlock("/^endobj$/");
        
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

    /**
     * Processes the PDF cross reference section, whose start is
     * identified by a line containing only the tag: xref.
     * @return void
     * @access private
     */
    private function _processPDFXref($matches)
    {
        //echo "\nPDF Xref\n";
    }

    /**
     * Processes the PDF trailer section, whose start is
     * identified by a line containing only the tag: 'trailer'
     * and goes until the '%%EOF' tag.
     * @return void
     * @access private
     */
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

    /**
     * Parses out the PDF metadata section. The info blocks can
     * either be stored inline or contain an indirect object
     * reference. In indirect reference will be used as a key
     * into the object array to get the contents, otherwise the
     * inline content will be parsed out.
     * @return void
     * @access private
     */
    private function _processPDFInfoBlock()
    {
        $info = $this->_bagTrailer->info;
        
        if ($info == "")
            return;
        
        $data = $this->_getContentBlockById($info, false);
        
        $data = str_replace("\(", "[", $data);
        $data = str_replace("\)", "]", $data);
                
        $patternTitle = "/\/Title\s{0,1}(\d+\s{0,1}\d+)\s{0,1}R/";
        
        if (1 == preg_match($patternTitle, $data, $matches)) {
            $this->_aryInfoKeys[] = $matches[1];
            $this->_title = $this->_getContentBlockById($matches[1], true);
        } else {
            $patternTitle = "/\/Title\(([^)]+)\)/";
            if (1 == preg_match($patternTitle, $data, $matches)) {
                $this->_aryInfoKeys[] = $info;
                $this->_title = $matches[1];
            }
        }
            
        $patternAuthor = "/\/Author\s{0,1}(\d+\s{0,1}\d+)\s{0,1}R/";
        
        if (1 == preg_match($patternAuthor, $data, $matches)) {
            $this->_aryInfoKeys[] = $matches[1];
            $this->_author = $this->_getContentBlockById($matches[1], true);
        } else {
            $patternAuthor = "/\/Author\(([^)]+)\)/";
            if (1 == preg_match($patternAuthor, $data, $matches)) {
                $this->_aryInfoKeys[] = $info;
                $this->_author = $matches[1];
            }            
        }
        
        $patternSubject = "/\/Subject\s{0,1}(\d+\s{0,1}\d+)\s{0,1}R/";
        
        if (1 == preg_match($patternSubject, $data, $matches)) {
            $this->_aryInfoKeys[] = $matches[1];
            $this->_subject = $this->_getContentBlockById($matches[1], true);
        } else {
            $patternSubject = "/\/Subject\(([^)]+)\)/";
            if (1 == preg_match($patternSubject, $data, $matches)) {
                $this->_aryInfoKeys[] = $info;
                $this->_subject = $matches[1];
            }
        }
        
        $patternProducer = "/\/Producer\s{0,1}(\d+\s{0,1}\d+)\s{0,1}R/";
        
        if (1 == preg_match($patternProducer, $data, $matches)) {
            $this->_aryInfoKeys[] = $matches[1];
            $this->_producer = $this->_getContentBlockById($matches[1], true);
        } else {
            $patternProducer = "/\/Producer\(([^)]+)\)/";
            if (1 == preg_match($patternProducer, $data, $matches)) {
                $this->_aryInfoKeys[] = $info;
                $this->_producer = $matches[1];
            }
        }
        
        $patternCreator = "/\/Creator\s{0,1}(\d+\s{0,1}\d+)\s{0,1}R/";
        
        if (1 == preg_match($patternCreator, $data, $matches)) {
            $this->_aryInfoKeys[] = $matches[1];
            $this->_creator = $this->_getContentBlockById($matches[1], true);
        } else {
            $patternCreator = "/\/Creator\(([^)]+)\)/";
            if (1 == preg_match($patternCreator, $data, $matches)) {
                $this->_aryInfoKeys[] = $info;
                $this->_creator = $matches[1];
            }
        }
        
        // Creation date looks like: "(D:20090922191205Z00'00')"
        $patternCreationDate = "/\/CreationDate\s{0,1}(\d+\s{0,1}\d+)\s{0,1}R/";
        
        if (1 == preg_match($patternCreationDate, $data, $matches)) {
            $this->_aryInfoKeys[] = $matches[1];
            $this->_creationDate = $this->_getContentBlockById($matches[1], true);
        } else {
            $patternCreationDate = "/\/CreationDate\(([^)]+)\)/";
            if (1 == preg_match($patternCreationDate, $data, $matches)) {
                $this->_aryInfoKeys[] = $info;
                $this->_creationDate = $matches[1];
            }
        }
        
        // Modification date looks like: "(D:20090922191205Z00'00')"
        $patternModDate = "/\/ModDate\s{0,1}(\d+\s{0,1}\d+)\s{0,1}R/";
        
        if (1 == preg_match($patternModDate, $data, $matches)) {
            $this->_aryInfoKeys[] = $matches[1];
            $this->_modDate = $this->_getContentBlockById($matches[1], true);
        } else {
            $patternModDate = "/\/ModDate\(([^)]+)\)/";
            if (1 == preg_match($patternModDate, $data, $matches)) {
                $this->_aryInfoKeys[] = $info;
                $this->_modDate = $matches[1];
            }
        }
        
        // Keywords look like: "(keyword1, keyword2, keyword3)"
        $patternKeywords = "/\/Keywords\s{0,1}(\d+\s{0,1}\d+)\s{0,1}R/";
        
        if (1 == preg_match($patternKeywords, $data, $matches)) {
            $this->_aryInfoKeys[] = $matches[1];
            $this->_keywords = $this->_getContentBlockById($matches[1], true);
        } else {
            $patternKeywords = "/\/Keywords\(([^)]+)\)/";
            if (1 == preg_match($patternKeywords, $data, $matches)) {
                $this->_aryInfoKeys[] = $info;
                $this->_keywords = $matches[1];
            }
        }

        // AAPL keywords look like: "[ (keyword1) (keyword2) (keyword3) ]"
        $patternAaplKeywords = "/\/AAPL\:Keywords\s{0,1}(\d+\s{0,1}\d+)\s{0,1}R/";
        
        if (1 == preg_match($patternAaplKeywords, $data, $matches)) {
            $this->_aryInfoKeys[] = $matches[1];
            $this->_aaplKeywords = $this->_getContentBlockById($matches[1], true);
        }

    }

    /**
     * Loops through the internal array of objects and if
     * object is identified as probably containing text then
     * its value is added to the parsed contents variable. At
     * the end of this routine, the PDF contents will have all
     * been consolidated to the final output variables.
     * @return void
     * @access private
     */
    private function _processContents()
    {
        $contents  = "";
        foreach ($this->_bagBody->objects as $obj) {
            $isInfoKey = false;
            foreach ($this->_aryInfoKeys as $infoKey)
                if ($infoKey == $obj['key'])
                    $isInfoKey = true;
                    
            if ($obj['probableText'] == true && !$isInfoKey)
                $contents .= $obj['contents'];
        }        
        $this->_contents = $contents;
    }

    /**
     * Searches the internal array of objects for the specified
     * id and returns either the content or dictionary values
     * associated with that object.
     * @param $id Id of object to get values for
     * @param $wantContent True, returns contents, else returns dictionary
     * @return string
     * @access private
     */
    private function _getContentBlockById($id, $wantContent)
    {
        foreach ($this->_bagBody->objects as $obj)
            if ($obj['key'] == $id)
                if ($wantContent)
                    return $obj['contents'];
                else
                    return $obj['dictionary'];
        return "";
    }

    /**
     * Processes a stream contained within an object
     * @param $header Enclosing object's metadata
     * @param $data Stream object to parse
     * @return mixed False if stream format not handled otherwise contents of stream
     * @access private
     */
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
        if ($this->_bagTrailer->encrypt == true)
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

    /**
     * Parses the contents out of stream object. If stream object is
     * compressed then it must be uncompressed first before calling
     * this function.
     * @param $data Stream data to parse
     * @param $wasCompressed Indicates that stream data was uncompressed
     * @return string
     * @access private
     */
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

    /**
     * Reads lines into a buffer until a line is read that
     * contains the specified stop pattern. The returned
     * results will contain the stop pattern matched text
     * as well.
     * @param $patternStop Regex pattern that identifies end of block
     * @return string
     * @access private
     */
    private function _readToEndOfBlock($patternStop)
    {
        $buffer = "";

        do {
            $buffer .= $this->_fileBuffer;
            
            if (1 == preg_match($patternStop, trim($this->_fileBuffer), $matches))
                break;
            
        } while ($this->_readLine());
        
        return $buffer;
        
    }

    /**
     * Reads a single line of input into an internal buffer
     * @access private
     * @return integer 1 if a line was read, 0 if end of file
     */
    private function _readLine()
    {
        if (!feof($this->_fileHandle)) {
            $this->_fileBuffer = fgets($this->_fileHandle);
            $this->_fileLine++;

            if(1 == preg_match("/^(\d+ \d+) obj/", $this->_fileBuffer, $obj)){
                foreach ($this->obj_pages as $key => $value) {
                    if(in_array($obj[1], $value))
                        $this->page = $key;
                }
            }

            //echo htmlentities($this->_fileBuffer);
            return 1;
        }
        
        return 0;
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
        $object = new TChester_Pdf2Text($this->file_name);

        if($object->getErrorPages() || $object->getEncrypt()){
            return false;
        }else{
            $body = $object->getBody();

            $i = 0;
            foreach ($body->objects as $obj)
            {
                if(($obj['probableText']) && ($obj['contents'] != "" && !is_null($obj['contents']))){
                    if($obj['page'] == $i && array_key_exists($obj['page'], $this->return)){
                        $this->return[$obj['page']] .= $obj['contents'];
                    }else{
                        $this->return[$obj['page']] = $obj['contents'];
                        $i = $obj['page'];
                    }
                }
            }
            $trailer = $object->getTrailer();

            if ($trailer->encrypt === true)
                //echo "Contents are not available because PDF is encrypted.\n";
                return false;
            else
                return $this->return;
                //FEITO PELO FABIO
                //return new TChester_Pdf2Text($this->file_name);
        }
    }
}