<?php

class LabelTranslator
{
	const SCRIPT_HANDLE = 'wpml-st-translation-memory';
	const NONCE         = 'wpml_translation_memory_nonce';
	const PLUGIN_NAME = 'bnpp-language-management';
	const LABEL_TABLE = 'wp_labels';
	const LABEL_TABLE_VALUES = 'wp_labels_translation';
	
	public function __construct(){
        add_action('wp_enqueue_scripts', array( __CLASS__, 'enqueue_scriptx' ) );
		add_action('admin_action_labels_save', array(__CLASS__, 'labels_save') );
	}

	public static function label_add_menu() {
		add_submenu_page(
			'language-admin-top-level',
			__('Label Translator Admin', 'new_labels'),
			__('Label Translator Admin', 'new_labels'),
			'manage_sites',
			'language-translator-admin',
			array(__CLASS__, 'translator_label_interface')
		);
	}

	public function translator_label_interface(){
		global $wpdb;
		$table_name = $wpdb->base_prefix . LabelTranslator::LABEL_TABLE;
		if ( ! current_user_can( 'manage_sites' ) ) {
		wp_die( __( 'Sorry, you are not allowed to edit this site.' ) );
		}
        $lang_management_table = $wpdb->base_prefix . LANG_MANAGEMENT_Plugin::DB_NAME;
		$add_meta_nonce = wp_create_nonce( "_meta_form_nonce" );
		#do 2 queries, ones to get all languages and the other to get all the labels and their existing translations if any
		$result_all_languages =  $wpdb->get_results("SELECT DISTINCT language_code,language_title FROM wp_lang_new_labels ORDER BY language_code");
		$result = $wpdb->get_results("SELECT wpl.id AS id_label,label,label_activated,label_lang,label_value FROM wp_labels wpl LEFT JOIN wp_labels_translation wpll on wpl.id = wpll.label_id ORDER BY label,label_lang");
		           // Security wp_nonce
		$selector = $wpdb->get_results( "SELECT * FROM  ". $lang_management_table );
		$dropdown_html = '<select required id="lang_selector" class="regular-text form-control form-control-lg" name="lang[user_select]">
		               <option value="default">'.__( 'Select a Language', 'new_labels' ).'</option>';
		foreach ( $selector as $lang ){
           $lang_code = esc_html( $lang->language_code );
           $lang_title = esc_html( $lang->language_title );
           $dropdown_html .= '<option value="' . $lang_code . '">' . $lang_title . ' (' . $lang_code  . ') ' . '</option>' . "\n";
		}
		$dropdown_html .= '</select>';
	    ?>
          <script type="text/javascript">
          //function to shwow/hide passed div
          function toggleDIV(myDIV) {
            var x = document.getElementById(myDIV);
            if (x.style.display === "none") {
              x.style.display = "block";
            } else {
              x.style.display = "none";
            }
          return false;
          }
          //function to launch ajax and update active flag
          function update_label_active(id,buttonID,labelActive){
                        document.getElementById(buttonID).disabled = true;
                    $.ajax({
                            type: "POST",
                            url: "<?php echo get_site_url();?>/wp-content/plugins/bnpp-post-ajax/update_label_active.php",
                            dataType: 'html',
                            data: ({ action: 'save_labels', label_id:id,label_active:labelActive }),
                            success: function(data)
                            {                                                                                    document.getElementById(buttonID).disabled = false;
                            },
                            error: function(data)
                            {
                                   alert("Error!"+data);
                                    return false;
                            }
                    });
          }
          //function to launch the ajax and save the labels
          function saveLabels(myLabel,code,id,value,buttonID,labelActive){
            document.getElementById(buttonID).disabled = true;
            $.ajax({
				type: "POST",
			    url: "<?php echo get_site_url();?>/wp-content/plugins/bnpp-post-ajax/save_labels.php",
			    dataType: 'html',
			    data: ({ action: 'save_labels', label:myLabel,langcode:code,label_id:id,label_value:value,label_active:labelActive }),
			    success: function(data)
			    {
			      document.getElementById(buttonID).disabled = false;
			    },
			    error: function(data)
			    {
			            alert("Error!"+data);
			            return false;
			    }
			});
          }

          function searchLabels(){
			searchVal = document.getElementById("inputSearchLabel").value;
			langID = document.getElementById("lang_selector").value;
			$.ajax({
				type: "POST",
				url: "<?php echo get_site_url();?>/wp-content/plugins/bnpp-post-ajax/search_labels.php",
				dataType: 'html',
				data: ({ action: 'search_labels', label: searchVal, langcode: langID }),
				beforeSend: function() {
				  jQuery("#labelLang > tbody").html('<div class="lds-dual-ring"></div>');
				},
				success: function(data){
				 jQuery("#labelLang > tbody").html(data);           
				},
				error: function(data)
				{
				    alert("Error!" + data);
				    return false;
				}
			});    
           }

          function toggle(label_id) {
            jQuery("#" + label_id ).val( jQuery("#" + label_id ).val() == 0 ? 1 : 0);
          }

          function resetSearch(){
           document.getElementById('inputSearchLabel').value = "";
           document.getElementById('lang_selector').selectedIndex = 0;
           searchLabels();
          }

          jQuery(document).ready(function(){
           searchLabels();
          });
      </script>
      <h1>
        <?php _e( 'Label Translator Interface', 'new_labels' ); ?>
      </h1>
      <hr>
      <div>
        <form class="form-inline">
          <div class="form-group mx-sm-3 mb-2">
            <label for="inputSearchLabel" class="sr-only">Enter Label...</label>
            <input type="text" class="form-control" id="inputSearchLabel" placeholder="Enter Label...">
          </div>
          <div class="form-group mx-sm-3 mb-2">
              <?php echo $dropdown_html; ?>
          </div>
          <input class="btn btn-info mb-2 action" onclick="searchLabels();" type="button" aria-pressed="false" value="Search" />&nbsp;&nbsp;
          <input class="btn btn-warning mb-2 action" onclick="resetSearch();" type="button" aria-pressed="false" value="Reset" />
        </form>
      </div>
      <hr>
      <div>
        <input type="hidden" name="action" value="labels_save">
        <input type="hidden" name="_meta_nonce" value="<?php echo $add_meta_nonce ?>" />
        <table class="widefat" id="labelLang" role="presentation">
          <thead>
          <tr>
             <th scope="col">Label</th>
             <th scope="col">&nbsp;</th>
             <th scope="col">Status</th>
          </tr>
          </thead>
      <tbody></tbody>
    </table>
  </div>
  <style type="text/css">
    .bnpp-st-col-string {
      border-bottom: 1px solid #ccd0d4;
    }
    #labelLang tr td:nth-child(2) {
      width: 60%;
      max-width: 60%;
    }
    #labelLang tr td:nth-child(3) {
      display: flex;
      text-align: left;
    }
    .child-table-translations {
      width: 90% !important;
      max-width: 90% !important;
    }
    .lds-dual-ring {
      width: 80px;
      height: 80px;
      margin-left: auto;
      margin-right: auto;
    }
    .lds-dual-ring:after {
      content: " ";
      display: block;
      width: 64px;
      height: 64px;
      margin: 8px;
      border-radius: 50%;
      border: 6px solid #00965e;
      border-color: #00965e transparent #00965e transparent;
      animation: lds-dual-ring 1.2s linear infinite;
    }
    @keyframes lds-dual-ring {
      0% {
        transform: rotate(0deg);
      }
      100% {
        transform: rotate(360deg);
      }
    }
  </style>
  <?php
  return;
 }
}

