jQuery(document).ready(function($) {
    $('#enquiry-form').on('submit', function(e) {
        e.preventDefault();

        // Hide any existing messages
        $('#enquiry-form-response').text('');

        // Collect form data
        var formData = {
            action: 'submit_enquiry_form',
            enquiry_name: $('#enquiry_name').val(),
            enquiry_email: $('#enquiry_email').val(),
            enquiry_phone: $('#enquiry_phone').val(),
            enquiry_message: $('#enquiry_message').val()
        };

        // If reCAPTCHA is being used, add the token
        var recaptchaField = $('.g-recaptcha');
        if ( recaptchaField.length ) {
            // By default, reCAPTCHA sets a hidden input "g-recaptcha-response"
            // So we just grab that value
            formData['g-recaptcha-response'] = $('#enquiry-form').find('textarea[name="g-recaptcha-response"]').val();
        }

        // Disable button to prevent multiple clicks
        $('#enquiry-submit-btn').prop('disabled', true);

        // Send AJAX request
        $.post( EnquiryFormVars.ajax_url, formData, function(response) {
            // Re-enable button
            $('#enquiry-submit-btn').prop('disabled', false);

            if ( response.success ) {
                $('#enquiry-form-response').css('color', 'green').text(response.data.message);
                // Optionally clear form fields
                $('#enquiry-form')[0].reset();
            } else {
                $('#enquiry-form-response').css('color', 'red').text(response.data.message);
            }

            // If you want to reset reCAPTCHA here:
            if ( typeof grecaptcha !== 'undefined' ) {
                grecaptcha.reset();
            }
        });
    });
});
