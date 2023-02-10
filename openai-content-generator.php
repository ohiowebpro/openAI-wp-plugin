<?php
/**
 * Plugin Name: OpenAI Content Generator
 * Plugin URI: [Insert the URL for your plugin's homepage]
 * Description: Generates content using OpenAI's API.
 * Version: 1.0.0
 * Author: [Your Name]
 * Author URI: [Your Website URL]
 * License: GPLv2 or later
 * Text Domain: openai-content-generator
 * Domain Path: /languages
 */


// Add the options page
function openai_content_generator_add_options_page() {
    add_options_page(
        'OpenAI Content Generator Options',
        'OpenAI Content Generator',
        'manage_options',
        'openai-content-generator',
        'openai_content_generator_options_page_callback'
    );
}
add_action( 'admin_menu', 'openai_content_generator_add_options_page' );

// Callback function for the options page
function openai_content_generator_options_page_callback() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            // Output security fields and sections
            settings_fields( 'openai_content_generator_options' );
            do_settings_sections( 'openai_content_generator' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register plugin options
function openai_content_generator_register_settings() {
    register_setting(
        'openai_content_generator_options',
        'openai_content_generator_options',
        'openai_content_generator_sanitize_options'
    );
    add_settings_section(
        'openai_content_generator_api_key_section',
        'API Key',
        'openai_content_generator_api_key_section_callback',
        'openai_content_generator'
    );

    add_settings_field(
        'openai_content_generator_api_key',
        'API Key',
        'openai_content_generator_api_key_callback',
        'openai_content_generator',
        'openai_content_generator_api_key_section'
    );
    // Add a "Max Tokens" setting to the plugin settings page
    add_settings_field(
        'openai_content_generator_max_tokens',
        'Max Tokens',
        'openai_content_generator_max_tokens_callback',
        'openai_content_generator',
        'openai_content_generator_api_key_section'
    );
}
add_action( 'admin_init', 'openai_content_generator_register_settings' );


// Callback for the API Key section
function openai_content_generator_api_key_section_callback() {
    echo 'Enter your OpenAI API Key below:';
}

// Callback for the API Key field
function openai_content_generator_api_key_callback() {
    $options = get_option( 'openai_content_generator_options' );
    $api_key = ! empty( $options['api_key'] ) ? $options['api_key'] : '';

    if ( ! empty( $api_key ) && openai_content_generator_check_api_key( $api_key ) === false ) {
        echo '<input type="text" name="openai_content_generator_options[api_key]" value="' . esc_attr( $api_key ) . '" class="regular-text" style="border-color: red;">';
        echo '<p style="color: red;">The API Key is invalid.</p>';
    } else {
        echo '<input type="text" name="openai_content_generator_options[api_key]" value="' . esc_attr( $api_key ) . '" class="regular-text">';
        echo '<p style="color: green;">The API Key is good.</p>';
    }
}

// Callback for the "Max Tokens" setting
function openai_content_generator_max_tokens_callback() {
    $value = get_option( 'openai_content_generator_max_tokens', 500 );
    echo '<input type="number" name="openai_content_generator_max_tokens" value="' . $value . '">';
    echo '<p class="description">Enter the maximum number of tokens to generate in the content. The token count is determined by the OpenAI API.</p>';

}
// Save the max tokens value when the form is submitted
if ( isset( $_POST['openai_content_generator_max_tokens'] ) ) {
    $max_tokens = sanitize_text_field( $_POST['openai_content_generator_max_tokens'] );
    update_option( 'openai_content_generator_max_tokens', $max_tokens );
}

// Retrieve the max tokens value from the database
$max_tokens = get_option( 'openai_content_generator_max_tokens', 500 );


// Sanitize and validate plugin options
function openai_content_generator_sanitize_options( $input ) {
    $output = array();

    if ( isset( $input['api_key'] ) && ! empty( $input['api_key'] ) ) {
        $output['api_key'] = sanitize_text_field( $input['api_key'] );
    }

    return $output;
}


// Function to check that the API key is valid
function openai_content_generator_check_api_key( $api_key ) {
    // Test data to send to the OpenAI API
    $data = array(
        "model" => "text-davinci-003",
        'prompt' => 'Say this is a test',
        'max_tokens' => 10,
        'temperature' => 0.1
    );

    // Make a request to the OpenAI API to validate the key
    $response = wp_remote_post( 'https://api.openai.com/v1/completions', array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,

            'model' => 'content-filter-alpha'
        ),
        'body' => json_encode( $data )
    ) );
    $body = json_decode(wp_remote_retrieve_body($response));
    // Check the response status code
    if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 && $body->choices[0]->text ) {
        // Return true if the response is successful
        return true;
    } else {
        // Return false if the response is unsuccessful
        return false;
    }
}

// Function to retrieve the API key
function openai_content_generator_get_api_key() {
    $options = get_option( 'openai_content_generator_options' );
    return ! empty( $options['api_key'] ) ? $options['api_key'] : '';
}

