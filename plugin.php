<?php

/**
 * Class Send_Squared_Action_After_Submit
 * @see https://developers.elementor.com/custom-form-action/
 * Custom elementor form action after submit to add a subscriber to
 * SendSquared list via API 
 */
class Send_Squared_Action_After_Submit extends \ElementorPro\Modules\Forms\Classes\Action_Base
{
    /**
     * Get Name
     *
     * Return the action name
     *
     * @access public
     * @return string
     */
    public function get_name()
    {
        return 'sendsquared';
    }

    /**
     * Get Label
     *
     * Returns the action label
     *
     * @access public
     * @return string
     */
    public function get_label()
    {
        return __('SendSquared', 'text-domain');
    }

    /**
     * Run
     *
     * Runs the action after submit
     *
     * @access public
     * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
     * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
     */
    public function run($record, $ajax_handler)
    {
        $settings = $record->get('form_settings');

        //  Make sure that there is a SendSquared list ID
        if (empty($settings['sendsquared_list'])) {
            return;
        }

        // Get submitted Form data
        $raw_fields = $record->get('fields');

        // Normalize the Form Data
        $fields = [];
        foreach ($raw_fields as $id => $field) {
            $fields[$id] = $field['value'];
        }

        // Make sure that the user entered an email or phone
        // one of which is required by SendSquared API to subscriber a contact
        if (empty($fields['email']) && empty($fields['phone'])) {
            return;
        }

        // If we got this far we can start building our request data
        // Based on the param list at https://sendsquared.com/blog/custom-forms
        /*
        $source = [
            'ipaddress-custom' => \ElementorPro\Classes\Utils::get_client_ip(),
            'referrer-custom' => isset($_POST['referrer']) ? $_POST['referrer'] : '',
        ]; */
        // $send_squared_data = array_merge_recursive($source, $fields);

        // Send the request
        $response = wp_remote_post('https://app-api.sendsquared.com/v1/pub/popup?token=' . $settings['sendsquared_list'], [
            'body' => $fields,
        ]);

        if (200 !== (int) wp_remote_retrieve_response_code($response)) {
            throw new \Exception(esc_html__($response['body'], 'elementor-pro'));
        }
    }

    /**
     * Register Settings Section
     *
     * Registers the Action controls
     *
     * @access public
     * @param \Elementor\Widget_Base $widget
     */
    public function register_settings_section($widget)
    {
        $widget->start_controls_section(
            'section_sendsquared',
            [
                'label' => __('SendSquared', 'text-domain'),
                'condition' => [
                    'submit_actions' => $this->get_name(),
                ],
            ]
        );

        $widget->add_control(
            'sendsquared_list',
            [
                'label' => __('SendSquared Group ID', 'text-domain'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'separator' => 'before',
                'description' => __('the group id you want to subscribe a user to. This is a UUID that can be found under Group edit.', 'text-domain'),
            ]
        );

        $widget->end_controls_section();
    }

    /**
     * On Export
     *
     * Clears form settings on export
     * @access Public
     * @param array $element
     */
    public function on_export($element)
    {
        unset(
            $element['sendsquared_list'],
        );
    }
}
add_action('elementor_pro/init', function () {
    // Here its safe to include our action class file
    include_once('sendsquared-elements.php');

    // Instantiate the action class
    $sendsquared_action = new Send_Squared_Action_After_Submit();

    // Register the action with form widget
    \ElementorPro\Plugin::instance()->modules_manager->get_modules('forms')->add_form_action($sendsquared_action->get_name(), $sendsquared_action);
});