var WPML_String_Translation = WPML_String_Translation || {};
WPML_String_Translation.TranslationMemory = function ( $ ) {
var init = function() {
	 $(document).ready( function( $ ) {
       $('#icl_string_translations').on('wpml-open-string-translations', function (e, element) {
          var inlineTranslations = $( element );
          var emptyTranslations  = inlineTranslations.find('textarea[name="icl_st_translation"]:empty');
          if ( 0 < emptyTranslations.length ) {
            fetchTranslationMemory( inlineTranslations, emptyTranslations );
          }
       });
	 });
};

var populateEmptyTranslations = function( emptyTranslations, translationMemory ) {
	$.each( emptyTranslations, function( i ) {
	   var empty = $( emptyTranslations[i] );
	   var translationObj = translationMemory.filter( function( el ) {
          return empty.data('lang') === el.language;
	   }).shift();
	   if ( translationObj ) {
          empty.text( translationObj.translation );
	   }
	});
};

var fetchTranslationMemory = function( inlineTranslations, emptyTranslations ) {
 var toggle = inlineTranslations.parent('.wpml-st-col-string').find('.js-wpml-st-toggle-translations');
 toggle.prepend('<span class="spinner is-active"></span>');
 var original = inlineTranslations.data('original');
 var source_lang = inlineTranslations.data('source-lang');
 $.post(
	ajaxurl,
		{
		  action: 'wpml_st_fetch_translations',
		  nonce: wpml_translation_memory_nonce.value,
		  strings: [ original ],
		  languages: {
		                source: source_lang,
		                target: ''
		}
	},
	function( response ) {
	          if (response.data) {
	                        populateEmptyTranslations( emptyTranslations, response.data );
	                        toggle.find('.spinner').remove();
	          }
	}
    );
  };
  init();
};

new WPML_String_Translation.TranslationMemory( jQuery );