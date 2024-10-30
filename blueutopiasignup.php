<?php
/*
Plugin Name: Blue Utopia Sign Up
Plugin URI: http://my.blueutopia.com/admin/apps/wordpress/signup/
Description: Allows your website vistors to volunteer or sign up to your campain/organization. 
Version: 1.0.0
Author: Blue Utopia
Author URI: http://blueutopia.com
License: GPLv2 or later
*/
/*  Copyright 2013 Blue Utopia  (email : info@blueutopia.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

	// Make sure we don't expose any info if called directly
	if ( !function_exists('add_action')) {
		echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
		exit;
	}
	require_once('assets/api.php');
	require_once('assets/emailaddressvalidator.php');

	$blueutopia = new BlueUtopiaSignUp();
	if (is_admin()){		
		add_action('admin_init', array($blueutopia,'adminSettings'));
		add_action('admin_head',  array($blueutopia,'adminHeader'));
		add_action('admin_footer', array($blueutopia,'adminFooter'));
		add_action('admin_menu', array($blueutopia,'AdminAddMenu'));
		add_filter('plugin_action_links_'.plugin_basename(__FILE__), array($blueutopia,'adminActionLinks'));
		add_filter('plugin_row_meta', array($blueutopia,'adminMetaLinks'), 10, 2 );
	}
	
	add_action('init', array($blueutopia,'init'));
	add_action('wp_ajax_blueutopiasignup', array($blueutopia,'submit'));
	add_action('wp_ajax_nopriv_blueutopiasignup', array($blueutopia,'submit'));
	add_action('widgets_init', create_function('', 'return register_widget("BlueUtopiaSignUpWidget");'));
	add_action('widgets_init', create_function('', 'return register_widget("BlueUtopiaVolunteerWidget");'));

	class BlueUtopiaVolunteerWidget extends BlueUtopiaSignUpWidget {
		public $name;
		public $slug;
		public $base;
		public $description;
		public $base_plugin;
		public $plugin;
				
  	function __construct() {
			$this->name = 'Blue Utopia Volunteer';
			$this->description = 'Allow people to volunteer to your campain/organization.';
			$this->slug = 'blueutopiasignup';
			$this->type = 'volunteer';
			$this->base = strtolower(get_class($this));
			$this->plugin = new BlueUtopiaSignUp();
			$this->base_plugin = strtolower(get_class($this->plugin));
			
    	$widget_ops = array('classname'=>$this->base, 'description'=>$this->description);
    	$this->WP_Widget($this->base, $this->name, $widget_ops);
  	}
				
	}

	class BlueUtopiaSignUpWidget extends WP_Widget {
		public $name;
		public $slug;
		public $base;
		public $description;
		public $base_plugin;
		public $plugin;
				
  	function __construct() {
			$this->name = 'Blue Utopia Rapid Sign Up';
			$this->description = 'Allow people to sign up to your campain/organization.';
			$this->slug = 'blueutopiasignup';
			$this->type = 'signup';
			$this->base = strtolower(get_class($this));
			$this->plugin = new BlueUtopiaSignUp();
			$this->base_plugin = strtolower(get_class($this->plugin));

    	$widget_ops = array('classname'=>$this->base, 'description'=>$this->description);
    	$this->WP_Widget($this->base, $this->name, $widget_ops);
  	}

		public function form($instance) {
			$totalcodes = 2; //five is the default but change it to what ever
			$args = array('title'=>'','source'=>'','thankyou'=>'','totalcodes'=>'');
			if(isset($this->type) and $this->type=='volunteer'){
				$args['showfname'] = 'no';
				$args['requirefname'] = 'no';
				$args['showlname'] = 'no';
				$args['requirelname'] = 'no';				
				$args['showaddr1'] = 'no';
				$args['requireaddr1'] = 'no';	
				$args['showcity'] = 'no';
				$args['requirecity'] = 'no';				
				$args['showstate'] = 'no';
				$args['requirestate'] = 'no';
				$args['showphone'] = 'no';
				$args['requirephone'] = 'no';
				
				unset($fields,$fieldoptions,$fieldvalues);
				$fieldvalues = array('yes','no');
				$fieldoptions = array('show','require');
				$fields[] = array('title'=>'First Name','name'=>'fname');
				$fields[] = array('title'=>'Last Name','name'=>'lname');
				$fields[] = array('title'=>'Address','name'=>'addr1');
				$fields[] = array('title'=>'City','name'=>'city');
				$fields[] = array('title'=>'State','name'=>'state');
				$fields[] = array('title'=>'Phone','name'=>'phone');
			}

    	$new_instance = wp_parse_args((array) $instance, $args);
    	$title = $new_instance['title'];
			$thankyou = $new_instance['thankyou'];
			$source = $new_instance['source'];		
			if($instance['totalcodes']!=$totalcodes){
				$totalcodes = $new_instance['totalcodes'];
			}
			if(!is_numeric($totalcodes) or $totalcodes=='0'){	
				$totalcodes = 1;	
			}
			
			if(is_numeric($totalcodes) and $totalcodes!='0'){
				unset($i);
				for ($i = 1; $i <= $totalcodes; $i++) {
					$args['code'.$i] = '';		
				}
				unset($i);
				$instance = wp_parse_args((array) $instance, $args);
			}

			$error = true;			
			if(trim(get_option($this->base_plugin.'_api_key'))!=''){
				try {
					$account_info = $this->plugin->get('account.json?access_token='.trim(get_option($this->base_plugin.'_api_key')));
					if($account_info){
						$account_info = json_decode($account_info);		
						$error = false;				
					}
				} catch (Exception $e) {}		

				try {
					$staticlists = $this->plugin->get('lists/type/constituent.json?access_token='.trim(get_option($this->base_plugin.'_api_key')));
					if($staticlists){
						$staticlists = json_decode($staticlists);									
					}
				} catch (Exception $e) {}
				
			}
			
			if($error){
				if(trim(get_option($this->base_plugin.'_api_key'))!=''){
					print '<p>Error api key is invalid. <a href="/wp-admin/options-general.php?page='.$this->slug.'" target="_blank">Click here</a> to fix it.</p>';
				} else {
					print '<p>Error api key has not been set. <a href="/wp-admin/options-general.php?page='.$this->slug.'" target="_blank">Click here</a> to set it.</a></p>';
				}
			} else {
													
				print '<p>';
				print '<label for="'.$this->get_field_id('title').'">Title:';
				print '<input class="widefat" id="'.$this->get_field_id('title').'" name="'.$this->get_field_name('title').'" 
	type="text" value="'.attribute_escape($title).'" placeholder="Sign Up" />';
				print '</label>';
				print '</p>';

				if(isset($fields) and is_array($fields) and isset($fieldoptions) and is_array($fieldoptions) and isset($fieldvalues) and is_array($fieldvalues)){
					foreach($fields as $field){
						if(isset($field['title']) and trim($field['title'])!='' and isset($field['name']) and trim($field['name'])!=''){
							foreach($fieldoptions as $fieldoption){
								unset($fieldname);
								$fieldname = $fieldoption.trim($field['name']);
								
								print '<p>';
								print '<label for="'.$this->get_field_id($fieldname).'">'.ucfirst(strtolower($fieldoption)).' '.trim($field['title']).':';
								print '<input type="hidden" name="'.$this->get_field_name($fieldname).'" value="'.$fieldoptions['0'].'" />';
								print '<select class="widefat" id="'.$this->get_field_id($fieldname).'" name="'.$this->get_field_name($fieldname).'"';
								print'>';
								foreach($fieldvalues as $fieldvalue){
									print '<option value="'.$fieldvalue.'" label="'.ucfirst(strtolower($fieldvalue)).'"';
									if($instance[$fieldname]==$fieldvalue){
										print ' selected';
									}
									print'>'.ucfirst(strtolower($fieldvalue)).'</option>';																		
									unset($fieldvalue);
								}
								print '</select>';
								print '</p>';
								unset($fieldname,$fieldoption);
							}
						}
						unset($field);
					}
				}
				
				print '<p>';
				print '<label for="'.$this->get_field_id('thankyou').'">Thank You Text:';
				print '<input class="widefat" id="'.$this->get_field_id('thankyou').'" name="'.$this->get_field_name('thankyou').'" 
	type="text" value="'.attribute_escape($thankyou).'" placeholder="Thank You!" />';
				print '</label>';
				print '</p>';	
	
				print '<p>';
				print '<label for="'.$this->get_field_id('source').'">Source:';
				print '<input class="widefat" id="'.$this->get_field_id('source').'" name="'.$this->get_field_name('source').'" 
	type="text" value="'.attribute_escape($source).'" placeholder="online" />';
				print '</label>';
				print '</p>';
				
				if($staticlists and isset($staticlists->info) and is_array($staticlists->info)){
					print '<p>';
					print '<label for="'.$this->get_field_id('totalcodes').'">Total Custom Codes:';
					//We had to put inline on change because of how the widget saves with ajax and javascript does not work after the first save :(
					print '<select class="widefat" onchange="document.getElementById(\''.$this->get_field_id('savewidget').'\').click();" id="'.$this->get_field_id('totalcodes').'" name="'.$this->get_field_name('totalcodes').'">';
					unset($i);
					for ($i = 1; $i <= 10; $i++) {
						print '<option value="'.$i.'" label="'.$i.'"';
						if($i==$totalcodes){
							print ' selected';
						}
						print '>'.$i.'</option>';					
					}
					unset($i);
						
					print '</select>';
					print '</label>';
					print '</p>';																
				}
								
				if($staticlists and isset($staticlists->info) and is_array($staticlists->info) and is_numeric($totalcodes) and $totalcodes!='0'){
					unset($i);
					for ($i = 1; $i <= $totalcodes; $i++) {
						print '<p>';
						print '<label for="'.$this->get_field_id('code'.$i).'">Custom Code:';
						print 
						print '<select class="widefat" id="'.$this->get_field_id('code'.$i).'" name="'.$this->get_field_name('code'.$i).'">';
						print '<option value="" selected>------------------ Select ------------------</option>';
							foreach($staticlists->info as $k=>$v){
								if(($this->type=='signup' and $v->id=='5') or ($this->type=='volunteer' and $v->id=='2')){
									unset($label);
									if(isset($v->name) and trim($v->name)!=''){
										$label = trim($v->name);
									} else {
										$label = 'No Name';
									}
									print '<optgroup label="'.$label.'">';
									unset($label);
									if(isset($v->lists) and is_array($v->lists)){
										foreach($v->lists as $j=>$l){
											unset($label);
											if(isset($l->name) and trim($l->name)!=''){
												$label = trim($l->name);
											} else {
												$label = 'No Name';
											}										
											print '<option value="'.$l->id.'" label="'.$label.'"';
											if($instance['code'.$i]==$l->id){
												print ' selected';
											}
											print '>'.$label.'</option>';
											unset($label,$j,$l);
										}
									}
									
									print '</optgroup>';
									unset($k,$v);
								}
							}
						print '</select>';

						print '</label>';
						print '</p>';
					}
					unset($i);
				}
				
				
			}

		}

  	public function update($new_instance, $old_instance) {
    	$instance = $old_instance;
    	$instance['title'] = $new_instance['title'];
			$instance['source'] = $new_instance['source'];
			$instance['thankyou'] = $new_instance['thankyou'];
			$instance['totalcodes'] = $new_instance['totalcodes'];
			if(isset($instance['totalcodes']) and is_numeric($instance['totalcodes']) and $instance['totalcodes']!='0'){
				unset($i,$check_code);
				$check_code = array();
				for ($i = 1; $i <= $instance['totalcodes']; $i++) {		
					if(!in_array($new_instance['code'.$i],$check_code)){		
						$check_code[] = $new_instance['code'.$i];
						$instance['code'.$i] = $new_instance['code'.$i];
					} else {
						$instance['code'.$i] = '';
					}
				}
				unset($i,$check_code);
			}
			
			if(isset($this->type) and $this->type=='volunteer'){
				$instance['showfname'] = $new_instance['showfname'];
				$instance['requirefname'] = $new_instance['requirefname'];
				$instance['showlname'] = $new_instance['showlname'];
				$instance['requirelname'] = $new_instance['requirelname'];
				$instance['showaddr1'] = $new_instance['showaddr1'];
				$instance['requireaddr1'] = $new_instance['requireaddr1'];		
				$instance['showcity'] = $new_instance['showcity'];
				$instance['requirecity'] = $new_instance['requirecity'];
				$instance['showstate'] = $new_instance['showstate'];
				$instance['requirestate'] = $new_instance['requirestate'];	
				$instance['showphone'] = $new_instance['showphone'];
				$instance['requirephone'] = $new_instance['requirephone'];								
			}
							
			return $instance;
  	}
		
		public function widget($args, $instance) {
    	extract($args, EXTR_SKIP);
    	$title = $instance['title'];
			$source = $instance['source'];
			$thankyou = $instance['thankyou'];
			$totalcodes = $instance['totalcodes'];
			$type = $this->type;
			if(!isset($type) and trim($type)==''){
				$type = 'signup';
			}
			$type = strtolower($type);	
										
			$error = true;			
			if(trim(get_option($this->base_plugin.'_api_key'))!=''){
				try {
					$account_info = $this->plugin->get('account.json?access_token='.trim(get_option($this->base_plugin.'_api_key')));
					if($account_info){
						$account_info = json_decode($account_info);		
						$error = false;				
					}
				} catch (Exception $e) {}		
			}			
			
			if(!$error){
				// WIDGET CODE GOES HERE
								
				print '<aside id="'.$args['widget_id'].'" class="widget widget_'.$this->slug.'" style="display:none;">';
				if(isset($title) and trim($title)!=''){
					print '<h3 class="widget-title">'.trim($title).'</h3>';
				}
				print '<form class="'.$this->slug.'form" id="'.$args['widget_id'].'" action="#" method="post">';
				print '<input type="hidden" name="action" value="'.$this->slug.'">'."\n";
				print '<input type="hidden" name="type" value="'.$type.'">'."\n";
				print '<p class="message" style="display:none;">Message goes here</p>'; //Ajax message goes here
				
				if(isset($type) and $type=='volunteer'){
					
					if(isset($instance['showfname']) and $instance['showfname']=='yes'){
						print '<p>';
						print '<label for="'.$args['widget_id'].'_fname">First Name ';
						if(isset($instance['requirefname']) and $instance['requirefname']=='yes'){
							print '<span class="required">*</span>';
						}
						print '</label>';
						print '<input type="text"';
						if(isset($instance['requirefname']) and $instance['requirefname']=='yes'){
							print ' data-required="yes" data-requiredtext="You must enter in your first name."';
						}
						print ' id="'.$args['widget_id'].'_fname" name="fname" class="text_input fname" ';
						print ' value="" autocomplete="off" style="width:100%;" />';
						print '</p>';						
					}
					
					if(isset($instance['showlname']) and $instance['showlname']=='yes'){
						print '<p>';
						print '<label for="'.$args['widget_id'].'_lname">Last Name ';
						if(isset($instance['requirelname']) and $instance['requirelname']=='yes'){
							print '<span class="required">*</span>';
						}
						print '</label>';
						print '<input type="text"';
						if(isset($instance['requirelname']) and $instance['requirelname']=='yes'){
							print ' data-required="yes" data-requiredtext="You must enter in your last name."';
						}
						print ' id="'.$args['widget_id'].'_lname" name="lname" class="text_input lname" ';
						print ' value="" autocomplete="off" style="width:100%;" />';
						print '</p>';						
					}					
				}				
				
				print '<p>';
				print '<label for="'.$args['widget_id'].'_email">Email <span class="required">*</span></label>';
				print '<input type="text" data-required="yes" data-requiredtext="You must enter in an email address." id="'.$args['widget_id'].'_email" name="email" class="text_input email" value="" autocomplete="off" style="width:100%;" />';
				print '</p>';				

				if(isset($type) and $type=='volunteer'){

					if(isset($instance['showaddr1']) and $instance['showaddr1']=='yes'){
						print '<p>';
						print '<label for="'.$args['widget_id'].'_addr1">Address ';
						if(isset($instance['requireaddr1']) and $instance['requireaddr1']=='yes'){
							print '<span class="required">*</span>';
						}
						print '</label>';
						print '<input type="text"';
						if(isset($instance['requireaddr1']) and $instance['requireaddr1']=='yes'){
							print ' data-required="yes" data-requiredtext="You must enter in your address."';
						}
						print ' id="'.$args['widget_id'].'_addr1" name="addr1" class="text_input addr1" ';
						print ' value="" autocomplete="off" style="width:100%;" />';
						print '</p>';						
					}						

					if(isset($instance['showcity']) and $instance['showcity']=='yes'){
						print '<p>';
						print '<label for="'.$args['widget_id'].'_city">City ';
						if(isset($instance['requirecity']) and $instance['requirecity']=='yes'){
							print '<span class="required">*</span>';
						}
						print '</label>';
						print '<input type="text"';
						if(isset($instance['requirecity']) and $instance['requirecity']=='yes'){
							print ' data-required="yes" data-requiredtext="You must enter in your city."';
						}
						print ' id="'.$args['widget_id'].'_city" name="city" class="text_input city" ';
						print ' value="" autocomplete="off" style="width:100%;" />';
						print '</p>';						
					}	

					unset($states);
					$states = $this->getStates();
					if(isset($instance['showstate']) and $instance['showstate']=='yes' and isset($states) and is_array($states)){
						print '<p>';
						print '<label for="'.$args['widget_id'].'_state">State ';
						if(isset($instance['requirestate']) and $instance['requirestate']=='yes'){
							print '<span class="required">*</span>';
						}
						print '</label>';
						print '<select';
						if(isset($instance['requirestate']) and $instance['requirestate']=='yes'){
							print ' data-required="yes" data-requiredtext="You must select a state."';
						}
						print ' id="'.$args['widget_id'].'_state" name="state" class="text_input state" ';
						print ' style="width:100%;" />';
						print '<option value="">------------ Select ------------</option>';
						foreach($states as $k=>$v){
							print '<option value="'.strtoupper($k).'" label="'.ucwords(strtolower($v)).'">'.ucwords(strtolower($v)).'</option>';							
							unset($k,$v);
						}
						print '</select>';
						print '</p>';						
					}	
					unset($states);
					
				}

				print '<p>';
				print '<label for="'.$args['widget_id'].'_zip">Zip Code <span class="required">*</span></label>';
				print '<input type="text" data-required="yes" data-requiredtext="You must enter in a zip code." id="'.$args['widget_id'].'_zip" name="zip" class="text_input zip" value="" autocomplete="off" style="width:100%;" />';
				print '</p>';

				if(isset($type) and $type=='volunteer'){
					if(isset($instance['showphone']) and $instance['showphone']=='yes'){
						print '<p>';
						print '<label for="'.$args['widget_id'].'_phone">Phone ';
						if(isset($instance['requirephone']) and $instance['requirephone']=='yes'){
							print '<span class="required">*</span>';
						}
						print '</label>';
						print '<input type="text"';
						if(isset($instance['requirephone']) and $instance['requirephone']=='yes'){
							print ' data-required="yes" data-requiredtext="You must enter in your phone number."';
						}
						print ' id="'.$args['widget_id'].'_phone" name="phone" class="text_input phone" ';
						print ' value="" autocomplete="off" style="width:100%;" />';
						print '</p>';						
					}	
				}
				
				print '<p>';
				print '<input id="btn_'.$args['widget_id'].'" type="submit" value="Submit" />';
				print '</p>';
				
				print '<input type="hidden" name="regquick" value="true">'."\n";
				print '<input type="hidden" name="widget_id" value="'.$args['widget_id'].'">'."\n";

				if(is_numeric($totalcodes) and $totalcodes!='0'){
					unset($i);
					for ($i = 1; $i <= $totalcodes; $i++) {
						unset($code);
						$code = $instance['code'.$i];
						if(isset($code) and is_numeric($code) and $code!='0'){
							print '<input type="hidden" name="staticlist[]" value="'.$code.'">'."\n";
						}
						unset($code);
					}
					unset($i);
				}

				if(!isset($source) or trim($source)==''){
					$source = 'wordpress plugin';	
				}
				print '<input type="hidden" name="source" value="'.$source.'">'."\n";
				
				print '</form>';
				print '</aside>';
				
				//End
			}
  	}
		
		protected function getStates(){
			$states['AK'] = "Alaska";
			$states['AL'] = "Alabama";
			$states['AR'] = "Arkansas";
			$states['AZ'] = "Arizona";
			$states['CA'] = "California";
			$states['CO'] = "Colorado";
			$states['CT'] = "Connecticut";
			$states['DC'] = "District of Columbia";
			$states['DE'] = "Delaware";
			$states['FL'] = "Florida";
			$states['GA'] = "Georgia";
			$states['HI'] = "Hawaii";
			$states['IA'] = "Iowa";
			$states['ID'] = "Idaho";
			$states['IL'] = "Illinois";
			$states['IN'] = "Indiana";
			$states['KS'] = "Kansas";
			$states['KY'] = "Kentucky";
			$states['LA'] = "Louisiana";
			$states['MA'] = "Massachusetts";
			$states['MD'] = "Maryland";
			$states['ME'] = "Maine";
			$states['MI'] = "Michigan";
			$states['MN'] = "Minnesota";
			$states['MO'] = "Missouri";
			$states['MS'] = "Mississippi";
			$states['MT'] = "Montana";
			$states['NC'] = "North Carolina";
			$states['ND'] = "North Dakota";
			$states['NE'] = "Nebraska";
			$states['NH'] = "New Hampshire";
			$states['NJ'] = "New Jersey";
			$states['NM'] = "New Mexico";
			$states['NV'] = "Nevada";
			$states['NY'] = "New York";
			$states['OH'] = "Ohio";
			$states['OK'] = "Oklahoma";
			$states['OR'] = "Oregon";
			$states['PA'] = "Pennsylvania";
			$states['RI'] = "Rhode Island";
			$states['SC'] = "South Carolina";
			$states['SD'] = "South Dakota";
			$states['TN'] = "Tennessee";
			$states['TX'] = "Texas";
			$states['UT'] = "Utah";
			$states['VA'] = "Virginia";
			$states['VT'] = "Vermont";
			$states['WA'] = "Washington";
			$states['WI'] = "Wisconsin";
			$states['WV'] = "West Virginia";
			$states['WY'] = "Wyoming";
			return $states;
		}

		
	}
