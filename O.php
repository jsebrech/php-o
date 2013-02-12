<?php

namespace O;

// TODO: use proper autoloader

$classPath = realpath(dirname(__FILE__)."/core");

// Session
if (!class_exists("\\O\\Session")) include($classPath."/Session.php");

// s()
if (!class_exists("\\O\\StringClass")) include($classPath."/StringClass.php");
// a()
if (!class_exists("\\O\\ArrayClass")) include($classPath."/ArrayClass.php");
// o()
if (!class_exists("\\O\\ObjectClass")) include($classPath."/ObjectClass.php");
// c()
if (!class_exists("\\O\\ChainableClass")) include($classPath."/ChainableClass.php");

// Validator and ReflectionClass
if (!class_exists("\\O\\Validator")) include($classPath."/Validator.php");
