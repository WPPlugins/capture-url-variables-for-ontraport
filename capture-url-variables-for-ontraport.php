<?php
/**
 * Plugin Name: Capture URL Variables for Ontraport
 * Plugin URI: http://www.itmooti.com/
 * Description: A plugin to add UTM and Referring Page fields on Ontraport Smart Forms
 * Version: 1.3.4
 * Stable tag: 1.3.4
 * Author: ITMOOTI
 * Author URI: http://www.itmooti.com/
 */
$utm_fields=array(
	"utm_source"=>"UTM Source",
	"utm_medium"=>"UTM Medium",
	"utm_term"=>"UTM Term",
	"utm_content"=>"UTM Content",
	"utm_campaign"=>"UTM Campaign",
	"afft_"=>"afft_",
	"aff_"=>"aff_",
	"sess_"=>"sess_",
	"ref_"=>"ref_",
	"own_"=>"own_",
	"oprid"=>"oprid",
	"contact_id"=>"contact_id",
);
$utm_extra_fields=array(
	"fname"=>"fname",
	"lname"=>"lname",
	"email"=>"Email",
	"referral_page"=>"Referral Page URL",
	"landing_page"=>"Landing Page URL",
	"user_ip_address"=>"IP Address"
);
defined('ABSPATH') or die("No script kiddies please!");
class OAPUTM
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
	private $utm_fields, $utm_fields_values=array();
	private $url;
	private $plugin_links;
	/**
     * Start up
     */
    public function __construct($utm_fields, $utm_extra_fields){
		$this->plugin_links=(object)array("support_link"=>get_option("oap_utm_plugin_link_support_link", ""), "license_link"=>get_option("oap_utm_plugin_link_license_link", ""));
		$this->utm_fields=$utm_fields;
		$this->utm_extra_fields=$utm_extra_fields;
		$oap_utm_custom_extra_fields=explode(",", get_option("oap_utm_custom_extra_fields", "var1,var2,var3,var4,var5"));
		foreach($oap_utm_custom_extra_fields as $k=>$v){
			$v=trim(preg_replace('/[^a-zA-Z0-9_\s]/','',$v));
			$v=trim(str_replace(' ',"_",$v));
			if($v!="")
				$this->utm_extra_fields[$v]=$v;
		}
		register_activation_hook(__FILE__, array($this, 'plugin_activation'));
		add_action('plugin_scheduled_event', array($this, 'plugin_authentication'));
		register_deactivation_hook(__FILE__, array($this, 'plugin_deactivation'));
		add_action('admin_enqueue_scripts', array( $this, 'load_admin_style'));
        add_action( 'admin_menu', array( $this, 'add_oap_utm_page' ) );
		add_action('wp_enqueue_scripts', array($this, 'oap_utm_enqueue_js'));
		add_action( 'send_headers', array($this, 'oap_utm_custom_cookies'));
		if($this->is_authenticated()) add_action('wp_head', array($this, 'oap_utm_custom_js'), 300);
		add_action( 'admin_notices', array( $this, 'show_license_info' ) );
		add_shortcode('cuv', array($this, 'shortcode_cuv'));
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array($this, 'itmooti_plugin_action_link'));
		add_filter( 'plugin_row_meta', array($this, 'itmooti_plugin_meta_link'), 10, 2);
    }
	public function is_authenticated(){
		if(get_option("oap_utm_plugin_authenticated", "no")=="yes")
			return true;
		else
			return false;
	}
	public function plugin_activation(){
		wp_schedule_event(time(), 'twicedaily', 'plugin_scheduled_event');
	}
	public function plugin_deactivation(){
		wp_clear_scheduled_hook('plugin_scheduled_event');
	}
	public function plugin_authentication(){
		$isSecure = false;
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
			$isSecure = true;
		}
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
			$isSecure = true;
		}
		$this->url=($isSecure ? 'http' : 'http')."://app.itmooti.com/wp-plugins/oap-utm/api.php";
		$request= "plugin_links";
		$postargs = "plugin=oap-utm&request=".urlencode($request);
		$session = curl_init($this->url);
		curl_setopt ($session, CURLOPT_POST, true);
		curl_setopt ($session, CURLOPT_POSTFIELDS, $postargs);
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($session, CURLOPT_CONNECTTIMEOUT ,3); 
		curl_setopt($session, CURLOPT_TIMEOUT, 3);
		$response = json_decode(curl_exec($session));
		curl_close($session);
		if(isset($response->status) && $response->status=="success"){
			add_option("oap_utm_plugin_link_support_link", $response->message->support_link) or update_option("oap_utm_plugin_link_support_link", $response->message->support_link);
			add_option("oap_utm_plugin_link_license_link", $response->message->license_link) or update_option("oap_utm_plugin_link_license_link", $response->message->license_link);
		}
		$license_key=get_option('oap_utm_license_key', "");
		if(!empty($license_key)){
			$request= "verify";
			$postargs = "domain=".urlencode($_SERVER['HTTP_HOST'])."&license_key=".urlencode($license_key)."&request=".urlencode($request);
			$session = curl_init($this->url);
			curl_setopt ($session, CURLOPT_POST, true);
			curl_setopt ($session, CURLOPT_POSTFIELDS, $postargs);
			curl_setopt($session, CURLOPT_HEADER, false);
			curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($session, CURLOPT_CONNECTTIMEOUT ,3); 
			curl_setopt($session, CURLOPT_TIMEOUT, 3);
			$response = json_decode(curl_exec($session));
			curl_close($session);
			if(isset($response->status) && $response->status=="success"){
				update_option("oap_utm_plugin_authenticated", "yes");
				if(isset($response->message))
					update_option("oap-utm_message", $response->message);
			}
			else if(isset($response->status) && $response->status=="error"){
				update_option("oap_utm_plugin_authenticated", "no");
				if(isset($response->message))
					update_option("oap-utm_message", $response->message);
			}
		}
		else{
			update_option("oap_utm_plugin_authenticated", "no");
			update_option("itmooti-utm_message", "Please enter valid license key");
		}
	}
	public function shortcode_cuv($atts){
		if($this->is_authenticated()){
			if(isset($atts["field"])){
				$field=$atts["field"];
				if(isset($_GET[$field])){
					return $_GET[$field];
				}
				else if(isset($_COOKIE["cuv_".$field])){
					$value=$_COOKIE["cuv_".$field];
					if($value=="undefined")
						$value="";
					return $value;
				}
				else{
					return "";
				}
			}
		}
	}
	public function oap_utm_enqueue_js(){
		wp_enqueue_script('jquery');
	}
	
	function itmooti_plugin_action_link( $links ) {
		return array_merge(
			array(
				'settings' => '<a href="options-general.php?page=oap-utm-admin">Settings</a>',
				'support_link' => '<a href="'.$this->plugin_links->support_link.'" target="_blank">Support</a>'
			),
			$links
		);
	}
	
	function itmooti_plugin_meta_link( $links, $file ) {
		$plugin = plugin_basename(__FILE__);
		if ( $file == $plugin ) {
			return array_merge(
				$links,
				array(
					'settings' => '<a href="options-general.php?page=oap-utm-admin">Settings</a>',
					'support_link' => '<a href="'.$this->plugin_links->support_link.'" target="_blank">Support</a>'
				)
			);
		}
		return $links;
	}
	
	public function show_license_info(){
		$license_key=get_option('oap_utm_license_key', "");
		if(empty($license_key)){
			echo '<div class="updated">
        		<p><strong>Capture URL Variables for Ontraport:</strong> How do I get License Key?<br />Please visit this URL <a href="'.$this->plugin_links->license_link.'" target="_blank">'.$this->plugin_links->license_link.'</a> to get a License Key .</p>
	    	</div>';
		}
		$message=get_option("oap-utm_message", "");
		if($message!=""){
			echo '<div class="error">
        		<p><strong>Capture URL Variables for Ontraport:</strong> '.$message.'</p>
	    	</div>';
		}
	}
	
	public function load_admin_style(){
        wp_enqueue_style( 'chosen_css', plugins_url('js/chosen/chosen.css', __FILE__), false, '1.0.0' );
		wp_enqueue_style( 'oap_utm_css', plugins_url('css/style.css', __FILE__), false, '1.0.0' );
		wp_enqueue_script( 'oap_utm_chosen', plugins_url('js/chosen/chosen.jquery.js', __FILE__), false, '1.0.0' );
		wp_enqueue_script( 'oap_utm_prism', plugins_url('js/js.js', __FILE__), false, '1.0.0' );
	}
	
	/**
     * Add options page
     */
    public function add_oap_utm_page(){
        // This page will be under "Settings"
        add_options_page(
            'Settings Admin', 
            'CUV Settings', 
            'manage_options', 
            'oap-utm-admin', 
            array( $this, 'create_admin_page' )
        );
		add_options_page(
            'Settings Admin', 
            'CUV Shortcodes', 
            'manage_options', 
            'oap-utm-cuv', 
            array( $this, 'create_cuv_page' )
        );
    }
	
	public function create_cuv_page(){
		?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>CUV Shortcode</h2>
            <p>CUV shortcode is used to display URL fields in the page and posts. Below is the syntax of the Shortcode.</p>
            <p><strong>[cuv field="<cite>Field Name</cite>"]</strong></p>
            <p>Where field name could be any of the follwoing values (In Bold).</p>
            <ul style="list-style:circle; padding-left:20px;">
            	<?php
                foreach($this->utm_fields as $k=>$v){
					?>
					<li><strong><?php echo $k?></strong> - <cite><?php echo $v?></cite></li>
					<?php
				}
				foreach($this->utm_extra_fields as $k=>$v){
					?>
					<li><strong><?php echo $k?></strong> - <cite><?php echo $v?></cite></li>
					<?php
				}
				?>
            </ul>
       	</div>
        <?php
	}
    /**
     * Options page callback
     */
    public function create_admin_page(){
		if(isset($_POST["oap_utm_clear_cache"])){
			global $wpdb;
			$wpdb->query( 
				$wpdb->prepare( 
					"DELETE FROM $wpdb->options
					 WHERE option_name like %s"					
				, 'oap_utm_form_id\_%')
			);
		}
		else{
			if(isset($_POST["oap_utm_custom_extra_fields"]))
				add_option("oap_utm_custom_extra_fields", $_POST["oap_utm_custom_extra_fields"]) or update_option("oap_utm_custom_extra_fields", $_POST["oap_utm_custom_extra_fields"]);
			if(isset($_POST["oap_utm_license_key"])){
				add_option("oap_utm_license_key", $_POST["oap_utm_license_key"]) or update_option("oap_utm_license_key", $_POST["oap_utm_license_key"]);
				$this->plugin_authentication();
			}
			if(isset($_POST["oap_utm_api_version"]))
				add_option("oap_utm_api_version", $_POST["oap_utm_api_version"]) or update_option("oap_utm_api_version", $_POST["oap_utm_api_version"]);
			if(isset($_POST["oap_utm_app_id"]))             				add_option("oap_utm_app_id", $_POST["oap_utm_app_id"]) or update_option("oap_utm_app_id", $_POST["oap_utm_app_id"]);
			if(isset($_POST["oap_utm_api_key"]))
				add_option("oap_utm_api_key", $_POST["oap_utm_api_key"]) or update_option("oap_utm_api_key", $_POST["oap_utm_api_key"]);
			
			if(isset($_POST["oap_utm_extra_fields"])){
				$oap_utm_extra_fields=array();
				foreach($_POST["oap_utm_extra_fields"] as $k=>$v){
					$oap_utm_extra_fields[]=$v;
				}
				$oap_utm_extra_fields=serialize($oap_utm_extra_fields);
				add_option("oap_utm_extra_fields", $oap_utm_extra_fields) or update_option("oap_utm_extra_fields", $oap_utm_extra_fields);
			}		
			
			if(isset($_POST["oap_utm_form_ids"])){
				$oap_utm_form_ids=$oap_utm_user_forms=array();
				foreach($_POST["oap_utm_form_ids"] as $k=>$v){
					$oap_utm_form_ids[]=$v;
					if(isset($_POST["form_id_".$v])){
						$oap_utm_fields=get_option("oap_utm_fields", "");
						if(!empty($oap_utm_fields))
							$oap_utm_fields=unserialize($oap_utm_fields);
						else
							$oap_utm_fields=array();
						$oap_utm_api_version=get_option('oap_utm_api_version', "");
						$oap_utm_api_version=$oap_utm_api_version!=""? esc_attr($oap_utm_api_version): "api.ontraport.com";
						if($oap_utm_api_version=="api.moon-ray.com"){
							foreach($this->utm_fields as $k1=>$v1){
								if(in_array($k1, $oap_utm_fields)){
									if(isset($_POST["utm_field_".$k1."_".$v])){
										$oap_utm_user_forms[$v][$k1]=$_POST["utm_field_".$k1."_".$v];
									}
								}
							}
						}
						foreach($this->utm_extra_fields as $k1=>$v1){
							if(isset($_POST["utm_field_".$k1."_".$v])){
								$oap_utm_user_forms[$v][$k1]=$_POST["utm_field_".$k1."_".$v];
							}
						}
					}
				}
				$oap_utm_form_ids=serialize($oap_utm_form_ids);
				add_option("oap_utm_form_ids", $oap_utm_form_ids) or update_option("oap_utm_form_ids", $oap_utm_form_ids);
				$oap_utm_user_forms=serialize($oap_utm_user_forms);
				add_option("oap_utm_user_forms", $oap_utm_user_forms) or update_option("oap_utm_user_forms", $oap_utm_user_forms);		
			}
			
			//UTM Fields
			if(isset($_POST["oap_utm_fields"])){
				$oap_utm_fields=array();
				foreach($_POST["oap_utm_fields"] as $k=>$v){
					$oap_utm_fields[]=$v;
				}
				$oap_utm_fields=serialize($oap_utm_fields);
				add_option("oap_utm_fields", $oap_utm_fields) or update_option("oap_utm_fields", $oap_utm_fields);
			}
			$oap_utm_fields=get_option("oap_utm_fields", "");
			if(!empty($oap_utm_fields))
				$oap_utm_fields=unserialize($oap_utm_fields);
			else
				$oap_utm_fields=array();
			foreach($this->utm_fields as $k=>$v){
				if(in_array($k, $oap_utm_fields)){
					if(isset($_POST["utm_fields_custom_".$k]) && !empty($_POST["utm_fields_custom_".$k])){
						add_option("utm_fields_custom_".$k, $_POST["utm_fields_custom_".$k]) or update_option("utm_fields_custom_".$k, $_POST["utm_fields_custom_".$k]);
					}
				}
			}
		}
		?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>CUV Settings</h2>           
            <form method="post">
           	  	<h3>Plugin Credentials</h3>
                Provide Plugin Credentials below:
                <?php $license_key=get_option('oap_utm_license_key', "");?>
                <table class="form-table">
                	<tr>
                    	<th scope="row">License Key</th>
                        <td><input type="text" name="oap_utm_license_key" id="oap_utm_license_key" value="<?php echo $license_key?>" /></td>
                   	</tr>
              	</table>
				<?php				
				if($this->is_authenticated()){
					$oap_utm_api_version=get_option('oap_utm_api_version', "");
					$oap_utm_app_id=get_option('oap_utm_app_id', "");
					$oap_utm_api_key=get_option('oap_utm_api_key', "");
					?>
					<h3>OAP Credentials</h3>
					Provide OAP Credentials below:
					<table class="form-table">
						<tr>
							<th scope="row">API Version</th>
							<td>
								<select id="oap_utm_api_version" name="oap_utm_api_version">
									<option value="api.ontraport.com"<?php echo ($oap_utm_api_version=="api.ontraport.com"? ' selected="selected"':"")?>>V3.0</option>
									<option value="api.moon-ray.com"<?php echo ($oap_utm_api_version=="api.moon-ray.com"? ' selected="selected"':"")?>>V2.4</option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">APP ID</th>
							<td><input type="text" name="oap_utm_app_id" id="oap_utm_app_id" value="<?php echo $oap_utm_app_id?>" /></td>
						</tr>
						<tr>
							<th scope="row">API Key</th>
							<td><input type="text" name="oap_utm_api_key" id="oap_utm_api_key" value="<?php echo $oap_utm_api_key?>" /></td>
						</tr>
					</table>
					<?php
					$this->get_oap_forms();
				}
				else{
					$message=get_option("oap-utm_message", "");
					if($message!=""){
						echo $message;
					}
				}
                submit_button(); 
            ?>
            <input type="submit" name="oap_utm_clear_cache" class="button button-primary" value="Clear Form Cache" />
            </form>
        </div>
        <?php
    }

    /** 
     * Print the Section text
     */
    public function get_oap_forms(){
		set_time_limit(5000);
		$oap_utm_user_forms=get_option("oap_utm_user_forms", "");
		if(!empty($oap_utm_user_forms))
			$oap_utm_user_forms=unserialize($oap_utm_user_forms);
		else
			$oap_utm_user_forms=array();
		$oap_utm_api_version=get_option('oap_utm_api_version', "");
		$oap_utm_app_id=get_option('oap_utm_app_id', "");
		$oap_utm_api_key=get_option('oap_utm_api_key', "");
		$oap_utm_license_key=get_option('oap_utm_license_key', "");
		if($oap_utm_app_id!="" && $oap_utm_api_key!=""){
			?>
            <h3>OAP Forms</h3>
            <?php
			$appid = $oap_utm_app_id;
			$key = $oap_utm_api_key;
			$license_key = $oap_utm_license_key;
			$ver=$oap_utm_api_version!=""? esc_attr($oap_utm_api_version): "api.ontraport.com";
			$reqType= "fetch";
			$postargs = "appid=".$appid."&key=".$key."&return_id=1&reqType=".$reqType;
			$request = "http://".$ver."/fdata.php";
			$session = curl_init($request);
			curl_setopt ($session, CURLOPT_POST, true);
			curl_setopt ($session, CURLOPT_POSTFIELDS, $postargs);
			curl_setopt($session, CURLOPT_HEADER, false);
			curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($session, CURLOPT_CONNECTTIMEOUT ,3); 
			curl_setopt($session, CURLOPT_TIMEOUT, 10);
			$response = curl_exec($session);
			curl_close($session);
			if($response!="" && strpos($response, "<error>Invalid AppId / Key Combination</error>")===false){
				$forms=array();
				$cnt=0;
				while(($start=strpos($response, '<form'))!==false && $cnt<20){
					if(($end=strpos($response, '</form>', $start))!==false){
						$form=substr($response, $start, $end-$start+7);
						$id=explode("id='", $form);
						$id=explode("'", $id[1]);
						$id=$id[0];
						$form_details=get_option('oap_utm_form_id_'.$id, "");
						if($form_details==""){
							$cnt++;
							$reqType= "fetch";
							$postargs = "appid=".$appid."&key=".$key."&return_id=1&id=".$id."&reqType=".$reqType;
							$request = "http://".$ver."/fdata.php";
							
							$session = curl_init($request);
							curl_setopt ($session, CURLOPT_POST, true);
							curl_setopt ($session, CURLOPT_POSTFIELDS, $postargs);
							curl_setopt($session, CURLOPT_HEADER, false);
							curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
							curl_setopt($session, CURLOPT_CONNECTTIMEOUT ,3); 
							curl_setopt($session, CURLOPT_TIMEOUT, 10);
							$str = curl_exec($session);
							$form_id="";
							if($str!=""){
								if(($start1=strpos($str, 'name="uid"'))!==false && ($start1=strpos($str, 'value="', $start1))!==false){
									$start1+=strlen('value="');
									if(($end1=strpos($str, '"', $start1))!==false){
										$form_id=trim(substr($str, $start1, $end1-$start1));
										$form_fields=array();
										while(($start1=strpos($str, '<input'))!==false){
if(($start2=strpos($str, '<select'))!==false && $start2<$start1) $start1=$start2;
if(($start2=strpos($str, '<textarea'))!==false && $start2<$start1) $start1=$start2;
											if(($end1=strpos($str, '>', $start1))!==false){
												$temp=substr($str, $start1, $end1-$start1+6);
												$field_name=$field_title="";
												if(($start2=strpos($temp, 'name="'))!==false){
													$start2+=6;
													if(($end2=strpos($temp, '"', $start2))!==false){
														$field_name=strip_tags(substr($temp, $start2, $end2-$start2));
														if($field_name!="uid"){
															$form_fields[]=array($field_title, $field_name);
														}
													}
												}
											}
											$str=substr($str, $start1+10);
										}
										$forms[]=array(
											"id" => $form_id,
											"title" => $id." - ".strip_tags($form),
											"form_fields" => $form_fields

										);
										add_option('oap_utm_form_id_'.$id, serialize(array(
											"id" => $form_id,
											"title" => $id." - ".strip_tags($form),
											"form_fields" => $form_fields
										)));
									}
								}
							}
						}
						else{
							$forms[]=unserialize($form_details);
						}
					}
					//if($cnt>1)
						//break;
					$response=substr($response, $start+10);
				}
				?>
                <table class="form-table">	
                    <tr valign="top">
                        <th scope="row">Select Forms<br /><small>Select all forms which need to have the below fields.</small></th>
                        <td>
                        	<?php
							$cnt=0;
							$oap_utm_form_ids=get_option("oap_utm_form_ids", "");
							if(!empty($oap_utm_form_ids))
								$oap_utm_form_ids=unserialize($oap_utm_form_ids);
							else
								$oap_utm_form_ids=array();
							?>
							<select name="oap_utm_form_ids[]" multiple="multiple" id="oap_utm_form_ids" class="owp_utm-multi-select">
							<?php
							foreach($forms as $k=>$v){
								?>
								<option value="<?php echo $v["id"]?>"<?php if(in_array($v["id"], $oap_utm_form_ids)) echo ' selected="selected"';?>><?php echo $v["title"]?></option>
								<?php
								$cnt++;
							}
							?>
                            </select>
                            <br /><small>On the first load of the CUV plugins settings page you will only see 10 forms. We limit it to 10 so as not to have the plugin timeout as OP is serving the form API. If you have more than 10 forms, these extra forms will load in to the database of the plugin on further loads of this settings page, 10 at a time.<br /><br />If you update any of your forms you will need to hit the clear cache button to reload the forms.<br /><br /><a name="oap_utm_load_more" class="button button-primary" href="<?php echo $_SERVER['REQUEST_URI']?>">Load More Forms</a></small>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Select Fields<br /><small>Select all fields which need to have in the forms as hidden fields.</small></th>
                        <td>
                        	<select name="oap_utm_fields[]" multiple="multiple" id="oap_utm_fields" class="owp_utm-multi-select">
							<?php
							$oap_utm_fields=get_option("oap_utm_fields", "");
							if(!empty($oap_utm_fields))
								$oap_utm_fields=unserialize($oap_utm_fields);
							else
								$oap_utm_fields=array();
							foreach($this->utm_fields as $k=>$v){
								?>
								<option value="<?php echo $k?>"<?php if(in_array($k, $oap_utm_fields)) echo ' selected="selected"';?>><?php echo $v?></option>
								<?php
								$cnt++;
							}
							?>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Define Custom Extra Fields<br /><small>If you want to use more fields then write them here seaparated by comma.</small></th>
                        <td>
                        	<?php
				$oap_utm_custom_extra_fields=get_option("oap_utm_custom_extra_fields", "var1,var2,var3,var4,var5");
				?>
                            <input type="text" name="oap_utm_custom_extra_fields" id="oap_utm_custom_extra_fields" value="<?php echo $oap_utm_custom_extra_fields?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Select Extra Fields<br /><small>Select extra fields which need to be selected and assigned for each selected form.</small></th>
                        <td>
                        	<select name="oap_utm_extra_fields[]" multiple="multiple" id="oap_utm_extra_fields" class="owp_utm-multi-select">
							<?php
							$oap_utm_extra_fields=get_option("oap_utm_extra_fields", "");
							if(!empty($oap_utm_extra_fields))
								$oap_utm_extra_fields=unserialize($oap_utm_extra_fields);
							else
								$oap_utm_extra_fields=array();
							foreach($this->utm_extra_fields as $k=>$v){
								?>
								<option value="<?php echo $k?>"<?php if(in_array($k, $oap_utm_extra_fields)) echo ' selected="selected"';?>><?php echo $v?></option>
								<?php
								$cnt++;
							}
							?>
                            </select>
                        </td>
                    </tr>
                    <?php
                    if(count($oap_utm_extra_fields)>0){
						?>
						<tr>
                        	<th colspan="2"><h3>Assign Extra Fields for each Form</h3></th>
                        </tr>
                        <tr>
                        	<td colspan="2">
                            	<?php
								$oap_utm_user_forms=get_option("oap_utm_user_forms");
								if($oap_utm_user_forms)
									$oap_utm_user_forms=unserialize($oap_utm_user_forms);
								else
									$oap_utm_user_forms=array();
								foreach($forms as $k=>$v){
								    if(in_array($v["id"], $oap_utm_form_ids)){
										if(isset($oap_utm_user_forms[$v["id"]]))
											$pos=$v["id"];
										else
											$pos=false;
										?>
                                        <div class="oap_utm_forms">
                                            <h3><label for="form_id_<?php echo $v["id"]?>"><?php echo $v["title"]?></label><input type="checkbox" id="form_id_<?php echo $v["id"]?>" name="form_id_<?php echo $v["id"]?>" value="<?php echo $v["id"]?>"<?php if($pos) echo ' checked="checked"';?> /></h3>
                                            <div class="oap_utm_forms_content"<?php if($pos) echo ' style="display: block"';?>>
                                                <p>Please select the fields of your form to be used by this plugin.</p>
                                                <?php
												if($oap_utm_api_version=="api.moon-ray.com"){
													foreach($this->utm_fields as $k1=>$v1){
														if(in_array($k1, $oap_utm_fields)){
															?>
															<label for="utm_field_<?php echo $k1."_".$v["id"]?>"><?php echo $v1?></label>
															<select name="utm_field_<?php echo $k1."_".$v["id"]?>">
																<option value=""<?php if($pos && isset($oap_utm_user_forms[$pos][$k1]) && $oap_utm_user_forms[$pos][$k1]=="") echo ' selected="selected"';?>>None</option>
																<?php
																foreach($v["form_fields"] as $k2=>$v2){
																	?>
																	<option value="<?php echo $v2[1]?>"<?php if($pos && isset($oap_utm_user_forms[$pos][$k1]) && $oap_utm_user_forms[$pos][$k1]==$v2[1]) echo ' selected="selected"';?>><?php echo $v2[1].""; ?></option>
																	<?php
																}
																?>
															</select>
															<div class="clr"></div>
															<?php
														}
													}
												}
                                                foreach($this->utm_extra_fields as $k1=>$v1){
                                                    if(in_array($k1, $oap_utm_extra_fields)){
                                                        ?>
                                                        <label for="utm_field_<?php echo $k1."_".$v["id"]?>"><?php echo $v1?></label>
                                                        <select name="utm_field_<?php echo $k1."_".$v["id"]?>">
                                                            <option value=""<?php if($pos && isset($oap_utm_user_forms[$pos][$k1]) && $oap_utm_user_forms[$pos][$k1]=="") echo ' selected="selected"';?>>None</option>
															<?php
                                                            foreach($v["form_fields"] as $k2=>$v2){
                                                                ?>
                                                                <option value="<?php echo $v2[1]?>"<?php if($pos && isset($oap_utm_user_forms[$pos][$k1]) && $oap_utm_user_forms[$pos][$k1]==$v2[1]) echo ' selected="selected"';?>><?php echo $v2[1].""; ?></option>
                                                                <?php
                                                            }
                                                            ?>
                                                        </select>
                                                        <div class="clr"></div>
                                                        <?php
                                                    }			
                                                }
                                                ?>
                                            </div>
                                       	</div>
                                        <?php
									}
								}
								?>
                            </td>
                        </tr>
						<?php
					}
					?>
                </table>
				<input type="hidden" name="oap_utm_total_forms" value="<?php echo count($forms)?>" />
				<?php
			}
			else{
				echo "Error in fetching data from API. Invalid AppId / Key Combination or Please try again.";
			}
		}
	}
	public function get_real_ip(){
		$ip = FALSE;
		if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
			$ip = $_SERVER["HTTP_CLIENT_IP"];
		}

		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
			if ($ip) {
				array_unshift($ips, $ip);
				$ip = FALSE;
			}
			for ($i = 0; $i < count($ips); $i++) {
				if (!eregi ("^(10|172\.16|192\.168)\.", $ips[$i])) {
					if (version_compare(phpversion(), "5.0.0", ">=")) {
						if (ip2long($ips[$i]) != false) {
							$ip = $ips[$i];
							break;
						}
					} else {
						if (ip2long($ips[$i]) != -1) {
							$ip = $ips[$i];
							break;

						}
					}
				}
			}
		}
		return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
	}
	
	public function get_variable_value($var){
		if($var=="user_ip_address"){
			return $this->get_real_ip();
		}
		else if($var=="referral_page"){
			if(isset($_COOKIE["cuv_referral_page"]))
				return $_COOKIE["cuv_referral_page"];
			else{
				if(isset($_SERVER['HTTP_REFERER']))
					$val=$_SERVER['HTTP_REFERER'];
				else
					$val="";
				setcookie("cuv_referral_page", $val, strtotime('+1 days'), "/");
				return $val;
			}
		}
		else if($var=="landing_page"){
			if(isset($_COOKIE["cuv_landing_page"]))
				return $_COOKIE["cuv_landing_page"];
			else{
				$pageURL = 'http';
				if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
					$pageURL .= "://";
				if (isset($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"] != "80") {
				 	$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
				} else {
				 	$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
				}
				setcookie("cuv_landing_page", $pageURL, strtotime('+1 days'), "/");
				return $pageURL;
			}
		}
		else{
			if(isset($_GET[$var]) || ($var=="fname" && isset($_GET["firstname"])) || ($var=="lname" && isset($_GET["lastname"]))){
				if($var=="fname")
					$val=$_GET["firstname"];
				else if($var=="lname")
					$val=$_GET["lastname"];
				else
					$val=$_GET[$var];
				setcookie("cuv_".$var, $val, strtotime('+1 days'), "/");
				return $val;
			}
			else if(isset($_COOKIE["cuv_".$var])){
				return $_COOKIE["cuv_".$var];
			}
			else
				return "";
		}
	}
	public function oap_utm_custom_cookies(){
		foreach($this->utm_fields as $k=>$v){
			$this->utm_fields_values[$k]=$this->get_variable_value($k);
		}
		foreach($this->utm_extra_fields as $k=>$v){
			$this->utm_fields_values[$k]=$this->get_variable_value($k);
		}
	}
	public function oap_utm_custom_js() {
		$oap_utm_form_ids=get_option("oap_utm_form_ids", "");
		if(!empty($oap_utm_form_ids))
			$oap_utm_form_ids=unserialize($oap_utm_form_ids);
		else
			$oap_utm_form_ids=array();
		$oap_utm_fields=get_option("oap_utm_fields", "");
		if(!empty($oap_utm_fields))
			$oap_utm_fields=unserialize($oap_utm_fields);
		else
			$oap_utm_fields=array();
		$oap_utm_user_forms=get_option("oap_utm_user_forms");
		if($oap_utm_user_forms)
			$oap_utm_user_forms=unserialize($oap_utm_user_forms);
		else
			$oap_utm_user_forms=array();
		?>
		<script type="text/javascript">
			oap_utm_forms=new Array();
			oap_utm_forms_fields=new Array();
			<?php
			$i=0;
			foreach($oap_utm_form_ids as $k=>$v){
				?>
				oap_utm_forms[<?php echo $i?>]='<?php echo $v?>';
				oap_utm_forms_fields[<?php echo $i?>]=new Object();
				<?php
				if(isset($oap_utm_user_forms[$v])){
					foreach($oap_utm_user_forms[$v] as $k1=>$v1){
						if($v1!="uid"){?>
							oap_utm_forms_fields[<?php echo $i?>].<?php echo $k1?>='<?php echo $v1?>';
						<?php
						}
					}
				}
				$i++;
			}
			?>
			var $utm_fields=new Object();
			function utm_fields_initialize(){
				<?php

				foreach($this->utm_fields as $k=>$v){
					?>
					$utm_fields.<?php echo $k?>='<?php echo $this->utm_fields_values[$k]?>';
					<?php
				}
				foreach($this->utm_extra_fields as $k=>$v){
					?>
					$utm_fields.<?php echo $k?>='<?php echo $this->utm_fields_values[$k]?>';
					<?php
				}
				?>
			}
			jQuery(window).load(function(){
				utm_fields_initialize();
				setTimeout(function(){
					jQuery("form").each(function(){
						$this=jQuery(this);
						if($this.find("input[name=uid]").length>0){
							$index=oap_utm_forms.indexOf($this.find("input[name=uid]").val());
							if($index!==-1){
								<?php
								foreach($oap_utm_fields as $k=>$v){
									$oap_utm_api_version=get_option('oap_utm_api_version', "");
									if($oap_utm_api_version!="api.moon-ray.com"){
										?>
										if($this.find("input[name=<?php echo $v?>]").length==0){
											$this.prepend('<input type="hidden" name="<?php echo $v?>" id="<?php echo $v?>" value="" />');
										}
										$this.find("input[name=<?php echo $v?>]").val($utm_fields.<?php echo $v?>);
										<?php
									}
								}
								?>
								if(typeof(oap_utm_forms_fields[$index])!='undefined'){
									for (key in oap_utm_forms_fields[$index]) {
										if(oap_utm_forms_fields[$index][key]!=""){
											if(key=="user_ip_address"){
												$this.find("input[name="+oap_utm_forms_fields[$index][key]+"]").val($user_ip_address_response);
											}
											else{
												$this.find("input[name="+oap_utm_forms_fields[$index][key]+"]").val($utm_fields[key]);
												if($this.find("select[name="+oap_utm_forms_fields[$index][key]+"]").length>0){
													$this.find("select[name="+oap_utm_forms_fields[$index][key]+"] option[value="+$utm_fields[key]+"]").prop('selected', true);
												}
												if($this.find("textarea[name="+oap_utm_forms_fields[$index][key]+"]").length>0){
													$this.find("textarea[name="+oap_utm_forms_fields[$index][key]+"]").val($utm_fields[key]);
												}
											}
										}
									}
								}
							}
						}
					});
				}, 2000);
			});
		</script>
		<?php
	}
}

$oap_utm_settings_page = new OAPUTM($utm_fields, $utm_extra_fields);