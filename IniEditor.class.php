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
	protected $ini_file;
	protected $backup_folder;
	protected $enable_edit = true;
	protected $enable_add = true;
	protected $enable_delete = true;
	protected $scanner_mode = INI_SCANNER_NORMAL;
	
	// contructor
	public function __construct()
	{
		$this->backup_folder = "backup/";
	}
	
	// set INI file to edit
	public function setIniFile($file)
	{
		$this->ini_file = $file;
	}
	
	// set backup folder where to save the backup before saving the new version
	public function setBackupFolder($folder)
	{
		$this->backup_folder = $folder;
	}
	
	// enable editing of the file
	public function enableEdit($bool)
	{
		$this->enable_edit = $bool;
	}
	
	// enable adding conf and sections in the file
	public function enableAdd($bool)
	{
		$this->enable_add = $bool;
	}
	
	// enable adding conf and sections in the file
	public function enableDelete($bool)
	{
		$this->enable_delete = $bool;
	}
	
	// get backup filename
	public function backupFilename($filename)
	{
		return str_replace("/", "_", $filename);
	}
	
	// set Scanner Mode in parsing the ini file
	public function setScannerMode($mode)
	{
		$this->scanner_mode = $mode;
	}
	
	// wrap a value inside quotes
	public function wrapValue($val, $type)
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
	
	// find values in array using regexp on the key
	public function preg_grep_keys($pattern, $input, $flags = 0)
	{
		$keys = preg_grep($pattern, array_keys($input), $flags);
		$vals = [];
		
		foreach ($keys as $key) {
			$vals[$key] = $input[$key];
		}
		
		return $vals;
	}
	
	// save the new file from form request
	public function saveForm()
	{
		if (!$this->enable_edit) {
			return;
		}
		
		if (!file_exists($this->backup_folder)) {
			mkdir($this->backup_folder, 0755);
		}
		
		$backup = file_put_contents(
			$this->backup_folder . "/" . $this->backupFilename($_REQUEST["ini_file"]) . "." . date("Ymd_His"),
			file_get_contents($_REQUEST["ini_file"])
		);
		
		if ($backup) {
			$vals = $this->preg_grep_keys('/ini#.*$/', $_REQUEST);
			$save = [];
			
			foreach ($vals as $key => $val) {
				$conf = explode("#", $key);
				
				if (!isset($save[$conf[1]])) {
					$save[$conf[1]] = [];
				}
				
				if (is_array($val)) {
					foreach ($val as $k => $v) {
						$save[$conf[1]][] = $conf[2] . "[" . (!is_numeric($k) ? $k : "") . "] = " . $this->wrapValue($v, $conf[3]);
					}
				} else {
					$save[$conf[1]][] = $conf[2] . " = " . $this->wrapValue($val, $conf[3]);
				}
			}
			
			$content = "";
			
			foreach ($save as $section => $rows) {
				$content .= "[$section]\n";
				$content .= implode("\n", $rows);
				$content .= "\n\n\n";
			}
			
			$res = file_put_contents($_REQUEST["ini_file"], $content);
			
			if ($res) {
				echo '<div class="alert alert-success"><span class="filename">' . $_REQUEST["ini_file"] . "</span> saved</div>";
			} else {
				echo '<div class="alert alert-error"><span class="filename">' . $_REQUEST["ini_file"] . "</span> cannot be saved</div>";
			}
		} else {
			echo '<div class="alert alert-error">Check write permissions on ' . $this->backup_folder . "</div>";
		}
	}
	
	// get Javascript and CSS from jQuery and Bootstrap CDN
	public static function getCssJsInclude()
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
	
	// get class CSS (use your own if you prefer)
	public static function getCSS()
	{
		return <<<'HEREDOC'
			<style>
				[onclick] {
					cursor:pointer;
				}
				.editor-container,
				.alert {
					max-width: 1250px;
					margin-left: auto;
					margin-right: auto;
				}
				.alert {
					margin-top: 5px;
				}
				.editor-container {
					margin-top: 20px;
					margin-bottom: 20px;
					border: 1px solid #D9D9D9;
					border-radius: 4px;
					padding: 20px;
				}
				.btn,
				.btn:hover,
				.btn:active,
				.btn:focus {
					font-size: 0.9rem;
					padding: 3px;
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
				.editor-container h3 {
					border-bottom: 1px solid #d9d9d9;
					margin-bottom: 20px;
				}
				.config-container {
					width: calc(100% - 50px);
					margin-left: 50px;
					display: block;
				}
				legend {
					margin-bottom: 10px;
				}
				textarea.form-control {
					width: calc(100% - 70px);
					display: inline-block;
					padding: 10px 7px 0px 7px;
				}
				.col-md-10 textarea.form-control {
					width: 100%;
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
					margin-bottom: 10px;
				}
				.config-container > div.form-group.row:last-child .col-md-8,
				.config-container > div.form-group.row:last-child .col-md-8 .array_add_value {
					margin-bottom: 0;
				}
				.editor-container fieldset {
					margin-top: 20px;
					margin-bottom: 20px;
					padding: 10px;
					background-color: #f2f2f2;
					border-radius: 4px;
				}
				.section {
					display: block;
				}
				input.btn.btn-success {
					font-size: 1.2rem;
					padding: 10px;
					margin-left: auto;
					margin-right: auto;
					display: block;
					min-width: 150px;
					font-weight: bold;
				}
				input.form-control[type="checkbox"] {
					height: 20px;
					margin-top: 0;
					margin-left: 30px;
					width: 20px;
					vertical-align: middle;
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
					margin-top: 10px;
				}
				.form-group div:nth-child(1) .col-md-10 label.array_key {
					margin-top: 0px;
				}
				.col-form-label {
					height: 32px;
				}
				.col-form-label span {
					height: 32px;
					line-height: 32px;
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
					width: 25%;
				}
				.col-md-8 {
					width: 75%;
					margin-bottom: 10px;
				}
				.col-md-10 {
					flex: 1 0 auto;
					width: auto;
				}
				.array_add_value {
					margin-bottom: 15px;
				}
				.center {
					text-align: center;
				}
				.filename {
					font-family: monospace;
				}
			</style>
			HEREDOC;
	}
	
	// get script used to manage the button callback
	public function getScripts()
	{
		if ($this->enable_edit) {
			return <<<'HEREDOC'
				<style>
					input.move-input {
						width: 20px;
						float: left;
						display: inline;
						background: #D9D9D9;
						border: 3px dotted #888;
						border-radius: 4px;
						margin-right: 5px;
					}
					input.move-input:focus {
						background: #00c4ff;
					}
				</style>
				
				<script>
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
								                 '<input class="form-control" type="checkbox" ' +
								                         'name="ini#' + section + '#' + name + '#' + type + '[]" /> ' +
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
								                 (
								                   $this->enable_delete ?
								                   '<a href="javascript:;" class="remove-btn" onclick="$(this).parent().parent().remove();">×</a>' :
								                   ''
								                 ) +
								                 '</div></div>';
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
								                              'name="ini#' + section + '#' + name + '#' + type + '[' + namekey + ']">' +
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
								                   (
								                     $this->enable_delete ?
								                     '<a href="javascript:;" class="remove-btn" onclick="$(this).parent().parent().remove();">×</a>' :
								                     ''
								                   ) +
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
								             '<input class="form-control" type="checkbox" name="ini#' + section + '#' + name + '#' + type + '" />' +
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
								             '<textarea rows="1" class="form-control" name="ini#' + section + '#' + name + '#' + type + '">' +
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
						         (
						           $this->enable_delete ?
						           '<a href="javascript:;" class="remove-btn" onclick="$(this).parents(\'.form-group\').remove();">×</a>' :
						           ''
						         ) +
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
						               '<textarea rows="1" class="form-control" name="ini#' + section + '#' + name + '#' + type + '[' + namekey + ']">' +
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
						          (
						            $this->enable_delete ?
						            '<a href="javascript:;" class="remove-btn" onclick="$(this).parent().parent().remove();">×</a>' :
						            ''
						          ) +
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
						               '<span>' +
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
					
					$(function() {
						$('.move-input:not(.move-initialized)').keydown(function(e) {
							moveOrder(this, e.which);
						});
					});
					
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
	
	// get the form from the file
	public function getForm()
	{
		$html = '<div class="editor-container">';
		
		if (isset($_REQUEST["save_ini_form"])) {
			$html .= $this->saveForm();
		}
		
		$html .= '<h3><span class="h3-label">Selected file:</span> <span class="filename">' . $this->ini_file . "</span></h3>";
		
		if ($this->enable_add && $this->enable_edit) {
			$html .= '<span><a href="javascript:;" class="btn btn-primary" onclick="addSection(this);">Add section</a></span>';
		}
		
		if (!is_writeable($this->ini_file)) {
			$html .= '<h4 style="color:red;">' . $this->ini_file . " is not writable</h4>";
		}
		
		error_reporting(E_ALL);
		
		$conf = parse_ini_file($this->ini_file, true, $this->scanner_mode);
		
		$html .= <<<HEREDOC
			<form method="post">
				<input type="hidden" name="save_ini_form" value="1" />
				<input type="hidden" name="ini_file" value="{$this->ini_file}" />
			HEREDOC;
		
		$additional = [];
		
		foreach ($conf as $c => $cv) {
			if (in_array("id", array_keys($cv))) {
				$conf[$c] = array_merge($additional, $cv);
			}
		}
		
		foreach ($conf as $c => $cv) {
			$html .= "<fieldset><legend>\n";
			$html .= '<span class="section" onclick="$(this).parent().next().slideToggle();">' . "$c</span>";
			
			if ($this->enable_add && $this->enable_edit) {
				$html .= <<<'HEREDOC'
					<span><a href="javascript:;"
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
			
			$html .= "</legend>\n";
			$html .= '<div class="config-container container">' . "\n";
			
			foreach ($cv as $label => $val) {
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
					
					$html .= ' <label class="col-form-label"><input type="text" class="move-input" size="1"/><span>' . "$label</span></label>";
					$html .= "</div>";
					$html .= '<div class="col-md-8">';
					
					if (
						(isset($c[$label]) && is_bool($c[$label])) ||
						$val == "1" ||
						(!$val && $val != "")
					) {
						$html .= "<input class='form_checkbox' type='hidden' name='ini#$c#$label#bool' value='0' />";
						$html .= "<input class='form-control' type='checkbox' name='ini#$c#$label#bool' value='1'" .
						         ($val ? ' checked="checked"' : "") . " />";
					} else {
						$html .= "<textarea rows='1' class='form-control' name='ini#$c#$label#text'>" .
						         str_replace('\\"', '"', $val) .
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
					
					$html .= ' <label class="col-form-label is-array"><input type="text" class="move-input" size="1" /><span>' . "$label</span></label>";
					$html .= "</div>";
					$html .= '<div class="col-md-8">';
					$html .= '<div class="form-group vector">';
					
					foreach ($val as $k => $v) {
						if (!is_numeric($k)) {
							$html .= '<div class="with-array-key">';
							$html .= '<div class="col-md-10">';
							$html .= "<label class='array_key'>$k</label>";
						} else {
							$html .= "<div>";
							$html .= '<div class="col-md-10">';
						}
						
						if (is_bool($val[$k]) || $v == "1" || !$v) {
							$html .= "<input class='form_checkbox' type='hidden' name='ini#$c#$label#bool[]' />";
							$html .= "<input class='form_checkbox' type='checkbox' name='ini#$c#$label#bool[]' value='1'" .
							         ($v ? ' checked="checked"' : "") . " />";
						} else {
							$html .= "<textarea rows='1' class='form-control' name='ini#$c#$label#text[$k]'>" .
							         str_replace('\\"', '"', $v) .
							         "</textarea>";
						}
						
						$html .= "</div>";
						$html .= '<div class="col-md-2">';
						
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
						
						$html .= "</div>";
						$html .= "</div>";
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
			
			$html .= "</div>";
			$html .= "</fieldset>\n";
		}
		
		if ($this->enable_edit) {
			$html .= '<input type="Submit" class="btn btn-success" value="Save" />';
		}
		
		$html .= "</form>";
		$html .= "</div>";
		
		return $html;
	}
	
	// print the form from the file
	public function printForm()
	{
		echo $this->getForm();
	}
}
?>
