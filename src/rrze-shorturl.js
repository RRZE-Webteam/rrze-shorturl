jQuery(document).ready(function ($) {
    var advancedSettingsChanged = false; // Variable to track changes in advanced settings

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

    // Event listener for changes in the advanced settings
    $('#div-advanced-settings input[type="text"], #div-advanced-settings input[type="date"], #div-advanced-settings select').on('change', function () {
        $("#generate").prop('value', 'Update');
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

        // console.log('label = ' + label);
        // console.log('url = ' + rrze_shorturl_ajax_object.ajax_url);

        // Send AJAX request to update category label
        $.ajax({
            url: rrze_shorturl_ajax_object.ajax_url,
            type: 'POST',
            dataType: 'json', // Specify that the response should be treated as JSON
            data: {
                action: 'update_category_label_action',
                category_id: id,
                updated_label: label,
                _ajax_nonce: rrze_shorturl_ajax_object.update_category_label_nonce, // Pass nonce                    
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



    // tokenfield for tags
    $('#tag-tokenfield').select2({
        tags: true,
        tokenSeparators: [','],
        placeholder: 'Add tags'
    });


    $('#tag-tokenfield').on('select2:selecting', function (e) {
        var tagValue = e.params.args.data.text;

        if (e.params.args.data.id === e.params.args.data.text) {
            $.ajax({
                url: rrze_shorturl_ajax_object.ajax_url,
                method: 'POST',
                data: {
                    action: 'add_shorturl_tag',
                    new_tag_name: tagValue,
                    dataType: 'json',
                    _ajax_nonce: rrze_shorturl_ajax_object.add_shorturl_tag_nonce
                },
                success: function (response) {
                    if (response.success) {
                        var newTagId = response.data.id;

                        // Workaround: remove the option that select2 automatically has added (with the wrong value)
                        $('#tag-tokenfield option[value="' + tagValue + '"]').remove();
                        // Append the new tag
                        $('#tag-tokenfield').append('<option value="' + newTagId + '" selected>' + tagValue + '</option>');
                        $('#tag-tokenfield').trigger('change');

                    } else {
                        console.error('Error adding tag:', response.error);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error adding tag:', error);
                }
            });
        }
    });


    // categories
    $('#add-new-shorturl-category').on('click', function (e) {
        e.preventDefault();
        $('#new-shorturl-category').slideToggle();
    });

    $('#add-shorturl-category-btn').on('click', function (e) {
        e.preventDefault();
        var categoryName = $('input[name=new_shorturl_category]').val();
        if (categoryName) {
            $.ajax({
                url: rrze_shorturl_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'add_shorturl_category',
                    categoryName: categoryName,
                    parentCategory: $('select[name=parent_category]').val(),
                    _ajax_nonce: rrze_shorturl_ajax_object.add_shorturl_category_nonce
                },
                success: function (response) {
                    if (response.success) {
                        // Replace the existing category list with the updated HTML
                        $('#shorturl-category-metabox').html(response.data.category_list_html);
                        // Check the checkbox for the newly added category
                        var newCategoryId = response.data.category_id;
                        $('input[name="shorturl_categories[]"][value="' + newCategoryId + '"]').prop('checked', true);

                        // alert('Category added successfully!');
                    } else {
                        alert('Failed to add category. Please try again.');
                    }
                }
            });
        } else {
            alert('Please enter a category name.');
        }
    });



    // links
    // Edit link click handler
    $(document).on('click', '.edit-link', function (e) {
        e.preventDefault();
        var linkId = $(this).data('link-id');
        var currentUrl = window.location.href;
        var newUrl = currentUrl.split('?')[0] + '?link_id=' + linkId + '#edit-link-form';

        window.location.href = newUrl;
    });


    // Delete link click handler
    $(document).on('click', '.delete-link', function (e) {
        e.preventDefault();
        var linkId = $(this).data('link-id');
        // Ask for confirmation before deleting
        if (confirm('Are you sure you want to delete this link?')) {
            // Send an AJAX request to delete the link
            $.ajax({
                url: rrze_shorturl_ajax_object.ajax_url,
                method: 'POST',
                data: {
                    action: 'delete_link',
                    link_id: linkId,
                    _ajax_nonce: rrze_shorturl_ajax_object.delete_shorturl_link_nonce
                },
                success: function (response) {
                    // Handle success response
                    console.log('Link deleted successfully');
                    location.reload();
                },
                error: function (xhr, status, error) {
                    // Handle error response
                    console.error('Error deleting link:', error);
                }
            });
        }
    });


    // IdM
    $('.allow-uri-checkbox, .allow-get-checkbox').change(function () {
        var id = $(this).data('id');
        var field = $(this).hasClass('allow-uri-checkbox') ? 'allow_uri' : 'allow_get';
        var value = $(this).prop('checked') ? 'true' : 'false';

        // Send AJAX request to update allow_uri or allow_get
        $.ajax({
            url: rrze_shorturl_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'update_idm',
                id: id,
                field: field,
                value: value,
                _ajax_nonce: rrze_shorturl_ajax_object.update_shorturl_idm_nonce
            },
            success: function (response) {
                console.log('Field updated successfully');
                // Reload the page or update UI as needed
                location.reload();
            },
            error: function (xhr, status, error) {
                console.error('Error updating field:', error);
            }
        });
    });

    // Copy to Clipboard
    function copyToClipboard(shortenedUrl) {
        if (shortenedUrl) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(shortenedUrl)
                    .then(() => {
                        console.log('URL copied!');
                        showTooltip('URL copied!');
                    })
                    .catch(err => {
                        console.error('Copy failed:', err);
                    });
            } else {
                // Fallback method for browsers that do not support Clipboard API
                const textArea = document.createElement('textarea');
                textArea.value = shortenedUrl;
                textArea.style.position = 'fixed'; // Ensure it's not visible in the viewport
                document.body.appendChild(textArea);
                textArea.select(); // Select the text
                try {
                    document.execCommand('copy');
                    console.log('URL copied!');
                    showTooltip('URL copied!');
                } catch (err) {
                    console.error('Copy failed:', err);
                } finally {
                    document.body.removeChild(textArea); // Remove the textarea from the DOM
                }
            }
        }
    }

    function showTooltip(message) {
        const tooltip = document.getElementById('tooltip');
        tooltip.textContent = message;
        tooltip.style.display = 'inline-block';
        setTimeout(() => {
            tooltip.style.display = 'none';
        }, 2000); // Hide the tooltip after 2 seconds
    }


    // Attach event listener to the "Copy to clipboard" image
    $(document).on('click', '#copyButton', function (event) {
        event.preventDefault();
        var shortenedUrl = $(this).data('shortened-url');
        copyToClipboard(shortenedUrl);
        event.stopPropagation(); // Stop event propagation
        return false; // Prevent the default action and propagation        
    });


    // Categories
    // Add mouseover event to show "Edit" link
    $('table.shorturl-categories tbody').on('mouseover', 'td.category-label', function () {
        if (!$(this).find('.edit-link').length) {
            $(this).append('<a href="#" class="edit-link">Edit</a>');
        }
    });

    // Remove "Edit" link on mouseout, unless it's being hovered or clicked
    $('table.shorturl-categories tbody').on('mouseout', 'td.category-label', function (e) {
        var editLink = $(this).find('.edit-link');
        if (!editLink.is(':hover') && !editLink.hasClass('editing')) {
            editLink.remove();
        }
    });

    // Handle "Edit" link click event
    $('table.shorturl-categories tbody').on('click', '.edit-link', function (e) {
        e.preventDefault(); // Prevent default link behavior
        e.stopPropagation(); // Prevent event bubbling
        $(this).hide(); // Hide the "Edit" link    
        var labelSpan = $(this).closest('td').find('span'); // Find the <span> element containing the label text
        var labelText = labelSpan.text().trim(); // Get the text content of the <span> element
        labelSpan.html('<input type="text" class="category-input">').find('.category-input').val(labelText); // Replace the <span> content with the input field and set its value to the label text
        $('.category-input').focus().select();
        $(this).addClass('editing'); // Mark as editing
    });

    // Handle input blur (editing finished)
    $('table.shorturl-categories tbody').on('blur', '.category-input', function () {
        var newValue = $(this).val().trim();
        var categoryId = $(this).closest('td').data('id');
        var editLink = $(this).parent().find('.edit-link');

        // Send AJAX request to update the label in the database
        $.ajax({
            url: rrze_shorturl_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'update_shorturl_category_label',
                category_id: categoryId,
                updated_label: newValue,
                _ajax_nonce: rrze_shorturl_ajax_object.update_category_label_nonce
            },
            success: function (response) {
                // Update the label cell with the new value
                var labelSpan = $('<span>').text(newValue); // Create a span containing the new label text
                $(this).parent().empty().append(labelSpan); // Empty the cell and append the span containing the new label text
                editLink.show(); // Show the "Edit" link again
            }.bind(this) // Ensure 'this' refers to the correct element inside the success callback
        });
    });


    // Delete link click handler
    $(document).on('click', '.delete-category', function (e) {
        e.preventDefault();
        var categoryId = $(this).data('category-id');
        // Ask for confirmation before deleting
        if (confirm('Are you sure you want to delete this category?')) {
            // Send an AJAX request to delete the category
            $.ajax({
                url: rrze_shorturl_ajax_object.ajax_url,
                method: 'POST',
                data: {
                    action: 'delete_category',
                    category_id: categoryId,
                    _ajax_nonce: rrze_shorturl_ajax_object.delete_shorturl_category_nonce
                },
                success: function (response) {
                    // Handle success response
                    console.log('Category deleted successfully');
                    location.reload();
                },
                error: function (xhr, status, error) {
                    // Handle error response
                    console.error('Error deleting category:', error);
                }
            });
        }
    });

    // Edit Category
    $(".edit-category-button").click(function() {
        var categoryId = $(this).data("category-id");
        $(".edit-category-form[data-category-id=" + categoryId + "]").toggle();
        $(".wp-list-table").hide();
    });

});

