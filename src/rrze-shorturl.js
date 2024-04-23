// import { __, _x, _n, sprintf } from '@wordpress/i18n';


jQuery(document).ready(function ($) {
    
    // Make some fancy QR
    var inputField = document.getElementById("shortened_url");
    var qrValue = inputField ? inputField.value : "https://www.fau.de";

    var qr = new QRious({
        element: document.getElementById("qr"),
        value: qrValue,
        size: 200,
        });    

    function handleFormSubmission() {
        // If the delete action is triggered
        if ($(this).find('input[type="checkbox"]:checked').length > 0) {
            var confirmationMessage = __('WARNING! Are you sure you want to delete the selected entries? All Short URLs related to them will be deleted, too. This action cannot be undone.', 'rrze-shorturl');

            var confirmDelete = confirm(confirmationMessage);

            if (!confirmDelete) {
                return false; // Prevent form submission
            }
        }
    }

    // Bind form submission handler to both customer domains form and services form
    $('#customer-domains-form, #services-form').on('submit', handleFormSubmission);

    // Toggle Advanced Settings
    $('#show-advanced-settings').addClass('shorturl-link-disabled');

    $('#url').on('input', function() {
        var url = $(this).val();
        if (url.trim() !== '') {
            $('#show-advanced-settings').attr('href', '#').removeClass('shorturl-link-disabled');
        } else {
            $('#show-advanced-settings').removeAttr('href').addClass('shorturl-link-disabled');
        }
    });

    $('#show-advanced-settings').on('click', function (e) {
        e.preventDefault();
        $('#div-advanced-settings').slideToggle();
        $('.shorturl-arrow').toggleClass('up');
        if ($('.shorturl-arrow').hasClass('up')) {
            $('.shorturl-arrow').html('&#9650;');
        } else {
            $('.shorturl-arrow').html('&#9660;');
        }        
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

        $.ajax({
            url: rrze_shorturl_ajax_object.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'update_category_label_action',
                category_id: id,
                updated_label: label,
                _ajax_nonce: rrze_shorturl_ajax_object.update_category_label_nonce,
            },
            success: function (response) {
                $("p").html(response.data);
                // Replace input field with label after successful update
                $(".shorturl-category-input[data-id='" + id + "']").parent().html("<span class='category-label'>" + label + "</span>");
            },
            error: function (xhr, status, error) {
                console.error('THIS IS AN ERROR! ' + xhr.responseText);
            }
        });
    });

    // tokenfield for tags
    // $('#tag-tokenfield').select2({
    //     tags: true,
    //     tokenSeparators: [','],
    //     placeholder: __('Add tags', 'rrze-shorturl')
    // });


    // $('#tag-tokenfield').on('select2:selecting', function (e) {
    //     var tagValue = e.params.args.data.text;

    //     if (e.params.args.data.id === e.params.args.data.text) {
    //         $.ajax({
    //             url: rrze_shorturl_ajax_object.ajax_url,
    //             method: 'POST',
    //             data: {
    //                 action: 'add_shorturl_tag',
    //                 new_tag_name: tagValue,
    //                 dataType: 'json',
    //                 _ajax_nonce: rrze_shorturl_ajax_object.add_shorturl_tag_nonce
    //             },
    //             success: function (response) {
    //                 if (response.success) {
    //                     var newTagId = response.data.id;

    //                     // Workaround: remove the option that select2 automatically has added (with the wrong value)
    //                     $('#tag-tokenfield option[value="' + tagValue + '"]').remove();
    //                     // Append the new tag
    //                     $('#tag-tokenfield').append('<option value="' + newTagId + '" selected>' + tagValue + '</option>');
    //                     $('#tag-tokenfield').trigger('change');

    //                 } else {
    //                     console.error('Error adding tag:', response.error);
    //                 }
    //             },
    //             error: function (xhr, status, error) {
    //                 console.error('Error adding tag:', error);
    //             }
    //         });
    //     }
    // });


    // Categories
    $('#new-shorturl-category').slideToggle();
    $('#add-new-shorturl-category').on('click', function (e) {
        e.preventDefault();
        $('#new-shorturl-category').slideToggle();
    });
    
    $(document).on('click', '#add-shorturl-category-btn', function (e) {
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
                    } else {
                        alert(__('Failed to add category. Please try again.', 'rrze-shorturl'));
                    }
                }
            });
        } else {
            alert(__('Please enter a category name.', 'rrze-shorturl'));
        }
    });



    // Links
    // Edit link
    $(document).on('click', '.edit-link', function (e) {
        e.preventDefault();
        var linkId = $(this).data('link-id');
        var currentUrl = window.location.href;
        var newUrl = currentUrl.split('?')[0] + '?link_id=' + linkId + '#edit-link-form';

        window.location.href = newUrl;
    });


    // Delete link
    $(document).on('click', '.delete-link', function (e) {
        e.preventDefault();
        var linkId = $(this).data('link-id');
        if (confirm('Are you sure you want to delete this link?')) {
            $.ajax({
                url: rrze_shorturl_ajax_object.ajax_url,
                method: 'POST',
                data: {
                    action: 'delete_link',
                    link_id: linkId,
                    _ajax_nonce: rrze_shorturl_ajax_object.delete_shorturl_link_nonce
                },
                success: function (response) {
                    location.reload();
                },
                error: function (xhr, status, error) {
                    console.error('Error deleting link:', error);
                }
            });
        }
    });


    // IdM
    $(document).on('change', '.allow-uri-checkbox, .allow-get-checkbox', function () {    
        var id = $(this).data('id');
        var field = $(this).hasClass('allow-uri-checkbox') ? 'allow_uri' : 'allow_get';
        var value = $(this).prop('checked') ? 'true' : 'false';

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
                textArea.select();
                try {
                    document.execCommand('copy');
                    showTooltip(__('URL copied!', 'rrze-shorturl'));
                } catch (err) {
                    console.error('Copy failed:', err);
                } finally {
                    document.body.removeChild(textArea); // Remove the textarea from the DOM
                }
            }
        }
    }

    function showTooltip(message) {
        const shorturl_tooltip = document.getElementById('shorturl-tooltip');
        shorturl_tooltip.textContent = message;
        shorturl_tooltip.style.display = 'inline-block';    
        setTimeout(() => {
            shorturl_tooltip.style.display = 'none';
        }, 2000); // Hide the shorturl-tooltip after 2 seconds
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
    $('table.shorturl-categories tbody').on('mouseover', 'td.category-label', function () {
        if (!$(this).find('.edit-link').length) {
            $(this).append('<a href="#" class="edit-link">' + __('Edit', 'rrze-shorturl') + '</a>');
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
        e.preventDefault();
        e.stopPropagation();
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


    // Delete Category
    $(document).on('click', '.delete-category', function (e) {
        e.preventDefault();
        var categoryId = $(this).data('category-id');
        if (confirm(__('Are you sure you want to delete this category?', 'rrze-shorturl'))) {
            $.ajax({
                url: rrze_shorturl_ajax_object.ajax_url,
                method: 'POST',
                data: {
                    action: 'delete_category',
                    category_id: categoryId,
                    _ajax_nonce: rrze_shorturl_ajax_object.delete_shorturl_category_nonce
                },
                success: function (response) {
                    // Remove 'action' query parameter from URL ( if we'd use location.reload(); instead the browser would ask to send the form again)
                    var url = window.location.href;
                    url = url.split('?')[0]; // Remove query string
                    window.location.href = url; // Reload the page without the 'action' parameter
                },
                error: function (xhr, status, error) {
                    console.error('Error deleting category:', error);
                }
            });
        }
    });

    // Edit Category
    $(document).on('click', '.edit-category-button', function () {    
        var categoryId = $(this).data("category-id");
        $(".edit-category-form[data-category-id=" + categoryId + "]").toggle();
        $(".shorturl-wp-list-table").hide();
    });

    // Edit Tag
    // $(document).on('click', '.edit-tag-button', function () {    
    //     var tagId = $(this).data("tag-id");
    //     $(".edit-tag-form[data-tag-id=" + tagId + "]").toggle();
    //     $(".shorturl-wp-list-table").hide();
    // });

    // Delete Tag
    // $(document).on('click', '.delete-tag', function (e) {
    //     e.preventDefault();
    //     var tagId = $(this).data('tag-id');
    //     if (confirm(__('Are you sure you want to delete this tag?', 'rrze-shorturl'))) {
    //         $.ajax({
    //             url: rrze_shorturl_ajax_object.ajax_url,
    //             method: 'POST',
    //             data: {
    //                 action: 'delete_tag',
    //                 tag_id: tagId,
    //                 _ajax_nonce: rrze_shorturl_ajax_object.delete_shorturl_tag_nonce
    //             },
    //             success: function (response) {
    //                 // Remove 'action' query parameter from URL ( if we'd use location.reload(); instead the browser would ask to send the form again)
    //                 var url = window.location.href;
    //                 url = url.split('?')[0]; // Remove query string
    //                 window.location.href = url; // Reload the page without the 'action' parameter
    //             },
    //             error: function (xhr, status, error) {
    //                 console.error('Error deleting tag:', error);
    //             }
    //         });
    //     }
    // });
});

