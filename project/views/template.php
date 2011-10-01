<html>
<head>
	<title><?php echo $title; ?> - Regnskap</title>
	<link type="text/css" rel="stylesheet" media="all" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.11/themes/base/jquery-ui.css" />
	<link type="text/css" rel="stylesheet" media="all" href="http://static.jquery.com/ui/css/demo-docs-theme/ui.theme.css" />
	<link type="text/css" rel="stylesheet" media="screen" href="<?php echo URL::site('/css/jquery.lightbox-0.5.css', null, false); ?>" />
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.js"></script>
	<script type="text/javascript" src="<?php echo URL::site('index.php/js/jqueryblockUIjs231', null, false); ?>"></script>
<?php
if(Request::current()->controller() == 'bankaccount' && Request::current()->action())
{
	echo '	<script type="text/javascript" src="'.URL::site('index.php/js/banktransactions', null, false).'"></script>'.chr(10);
}
if(Request::current()->controller() == 'import' && Request::current()->action() == 'transactionfiles')
{
	echo '	<script type="text/javascript" src="'.URL::site('index.php/js/transactionfiles', null, false).'"></script>'.chr(10);
	echo '	<script type="text/javascript" src="'.URL::site('/js/jquery.lightbox-0.5.min.js', null, false).'"></script>'.chr(10);
}
?>
	<link href="<?php echo URL::site('index.php/css/regnskap', null, false); ?>" rel="stylesheet" type="text/css" />
</head>

<body>

<?php

// Menu
echo '<span style="font-size:0.8em;">'.chr(10).

html::anchor('index.php/', __('Overview')).' -:- '.chr(10).
html::anchor('index.php/import', __('Import')).' -:- '.chr(10).
html::anchor('index.php/bankaccount', __('Bank accounts')).' -:- '.chr(10).
'<br>'.chr(10).chr(10).
__('Administration').': '.chr(10).
html::anchor('index.php/account', __('Accounts')).' -:- '.chr(10).
html::anchor('index.php/transaction', __('Transactions')).' -:- '.chr(10).
html::anchor('index.php/bankaccount_autoimport', __('Autoimports')).' -:- '.chr(10).

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
