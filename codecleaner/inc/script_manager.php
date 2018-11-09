<?php
//Security Check
if(!current_user_can('manage_options') || is_admin() || !isset($_GET['codecleaner']) || !codecleaner_network_access()) {
	return;
}

//Set Variables
global $codecleaner_extras;
global $wp;
global $wp_scripts;
global $wp_styles;
global $codecleaner_options;
global $currentID;
$currentID = get_queried_object_id();

//Settings Process Form
if(isset($_POST['codecleaner_script_manager_settings'])) {

	//Settings Validate
	if(!isset($_POST['codecleaner_script_manager_settings_nonce']) || !wp_verify_nonce($_POST['codecleaner_script_manager_settings_nonce'], 'codecleaner_script_manager_save_settings')) {
		print 'Sorry, your nonce did not verify.';
	    exit;
	} else {

		//Update Settings
		update_option('codecleaner_script_manager_settings', $_POST['codecleaner_script_manager_settings']);
	}
}

//Load Script Settings
global $codecleaner_script_manager_settings;
$codecleaner_script_manager_settings = get_option('codecleaner_script_manager_settings');

//Build Array of Existing Disables
global $codecleaner_disables;
$codecleaner_disables = array();
if(!empty($codecleaner_options['disable_google_maps']) && $codecleaner_options['disable_google_maps'] == "1") {
	$codecleaner_disables[] = 'maps.google.com';
	$codecleaner_disables[] = 'maps.googleapis.com';
	$codecleaner_disables[] = 'maps.gstatic.com';
}

//Setup Filters Array
global $codecleaner_filters;
$codecleaner_filters = array(
	"js" => array (
		"title" => "JS",
		"scripts" => $wp_scripts
	),
	"css" => array(
		"title" => "CSS",
		"scripts" => $wp_styles
	)
);

//Load Script Options
global $codecleaner_script_manager_options;
$codecleaner_script_manager_options = get_option('codecleaner_script_manager');

//Load Styles
require_once('script_manager_css.php');

