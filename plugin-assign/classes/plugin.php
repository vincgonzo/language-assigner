<?php
include( dirname( __FILE__ ) .  "/label-translator.php" );
class LANG_MANAGEMENT_Plugin {
  const DB_NAME = "lang_language_admin";
  const DB_LABEL_TRANSLATOR_MAIN = "labels_main";
  const DB_LABEL_TRANSLATOR = "labels_translations";
  const LANG_MARKER = "custom_lang";
  const LANG_MARKER_VALUE = "custom_lang_value";
  private $plugin_name = "language-admin";

  /**
  * Register hooks
  */
  public function __construct()
  {
    add_action( 'admin_menu', array($this,'add_menu'));
    // hook to save datas from custom form creation page
    add_action( 'admin_action_lang_save', array($this, 'lang_save') );
    add_action('admin_footer', array($this, 'lang_admin_page'));
    add_action('admin_init', array($this, 'save_custom_site_options'));
    add_action( 'wp_initialize_site',  array($this, 'action_function_name_4156'));
    add_action( 'admin_enqueue_scripts', array($this, '_enqueue_admin_script'));
  }

  public static function action_function_name_4156( $new_site ){
    $tttt = update_blog_option( $new_site->blog_id, self::LANG_MARKER, $_POST['blog']['custom_lang'] );
    return true;
  }

  public static function init() {
    // creation db script
    register_activation_hook( LANG_MANAGE_FILE, array(__CLASS__, 'check_table' ));
  }

  /**
  * Enqueue a script in the WordPress admin on edit.php.
  *
  * @param int $hook Hook suffix for the current admin page.
  */
   function _enqueue_admin_script( $hook ) {
    global $pagenow;
    if ( $_REQUEST['page'] == 'language-translator-admin' ) {
       wp_enqueue_style( 'bootstrap_min', plugins_url( '../assets/css/bootstrap.min.css', __FILE__ ), array(), '1.0' );
    }
    return;
   }

  public static function save_custom_site_options(){
     global $pagenow;
     if( ('site-info.php' == $pagenow || 'site-new.php' == $pagenow) && isset($_REQUEST['action']) && ('update-site' == $_REQUEST['action'] || 'add-site' == $_REQUEST["action"] ) ){
       if ( isset( $_POST['blog'][self::LANG_MARKER] ) ) {
         $new_field_value = intval( $_POST['blog'][self::LANG_MARKER] );
         if( is_int($new_field_value) ){
            global $wpdb;
            $query_select = "SELECT blog_id FROM wp_blogs WHERE path ='/".$_POST['blog']['domain']."/'";
            $all_sites = $wpdb->get_results($query_select);
            foreach( $all_sites as $site ){
             update_blog_option( $all_sites->blog_id, self::LANG_MARKER, $new_field_value );
            }
            update_blog_option( $_POST['id'], self::LANG_MARKER, $new_field_value );
          }
        }
     }
  }

  public static function lang_admin_page(){
    global $pagenow, $wpdb;
    $table_name = $wpdb->base_prefix . self::DB_NAME;
    $result = $wpdb->get_results( "SELECT * FROM  ". $table_name );
    if(ctype_digit($_GET['id']))
      $custom_limit_site_id = $_GET['id'];
    else
      $custom_limit_site_id ='';
    $opt_def='';
    if(!empty($custom_limit_site_id)){
      $opt_def = (get_blog_option( $custom_limit_site_id, self::LANG_MARKER))? get_blog_option( $custom_limit_site_id, self::LANG_MARKER) : NULL;
    }
    $dropdown_html = '<select required id="lang_selector" class="regular-text" name="blog[' . self::LANG_MARKER . ']">
          <option value="default">'.__( 'Select a Language', 'language_admin' ).'</option>';
    
    foreach ( $result as $lang ){
      $lang_id = esc_html( $lang->id );
      $lang_code = esc_html( $lang->language_code );
      $lang_title = esc_html( $lang->language_title );
      $selected = "";
      if($opt_def != NULL)
        $selected = ($lang->id == $opt_def)? "selected" : "";
      $dropdown_html .= '<option value="' . $lang_id . '" '. $selected.'>' . $lang_title . ' (' . $lang_code  . ') ' . '</option>' . "\n";
    }
    
    $dropdown_html .= '</select>';
    if( 'site-info.php' == $pagenow || 'site-new.php' == $pagenow) {
       ?><table><tr id="lang_admin_page">
          <th scope="row">Language select </th>
          <td><?php echo $dropdown_html; ?></td>
          </tr>
         </table>
         <script>
          jQuery(function($){
            $('.form-table tbody').append($('#lang_admin_page'));
           });
         </script>
         <?php
      }
    }

