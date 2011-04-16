<?php

echo __('Are you sure you want to delete this?').'<br />';
echo HTML::anchor('index.php/'.Request::current()->uri().'/true', __('Yes')).' ';
echo HTML::anchor('index.php/'.Request::current()->controller(), __('No'));
