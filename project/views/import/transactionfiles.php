<?php

echo '<script>


</script>';

echo '<table>';
$i = 0;
foreach($importfiles_files_found as $bankaccount_id => $files)
{
	foreach($files as $file)
	{
		echo '<form>';
		
		echo '<tr class="main_file" id="file_'.$i.'">';
		echo '<td rowspan="2" class="folder'.$i.'">'.str_replace(Controller_Import::$transactionfiles_main_folder, '', dirname($file)).'</td>';
		echo '<th style="text-align: left;" rowspan="2" class="filenameoriginal'.$i.'">'.pathinfo($file, PATHINFO_BASENAME).'</th>';
		
		$analyze = Controller_Import::transactionfiles_analyze($file);
		echo '<td><input type="text" name="account'.$i.'" value="'.     ((isset($analyze['account']))     ?$analyze['account']:'').'" size="12"></td>';
		echo '<td><input type="text" name="date'.$i.'" value="'.        ((isset($analyze['date']))        ?date('Y-m-d', $analyze['date']):'').'" size="7"></td>';
		echo '<td><input type="text" name="amount'.$i.'" value="'.      ((isset($analyze['amount']))      ?$analyze['amount']:'').'" size="7" style="text-align: right;"></td>';
		echo '<td><input type="text" name="description'.$i.'" value="'. ((isset($analyze['description'])) ?$analyze['description']:'').'" size="30"></td>';
		echo '<td><input type="text" name="extention'.$i.'" value="'.   ((isset($analyze['extention']))   ?$analyze['extention']:'').'" size="4"></td>';
		echo '<td><input type="button" class="savefilename" value="Save" id="savebutton'.$i.'"></td>';
		
		echo '</tr>';
		
		echo '<tr><td colspan="6" class="filename'.$i.'"></td></tr>';
		
		echo '</form>';
		$i++;
		
		
		echo '<tr><td colspan="8"><br /><br /></td></tr>';
	}
}
echo '</table>';
