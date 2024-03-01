jQuery(document).ready(function ($) {
    // Function to handle form submission for both customer domains and services
    function handleFormSubmission() {
        // If the delete action is triggered
        if ($(this).find('input[type="checkbox"]:checked').length > 0) {
            // Determine the confirmation message based on the form
            var confirmationMessage = 'WARNING! Are you sure you want to delete the selected entries? All Short URLs related to them will be deleted, too. This action cannot be undone.';

            // Ask for confirmation
            var confirmDelete = confirm(confirmationMessage);

            // If the user confirms, proceed with the delete action
            if (!confirmDelete) {
                return false; // Prevent form submission
            }
        }
    }

    // Bind form submission handler to both customer domains form and services form
    $('#customer-domains-form, #services-form').on('submit', handleFormSubmission);

    // Toggle Advanced Settings
    $('#show-advanced-settings').on('click', function (e) {
        e.preventDefault();
        $('#div-advanced-settings').slideToggle();
    });

    // Copy Short URL to clipboard
    new ClipboardJS('.copy-to-clipboard');

    // Optional: Show a tooltip or feedback after copying
    document.querySelectorAll('.copy-to-clipboard').forEach(function (button) {
        button.addEventListener('click', function () {
            button.setAttribute('data-original-title', 'Copied!');
        });
    });

    $(document).on("mouseover", ".shorturl-category-row", function () {
        $(this).find(".shorturl-edit-category, .shorturl-delete-category").removeClass("hidden");
    });

    $(document).on("mouseleave", ".shorturl-category-row", function () {
        $(this).find(".shorturl-edit-category, .shorturl-delete-category").addClass("hidden");
    });


    $(document).on("click", ".shorturl-edit-category", function (e) {
        e.preventDefault();
        var label = $(this).siblings(".shorturl-category-label").text().trim(); // Get the label from the adjacent span
        var id = $(this).data("id");
        // Replace label with input field and update button on click
        $(this).parent().html("<div class='shorturl-edit-container'><input type='text' class='shorturl-category-input' data-id='" + id + "' value='" + label + "'><button class='shorturl-update-category' data-id='" + id + "'>Update</button></div>");
    });


    // update category
    $(document).on("click", ".shorturl-update-category", function () {
        // Handle category update on button click
        var id = $(this).data("id");
        var label = $(this).parent().find(".shorturl-category-input").val();

        console.log('label = ' + label);
        console.log('url = ' + rrze_shorturl_ajax_object.ajax_url);

        // Send AJAX request to update category label
        $.ajax({
            url: rrze_shorturl_ajax_object.ajax_url,
            type: 'POST',
            dataType: 'json', // Specify that the response should be treated as JSON
            data: {
                action: 'update_category_label_action',
                category_id: id,
                updated_label: label,
                security: rrze_shorturl_ajax_object.update_category_label_nonce, // Pass nonce                    
            },
            success: function (response) {
                // Display message
                $("p").html(response.data);
                // Replace input field with label after successful update
                $(".shorturl-category-input[data-id='" + id + "']").parent().html("<span class='category-label'>" + label + "</span>");
            },
            error: function (xhr, status, error) {
                // Display error message
                console.error('THIS IS AN ERROR! ' + xhr.responseText);
            }
        });
    });



    

});
