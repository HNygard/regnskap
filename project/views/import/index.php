<?php

echo html::anchor('index.php/import/srbank', __('Import SR-bank')).'<br />';
echo html::anchor('index.php/import/srbank_pdf', __('Import SR-bank PDF')).'<br />';
echo html::anchor('index.php/import/generic_csv', __('Import generic CSV files')).' '.
	'(Column A = date, column B = description, column C = amount)<br />';
echo html::anchor('index.php/import/kolumbus', __('Import from Kolumbus reisekonto')).'<br />';
echo html::anchor('index.php/import/transactionfiles', __('View and rename files that can link to a transaction')).'<br />';
