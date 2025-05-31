jQuery(document).ready(function($) {
    var $contactForm = $('#mini-crm-contact-form');

    if ($contactForm.length === 0) {
        return; // Form not present on this page
    }

    var $successMessageDiv = $('#mini-crm-success-message');
    var $errorMessageDiv = $('#mini-crm-error-message');
    var $submitButton = $contactForm.find('input[type="submit"]');

    $contactForm.on('submit', function(e) {
        e.preventDefault();

        var originalButtonText = $submitButton.val();

        // Clear previous messages and disable button
        $successMessageDiv.text('').removeClass('show').slideUp();
        $errorMessageDiv.text('').removeClass('show').slideUp();
        $submitButton.val('در حال ارسال...').prop('disabled', true);

        var formData = {
            action: 'mini_crm_handle_form', // Matches PHP hook
            full_name: $contactForm.find('#mini_crm_full_name').val(),
            phone: $contactForm.find('#mini_crm_phone').val(),
            form_channel: $contactForm.find('input[name="form_channel"]').val(),
            mini_crm_nonce_field: $contactForm.find('input[name="mini_crm_nonce_field"]').val() // Matches PHP nonce field name
        };

        $.ajax({
            url: miniCrmAjax.ajaxurl, // Localized variable
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $successMessageDiv.text(response.data.message).addClass('show').slideDown();
                    $contactForm[0].reset(); // Clear form fields
                    setTimeout(function() {
                        $successMessageDiv.slideUp(function(){ $(this).removeClass('show'); });
                    }, 4000); // Display success message for 4 seconds
                } else {
                    var errorMessage = response.data.message || 'خطا در ثبت اطلاعات. لطفاً همه فیلدها را بررسی کنید.';
                    $errorMessageDiv.text(errorMessage).addClass('show').slideDown();
                     setTimeout(function() {
                        $errorMessageDiv.slideUp(function(){ $(this).removeClass('show'); });
                    }, 4000); // Display error message for 4 seconds
                }
            },
            error: function(xhr, status, error) {
                // console.log('AJAX Error:', xhr.responseText, status, error);
                $errorMessageDiv.text('خطا در برقراری ارتباط با سرور. لطفاً دوباره تلاش کنید.').addClass('show').slideDown();
                setTimeout(function() {
                    $errorMessageDiv.slideUp(function(){ $(this).removeClass('show'); });
                }, 4000);
            },
            complete: function() {
                $submitButton.val(originalButtonText).prop('disabled', false);
            }
        });
    });
});