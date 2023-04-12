<?php
/*
Plugin Name: Contact Form
Description: Add a contact form to your website
Version: 1.0
Author: Ikram
*/

// Add plugin to the dashboard extensions page
function cf_add_plugin_to_dashboard($plugins) {
    $cf_plugin = array(
        'contact-form/contact-form.php' => 'Contact Form'
    );
    $plugins = array_merge($cf_plugin, $plugins);
    return $plugins;
}
add_filter('all_plugins', 'cf_add_plugin_to_dashboard');


// Create the table when the plugin is activated
register_activation_hook(__FILE__, 'create_contact_form_table');
 
// Register the plugin in the admin menu
add_action('admin_menu', 'register_contact_form_plugin');

function register_contact_form_plugin() {
    add_menu_page(
        'Contact Form',
        'Contact Form',
        'manage_options',
        'contact-form-plugin',
        'contact_form_plugin_page',
        'dashicons-admin-comments',
        6
    );
    add_submenu_page(
      'contact-form-plugin',
      'Customize Form Fields',
      'Customize Fields',
      'manage_options',
      'contact-form-plugin-customize',
      'contact_form_plugin_submenu_page'
  );
}
function contact_form_plugin_submenu_page() {
  ?>
  <div class="wrap">
      <h2><?php _e('Customize Contact Form', 'contact-form-plugin'); ?></h2>
      <form method="post" action="options.php">
          <?php
          settings_fields('contact_form_plugin_options');
          do_settings_sections('contact_form_plugin');
          submit_button(__('Save Changes', 'contact-form-plugin'));
          ?>
      </form>
  </div>
  <?php
}



function contact_form_plugin_page() {
  global $wpdb;

  // Get all the form responses from the database
  $form_responses = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}contact_form" );

  // Output the responses in a table
  echo '<div class="wrap">';
  echo '<h1>Contact Form Responses</h1>';
  echo '<table class="wp-list-table widefat fixed striped">';
  echo '<thead>';
  echo '<tr>';
  echo '<th>Subject</th>';
  echo '<th>Name</th>';
  echo '<th>Email</th>';
  echo '<th>Message</th>';
  echo '<th>Date</th>';
  echo '</tr>';
  echo '</thead>';
  echo '<tbody>';

  foreach ( $form_responses as $form_response ) {
      echo '<tr>';
      echo '<td>' . esc_html( $form_response->subject ) . '</td>';
      echo '<td>' . esc_html( $form_response->first_name ) . ' ' . esc_html( $form_response->last_name ) . '</td>';
      echo '<td>' . esc_html( $form_response->email ) . '</td>';
      echo '<td>' . esc_html( $form_response->message ) . '</td>';
      echo '<td>' . esc_html( $form_response->date_sent ) . '</td>';
      echo '</tr>';
  }

  echo '</tbody>';
  echo '</table>';
  echo '</div>';
}


function create_contact_form_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'contact_form';
    $sql = "CREATE TABLE $table_name (
            id INT(11) NOT NULL AUTO_INCREMENT,
            subject VARCHAR(255) NOT NULL,
            first_name VARCHAR(255) NOT NULL,
            last_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            date_sent TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
    )";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
// Drop the table when the plugin is deactivated
register_deactivation_hook(__FILE__, 'drop_contact_form_table');

function drop_contact_form_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'contact_form';

    $sql = "DROP TABLE IF EXISTS $table_name;";

    $wpdb->query($sql);
}

// Ajouter le shortcode pour afficher le formulaire
function contact_form_shortcode() {
  wp_enqueue_style( 'contact-form-style', plugin_dir_url( __FILE__ ) . './contact.css' );
  ob_start();
  ?>
  <form method="post">
    <div class="form-group">
      <label for="subject">Sujet :</label>
      <input type="text" name="subject" id="subject" required>
    </div>
    <div class="form-group">
      <label for="first_name">Prénom :</label>
      <input type="text" name="first_name" id="first_name" required>
    </div>
    <div class="form-group">
      <label for="last_name">Nom :</label>
      <input type="text" name="last_name" id="last_name" required>
    </div>
    <div class="form-group">
      <label for="email">E-mail :</label>
      <input type="email" name="email" id="email" required>
    </div>
    <div class="form-group">
      <label for="message">Message :</label>
      <textarea name="message" id="message" required></textarea>
    </div>
    <button type="submit" name="submit" class="btn btn-primary">Envoyer</button>
  </form>
  <?php
    // Process the form data
    process_contact_form();

    // Display the form
  return ob_get_clean();
}
add_shortcode( 'contact-form', 'contact_form_shortcode' );

//insert
function process_contact_form() {
  global $wpdb;

  // Check if the form was submitted
  if (isset($_POST['submit'])) {
    // Sanitize the input values
    $sujet = sanitize_text_field($_POST['subject']);
    $nom = sanitize_text_field($_POST['first_name']);
    $prenom = sanitize_text_field($_POST['last_name']);
    $email = sanitize_email($_POST['email']);
    $message = sanitize_textarea_field($_POST['message']);
    $date_envoi = current_time('mysql');

    // Insert the data into the wp_contact_form table
    $wpdb->insert(
      'wp_contact_form',
      array(
        'subject' => $sujet,
        'first_name' => $nom,
        'last_name' => $prenom,
        'email' => $email,
        'message' => $message,
        'date_sent' => $date_envoi
      ),
      array(
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s'
      )
    );

    // Display a success message
    if ($wpdb->insert_id) {
      echo '<div class="notice-success" style="color: green"><p>Votre message a bien été envoyé.</p></div>';
    } else {
      echo '<div class="notice-error" style="color: red"><p>Une erreur s\'est produite. Veuillez réessayer plus tard.</p></div>';
    }  
  }

}


