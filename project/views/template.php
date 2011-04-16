<html>
<head>
	<title><?php echo $title; ?> - Regnskap</title>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>
	<link href="<?php echo URL::site('index.php/css/regnskap', null, false); ?>" rel="stylesheet" type="text/css" />
</head>

<body>

<?php

// Menu
echo '<span style="font-size:0.8em;">'.chr(10).

html::anchor('index.php/import/srbank', __('Import')).' -:- '.chr(10).
html::anchor('index.php/bankaccount', __('Bank accounts')).' -:- '.chr(10).

'</span>'.chr(10).chr(10);

// Title
echo '<h1>'.$title.'</h1>'; 


// Content
echo $content;


?>

<div class="footer">
<br />Laget av Hallvard Nygård (<a href="https://twitter.com/hallny/">@hallny</a>). CC-BY-SA, <a href="https://github.com/HNygard/regnskap">kildekode på Github</a>.
</div>
</body>
</html>