// Register the block using ACF Pro
add_action('acf/init', 'openai_content_generator_register_block');
function openai_content_generator_register_block() {
    // Check if ACF is active
    if (function_exists('acf_register_block')) {
        acf_register_block(array(
            'name' => 'openai-content-generator/textarea',
            'title' => __('OpenAI Content Generator'),
            'description' => __('A custom block that generates content using OpenAI.'),
            'render_callback' => 'openai_content_generator_render_block',
            'category' => 'common',
            'icon' => 'admin-comments',
            'keywords' => array('openai', 'content', 'generator'),
        ));
    }
}

// Render the block
function openai_content_generator_render_block($block) {
    $textarea_value = get_field('textarea');
    $api_key = openai_content_generator_get_api_key();
    $max_tokens = get_option( 'openai_content_generator_max_tokens', 500 );
    ?>
    <div style="padding:12px;border:2px solid #8c8c8c">
        <p style="color:#9b9b9b"><strong>Max Tokens:</strong> <input type="text" value="<?php echo esc_html( $max_tokens ); ?>" id="maxTokens"/></p>
        <p><textarea name="textarea" id="textarea" style="width:100%;" rows="8"><?php echo $textarea_value; ?></textarea></p>
        <p><button id="generate-content-button" class="generate-content-button">Generate Content</button></p>
    </div>
    <script>










        //document.querySelector('#generate-content-button').addEventListener('click', function() {
        //    // Get the textarea value
        //    var textarea = document.querySelector('#textarea');
        //    var textareaValue = textarea.value;
        //    var maxTokens = document.querySelector('#maxTokens');
        //    var maxTokensValue = maxTokens.value;
        //
        //    var generateContentButton = this;
        //    generateContentButton.setAttribute('disabled', true);
        //    generateContentButton.innerHTML = '<span class="spinner is-active"></span> Generating Content...';
        //
        //    // Make the API request to OpenAI
        //    var xhr = new XMLHttpRequest();
        //    xhr.open('POST', 'https://api.openai.com/v1/completions');
        //    xhr.setRequestHeader('Content-Type', 'application/json');
        //    xhr.setRequestHeader('Authorization', 'Bearer <?php //echo $api_key; ?>//');
        //    xhr.setRequestHeader('model', 'content-filter-alpha');
        //
        //
        //    xhr.onload = function() {
        //        if (xhr.status === 200) {
        //            // Split the content by line returns and create separate blocks
        //            var response = JSON.parse(xhr.responseText);
        //            var content = response.choices[0].text;
        //            var contents = content.split(/\r\n|\n|\r/);
        //            var blocks = [];
        //            contents.forEach(function(c) {
        //                if (c.trim() !== '') {
        //                    blocks.push(wp.blocks.createBlock('core/paragraph', {
        //                        content: c
        //                    }));
        //                }
        //            });
        //            var clientId = wp.data.select('core/block-editor').getSelectedBlockClientId();
        //            wp.data.dispatch('core/block-editor').replaceBlock(
        //                clientId,
        //                blocks
        //            );
        //        } else {
        //            // Handle error
        //            console.error('OpenAI API request failed with status code: ' + xhr.status);
        //            generateContentButton.removeAttribute('disabled');
        //            generateContentButton.innerHTML = 'Generate Content';
        //        }
        //    };
        //
        //    xhr.send(JSON.stringify({
        //        model: "text-davinci-003",
        //        prompt: textareaValue,
        //        max_tokens: parseInt(maxTokensValue),
        //        temperature: 0.5,
        //    }));

        //});
    </script>
    <?php
}


//enqueue js
//function openai_content_generator_admin_scripts() {
//    wp_enqueue_script( 'openai-content-generator-admin-js', plugins_url( 'js/openai-content-generator-admin.js', __FILE__ ), array( 'jquery' ), '1.0.0', true );
//}
//add_action( 'admin_enqueue_scripts', 'openai_content_generator_admin_scripts' );

function openai_content_generator_enqueue_block_assets() {
    wp_enqueue_script( 'openai-content-generator-block-script', plugins_url( 'js/openai-content-generator-admin.js', __FILE__ ), array( 'wp-blocks', 'wp-i18n', 'wp-element' ), filemtime( plugin_dir_path( __FILE__ ) . 'js/openai-content-generator-admin.js' ), true );
}
add_action( 'enqueue_block_assets', 'openai_content_generator_enqueue_block_assets' );



//Get and send the data to API
add_action( 'wp_ajax_openai_content_generator_generate_content', 'openai_content_generator_generate_content' );

function openai_content_generator_generate_content() {
    // Check the security nonce
    check_ajax_referer( 'openai_content_generator_nonce', 'security' );

    // Retrieve the block data
    $blockData = $_POST['blockData'];

    // Make the API request to OpenAI using the API key and the textarea value as input
    $apiKey = openai_content_generator_get_api_key();
    $prompt = $blockData['prompt'];
    $maxTokens = isset($blockData['maxTokens']) ? $blockData['maxTokens'] : openai_content_generator_get_max_tokens();
    $response = openai_content_generator_send_request($apiKey, $prompt, $maxTokens);

    // Return the generated content
    wp_send_json_success(array_map(function ($text) {
        return preg_replace('/\s+/', ' ', $text);
    }, explode("\n\n", $response->choices[0]->text)));
}