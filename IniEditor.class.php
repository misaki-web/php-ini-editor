<?php

# Copyright (C) 2022  Misaki F. <https://github.com/misaki-web/php-ini-editor>
# Copyright (c) 2017  Blupixel IT Srl <https://www.blupixelit.eu>
# 
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

class IniEditor
{
	protected $ini_file = '';
	protected $backup_folder = 'backup';
	protected $doc_path = '';
	protected $doc_format = 'text';
	protected $enable_edit = true;
	protected $enable_add = true;
	protected $enable_delete = true;
	protected $scanner_mode = INI_SCANNER_TYPED; # INI_SCANNER_NORMAL | INI_SCANNER_RAW | INI_SCANNER_TYPED
	
	################################################################################
	# 
	# PRIVATE
	# 
	################################################################################
	
	private function backupFilename($filename)
	{
		return str_replace("/", "_", $filename);
	}
	
	private static function base64EncodeUrl($value) {
		return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
	}
	
	private static function base64DecodeUrl($value) {
		return base64_decode(str_pad(strtr($value, '-_', '+/'), strlen($value) % 4, '=', STR_PAD_RIGHT));
	}
	
	private static function formatValue($value)
	{
		if ($value === "true") {
			$formatted = true;
		} else if ($value === "false") {
			$formatted = false;
		}
		
		if (!isset($formatted)) {
			$formatted = $value;
		}
		
		return $formatted;
	}
	
	// Initialize properties.
	private function init()
	{
		if (isset($_REQUEST["ini_file"])) {
			$this->ini_file = static::base64DecodeUrl($_REQUEST["ini_file"]);
		}
	}
	
	// Find values in array using regexp on the key.
	private function pregGrepKeys($pattern, $input, $flags = 0)
	{
		$keys = preg_grep($pattern, array_keys($input), $flags);
		$vals = [];
		
		foreach ($keys as $key) {
			$vals[$key] = $input[$key];
		}
		
		return $vals;
	}
	
	// Save new file from form request.
	private function saveForm()
	{
		$ret = '';
		$this->init();
		
		if (!$this->enable_edit) {
			return;
		}
		
		if (!file_exists($this->backup_folder)) {
			mkdir($this->backup_folder, 0755);
		}
		
		$backup = file_put_contents(
			$this->backup_folder . "/" . date("Y-m-d_H-i-s") . "_" . $this->backupFilename($this->ini_file),
			file_get_contents($this->ini_file)
		);
		
		if ($backup) {
			$vals = $this->pregGrepKeys('/ini#.*$/', $_REQUEST);
			$save = [];
			
			foreach ($vals as $key => $val) {
				$key = substr($key, 4);
				$conf = explode("#", $key);
				
				for ($i = 0; $i < 2; $i++) {
					$conf[$i] = static::base64DecodeUrl($conf[$i]);
				}
				
				if (!isset($save[$conf[0]])) {
					$save[$conf[0]] = [];
				}
				
				if (is_array($val)) {
					foreach ($val as $k => $v) {
						$k = static::base64DecodeUrl($k);
						$save[$conf[0]][] = $conf[1] . "[" . (!is_numeric($k) ? $k : "") . "]=" . $this->wrapValue($v, $conf[2]);
					}
				} else {
					$save[$conf[0]][] = $conf[1] . "=" . $this->wrapValue($val, $conf[2]);
				}
			}
			
			$content = '';
			
			foreach ($save as $section => $rows_array) {
				if ($content !== '') {
					$content .= "\n";
				}
				
				$content .= "[$section]\n";
				$previous_property = '';
				
				foreach ($rows_array as $row) {
					$property = '';
					preg_match('/^([^ =\[]+)/', $row, $matches, PREG_UNMATCHED_AS_NULL);
					
					if (isset($matches[1]) && $matches[1] !== null) {
						$property = $matches[1];
					}
					
					if ($property != $previous_property) {
						$content .= "\n";
					}
					
					$content .= "$row\n";
					$previous_property = $property;
				}
			}
			
			$res = file_put_contents($this->ini_file, $content);
			
			if ($res) {
				$ret = '<div id="msg" class="msg-success"><span class="filename">' . $this->ini_file . "</span> saved</div>";
			} else {
				$ret = '<div id="msg" class="msg-error"><span class="filename">' . $this->ini_file . "</span> cannot be saved</div>";
			}
		} else {
			$ret = '<div id="msg" class="msg-error">Check write permissions on ' . $this->backup_folder . "</div>";
		}
		
		return $ret;
	}
	