    /**
    * Check the language table exists
    *
    * @return string|boolean One of 'exists' (table already existed), 'created' (table was created), or false if could not be created
    */
    public static function check_table() {
       global $wpdb;
       $t_main = $wpdb->base_prefix . self::DB_NAME;
       $t_labelMain = $wpdb->base_prefix . self::DB_LABEL_TRANSLATOR_MAIN;
       $t_labelTranslation = $wpdb->base_prefix . self::DB_LABEL_TRANSLATOR;
       $sqlMain = "CREATE TABLE $t_main (
          id bigint(20) NOT NULL auto_increment,
          language_code varchar(8) NOT NULL,
          language_title varchar(200) NOT NULL,
          user_id int(20) NOT NULL,
          creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY  (id),
          KEY lang_id (id,language_code)
         )";
         $sqlLabel = "CREATE TABLE $t_labelMain (
          id bigint PRIMARY KEY NOT NULL,
          label text NOT NULL,
         );
         CREATE UNIQUE INDEX PRIMARY ON $t_labelMain(id);
         CREATE TABLE $t_labelTranslation (
            id bigint PRIMARY KEY NOT NULL,
            label_id bigint NOT NULL,
            label_value text NOT NULL,
            label_lang varchar(10) NOT NULL
            label_activated boolean
         );
         CREATE UNIQUE INDEX PRIMARY ON $t_labelTranslation(id);";
         $qLabel = $wpdb->get_results( 'SHOW TABLES LIKE ' . $t_label );
         if ( ! $wpdb->get_var( $qLabel ) == $t_label ) {
             $wpdb->query($sqlLabel);
                        return 'created';
         }
     return 'exists';
   }

   public function add_menu()
   {
     add_menu_page(
      'Language Administration',
      'Language Administration',
      'manage_sites',
      'language-admin-top-level',
      array(__CLASS__, '_lang_admin_page_contents'),
      'dashicons-translation'
     );
     if(class_exists('LabelTranslator'))
        $LabelOBJ = new LabelTranslator();
      $LabelOBJ::label_add_menu();

   }

  /**
  * Element for creation page language form
  */
  public function _lang_admin_page_contents() {
    global $wpdb;
    // user rights verification
    if ( ! current_user_can( 'manage_sites' ) ) {
     wp_die( __( 'Sorry, you are not allowed to edit this site.' ) );
    }
    $table_name = $wpdb->base_prefix . self::DB_NAME;
    // Security wp_nonce
    $nds_add_meta_nonce = wp_create_nonce( "_meta_form_nonce" );
    $result = $wpdb->get_results( "SELECT * FROM  ". $table_name );
    $dropdown_html = '<select required id="lang_selector" class="regular-text" name="lang[user_select]">
       <option value="default">'.__( 'Select a Language', 'language_admin' ).'</option>';
    foreach ( $result as $lang ){
      $lang_code = esc_html( $lang->language_code );
      $lang_title = esc_html( $lang->language_title );
      $dropdown_html .= '<option value="' . $lang_code . '">' . $lang_title . ' (' . $lang_code  . ') ' . '</option>' . "\n";
    }
    $dropdown_html .= '</select>';
    ?>
    <h1>
      <?php _e( 'Welcome to Language creation page.', 'language_admin' ); ?>
    </h1>
    <div>
      <table class="form-table" role="presentation">
      <tbody><tr><th scope="row"><label for="lang_selector"><?php _e("Language select", "language_admin"); ?></label></th></tr>
      <tr><td>
      <?php echo $dropdown_html; ?>
      <p class="description" id="language-code-description"><?php _e("Lists of all languages in database.", "language_admin"); ?></p>
      </td></tr></tbody></table>
    </div>
    <hr>
    <div>
      <h2><?php _e("Add New Language", "language_admin"); ?></h2>
      <form method="POST" action="<?php echo admin_url( 'admin.php' ); ?>">
        <input type="hidden" name="action" value="lang_save">
        <input type="hidden" name="_meta_nonce" value="<?php echo $nds_add_meta_nonce ?>" />
        <table class="form-table" role="presentation">
          <tbody>
             <tr><th scope="row"><label for="lang_title"><?php _e("Language Title", "language_admin"); ?></label></th><td><input class="regular-text" type="text"  name="lang_title" id="lang_title" placeholder="Enter title for language description"><p class="description" id="language-code-description"><?php _e("Language title used in admin to assigned on child sites.", "language_admin"); ?></p></td></tr>
             <tr><th scope="row"><label for="lang_code"><?php _e("Language Code", "language_admin"); ?></label></th>
             <td><input class="regular-text" type="text" name="lang_code" id="lang_code" placeholder="Enter language code"><p class="description" id="language-code-description"><?php _e("language code is for Back end use.", "language_admin"); ?></p></td></tr>
           </tbody>
         </table>                                                  
        <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Submit Form"></p>
        </form>
    </div>
   <?php
   return;
  }


  /**
  * Hook for treatment and save of datas from creation page
  */
  public function lang_save(){
    global $wpdb;
    $table_name = $wpdb->base_prefix . self::DB_NAME;
    if( isset( $_POST['_meta_nonce'] ) && wp_verify_nonce( $_POST['_meta_nonce'], '_meta_form_nonce') ) {
      // collect && sanitize the input
      $langTitle = (isset($_POST["lang_title"]) && !empty($_POST["lang_title"]))? sanitize_text_field($_POST["lang_title"]) : 0;
      $langCode = (isset($_POST["lang_code"]) && !empty($_POST["lang_code"]))? sanitize_text_field($_POST["lang_code"]) : 0;
      $current_user = get_current_user_id();
      $wpdb->query( "INSERT INTO $table_name (language_code, language_title, user_id) VALUES ('$langCode', '$langTitle', $current_user)" );
                                         

      $admin_notice = "success";
      wp_redirect( admin_url( 'admin.php?page=language-admin-top-level' ));
      exit;
     } else {
        wp_die( 
          __( 'Invalid nonce specified', $this->plugin_name ), 
          __( 'Error', $this->plugin_name ), 
          array(
            'response' => 403,
            'back_link' => 'admin.php?page=' . $this->plugin_name,
          ) 
        );
     }
   }
}            


LANG_MANAGEMENT_Plugin::init();