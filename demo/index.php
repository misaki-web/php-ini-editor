<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta http-equiv="x-ua-compatible" content="ie=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		
		<title>PHP INI Editor Demo</title>
		
		<?php
			// include the class
			include('../IniEditor.class.php');
			
			// include Javascript and CSS from jQuery and Bootstrap CDN
			echo IniEditor::getCssJsInclude();
			
			// include class CSS (use your own if you prefer)
			echo IniEditor::getCSS();
			
			// initialize the class object
			$ini_editor = new IniEditor();
			
			// set folder where to put backups before saving the new version of the file (folder needs write permissions)
			$ini_editor->setBackupFolder('backups');
			
			// set different Scanner Mode (optional)
			$ini_editor->setScannerMode(INI_SCANNER_NORMAL);
			
			// set the path of the file you want to edit or view
			$ini_editor->setIniFile('demo.ini');
			
			// set to true to allow edit of the config file (default is true)
			$ini_editor->enableEdit(true);
			
			// set to true to allow add of sections and conf in the config file (default is true)
			$ini_editor->enableAdd(true);
			
			// set to true to allow delete of conf in the config file (default is true)
			$ini_editor->enableDelete(true);
			
			// get scripts
			echo $ini_editor->getScripts();
		?>
	</head>
	<body id="top">
		<div class="scroll go-to-bottom"><a href="#bottom"> ⇓ </a></div>
		
		<?php
			// print the form. Use $ini_editor->getForm() to store it in a variable
			$ini_editor->printForm();
		?>
		
		<div class="scroll go-to-top"><a href="#top"> ⇑ </a></div>
		
		<div id="bottom"></div>
	</body>
</html>