	// Wrap value inside quotes.
	private function wrapValue($val, $type)
	{
		if ($type == "bool") {
			if ($val) {
				return "true";
			} else {
				return "false";
			}
		} else {
			return '"' . str_replace('"', '\\"', $val) . '"';
		}
	}
	
	################################################################################
	# 
	# PUBLIC
	# 
	################################################################################
	
	// Contructor.
	public function __construct()
	{
		
	}
	
	// Set the INI file to edit.
	public function setIniFile($file)
	{
		$this->ini_file = $file;
	}
	
	// Set the backup folder where to create a backup before saving the new version.
	public function setBackupFolder($folder)
	{
		$this->backup_folder = $folder;
	}
	
	// Enable file editing.
	public function enableEdit($bool)
	{
		$this->enable_edit = $bool;
	}
	
	// Enable adding conf and sections.
	public function enableAdd($bool)
	{
		$this->enable_add = $bool;
	}
	
	// Enable deleting conf.
	public function enableDelete($bool)
	{
		$this->enable_delete = $bool;
	}
	
	// Set the scanner mode when parsing the INI file.
	public function setScannerMode($mode)
	{
		$this->scanner_mode = $mode;
	}
	
	// Set documentation to be displayed when clicked.
	// The first argument is the path to the documentation content.
	// The second argument is the documentation format ("html", "ini" or "text").
	public function setDocumentation($doc_path, $doc_format)
	{
		$this->doc_path = $doc_path;
		
		if ($doc_format == 'html' || $doc_format == 'ini' || $doc_format == 'text') {
			$this->doc_format = $doc_format;
		}
	}
	
