<?php

require_once('pdftext.php');

if(isset($_POST['enviar'])){

	$arquivo = $_FILES['arquivo'];

	$pdf = new PDFtext();

	$pdf->setPdf($arquivo['name']);

	if($resultado = $pdf->getPdf()){
		if(!is_array($resultado))
			print_r("O PDF poss&uacute;i somente imanges");
		else
			print_r($resultado);
	}else{
		print_r("N&atilde;o &eacute; poss&iacute;vel ler o arquivo PDF");
	}
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