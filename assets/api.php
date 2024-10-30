<?php

	class BlueUtopiaSignUp {
		private $name;
		private $slug;
		private $base;
		private $api_url;
		private $version;

   	function __construct() {
			$this->name = 'Blue Utopia Sign Up';
			$this->slug = 'blueutopiasignup';
			$this->base = strtolower(get_class($this));
			$this->version = '1.0';
			$this->api_url = 'https://api.blueutopia.com/v1/';
   	}

		public function init() {
   		//register your script location, dependencies and version
			wp_register_script('ajax-'.$this->slug,  plugin_dir_url(__FILE__).'validate.js', array("jquery"));
			wp_enqueue_script('ajax-'.$this->slug);
			wp_localize_script('ajax-'.$this->slug,$this->slug,array("url"=>admin_url("admin-ajax.php")));			
   		//enqueue the script
			wp_enqueue_script('jquery');
			wp_enqueue_script($this->slug);
			wp_enqueue_style($this->slug, plugin_dir_url(__FILE__).'style.css', false, $this->version, "screen");			
		}

		public function submit() {
			global $wpdb; // this is how you get access to the database
			$success = false;
			$message = 'There was an error.';
			if(isset($_REQUEST) and is_array($_REQUEST) and isset($_REQUEST['regquick'])){
				$EmailAddressValidator = new EmailAddressValidator();		
				$type = $_POST['type'];
				if(!isset($type) and trim($type)==''){
					$type = 'signup';
				}
				$type = strtolower($type);		
				if(trim($_POST['email'])==''){
					$message = 'You must enter in an email address.';
				} elseif(!$EmailAddressValidator->check_email_address(trim($_POST['email']))){
					$message = 'You must enter in a valid email address.';
				} elseif(trim($_POST['zip'])==''){
					$message = 'You must enter in a zip code.';		
				} elseif(!$this->validateZipCode(trim($_POST['zip']))){
					$message = 'You must enter in a valid zip code.';	
				} else {
					try {
						$widget_id = $_POST['widget_id'];
						$widget_id = preg_replace("/[^0-9]/", '', $widget_id);
						unset($data);
						$data['access_token'] = trim(get_option($this->slug.'_api_key'));
						$data['zip'] = trim($_POST['zip']);
						$data['email'] = trim($_POST['email']);
						$data['source'] = trim($_POST['source']);
						if(isset($type) and $type=='volunteer'){
							$data['fname'] = trim($_POST['fname']);
							$data['lname'] = trim($_POST['lname']);
							$data['addr1'] = trim($_POST['addr1']);
							$data['city'] = trim($_POST['city']);
							$data['state'] = trim($_POST['state']);
							$data['phone'] = trim($_POST['phone']);	
						}
						if(isset($_POST['staticlist']) and is_array($_POST['staticlist'])){
							$data['lists'] = $_POST['staticlist'];
						}
						$user_info = $this->post('constituents.json',$data);
						if($user_info){
							$user_info = json_decode($user_info);	
							if(isset($user_info) and isset($user_info->info)){
								$options = get_option('widget_blueutopia'.$type.'widget');
								$options = wp_parse_args($options);
								if(isset($widget_id) and is_numeric($widget_id) and isset($options) and is_array($options) and isset($options[$widget_id]) and is_array($options[$widget_id]) and isset($options[$widget_id]['thankyou']) and trim($options[$widget_id]['thankyou'])!=''){
									$thankyou = trim($options[$widget_id]['thankyou']);
								}
								if(isset($thankyou) and trim($thankyou)!=''){
									$message = trim($thankyou);
								} else {
									$message = 'Thank you!';	
								}
								$success = true;	
							}
						}
						unset($data);
					} catch (Exception $e) {
						$message = $e->getMessage();	
					}					
				}			
			} 
			
			$info = array('results'=>array('status'=>$success,'message'=>$message));
			
			if(is_array($info)){
				print json_encode($info);
			}	
			die(); // this is required to return a proper result
		}

		private function isJson($string) {
		 json_decode($string);
		 return (json_last_error() == JSON_ERROR_NONE);
		}

		public function validateZipCode($zipcode=NULL){
			if(isset($zipcode) and trim($zipcode)!=''){
				try {
					$validate_zipcode = $this->get('validate/zip.json?access_token='.trim(get_option($this->base.'_api_key')).'&zip='.$zipcode);
					
					if($validate_zipcode){
						return true;					
					} else {
						return false;
					}
				} catch (Exception $e) {
					return false;
				}						
			} else {
				return false;
			}
		}
		
		
		
		public function post($url_string=NULL,$data=NULL){
			if(isset($url_string) and trim($url_string)!=''){
				$response = wp_remote_post($this->api_url.''.$url_string,array('method'=>'POST','body'=>$data));
				if(is_wp_error($response)) {	
					throw new Exception($response->get_error_message(),$response->get_error_code());
				} else {
					if(isset($response['response']['code']) and is_numeric($response['response']['code']) and ($response['response']['code']=='200' or $response['response']['code']=='201')){
						if(isset($response['body']) and trim($response['body'])!='' and $this->isJson($response['body'])){
							return $response['body'];
						} else {
							throw new Exception('Result must be json!',0);
						}
					} else {
						throw new Exception($response['response']['message'],$response['response']['code']);
					}
				}
			} else {
				return false;
			}	
		}

		public function get($url_string=NULL){
			if(isset($url_string) and trim($url_string)!=''){	
				$response = wp_remote_get($this->api_url.''.$url_string);
				if(is_wp_error($response)) {	
					throw new Exception($response->get_error_message(),$response->get_error_code());
				} else {
					if(isset($response['response']['code']) and is_numeric($response['response']['code']) and ($response['response']['code']=='200' or $response['response']['code']=='201')){
						if(isset($response['body']) and trim($response['body'])!='' and $this->isJson($response['body'])){
							return $response['body'];
						} else {
							throw new Exception('Result must be json!',0);
						}
					} else {
						throw new Exception($response['response']['message'],$response['response']['code']);
					}
				}
			} else {
				return false;
			}			
		}
		
		public function adminMetaLinks( $links, $file ) {
			$plugin = plugin_basename(__FILE__);
			if ( $file == $plugin ) {
				return array_merge(
					$links,
						array('<a href="http://my.blueutopia.com/" target="_blank" title="Log In">Log In</a>' )
					);
				}
			return $links;
		 
		}

		public function adminActionLinks($links){
			$links[] = '<a href="'.admin_url('options-general.php?page='.$this->slug.'').'">'.__('Settings').'</a>';
			return $links;			
		}
	
    public function version_warning() {
    	print '<div id="'.$this->slug.'-warning" class="updated fade"><p><strong>'.sprintf(__($this->name." %s requires WordPress 3.0 or higher."), $this->version).'</strong> '.sprintf(__("Please <a href='%s'>upgrade WordPress</a> to a current version."), "http://codex.wordpress.org/Upgrading_WordPress")."</p></div>";
    }	

		public function adminHeader(){
			//not use right now but soon
		}

		public function adminFooter(){
			//not use right now but soon
		}
		
		public function adminSettings(){
			global $wp_version;
						
    	if (version_compare($wp_version, '3.0', '<' ) ) {
        add_action('admin_notices', $this->version_warning()); 
    	}
						
			register_setting($this->base.'-group', $this->base.'_api_key');
			//register_setting('myoption-group', 'some_other_option' );
			//register_setting('myoption-group', 'option_etc' );
		}

		public function adminAddMenu(){
			add_options_page($this->name, $this->name, 'manage_options', $this->slug, array($this, 'optionPage'));
		}
	
		public function optionPage(){
			if (!current_user_can('manage_options'))  {
				wp_die( __('You do not have sufficient permissions to access this page.') );
			}			
	
			unset($account_info);
			if(trim(get_option($this->base.'_api_key'))!=''){
				try {					
					$account_info = $this->get('account.json?access_token='.trim(get_option($this->base.'_api_key')));
					if($account_info){
						$account_info = json_decode($account_info);						
					}
				} catch (Exception $e) {
					print '<div id="'.$this->base.'-warning" class="updated fade"><p>'.$e->getMessage().'</p></div>';
				}		
			} else {
				//print '<div id="'.$this->base.'-warning" class="updated fade"><p>You must set your Blue Utopia Api Key</p></div>';
			}
	
	
			print '<div class="wrap">';
			print screen_icon();
			print '<h2>'.$this->name.' Settings</h2>';
			print '<form method="post" action="options.php"> ';
			print settings_fields($this->base.'-group');
			print do_settings_fields($this->base.'-group',NULL);
			print '<table class="form-table">';
						
      print '<tr valign="top">';
      print '<th scope="row"><label for="'.$this->base.'_api_key">Api Key</label></th>';
      print '<td>';
			print '<input type="text" id="'.$this->base.'_api_key" name="'.$this->base.'_api_key" class="regular-text" value="'.get_option($this->base.'_api_key').'" autocomplete="off" />';
			print '<p class="description">Your api key can be found <a href="http://my.blueutopia.com/admin/index.php?module=107&child=21&subchild=1" target="_blank">here</a>.</p>';
			print '</td>';
      print '</tr>';	
			
			if($account_info and isset($account_info->info)){
				print '<tr valign="top">';
				print '<th scope="row"><label for="'.$this->base.'_name">Name</label></th>';
				print '<td>';
				print $account_info->info->name;
				print '</td>';
				print '</tr>';
				
				print '<tr valign="top">';
				print '<th scope="row"><label for="'.$this->base.'_domain">Domain</label></th>';
				print '<td>';
				if($account_info->info->domain!=''){
					print '<a href="'.$account_info->info->domain.'" target="_blank">'.$account_info->info->domain.'</a>';
				} else {
					print '---';
				}
				print '</td>';
				print '</tr>';	
				
			}			
			
					
			print '</table>';
			print submit_button(); 
			print '</form>';
			print '</div>';
		}
	}