	// Get CSS style.
	public static function getCSS()
	{
		return <<<'HEREDOC'
			<style>
				[onclick] {
					cursor:pointer;
				}
				body,
				.title-container,
				.save-button,
				.scroll {
					max-width: 1250px;
					margin-left: auto;
					margin-right: auto;
				}
				#msg {
					margin-top: 95px;
					margin-bottom: -50px;
					border-radius: 4px;
				}
				.msg-success {
					color: #0f5132;
					background-color: #d1e7dd;
					border: 1px solid #badbcc;
				}
				.msg-error {
					color: #842029;
					background-color: #f8d7da;
					border: #f5c2c7;
				}
				.editor-container {
					margin-top: 0;
					margin-bottom: 15px;
				}
				.btn,
				.btn:hover,
				.btn:active,
				.btn:focus {
					font-size: 0.9rem;
					padding: 3px 6px;
					background-color: #0b5ed7;
					border: none;
					border-radius: 4px;
					color: #ffffff;
				}
				.btn:hover,
				.btn:active,
				.btn:focus {
					background-color: #0c69f0;
				}
				.title-container,
				.save-button {
					position: fixed;
					top: 0;
				}
				.title-container {
					display: flex;
					align-items: center;
					width: 100%;
					min-height: 70px;
					padding: 10px 0 10px 5px;
					background-color: #f2f2f2;
					box-shadow: rgba(0, 0, 0, 0.12) 0px 1px 3px, rgba(0, 0, 0, 0.24) 0px 1px 2px;
					border-radius: 0 0 4px 4px;
				}
				.title-container h3 {
					margin-top: 0;
					margin-bottom: 0;
					padding-right: 160px;
					font-size: 1.75em;
					font-weight: bold;
				}
				.global-actions {
					padding-top: 95px;
				}
				.global-actions .btn {
					margin-right: 5px;
				}
				.doc-content {
					max-height:400px;
					overflow-y: auto;
					margin-top: 20px;
					background-color: #f2f2f2;
					box-shadow: rgba(0, 0, 0, 0.12) 0px 1px 3px, rgba(0, 0, 0, 0.24) 0px 1px 2px;
					border-radius: 4px;
					font-family: monospace;
				}
				.doc-content .comment {
					color: #1d1dfd;
				}
				.doc-content .section {
					color: #a52a2a;
				}
				.doc-content .property {
					color: #2e8b57;
				}
				.hide {
					display: none;
				}
				.save-button {
					width: 100%;
					padding: 0;
				}
				input.btn.btn-success {
					display: block;
					width: 150px;
					min-height: 50px;
					margin-left: auto;
					padding: 10px;
					border-radius: 0 0 4px 0;
					font-size: 1.2rem;
					font-weight: bold;
				}
				.config-container {
					width: 100%;
					margin-left: 0;
					display: block;
				}
				legend {
					display: flex;
					justify-content: space-between;
					width: calc(100% + 20px);
					margin: 0 0 0 -10px;
					padding: 7px 10px 10px 10px;
					background-color: #d5d5d5;
					border-bottom: 1px solid #d9d9d9;
					border-radius: 4px 4px 0 0;
				}
				textarea.form-control {
					width: calc(100% - 70px);
					display: inline-block;
					padding: 7px;
				}
				.col-md-10 textarea.form-control {
					width: 100%;
				}
				div:not(.form-group.vector) > div > div > textarea.form-control {
					margin-top: 10px;
				}
				.form-group.vector {
					display: inline-block;
					width: 100%;
					vertical-align: top;
				}
				.form-group.vector > div {
					display: flex;
					flex-direction: row;
					align-items: center;
					margin-top: 10px;
					margin-bottom: 0;
				}
				.config-container > div.form-group.row:not(:last-child) {
					border-bottom: 1px solid #a9a9a947;
				}
				.config-container > div.form-group.row:last-child .col-md-8,
				.config-container > div.form-group.row:last-child .col-md-8 .array_add_value {
					margin-bottom: 0;
				}
				.editor-container fieldset {
					margin-top: 20px;
					margin-bottom: 45px;
					padding: 0 10px 10px 10px;
					background-color: #f2f2f2;
					border-radius: 4px;
				}
				.section {
					display: block;
					font-weight: bold;
				}
				input[type="checkbox"] {
					height: 20px;
					margin-top: 10px;
					margin-left: 0;
					padding: 0;
					width: 20px;
					vertical-align: middle;
					accent-color: #0c69f0;
				}
				.col-md-10 input[type="checkbox"][name*="#bool"] {
					margin-top: -10px;
				}
				.remove-btn {
					margin-right: 0;
				}
				.down-arr, .up-arr {
					margin-right: 5px;
				}
				.config-container > div.form-group.row:first-child .col-md-8 .with-array-key {
					margin-top: 10px;
				}
				.with-array-key .col-md-2 {
					margin-top: 20px;
				}
				label.array_key {
					display: block;
					margin-top: 10px;
					font-weight: bold;
				}
				.form-group div:nth-child(1) .col-md-10 label.array_key {
					margin-top: 0px;
				}
				.col-form-label {
					height: 24px;
					margin-left: 5px;
				}
				.col-form-label span {
					height: 24px;
					line-height: 24px;
					font-weight: bold;
				}
				.col-form-label.is-array span::after {
					content: "[]";
				}
				.col-md-2 {
					width: 60px;
					margin-bottom: 0;
					margin-left: 10px;
				}
				.col-md-4 {
					width: 35%;
					margin-top: 7px;
				}
				.col-md-8 {
					width: 65%;
					margin-bottom: 10px;
				}
				.col-md-10 {
					flex: 1 0 auto;
					width: auto;
				}
				.array_add_value {
					margin-top: 10px;
				}
				.center {
					text-align: center;
				}
				.filename {
					font-family: monospace;
				}
				.scroll {
					position: fixed;
					width: 100%;
					text-align: right;
				}
				.scroll a,
				.scroll a:hover {
					text-decoration: none;
				}
				.scroll a {
					display: inline-block;
					width: 30px;
					height: 50px;
					margin-right: -35px;
					border: 1px solid #ced4da;
					background-color: #f2f2f2;
					border-radius: 4px;
					font-size: 2em;
					font-weight: bold;
				}
				.go-to-bottom {
					top: 10px;
				}
				.go-to-top {
					bottom: 10px;
				}
				
				@media only screen and (max-width: 600px) {
					#msg {
						margin-bottom: -60px;
					}
					
					.editor-container {
						margin: 10px;
					}
					.title-container,
					.save-button {
						width: calc(100% - 20px);
					}
					.title-container h3 {
						padding-right: 60px;
						font-size: 1.25em;
					}
					
