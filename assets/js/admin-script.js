jQuery(document).ready(function($) {
    var ajaxMessageGlobalDiv = $('#mini-crm-ajax-message-global'); // For global messages on list page
    var contactsTbody = $('#mini-crm-contacts-tbody');
    var paginationContainer = $('#mini-crm-pagination-container');
    var currentPage = 1; // Keep track of current page for AJAX pagination

    function showGlobalAjaxMessage(message, isSuccess) {
        ajaxMessageGlobalDiv.html(message).removeClass('success error updated notice-error notice-success').addClass(isSuccess ? 'notice-success' : 'notice-error').addClass('notice is-dismissible').fadeIn();
        // No auto-hide for global messages, let user dismiss
        ajaxMessageGlobalDiv.off('click.dismiss').on('click.dismiss', '.notice-dismiss', function() {
            $(this).parent().fadeOut();
        });
         if (ajaxMessageGlobalDiv.find('.notice-dismiss').length === 0) {
            ajaxMessageGlobalDiv.append($('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>'));
        }
    }

    // Debounce function
    function debounce(func, wait) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }

    // --- Filter and Search Contacts ---
    function fetchContacts(pageToFetch) {
        currentPage = parseInt(pageToFetch) || 1; // Ensure currentPage is an int
        var searchTerm = $('#mini-crm-search-input').val().trim();
        var statusFilter = $('#mini-crm-filter-status').val();
        var callStatusFilter = $('#mini-crm-filter-call-status').val();

        contactsTbody.html('<tr><td colspan="13" style="text-align:center;">' + miniCrmAdminAjax.text.loading + '</td></tr>');
        paginationContainer.html(''); // Clear old pagination

        $.ajax({
            url: miniCrmAdminAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'mini_crm_filter_search_contacts',
                nonce: miniCrmAdminAjax.nonce_filter,
                paged: currentPage,
                search_term: searchTerm,
                status_filter: statusFilter,
                call_status_filter: callStatusFilter
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    contactsTbody.html(response.data.html_rows);
                // Trigger event for datepicker initialization
                $(document).trigger('mini-crm-contacts-loaded');
                    paginationContainer.html(response.data.pagination_html);
                    // Update current page based on response if server corrected it (e.g., page out of bounds)
                    currentPage = parseInt(response.data.current_page) || 1;
                } else {
                    contactsTbody.html('<tr><td colspan="13" style="text-align:center;">' + (response.data.message || miniCrmAdminAjax.text.error_loading) + '</td></tr>');
                }
            },
            error: function() {
                contactsTbody.html('<tr><td colspan="13" style="text-align:center;">' + miniCrmAdminAjax.text.error_server + '</td></tr>');
            }
        });
    }

    $('#mini-crm-search-button, #mini-crm-filter-button').on('click', function() {
        fetchContacts(1); // Reset to page 1 on new search/filter
    });

    $('#mini-crm-search-input').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            fetchContacts(1);
        }
    });
    
    // Delegated click handler for AJAX pagination
    paginationContainer.on('click', 'a.page-numbers', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        var pageMatch = href.match(/paged=(\d+)/); // Try to get from URL first
        var pageNum = $(this).text(); // Fallback to text content

        if (pageMatch && pageMatch[1]) {
            fetchContacts(pageMatch[1]);
        } else if ($.isNumeric(pageNum)) {
            fetchContacts(pageNum);
        } else { // Previous/Next buttons might not have a direct page number in text
            if ($(this).hasClass('next')) {
                fetchContacts(currentPage + 1);
            } else if ($(this).hasClass('prev')) {
                fetchContacts(currentPage - 1);
            }
        }
    });

    // --- Check Visit DateTime (Start and End) ---
    function checkVisitDateTime($triggerElement) {
        var $row = $triggerElement.closest('tr');
        var $visitDateInput = $row.find('.persian-datepicker');
        var $visitEndTimeInput = $row.find('.visit-end-time-input');
        var $confirmBtn = $row.find('.confirm-visit-datetime');
        
        var visitDateTime = $visitDateInput.val();
        var visitEndTime = $visitEndTimeInput.val();
        
        // Show button only if both start date/time and end time are set
        if (visitDateTime && visitDateTime.trim() !== '' && visitEndTime && visitEndTime.trim() !== '') {
            $confirmBtn.show();
        } else {
            $confirmBtn.hide();
        }
    }

    // --- Persian Datepicker Initialization ---
    function initializePersianDatepickers() {
        $('.persian-datepicker').each(function() {
            var $input = $(this);
            var contactId = $input.data('contact-id');
            
            if (!$input.data('persian-datepicker-initialized')) {
                $input.persianDatepicker({
                    showTimePicker: true,
                    defaultTime: '09:00'
                });
                
                $input.data('persian-datepicker-initialized', true);
                
                // Show confirm button when date is selected
                $input.on('change', function() {
                    checkVisitDateTime($(this));
                });
            }
        });
    }

    // --- Confirm Visit DateTime and Send SMS ---
    contactsTbody.on('click', '.confirm-visit-datetime', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var contactId = $btn.data('contact-id');
        var $row = $btn.closest('tr');
        var $container = $btn.closest('.visit-date-container');
        var $datepicker = $container.find('.persian-datepicker');
        var dateTimeValue = $datepicker.val();
        
        // Get visit end time from the same row
        var $visitEndTimeInput = $row.find('.visit-end-time-input');
        var visitEndTime = $visitEndTimeInput.val() || '';
        
        if (!dateTimeValue || dateTimeValue.trim() === '') {
            alert(miniCrmAdminAjax.text.select_date_time);
            return;
        }
        
        // Parse date and time
        var parts = dateTimeValue.split(' ');
        var visitDate = parts[0]; // Persian date (1402/12/15)
        var visitTime = parts[1] || '09:00'; // Time (09:00)
        
        if (!confirm(miniCrmAdminAjax.text.confirm_visit_sms)) {
            return;
        }
        
        var originalText = $btn.text();
        $btn.text(miniCrmAdminAjax.text.sending).prop('disabled', true);
        $container.addClass('loading');
        
        $.ajax({
            url: miniCrmAdminAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'mini_crm_confirm_visit_sms',
                nonce: miniCrmAdminAjax.nonce_confirm_visit_sms,
                contact_id: contactId,
                visit_date: visitDate,
                visit_time: visitTime,
                visit_end_time: visitEndTime
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showGlobalAjaxMessage(response.data.message, true);
                    
                    // Replace the entire row with updated data
                    var $row = $btn.closest('tr');
                    var $newRow = $(response.data.new_row_html);
                    $row.replaceWith($newRow);
                    
                    // Re-initialize datepickers for the new row
                    initializePersianDatepickers();
                } else {
                    showGlobalAjaxMessage(response.data.message || miniCrmAdminAjax.text.sms_sent_failed, false);
                }
            },
            error: function() {
                showGlobalAjaxMessage(miniCrmAdminAjax.text.error_sending_sms, false);
            },
            complete: function() {
                $btn.text(originalText).prop('disabled', false);
                $container.removeClass('loading');
            }
        });
    });

    // --- Check Visit End Time Changes ---
    contactsTbody.on('change', '.visit-end-time-input', function() {
        checkVisitDateTime($(this));
    });

    // --- Inline Editing for Contact Details ---
    contactsTbody.on('change', '.contact-dynamic-update', debounce(function() {
        var $this = $(this);
        var $row = $this.closest('tr');
        var contactId = $row.data('contact-id');
        var field = $this.data('field');
        var value = $this.val();
        
        $row.css('opacity', '0.5'); // Visual feedback

        $.ajax({
            url: miniCrmAdminAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'mini_crm_update_contact_details',
                nonce: miniCrmAdminAjax.nonce_update,
                contact_id: contactId,
                field: field,
                value: value
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showGlobalAjaxMessage(response.data.message, true);
                    // Replace the row content with the updated HTML from server
                    // This ensures all dependent UI elements (like sub-status dropdown) are correctly re-rendered
                    var $newRow = $(response.data.new_row_html);
                    $row.replaceWith($newRow);
                    // Re-initialize datepickers for the new row
                    initializePersianDatepickers();
                } else {
                    showGlobalAjaxMessage(response.data.message || miniCrmAdminAjax.text.error_update, false);
                    // Attempt to revert the change visually (might not be perfect for all field types)
                    $this.val($this.data('original-value') || ''); // Revert to original or empty
                }
            },
            error: function() {
                showGlobalAjaxMessage(miniCrmAdminAjax.text.error_server, false);
                $this.val($this.data('original-value') || '');
            },
            complete: function() {
                 // Find the row again in case it was replaced, then restore opacity
                $('#contact-row-' + contactId).css('opacity', '1');
            }
        });
    }, 750));


    // --- Manual SMS Sending ---
    contactsTbody.on('click', '.mini-crm-send-manual-sms', function() {
        var $button = $(this);
        var $row = $button.closest('tr');
        var contactId = $row.data('contact-id');
        var smsType = $button.data('sms-type');
        var originalButtonText = $button.text();

        if (!confirm(miniCrmAdminAjax.text.confirm_sms + " (" + originalButtonText + ")")) {
            return;
        }

        $button.prop('disabled', true).text(miniCrmAdminAjax.text.sending);

        $.ajax({
            url: miniCrmAdminAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'mini_crm_send_manual_sms',
                nonce: miniCrmAdminAjax.nonce_send_manual_sms,
                contact_id: contactId,
                sms_type: smsType
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showGlobalAjaxMessage(response.data.message || miniCrmAdminAjax.text.sms_sent_success, true);
                } else {
                    showGlobalAjaxMessage(response.data.message || miniCrmAdminAjax.text.sms_sent_failed, false);
                }
            },
            error: function() {
                showGlobalAjaxMessage(miniCrmAdminAjax.text.error_sending_sms, false);
            },
            complete: function() {
                 $button.prop('disabled', false).text(originalButtonText);
            }
        });
    });

    // --- Add New Contact Page - Show/Hide Visit Details ---
    var $addContactStatusSelect = $('#add_contact_status');
    var $addVisitDetailsSection = $('#add-visit-details-section');

    function toggleAddVisitDetails() {
        if ($addContactStatusSelect.val() === 'ACCEPT') {
            $addVisitDetailsSection.slideDown();
        } else {
            $addVisitDetailsSection.slideUp();
        }
    }
    if ($addContactStatusSelect.length) { // Only run if the select element exists
        $addContactStatusSelect.on('change', toggleAddVisitDetails);
        toggleAddVisitDetails(); // Initial check on page load
    }


    // --- Settings Page Tabs & Accordion (already in PHP, but if moved to JS file) ---
    // Tabs for settings page
    var $settingsTabs = $('.nav-tab-wrapper a.nav-tab');
    var $settingsTabContents = $('.settings-tab-content');

    if ($settingsTabs.length && $settingsTabContents.length) {
        $settingsTabs.on('click', function(e) {
            e.preventDefault();
            var targetId = $(this).attr('href');

            $settingsTabs.removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            $settingsTabContents.hide();
            $(targetId).show();
        });

        // Activate the first tab by default or based on URL hash
        var hash = window.location.hash;
        if (hash && $settingsTabs.filter('[href="' + hash + '"]').length) {
            $settingsTabs.filter('[href="' + hash + '"]').click();
        } else {
            $settingsTabs.first().click();
        }
    }
    
    // Accordion for SMS templates on settings page
    var $smsAccordion = $("#sms-templates-accordion");
    if ($smsAccordion.length && typeof $.fn.accordion === 'function') {
        $smsAccordion.accordion({
            heightStyle: "content",
            collapsible: true,
            active: false // All sections collapsed by default
        });
    } else if ($smsAccordion.length) {
        // Fallback if accordion JS is not loaded: make headers clickable to toggle content
        console.warn("Mini CRM: jQuery UI Accordion not fully functional. Using fallback for SMS templates.");
        $smsAccordion.find("> h3").on("click", function() {
            $(this).next("div").slideToggle();
        }).css("cursor", "pointer");
        $smsAccordion.find("> div").hide(); // Hide all content initially
    }


    // Placeholder for initializing Persian Datepicker on dynamically added rows or on page load
    // Initialize Persian datepickers on page load
    initializePersianDatepickers();
    
    // Re-initialize datepickers after filtering/searching
    $(document).on('mini-crm-contacts-loaded', function() {
        initializePersianDatepickers();
    });

    // --- Call Status Handling ---
    // Change call status select background color based on selection
    $(document).on('change', '.call-status-select', function() {
        var selectedValue = $(this).val();
        var colors = {
            'pending': '#ffc107',
            'attempted': '#6c757d',
            'successful': '#28a745',
            'failed': '#dc3545',
            'no_answer': '#fd7e14',
            'busy': '#e83e8c',
            'not_reachable': '#6f42c1'
        };
        $(this).css('background-color', colors[selectedValue] || '#6c757d');
    });

    // Increment call attempts
    $(document).on('click', '.increment-call-attempts', function(e) {
        e.preventDefault();
        var button = $(this);
        var contactId = button.data('contact-id');
        var row = button.closest('tr');
        
        button.prop('disabled', true).text('...');
        
        $.ajax({
            url: miniCrmAdminAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'mini_crm_increment_call_attempts',
                nonce: miniCrmAdminAjax.nonce_increment_call,
                contact_id: contactId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Replace the entire row with updated HTML
                    row.replaceWith(response.data.new_row_html);
                    showGlobalAjaxMessage(response.data.message, true);
                } else {
                    showGlobalAjaxMessage(response.data.message || 'خطا در افزایش تعداد تماس', false);
                    button.prop('disabled', false).text('+');
                }
            },
            error: function() {
                showGlobalAjaxMessage('خطا در اتصال به سرور', false);
                button.prop('disabled', false).text('+');
            }
        });
    });

    // Delete contact
    $(document).on('click', '.delete-contact', function(e) {
        e.preventDefault();
        var button = $(this);
        var contactId = button.data('contact-id');
        var row = button.closest('tr');
        var contactName = row.find('td:nth-child(2)').text().trim();
        
        if (!confirm(miniCrmAdminAjax.text.confirm_delete.replace('%s', contactName))) {
            return;
        }
        
        var originalText = button.html();
        button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> ' + miniCrmAdminAjax.text.deleting);
        
        $.ajax({
            url: miniCrmAdminAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'mini_crm_delete_contact',
                nonce: miniCrmAdminAjax.nonce_delete_contact,
                contact_id: contactId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    row.fadeOut(300, function() {
                        $(this).remove();
                    });
                    showGlobalAjaxMessage(response.data.message, true);
                } else {
                    showGlobalAjaxMessage(response.data.message || 'خطا در حذف مخاطب', false);
                    button.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                showGlobalAjaxMessage('خطا در اتصال به سرور', false);
                button.prop('disabled', false).html(originalText);
            }
        });
    });

    // Edit contact basic info
    $(document).on('click', '.edit-contact-basic', function(e) {
        e.preventDefault();
        var button = $(this);
        var contactId = button.data('contact-id');
        var row = button.closest('tr');
        var currentName = row.find('td:nth-child(2)').text().trim();
        var currentPhone = row.find('td:nth-child(3)').text().trim();
        
        // Create modal HTML
        var modalHtml = '<div class="edit-contact-modal">' +
            '<h3>ویرایش اطلاعات مخاطب</h3>' +
            '<div class="edit-form-container">' +
            '<div class="form-field">' +
            '<label for="edit-name-input">نام کامل:</label>' +
            '<input type="text" id="edit-name-input" class="edit-name" value="' + currentName + '">' +
            '</div>' +
            '<div class="form-field">' +
            '<label for="edit-phone-input">شماره تلفن:</label>' +
            '<input type="text" id="edit-phone-input" class="edit-phone" value="' + currentPhone + '">' +
            '</div>' +
            '<div class="form-actions">' +
            '<button type="button" class="button button-primary save-edit">ذخیره تغییرات</button>' +
            '<button type="button" class="button cancel-edit">انصراف</button>' +
            '</div>' +
            '</div>' +
            '</div>';
        
        // Create modal overlay
        var modalOverlay = $('<div class="edit-contact-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">');
        var modalContent = $('<div style="background: white; padding: 30px; border-radius: 8px; max-width: 500px; width: 90%; position: relative; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">');
        
        modalContent.html(modalHtml);
        modalOverlay.append(modalContent);
        $('body').append(modalOverlay);
        
        // Focus on name input
        modalContent.find('.edit-name').focus();
        
        // Handle save
        modalContent.find('.save-edit').on('click', function() {
            var newName = modalContent.find('.edit-name').val().trim();
            var newPhone = modalContent.find('.edit-phone').val().trim();
            
            console.log('Edit form data:', {
                contactId: contactId,
                newName: newName,
                newPhone: newPhone,
                nonce: miniCrmAdminAjax.nonce_edit_contact_basic
            });
            
            if (!newName || !newPhone) {
                alert('نام و شماره تلفن نمی‌توانند خالی باشند.');
                return;
            }
            
            var saveButton = $(this);
            var originalText = saveButton.text();
            saveButton.prop('disabled', true).text(miniCrmAdminAjax.text.editing || 'در حال ویرایش...');
            
            $.ajax({
                url: miniCrmAdminAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'mini_crm_edit_contact_basic_info',
                    nonce: miniCrmAdminAjax.nonce_edit_contact_basic,
                    contact_id: contactId,
                    full_name: newName,
                    phone: newPhone
                },
                dataType: 'json',
                beforeSend: function() {
                    console.log('Sending AJAX request for contact edit...');
                },
                success: function(response) {
                    console.log('AJAX response:', response);
                    if (response.success) {
                        // Replace the row with updated HTML
                        row.replaceWith(response.data.new_row_html);
                        modalOverlay.remove();
                        showGlobalAjaxMessage(response.data.message, true);
                        // Re-initialize datepickers for the new row
                        initializePersianDatepickers();
                    } else {
                        console.error('Edit failed:', response.data.message);
                        showGlobalAjaxMessage(response.data.message || 'خطا در ویرایش اطلاعات', false);
                        saveButton.prop('disabled', false).text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    showGlobalAjaxMessage('خطا در اتصال به سرور', false);
                    saveButton.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Handle cancel
        modalContent.find('.cancel-edit').on('click', function() {
            modalOverlay.remove();
        });
        
        // Close modal when clicking overlay
        modalOverlay.on('click', function(e) {
            if (e.target === this) {
                $(this).remove();
            }
        });
        
        // Close modal with Escape key
        $(document).on('keyup.edit-contact', function(e) {
            if (e.keyCode === 27) {
                modalOverlay.remove();
                $(document).off('keyup.edit-contact');
            }
        });
        
        // Handle Enter key in inputs
        modalContent.find('input').on('keypress', function(e) {
            if (e.which === 13) {
                modalContent.find('.save-edit').click();
            }
        });
    });

    // View SMS history
    $(document).on('click', '.view-sms-history', function(e) {
        e.preventDefault();
        var button = $(this);
        var contactId = button.data('contact-id');
        var originalText = button.html();
        
        button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt"></span> ' + miniCrmAdminAjax.text.loading);
        
        $.ajax({
            url: miniCrmAdminAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'mini_crm_view_sms_history',
                nonce: miniCrmAdminAjax.nonce_view_sms_history,
                contact_id: contactId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Create modal overlay
                    var modalOverlay = $('<div class="sms-history-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;">');
                    var modalContent = $('<div style="background: white; padding: 20px; border-radius: 5px; max-width: 90%; max-height: 80%; overflow-y: auto; position: relative;">');
                    
                    modalContent.html(response.data.html);
                    modalOverlay.append(modalContent);
                    $('body').append(modalOverlay);
                    
                    // Close modal when clicking overlay
                    modalOverlay.on('click', function(e) {
                        if (e.target === this) {
                            $(this).remove();
                        }
                    });
                    
                    // Close modal with Escape key
                    $(document).on('keyup.sms-history', function(e) {
                        if (e.keyCode === 27) {
                            modalOverlay.remove();
                            $(document).off('keyup.sms-history');
                        }
                    });
                    
                } else {
                    showGlobalAjaxMessage(response.data.message || 'خطا در بارگذاری تاریخچه پیامک‌ها', false);
                }
            },
            error: function() {
                showGlobalAjaxMessage('خطا در اتصال به سرور', false);
            },
            complete: function() {
                button.prop('disabled', false).html(originalText);
            }
        });
    });

    // --- Test Email Button ---
    $('#mini_crm_test_email').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var originalText = button.text();
        var resultDiv = $('#email_test_result');
        
        // Disable button and show loading
        button.prop('disabled', true).text('در حال ارسال...');
        resultDiv.html('');
        
        $.ajax({
            url: miniCrmAdminAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'mini_crm_test_email',
                nonce: miniCrmAdminAjax.nonce_test_email
            },
            success: function(response) {
                if (response.success) {
                    resultDiv.html('<div class="notice notice-success inline"><p>' + response.data + '</p></div>');
                } else {
                    resultDiv.html('<div class="notice notice-error inline"><p>' + response.data + '</p></div>');
                }
            },
            error: function() {
                resultDiv.html('<div class="notice notice-error inline"><p>خطا در اتصال به سرور</p></div>');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    });

});