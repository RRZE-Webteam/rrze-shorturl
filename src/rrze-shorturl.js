jQuery(document).ready(function($) {
    // Bind form submission handler to both customer domains form and services form
    $('#customer-domains-form, #services-form').on('submit', handleFormSubmission);

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


    $('#show-advanced-settings').on('click', function(e) {
        e.preventDefault();
        $('#div-advanced-settings').slideToggle();
    });


    new ClipboardJS('.copy-to-clipboard');

            // Optional: Show a tooltip or feedback after copying
            document.querySelectorAll('.copy-to-clipboard').forEach(function(button) {
                button.addEventListener('click', function() {
                    button.setAttribute('data-original-title', 'Copied!');
                });
            });
});