					input.btn.btn-success {
						width: 50px;
						padding: 2px;
						font-size: 1rem;
					}
					
					.editor-container fieldset {
						margin-bottom: 30px;
					}
					
					.col-md-2 {
						margin-left: 5px;
					}
					
					.col-md-4 {
						width: auto;
					}
					
					.col-md-4,
					.col-md-8 {
						padding-left: 0;
						padding-right: 0;
					}
					
					.col-md-10 {
						width: 100%;
					}
					
					.col-form-label {
						margin-bottom: 10px;
					}
					
					legend {
						display: block;
						width: calc(100% + 20px);
						margin: 0 0 10px -10px;
						padding: 5px;
					}
					
					textarea.form-control {
						width: 100%;
					}
					
					.scroll a {
						margin-right: 10px;
					}
				}
			</style>
			HEREDOC;
	}
	
	// Get external resources (JavaScript and CSS from jQuery and Bootstrap CDN).
	public static function getExternalResources()
	{
		return <<<'HEREDOC'
			<script src="https://code.jquery.com/jquery-3.6.0.min.js"
			        integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
			        crossorigin="anonymous"></script>
			
			<link rel="stylesheet"
			      href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css"
			      integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3"
			      crossorigin="anonymous" />
			HEREDOC;
	}
	
	// Get scripts.
	public function getScripts()
	{
		if ($this->enable_edit) {
			if ($this->enable_delete) {
				$remove_btn = '<a href="javascript:;" class="remove-btn" onclick="$(this).parent().parent().remove();">×</a>';
				$remove_btn_2 = '<a href="javascript:;" class="remove-btn" onclick="$(this).parents(\\\'.form-group\\\').remove();">×</a>';
			} else {
				$remove_btn = "";
				$remove_btn_2 = "";
			}
			
			return <<<HEREDOC
				<style>
					input.move-input {
						width: 24px;
						height: 24px;
						float: left;
						display: inline;
						background: #D9D9D9;
						border: 3px dotted #888;
						border-radius: 3px;
						margin-right: 5px;
						text-align: center;
					}
					input.move-input:focus {
						background: #00c4ff;
					}
				</style>
				
				<script>
					function base64EncodeUrl(value) {
						return btoa(value).replaceAll('+', '-').replaceAll('/', '_').replace(/\=+$/, '');
					}
					
					function addRow(obj, type, isarray) {
						var name = prompt('Which is the name of the new config field?');
						
						if (!name) {
							return;
						}
						
						var section = $(obj).parents('fieldset').find('legend').find('span.section').text();
						
						if (isarray == 'array') {
							if (type == 'bool') {
								var html =   '<label class="col-form-label is-array">' +
								               '<input type="text" class="move-input" size="1" />' +
								               '<span>' +
								                 name +
								               '</span>' +
								             '</label>' +
								           '</div>' +
								           '<div class="col-md-8">' +
								             '<div class="form-group vector">' +
								               '<div>' +
								                 '<input type="checkbox" ' +
								                         'name="ini#' + base64EncodeUrl(section) +
								                               '#' + base64EncodeUrl(name) +
								                               '#' + type + '[]" /> ' +
								                 '<a href="javascript:;" ' +
								                     'onclick="$(this).parent().parent().insertAfter($(this).parent().parent().next())" ' +
								                     'class="down-arr">' +
								                   '&#8595;' +
								                 '</a>' +
								                 '<a href="javascript:;" ' +
								                     'onclick="$(this).parent().parent().insertBefore($(this).parent().parent().prev())" ' +
								                     'class="up-arr">' +
								                   '&#8593;' +
								                 '</a>' +
								                 '$remove_btn' +
								               '</div>' +
								             '</div>';
							} else {
								var namekey = prompt('Which is the key of the first value (leave blank for none)?');
								var html =   '<label class="col-form-label is-array">' +
								               '<input type="text" class="move-input" size="1" />' +
								               '<span>' +
								                 name +
								               '</span>' +
								             '</label>' +
								           '</div>' +
								           '<div class="col-md-8">' +
								             '<div class="form-group vector">' +
								               '<div class="with-array-key">' +
								                 '<div class="col-md-10">' +
								                   '<label class="array_key">' +
								                     namekey +
								                   '</label>' +
								                   '<textarea rows="1" ' +
								                              'class="form-control" ' +
								                              'name="ini#' + base64EncodeUrl(section) +
								                                    '#' + base64EncodeUrl(name) +
								                                    '#' + type + '[' + base64EncodeUrl(namekey) + ']">' +
								                   '</textarea>' +
								                 '</div>' +
								                 '<div class="col-md-2">' +
								                   '<a href="javascript:;" ' +
								                       'onclick="$(this).parent().parent().insertAfter($(this).parent().parent().next())" ' +
								                       'class="down-arr">' +
								                     '&#8595;' +
								                   '</a>' +
								                   '<a href="javascript:;" ' +
								                       'onclick="$(this).parent().parent().insertBefore($(this).parent().parent().prev())" ' +
								                       'class="up-arr">' +
								                     '&#8593;' +
								                   '</a>' +
								                   '$remove_btn' +
								                 '</div>' +
								               '</div>' +
								             '</div>';
							}
							
							html +=   '<table class="array_add_value">' +
							            '<tbody>' +
							              '<tr>' +
							                '<td class="center">' +
							                  '<a href="javascript:;" ' +
							                      'class="btn btn-info" ' +
							                      'onclick="javascript:addArrayRow(this, \'text\');">' +
							                    'Add value' +
							                  '</a>' +
							                '</td>' +
							              '</tr>' +
							            '</tbody>' +
							          '</table>' +
							        '</div>';
						} else {
							if (type == 'bool') {
								var html =   '<label class="col-form-label">' +
								               '<input type="text" class="move-input" size="1" />' +
								               '<span>' +
								                 name +
								               '</span>' +
								             '</label>' +
								           '</div>' +
								           '<div class="col-md-8">' +
								             '<input type="checkbox" ' +
								                     'name="ini#' + base64EncodeUrl(section) +
								                           '#' + base64EncodeUrl(name) +
								                           '#' + type + '" />' +
								           '</div>';
							} else {
								var html =   '<label class="col-form-label">' +
								               '<input type="text" class="move-input" size="1" />' +
								               '<span>' +
								                 name +
								               '</span>' +
								             '</label>' +
								           '</div>' +
								           '<div class="col-md-8">' +
								             '<textarea rows="1" ' +
								                        'class="form-control" ' +
								                        'name="ini#' + base64EncodeUrl(section) +
								                              '#' + base64EncodeUrl(name) +
								                              '#' + type + '">' +
								             '</textarea>' +
								           '</div>';
							}
						}
						
						html = '<div class="col-md-4">' +
						         '<a href="javascript:;" ' +
						             'onclick="$(this).parent().parent().insertAfter($(this).parent().parent().next())" ' +
						             'class="down-arr">' +
						           '&darr;' +
						         '</a>' +
						         '<a href="javascript:;" ' +
						             'onclick="$(this).parent().parent().insertBefore($(this).parent().parent().prev())" ' +
						             'class="up-arr">' +
						           '&uarr;' +
						         '</a>' +
						         '$remove_btn_2' +
						         html;
						
						html = '<div class="form-group row">' + html + '</div>';
						
						$(obj).parents('fieldset').find('.config-container').append(html);
						$('.move-input:not(.move-initialized)').keydown(function(e) {
							moveOrder(this, e.which);
						});
					}
					
					function addArrayRow(obj, type) {
						var name = $(obj).parents('.form-group').find('.col-form-label').text();
						var namekey = prompt('Which is the key of the value to add (leave blank for none)?');
						var section = $(obj).parents('fieldset').find('legend span.section').text();
						
						var html = '<div class="with-array-key">' +
						             '<div class="col-md-10">' +
						               '<label class="array_key">' +
						                 namekey +
						               '</label>' +
						               '<textarea rows="1" ' +
						                          'class="form-control" ' +
						                          'name="ini#' + base64EncodeUrl(section) +
						                                '#' + base64EncodeUrl(name) +
						                                '#' + type + '[' + base64EncodeUrl(namekey) + ']">' +
						               '</textarea>' +
						             '</div>';
						
						html += '<div class="col-md-2">' +
						          '<a href="javascript:;" ' +
						              'onclick="$(this).parent().parent().insertAfter($(this).parent().parent().next())" ' +
						              'class="down-arr">' +
						            '&darr;' +
						          '</a>' +
						          '<a href="javascript:;" ' +
						              'onclick="$(this).parent().parent().insertBefore($(this).parent().parent().prev())" ' +
						              'class="up-arr">' +
						            '&uarr;' +
						          '</a>' +
						          '$remove_btn' +
						        '</div>';
						
						$(obj).parents('.col-md-8').find('.form-group:first').append(html);
						$('.move-input:not(.move-initialized)').keydown(function(e) {
							moveOrder(this, e.which);
						});
					}
					
					function addSection(obj) {
						var section = prompt('Which is the name of the new section?');
						
						if (!section) {
							return;
						}
						
						var html = '<fieldset>' +
						             '<legend>' +
						               '<span class="section" onclick="$(this).parent().parent().next().slideToggle();">' +
						                 section +
						               '</span>' +
						               '<span class="btns">' +
						                 '<a href="javascript:;" ' +
						                     'class="btn btn-info" ' +
						                     'onclick="addRow(this, \'text\');">' +
						                   'Add text config' +
						                 '</a> ' +
						                 '<a class="btn btn-info" ' +
						                     'href="javascript:;" ' +
						                     'onclick="addRow(this, \'bool\');">' +
						                   'Add Bool config' +
						                 '</a> ' +
						                 '<a class="btn btn-info" ' +
						                     'href="javascript:;" ' +
						                     'onclick="addRow(this, \'text\', \'array\');">' +
						                   'Add Array config' +
						                 '</a>' +
						               '</span> ' +
						             '</legend>' +
						             '<div class="config-container">' +
						             '</div>' +
						           '</fieldset>';
						
						$(obj).parents('.editor-container').find('form').prepend(html);
					}
					
					function moveOrder(obj, which) {
						$(obj).addClass('move-initialized');
						
						// UP
						if (which == 38) {
							$(obj).parents('.form-group:first').find('.up-arr').click();
							$(obj).focus();
						}
						
						// DOWN
						if (which == 40) {
							$(obj).parents('.form-group:first').find('.down-arr').click();
							$(obj).focus();
						}
					}
					
					$(function() {
						$('.move-input:not(.move-initialized)').keydown(function(e) {
							moveOrder(this, e.which);
						});
					});
					
					function toggleDisplay(selector) {
						if ($(selector).hasClass('hide')) {
							$(selector).removeClass('hide');
						} else {
							$(selector).addClass('hide');
						}
					}
					
					$(document).ready(function() {
						$('textarea').each(function() {
							this.setAttribute('style', 'height: ' + (this.scrollHeight) + 'px; overflow-y: hidden;');
						}).on('input', function() {
							this.style.height = 'auto';
							this.style.height = (this.scrollHeight) + 'px';
						});
						
						var title_height = $('.title-container').innerHeight();
						var add_section_height = title_height + 5;
						
						$('.save-button .btn-success').css({'height': title_height + 'px'});
						$('.global-actions, #msg').css({'padding-top': add_section_height + 'px'});
						
						if (window.location.hash.substr(1) == 'msg') {
							window.scrollTo({top: 0, behavior: 'smooth'});
						}
					});
				</script>
				HEREDOC;
		} else {
			return <<<'HEREDOC'
				<style>
					input.move-input {
						display: none;
					}
				</style>
				HEREDOC;
		}
	}
	
	// Get the HTML code for the form.
	public function getForm()
	{
		$html = '<div class="editor-container">';
		
		if (isset($_REQUEST["save_ini_form"])) {
			$html .= $this->saveForm();
		}
		
		$html .= <<<HEREDOC
			<div class="title-container">
				<h3><span class="title-label">Updating the file</span> <span class="filename">"{$this->ini_file}"</span></h3>
			</div>
			<div class="global-actions">
			HEREDOC;
		
		if ($this->enable_add && $this->enable_edit) {
			$html .= '<a class="btn btn-primary add-section" href="javascript:;" onclick="addSection(this);">Add section</a>';
		}
		
		if (file_exists($this->doc_path)) {
			$doc_content = file_get_contents($this->doc_path);
			
			if ($doc_content !== false) {
				if ($this->doc_format == 'ini' || $this->doc_format == 'text') {
					$doc_content = htmlspecialchars($doc_content);
					
					if ($this->doc_format == 'ini') {
						$doc_content = preg_replace('/^(;.*)$/m', '<span class="comment">$1</span>', $doc_content);
						$doc_content = preg_replace('/^(\[[^\]]+\])$/m', '<span class="section">$1</span>', $doc_content);
						$doc_content = preg_replace('/^([a-z][^= \[]*)/m', '<span class="property">$1</span>', $doc_content);
					}
					
					$doc_content = str_replace("\n", '<br />', $doc_content);
				}
				
				$html .= <<<HEREDOC
					<a class="btn btn-primary" href="javascript:;" onclick="toggleDisplay('.doc-content');">Help</a>
					<div class="doc-content hide">$doc_content</div>
					HEREDOC;
			}
		}
		
		$html .= '</div>';
		
		if (!is_writeable($this->ini_file)) {
			$html .= '<h4 style="color:red;">' . $this->ini_file . " is not writable</h4>";
		}
		
		$conf = parse_ini_file($this->ini_file, true, $this->scanner_mode);
		
		if (is_array($conf)) {
			$input_ini_file = static::base64EncodeUrl($this->ini_file);
			
			$html .= <<<HEREDOC
				<form method="post" action="#msg">
					<input type="hidden" name="save_ini_form" value="1" />
					<input type="hidden" name="ini_file" value="$input_ini_file" />
				HEREDOC;
			
			if ($this->enable_edit) {
				$html .= <<<HEREDOC
					<div class="save-button">
						<input type="Submit" class="btn btn-success" value="Save" />
					</div>
					HEREDOC;
			}
			
			$additional = [];
			
			foreach ($conf as $c => $cv) {
				if (in_array("id", array_keys($cv))) {
					$conf[$c] = array_merge($additional, $cv);
				}
			}
			
			foreach ($conf as $c => $cv) {
				$html .= <<<HEREDOC
					<fieldset><legend>
						<span class="section" onclick="$(this).parent().next().slideToggle();">$c</span>
					HEREDOC;
				
				if ($this->enable_add && $this->enable_edit) {
					$html .= <<<'HEREDOC'
						<span class="btns"><a href="javascript:;"
						         class="btn btn-info"
						         onclick="addRow(this, 'text');">Add text config</a> 
						      <a class="btn btn-info"
						         href="javascript:;"
						         onclick="addRow(this, 'bool');">Add Bool config</a> 
						      <a class="btn btn-info"
						         href="javascript:;"
						         onclick="addRow(this, 'text', 'array');">Add Array config</a></span>
						
						HEREDOC;
				}
				
				$html .= <<<HEREDOC
						</legend>
					<div class="config-container container">
					HEREDOC;
				
				foreach ($cv as $label => $val) {
					$val = static::formatValue($val);
					
					$html .= '<div class="form-group row">';
					
					if (!is_array($val)) {
						$html .= '<div class="col-md-4">';
						
						if ($this->enable_edit) {
							$html .= <<<'HEREDOC'
								<a href="javascript:;"
								   onclick="$(this).parent().parent().insertAfter($(this).parent().parent().next())"
								   class="down-arr">&darr;</a><a href="javascript:;"
								   onclick="$(this).parent().parent().insertBefore($(this).parent().parent().prev())"
								   class="up-arr">&uarr;</a>
								HEREDOC;
							
							if ($this->enable_delete) {
								$html .= '<a href="javascript:;" class="remove-btn" onclick="$(this).parents(\'.form-group\').remove();">×</a>';
							}
						}
						
						$html .= <<<HEREDOC
								<label class="col-form-label">
									<input type="text" class="move-input" size="1"/>
									<span>$label</span>
								</label>
							</div>
							<div class="col-md-8">
							HEREDOC;
						
						$c_base64url = static::base64EncodeUrl($c);
						$label_base64url = static::base64EncodeUrl($label);
						
						if (
							(isset($c[$label]) && is_bool($c[$label])) ||
							$val == "1" ||
							$val === true || $val === false ||
							(!$val && $val != "")
						) {
							$checked = $val ? ' checked="checked"' : "";
							$html .= <<<HEREDOC
								<input class='form_checkbox' type='hidden' name='ini#$c_base64url#$label_base64url#bool' value='0' />
								<input type='checkbox' name='ini#$c_base64url#$label_base64url#bool' value='1'$checked />
								HEREDOC;
						} else {
							$textarea_content = str_replace('\\"', '"', $val);
							$html .= "<textarea rows='1' class='form-control' name='ini#$c_base64url#$label_base64url#text'>" .
							           $textarea_content .
							         "</textarea>";
						}
						
						$html .= "</div>";
					} else {
						$html .= '<div class="col-md-4">';
						
						if ($this->enable_edit) {
							$html .= <<<'HEREDOC'
								<a href="javascript:;"
								   onclick="$(this).parent().parent().insertAfter($(this).parent().parent().next())"
								   class="down-arr">&darr;</a><a href="javascript:;"
								   onclick="$(this).parent().parent().insertBefore($(this).parent().parent().prev())"
								   class="up-arr">&uarr;</a>
								HEREDOC;
							
							if ($this->enable_delete) {
								$html .= '<a class="remove-btn" href="javascript:;" onclick="$(this).parents(\'.form-group\').remove();">×</a>';
							}
						}
						
						$html .= <<<HEREDOC
								<label class="col-form-label is-array">
									<input type="text" class="move-input" size="1" />
									<span>$label</span>
								</label>
							</div>
							<div class="col-md-8">
								<div class="form-group vector">
							HEREDOC;
						
						foreach ($val as $k => $v) {
							$v = static::formatValue($v);
							
							if (!is_numeric($k)) {
								$html .= <<<HEREDOC
									<div class="with-array-key">
										<div class="col-md-10">
											<label class='array_key'>$k</label>
									HEREDOC;
							} else {
								$html .= <<<HEREDOC
									<div>
										<div class="col-md-10">
									HEREDOC;
							}
							
							$c_base64url = static::base64EncodeUrl($c);
							$label_base64url = static::base64EncodeUrl($label);
							
							if (
								is_bool($val[$k]) ||
								$v == "1" ||
								$v === true || $v === false ||
								!$v
							) {
								$checked = ($v ? ' checked="checked"' : "");
								$html .= <<<HEREDOC
									<input class='form_checkbox' type='hidden' name='ini#$c_base64url#$label_base64url#bool[]' />
									<input class='form_checkbox' type='checkbox' name='ini#$c_base64url#$label_base64url#bool[]' value='1'$checked />
									HEREDOC;
							} else {
								$k_base64url = static::base64EncodeUrl($k);
								$textarea_content = str_replace('\\"', '"', $v);
								$html .= "<textarea rows='1' class='form-control' name='ini#$c_base64url#$label_base64url#text[$k_base64url]'>" .
								           $textarea_content .
								         "</textarea>";
							}
							
							$html .= <<<HEREDOC
								</div>
								<div class="col-md-2">
								HEREDOC;
							
							if ($this->enable_edit) {
								$html .= " ";
								$html .= <<< 'HEREDOC'
									<a href="javascript:;"
									   onclick="$(this).parent().parent().insertAfter($(this).parent().parent().next())"
									   class="down-arr">&darr;</a><a href="javascript:;"
									   onclick="$(this).parent().parent().insertBefore($(this).parent().parent().prev())"
									   class="up-arr">&uarr;</a>
									HEREDOC;
								
								if ($this->enable_delete) {
									$html .= '<a href="javascript:;" class="remove-btn" onclick="$(this).parent().parent().remove();">×</a>';
								}
							}
							
							$html .= <<<HEREDOC
									</div>
								</div>
								HEREDOC;
						}
						
						$html .= "</div>";
						
						if ($this->enable_add && $this->enable_edit) {
							$html .= <<<'HEREDOC'
								<table class="array_add_value">
									<tr>
										<td class="center">
											<a href="javascript:;" class="btn btn-info" onclick="javascript:addArrayRow(this, 'text');">Add value</a>
										</td>
									</tr>
								</table>
								HEREDOC;
						}
						
						$html .= "</div>";
					}
					
					$html .= "</div>\n";
				}
				
				$html .= <<<HEREDOC
						</div>
					</fieldset>
					HEREDOC;
			}
		}
		
		$html .= <<<HEREDOC
				</form>
			</div>
			HEREDOC;
		
		return $html;
	}
	
	// Print the form.
	public function printForm()
	{
		echo $this->getForm();
	}
}
