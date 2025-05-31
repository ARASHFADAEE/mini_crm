jQuery(document).ready(function($) {
    var ajaxMessageGlobalDiv = $('#mini-crm-ajax-message-global');
    var contactsTbody = $('#mini-crm-contacts-tbody');
    var paginationContainer = $('#mini-crm-pagination-container');
    var currentPage = 1;

    function showGlobalAjaxMessage(message, isSuccess) {
        var messageClass = isSuccess ? 'notice-success' : 'notice-error';
        // Clear previous classes before adding new ones
        ajaxMessageGlobalDiv.removeClass('notice-success notice-error notice is-dismissible').addClass(messageClass + ' notice is-dismissible');
        ajaxMessageGlobalDiv.html('<p>' + message + '</p>'); // Wrap message in <p> for consistent styling
        
        // Ensure dismiss button exists or add it
        if (ajaxMessageGlobalDiv.find('.notice-dismiss').length === 0) {
            ajaxMessageGlobalDiv.append($('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>'));
        }
        ajaxMessageGlobalDiv.fadeIn();

        // Handle dismiss click
        ajaxMessageGlobalDiv.off('click.dismiss').on('click.dismiss', '.notice-dismiss', function(e) {
            e.preventDefault();
            $(this).closest('.notice').fadeOut();
        });
    }

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

    // --- Persian Datepicker Initialization ---
    function initializePersianDatepicker(selector) {
        if (typeof $.fn.persianDatepicker === 'function' && $(selector).length) {
            $(selector).each(function() {
                var $input = $(this);
                if ($input.data('hasPersianDatepicker')) {
                    // If re-initialization is needed, you might need to destroy first
                    // For now, we assume it's not re-initialized on the same element without destruction
                    return;
                }

                var initialGregorianDate = $input.data('gregorian-date-init');
                var altFieldSelector = $input.siblings('.contact-visit-date-gregorian-alt'); // Alt field for Gregorian date

                if (!altFieldSelector.length && $input.attr('id') === 'add_visit_date_display_field') {
                    // For "Add New" form, the alt field is fixed by ID
                    altFieldSelector = $('#add_visit_date_gregorian_alt_field');
                }
                
                if (!altFieldSelector.length) {
                    // console.warn("Mini CRM: Alt field for Gregorian date not found for a datepicker input.", $input);
                    // return; // Or proceed without altField, but then onSelect needs to handle Gregorian conversion
                }

                $input.persianDatepicker({
                    format: 'YYYY/MM/DD',       // Display format for Persian date
                    altField: altFieldSelector.length ? altFieldSelector : null,
                    altFormat: 'YYYY-MM-DD',    // Storage format for Gregorian date in altField
                    observer: true,             // Auto-updates altField
                    initialValue: !!initialGregorianDate,
                    initialValueType: initialGregorianDate ? 'gregorian' : 'persian',
                    autoClose: true,
                    toolbox: {
                        calendarSwitch: { enabled: true, format: 'YYYY/MM/DD' }
                    },
                    onSelect: function(unixDate, pickerInstance) {
                        // 'this' is the datepicker instance, pickerInstance.model.inputElement is the jQuery input
                        var $selectedInput = $(pickerInstance.model.inputElement);
                        // When a date is selected, the altField (if configured) is updated.
                        // We need to trigger 'change' on a field that our main AJAX handler listens to.
                        // Let's trigger it on the altField if it exists and has the dynamic update class,
                        // or on the main datepicker input if the altField doesn't trigger updates.
                        var $altField = $(pickerInstance.model.altField);
                        if ($altField.length) {
                             // Trigger change on the altField to make sure its new value is picked up by any listeners,
                             // including our dynamic update if the altField itself is what we should monitor.
                             // However, our current setup monitors the visible input or the time input.
                             // The most reliable way is to ensure the main AJAX handler reads from altField.
                             // Let's trigger change on the main input, which will cause the AJAX handler to fire
                             // and read values from both date (altField) and time inputs.
                            $selectedInput.trigger('change');
                        }
                    }
                });
                $input.data('hasPersianDatepicker', true);
            });
        } else if ($(selector).length) {
            // console.warn("Persian Datepicker library ($.fn.persianDatepicker) not loaded, but elements exist for it.");
        }
    }

    // Initialize for "Add New Contact" page
    initializePersianDatepicker('#add_visit_date_display_field');
    // Initialize for existing rows in the contacts list on page load
    initializePersianDatepicker(contactsTbody.find('.mini-crm-persian-datepicker-input'));

    // --- Filter and Search Contacts ---
    function fetchContacts(pageToFetch) {
        currentPage = parseInt(pageToFetch) || 1;
        var searchTerm = $('#mini-crm-search-input').val().trim();
        var statusFilter = $('#mini-crm-filter-status').val();

        contactsTbody.html('<tr><td colspan="11" style="text-align:center;">' + miniCrmAdminAjax.text.loading + '</td></tr>');
        paginationContainer.html('');

        $.ajax({
            url: miniCrmAdminAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'mini_crm_filter_search_contacts',
                nonce: miniCrmAdminAjax.nonce_filter,
                paged: currentPage,
                search_term: searchTerm,
                status_filter: statusFilter
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    contactsTbody.html(response.data.html_rows);
                    initializePersianDatepicker(contactsTbody.find('.mini-crm-persian-datepicker-input')); // Re-initialize for new rows
                    paginationContainer.html(response.data.pagination_html);
                    currentPage = parseInt(response.data.current_page) || 1;
                } else {
                    contactsTbody.html('<tr><td colspan="11" style="text-align:center;">' + (response.data.message || miniCrmAdminAjax.text.error_loading) + '</td></tr>');
                }
            },
            error: function() {
                contactsTbody.html('<tr><td colspan="11" style="text-align:center;">' + miniCrmAdminAjax.text.error_server + '</td></tr>');
            }
        });
    }

    $('#mini-crm-search-button, #mini-crm-filter-button').on('click', function() { fetchContacts(1); });
    $('#mini-crm-search-input').on('keypress', function(e) { if (e.which === 13) fetchContacts(1); });
    paginationContainer.on('click', 'a.page-numbers', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        var pageMatch = href.match(/paged=(\d+)/);
        var pageNumText = $(this).text();
        var targetPage = currentPage;

        if (pageMatch && pageMatch[1]) { targetPage = parseInt(pageMatch[1]); }
        else if ($.isNumeric(pageNumText)) { targetPage = parseInt(pageNumText); }
        else if ($(this).hasClass('next')) { targetPage = currentPage + 1; }
        else if ($(this).hasClass('prev')) { targetPage = currentPage - 1 > 0 ? currentPage - 1 : 1; }
        fetchContacts(targetPage);
    });

    // --- Inline Editing for Contact Details ---
    contactsTbody.on('change', '.contact-dynamic-update', debounce(function() {
        var $changedElement = $(this);
        var $row = $changedElement.closest('tr');
        var contactId = $row.data('contact-id');
        var field = $changedElement.data('field'); // The field that actually triggered the change
        var value = $changedElement.val();        // Value of the field that triggered the change

        var dataToSend = {
            action: 'mini_crm_update_contact_details',
            nonce: miniCrmAdminAjax.nonce_update,
            contact_id: contactId,
            field: field, // Send the original field that changed
            value: value  // and its value
        };

        // If a date or time field changed, we need to collect both date (Gregorian) and time (start)
        // The PHP handler for 'visit_date_gregorian_alt' or 'visit_time_start' will handle this.
        if (field === 'visit_date_display' || field === 'visit_time_start' || field === 'visit_date_gregorian_alt') {
            // When the Persian date display field changes (due to datepicker selection),
            // or the time input changes.
            // We always send both the gregorian date (from alt field) and the time start.
            dataToSend.gregorian_date_alt = $row.find('.contact-visit-date-gregorian-alt').val(); // YYYY-MM-DD
            dataToSend.time_start_val = $row.find('.mini-crm-visit-time-input').val();           // HH:MM
            
            // PHP will use these two to reconstruct the full visit_date (datetime)
            // We also still send the original 'field' and 'value' that triggered the event,
            // PHP can decide if it needs them or primarily uses gregorian_date_alt and time_start_val.
            // The PHP switch case for 'visit_date_gregorian_alt' and 'visit_time_start' should take precedence.
        }
        // For other fields (status, sub_status, visit_end_time, visit_note),
        // dataToSend.field and dataToSend.value are already correctly set.

        $row.addClass('mini-crm-row-loading'); // Use the CSS class for loading

        $.ajax({
            url: miniCrmAdminAjax.ajaxurl,
            type: 'POST',
            data: dataToSend,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showGlobalAjaxMessage(response.data.message, true);
                    var $newRow = $(response.data.new_row_html);
                    $row.replaceWith($newRow);
                    initializePersianDatepicker($newRow.find('.mini-crm-persian-datepicker-input')); // Re-initialize for the new row
                } else {
                    showGlobalAjaxMessage(response.data.message || miniCrmAdminAjax.text.error_update, false);
                    // Reverting changes can be complex, especially for date/time.
                    // For simplicity, we might just show an error. The user can refresh or try again.
                    // To attempt revert: $changedElement.val($changedElement.data('original-value') || '');
                }
            },
            error: function() {
                showGlobalAjaxMessage(miniCrmAdminAjax.text.error_server, false);
            },
            complete: function() {
                // Ensure the correct row (which might be the new one) has loading class removed
                $('#contact-row-' + contactId).removeClass('mini-crm-row-loading');
            }
        });
    }, 750));

    // --- Manual SMS Sending ---
    contactsTbody.on('click', '.mini-crm-send-manual-sms', function() { /* ...  کد این بخش مانند قبل بدون تغییر ... */
        var $button = $(this); var $row = $button.closest('tr'); var contactId = $row.data('contact-id');
        var smsType = $button.data('sms-type'); var originalButtonText = $button.text();
        if (!confirm(miniCrmAdminAjax.text.confirm_sms + " (" + originalButtonText + ")")) return;
        $button.prop('disabled', true).text(miniCrmAdminAjax.text.sending);
        $.ajax({
            url: miniCrmAdminAjax.ajaxurl, type: 'POST',
            data: { action: 'mini_crm_send_manual_sms', nonce: miniCrmAdminAjax.nonce_send_manual_sms, contact_id: contactId, sms_type: smsType },
            dataType: 'json',
            success: function(response) { if (response.success) showGlobalAjaxMessage(response.data.message || miniCrmAdminAjax.text.sms_sent_success, true); else showGlobalAjaxMessage(response.data.message || miniCrmAdminAjax.text.sms_sent_failed, false); },
            error: function() { showGlobalAjaxMessage(miniCrmAdminAjax.text.error_sending_sms, false); },
            complete: function() { $button.prop('disabled', false).text(originalButtonText); }
        });
    });

    // --- Add New Contact Page - Show/Hide Visit Details ---
    var $addContactStatusSelect = $('#add_contact_status');
    var $addVisitDetailsSection = $('#add-visit-details-section');
    function toggleAddVisitDetails() { if ($addContactStatusSelect.val() === 'ACCEPT') $addVisitDetailsSection.slideDown(); else $addVisitDetailsSection.slideUp(); }
    if ($addContactStatusSelect.length) { $addContactStatusSelect.on('change', toggleAddVisitDetails); toggleAddVisitDetails(); }

    // --- Settings Page Tabs & Accordion ---
    var $settingsTabs = $('.nav-tab-wrapper a.nav-tab'); var $settingsTabContents = $('.settings-tab-content');
    if ($settingsTabs.length && $settingsTabContents.length) {
        $settingsTabs.on('click', function(e) { e.preventDefault(); var targetId = $(this).attr('href'); $settingsTabs.removeClass('nav-tab-active'); $(this).addClass('nav-tab-active'); $settingsTabContents.hide(); $(targetId).show(); });
        var hash = window.location.hash; if (hash && $settingsTabs.filter('[href="' + hash + '"]').length) { $settingsTabs.filter('[href="' + hash + '"]').click(); } else if ($settingsTabs.length) { $settingsTabs.first().click(); }
    }
    var $smsAccordion = $("#sms-templates-accordion");
    if ($smsAccordion.length && typeof $.fn.accordion === 'function') { $smsAccordion.accordion({ heightStyle: "content", collapsible: true, active: false }); }
    else if ($smsAccordion.length) { $smsAccordion.find("> h3").on("click", function() { $(this).next("div").slideToggle(); }).css("cursor", "pointer"); $smsAccordion.find("> div").hide(); }
});