//Wrapper Script Manager 
echo "<div id='codecleaner-script-manager-wrapper' " . (isset($_GET['codecleaner']) ? "style='display: block;'" : "") . ">";

	echo "<div id='codecleaner-script-manager'>";

		$master_array = codecleaner_script_manager_load_master_array();

		//Header
		echo "<div id='codecleaner-script-manager-header'>";

			//Logo
			echo "<img src='" . plugins_url('img/logo.svg', dirname(__FILE__)) . "' title='Codecleaner' id='codecleaner-logo' />";
		
			//Main Navigation Form
			echo "<form method='POST'>";
				echo "<div id='codecleaner-script-manager-tabs'>";
					echo "<button name='tab' value='' class='"; if(empty($_POST['tab'])){echo "active";} echo "' title='" . __('Script Manager', 'codecleaner') . "'>" . __('Script Manager', 'codecleaner') . "</button>";
					echo "<button name='tab' value='global' class='"; if(!empty($_POST['tab']) && $_POST['tab'] == "global"){echo "active";} echo "' title='" . __('Global View', 'codecleaner') . "'>" . __('Global View', 'codecleaner') . "</button>";
					echo "<button name='tab' value='settings' class='"; if(!empty($_POST['tab']) && $_POST['tab'] == "settings"){echo "active";} echo "' title='" . __('Settings', 'codecleaner') . "'>" . __('Settings', 'codecleaner') . "</button>";
				echo "</div>";
			echo "</form>";

		echo "</div>";

		//Disclaimer
		if(empty($codecleaner_script_manager_settings['hide_disclaimer']) || $codecleaner_script_manager_settings['hide_disclaimer'] != "1") {
			echo "<div id='codecleaner-script-manager-disclaimer'>";
				echo "<p>";
					_e("Below you can disable/enable CSS and JS files on a per page/post basis, as well as by custom post types. We recommend testing this locally or on a staging site first, as you could break the appearance of your live site. If you aren't sure about a certain script, you can try clicking on it, as a lot of authors will mention their plugin or theme in the header of the source code.", 'codecleaner');
				echo "</p>";
				echo "<p>";
					_e("If for some reason you run into trouble, you can always enable everything again to reset the settings. Make sure to check out the <a href='https://cleancoded.com/docs/' target='_blank' title='Codecleaner Knowledge Base'>Codecleaner knowledge base</a> for more information.", 'codecleaner');
				echo "</p>";
			echo "</div>";
		}

		echo "<div id='codecleaner-script-manager-container'>";

			//Default/Main Tab
			if(empty($_POST['tab'])) {

				echo "<div class='codecleaner-script-manager-title-bar'>";
					echo "<h1>" . __('Script Manager', 'codecleaner') . "</h1>";
					echo "<p>" . __('Manage scripts loading on the current page.', 'codecleaner') . "</p>";
				echo "</div>";

				//Form
				echo "<form method='POST'>";

					foreach($master_array as $category => $groups) {
						if(!empty($groups)) {
							echo "<h3>" . $category . "</h3>";
							if($category != "misc") {
								echo "<div style='background: #ffffff; padding: 10px;'>";
								foreach($groups as $group => $details) {
									if(!empty($details['assets'])) {
										echo "<div class='codecleaner-script-manager-group'>";
											echo "<h4>" . $details['name'];

												//Status
												echo "<div class='codecleaner-script-manager-group-status' style='float: right;'>";
												    codecleaner_script_manager_print_status($category, $group);
												echo "</div>";

											echo "</h4>";

											codecleaner_script_manager_print_section($category, $group, $details['assets']);
										echo "</div>";
									}
								}
								echo "</div>";
							}
							else {
								if(!empty($groups)) {
									codecleaner_script_manager_print_section($category, $category, $groups);
								}
							}
						}
					}

					//Save Button
					echo "<input type='submit' name='codecleaner_script_manager' value='" . __('Save', 'codecleaner') . "' />";

				echo "</form>";

			}
			//Global View Tab
			elseif(!empty($_POST['tab']) && $_POST['tab'] == "global") {

				echo "<div class='codecleaner-script-manager-title-bar'>";
					echo "<h1>" . __('Global View', 'codecleaner') . "</h1>";
					echo "<p>" . __('This is a visual representation of the Script Manager configuration across your entire site.', 'codecleaner') . "</p>";
				echo "</div>";
				
				if(!empty($codecleaner_script_manager_options)) {
					foreach($codecleaner_script_manager_options as $category => $types) {
						echo "<h3>" . $category . "</h3>";
						if(!empty($types)) {
							echo "<table>";
								echo "<thead>";
									echo "<tr>";
										echo "<th>" . __('Type', 'codecleaner') . "</th>";
										echo "<th>" . __('Script', 'codecleaner') . "</th>";
										echo "<th>" . __('Setting', 'codecleaner') . "</th>";
									echo "</tr>";
								echo "</thead>";
								echo "<tbody>";
									foreach($types as $type => $scripts) {
										if(!empty($scripts)) {
											foreach($scripts as $script => $details) {
												if(!empty($details)) {
													foreach($details as $detail => $values) {
														echo "<tr>";
															echo "<td><span style='font-weight: bold;'>" . $type . "</span></td>";
															echo "<td><span style='font-weight: bold;'>" . $script . "</span></td>";
															echo "<td>";
																echo "<span style='font-weight: bold;'>" . $detail . "</span>";
																if($detail == "current" || $detail == "post_types") {
																	if(!empty($values)) {
																		echo " (";
																		$valueString = "";
																		foreach($values as $key => $value) {
																			if($detail == "current") {
																				$valueString.= "<a href='" . get_page_link($value) . "' target='_blank'>" . $value . "</a>, ";
																			}
																			elseif($detail == "post_types") {
																				$valueString.= $value . ", ";
																			}
																		}
																		echo rtrim($valueString, ", ");
																		echo ")";
																	}
																}
															echo "</td>";
														echo "</tr>";
													}
												}
											}
										}
									}
								echo "</tbody>";
							echo "</table>";
						}
					}
				}
			}
			//Settings Tab
			elseif(!empty($_POST['tab']) && $_POST['tab'] == "settings") {

				echo "<div class='codecleaner-script-manager-title-bar'>";
					echo "<h1>" . __('Settings', 'codecleaner') . "</h1>";
					echo "<p>" . __('View and manage all of your Script Manager settings.', 'codecleaner') . "</p>";
				echo "</div>";

				//Form
				echo "<form method='POST' id='script-manager-settings'>";
					echo "<input type='hidden' name='tab' value='settings' />";

					echo "<div class='codecleaner-script-manager-section'>";

						echo "<table>";
							echo "<tbody>";
								echo "<tr>";
									echo "<th>" . codecleaner_title(__('Hide Disclaimer', 'codecleaner'), 'hide_disclaimer') . "</th>";
									echo "<td>";
										echo "<input type='hidden' name='codecleaner_script_manager_settings[hide_disclaimer]' value='0' />";
										$args = array(
								            'id' => 'hide_disclaimer',
								            'option' => 'codecleaner_script_manager_settings',
								            'tooltip' => __('Hide the disclaimer message box across all Script Manager views.', 'codecleaner')
								        );
										codecleaner_print_input($args);
									echo "</td>";
								echo "</tr>";
								echo "<tr>";
									echo "<th>" . codecleaner_title(__('Display Archives', 'codecleaner'), 'separate_archives') . "</th>";
									echo "<td>";
									$args = array(
							            'id' => 'separate_archives',
							            'option' => 'codecleaner_script_manager_settings',
							            'tooltip' => __('Add WordPress archives to your Script Manager selection options. Archive posts will no longer be grouped with their post type.', 'codecleaner')
							        );
									codecleaner_print_input($args);
									echo "</td>";
								echo "</tr>";
							echo "</tbody>";
						echo "</table>";

						//Nonce
						wp_nonce_field('codecleaner_script_manager_save_settings', 'codecleaner_script_manager_settings_nonce');

						//Save Button
						echo "<input type='submit' name='codecleaner_script_manager_settings_submit' value='" . __('Save', 'codecleaner') . "' />";

						
					echo "<div>";
				echo "</form>";
			}
		echo "</div>";
	echo "</div>";
echo "</div>";