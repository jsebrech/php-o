<?php

namespace O;

$classPath = realpath(dirname(__FILE__)."/src/O");
if (!class_exists("\\O\\O")) include($classPath."/O.php");

O::init();
