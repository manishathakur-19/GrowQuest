jQuery(document).ready(function ($) {
    $('#user_tags').select2({
        placeholder: "Select User Tags",
        allowClear: true,
        width: '300px'
    });

    $('.filter_users_btn').click(function () {
        var selectedTag = $(this).closest(".filter-container").find(".user_tag_filter").val();
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'filter_users_by_tag',
                user_tag: selectedTag
            },
            beforeSend: function () {
                $('#the-list').html('<tr><td colspan="2">Loading...</td></tr>');
            },
            success: function (response) {
                if (response.success) {
                    $('#the-list').html(response.data);
                } else {
                    $('#the-list').html('<tr><td colspan="2">No users found.</td></tr>');
                }
            }
        });
    });
});
