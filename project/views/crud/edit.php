<?php

// TODO: Form::open does not give the current URI as action attribute by default. Fix the problem and remove the workaround below
echo form::open(Request::current());

foreach($object->fields() as $field_name => $field)
{
	if(!$field->editable)
		continue;
	
	echo '<b>'.$field->label.'</b><br />'.chr(10);
	echo $object->input($field_name).'<br /><br />'.chr(10).chr(10);
}

echo form::submit('save', __('Save')).chr(10).chr(10);
echo form::close();
