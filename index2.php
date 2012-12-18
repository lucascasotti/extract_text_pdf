<?php
// Patch for pdf2txt() posted Sven Schuberth
// Add/replace following code (cannot post full program, size limitation)

// handles the verson 1.2
// New version of handleV2($data), only one line changed
function handleV2($data){
        
    // grab objects and then grab their contents (chunks)
    $a_obj = getDataArray($data,"obj","endobj");
    
    foreach($a_obj as $obj){
        
        $a_filter = getDataArray($obj,"<<",">>");
    
        if (is_array($a_filter)){
            $j++;
            $a_chunks[$j]["filter"] = $a_filter[0];

            $a_data = getDataArray($obj,"stream\r\n","endstream");
            if (is_array($a_data)){
                $a_chunks[$j]["data"] = substr($a_data[0],
        strlen("stream\r\n"),
        strlen($a_data[0])-strlen("stream\r\n")-strlen("endstream"));
            }
        }
    }

    // decode the chunks
    foreach($a_chunks as $chunk){

        // look at each chunk and decide how to decode it - by looking at the contents of the filter
        $a_filter = split("/",$chunk["filter"]);
        
        if ($chunk["data"]!=""){
            // look at the filter to find out which encoding has been used            
            if (substr($chunk["filter"],"FlateDecode")!==false){
                $data =@ gzuncompress($chunk["data"]);
                if (trim($data)!=""){
            // CHANGED HERE, before: $result_data .= ps2txt($data);    
                    $result_data .= PS2Text_New($data);
                } else {
                
                    //$result_data .= "x";
                }
            }
        }
    }
    return $result_data;
}

// New function - Extract text from PS codes
function ExtractPSTextElement($SourceString)
{
$CurStartPos = 0;
$Result = null;
while (($CurStartText = strpos($SourceString, '(', $CurStartPos)) !== FALSE)
    {
    // New text element found
    if ($CurStartText - $CurStartPos > 8) $Spacing = ' ';
    else    {
        $SpacingSize = substr($SourceString, $CurStartPos, $CurStartText - $CurStartPos);
        if ($SpacingSize < -25) $Spacing = ' '; else $Spacing = '';
        }
    $CurStartText++;

    $StartSearchEnd = $CurStartText;
    while (($CurStartPos = strpos($SourceString, ')', $StartSearchEnd)) !== FALSE)
        {
        if (substr($SourceString, $CurStartPos - 1, 1) != '\\') break;
        $StartSearchEnd = $CurStartPos + 1;
        }
    if ($CurStartPos === FALSE) break; // something wrong happened
    
    $Result = $StartSearchEnd;

    // Remove ending '-'
    if (substr($Result, -1, 1) == '-')
        {
        $Spacing = '';
        $Result = substr($Result, 0, -1);
        }

    // Add to result
    $Result .= $Spacing . substr($SourceString, $CurStartText, $CurStartPos - $CurStartText);
    $CurStartPos++;
    }
// Add line breaks (otherwise, result is one big line...)
return $Result . "\n";
}

// Global table for codes replacement 
$TCodeReplace = array ('\(' => '(', '\)' => ')');

// New function, replacing old "pd2txt" function
function PS2Text_New($PS_Data)
{
global $TCodeReplace;

// Catch up some codes
if (ord($PS_Data[0]) < 10) return ''; 
if (substr($PS_Data, 0, 8) == '/CIDInit') return '';

// Some text inside (...) can be found outside the [...] sets, then ignored 
// => disable the processing of [...] is the easiest solution

$Result = ExtractPSTextElement($PS_Data);

// echo "Code=$PS_Data\nRES=$Result\n\n";

// Remove/translate some codes
return strtr($Result, $TCodeReplace);
}

if(isset($_POST['enviar'])){

    $arquivo = $_FILES['arquivo'];

    $teste = PS2Text_New($arquivo['name']);

    var_dump($teste);
}

?>

<html>
    <head>
        <title>Testando PDF</title>
    </head>
    <body>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="arquivo" />
            <input type="submit" name="enviar" value="Testar" />
        </form>
    </body>
</html>