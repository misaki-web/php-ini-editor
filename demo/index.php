<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta http-equiv="x-ua-compatible" content="ie=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		
		<title>PHP INI Editor Demo</title>
		
		<?php
			// Include the class.
			include('../IniEditor.class.php');
			
			// Initialize the class object.
			$ini_editor = new IniEditor();
			
			// Set the backup folder where to create a backup before saving the new version. Default is "backup".
			$ini_editor->setBackupFolder('backup-folder');
			
			// Set the path to the INI file.
			$ini_editor->setIniFile('demo.ini');
			
			// Set to true to allow editing the INI file. Default is true.
			$ini_editor->enableEdit(true);
			
			// Set to true to allow adding conf and sections in the INI file. Default is true.
			$ini_editor->enableAdd(true);
			
			// set to true to allow deleting conf in the INI file. Default is true.
			$ini_editor->enableDelete(true);
			
			// Set the scanner mode when parsing the INI file (optional). Default is INI_SCANNER_TYPED.
			$ini_editor->setScannerMode(INI_SCANNER_TYPED);
			
			// Set documentation to be displayed when clicked.
			// The first argument is the path to the documentation content.
			// The second argument is the documentation format ("html", "ini" or "text").
			$ini_editor->setDocumentation('demo.template.ini', 'ini');
			
			// Include external resources (JavaScript and CSS from jQuery and Bootstrap CDN).
			echo IniEditor::getExternalResources();
			
			// Include CSS style.
			echo IniEditor::getCSS();
			
			// Get scripts.
			echo $ini_editor->getScripts();
		?>
	</head>
	<body id="top">
		<div class="scroll go-to-bottom"><a href="#bottom"> ⇓ </a></div>
		
		<?php
			// Print the form.
			$ini_editor->printForm();
			
			// Note: use "$ini_editor->getForm()" to store it in a variable. Example:
			// $form = $ini_editor->getForm();
		?>
		
		<div class="scroll go-to-top"><a href="#top"> ⇑ </a></div>
		
		<div id="bottom"></div>
	</body>
</html>
