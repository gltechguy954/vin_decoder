jQuery(document).ready(function($) {
    
    // Tab switching
    $('.tab-button').on('click', function() {
        const tab = $(this).data('tab');
        
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
        
        $('.tab-content').removeClass('active');
        $('#' + tab + '-tab').addClass('active');
    });
    
    // Add new field
    $('.add-new-field').on('click', function() {
        openFieldModal();
    });
    
    // Edit field
    $(document).on('click', '.edit-field', function() {
        const fieldKey = $(this).data('field-key');
        loadFieldData(fieldKey);
    });
    
    // Delete field
    $(document).on('click', '.delete-field', function() {
        const fieldKey = $(this).data('field-key');
        if (confirm('Are you sure you want to delete this field? This cannot be undone.')) {
            deleteField(fieldKey);
        }
    });
    
    // Add new group
    $('.add-new-group').on('click', function() {
        openGroupModal();
    });
    
    // Edit group
    $(document).on('click', '.edit-group', function() {
        const groupId = $(this).data('group-id');
        loadGroupData(groupId);
    });
    
    // Delete group
    $(document).on('click', '.delete-group', function() {
        const groupId = $(this).data('group-id');
        if (confirm('Are you sure you want to delete this group and all its fields? This cannot be undone.')) {
            deleteGroup(groupId);
        }
    });
    
    // Field type change handler
    $('#field-type').on('change', function() {
        const type = $(this).val();
        
        // Show/hide options based on type
        if (type === 'select' || type === 'radio' || type === 'checkbox_array') {
            $('#field-options-container').show();
        } else {
            $('#field-options-container').hide();
        }
        
        // Show/hide number settings
        if (type === 'number') {
            $('#field-number-settings').show();
        } else {
            $('#field-number-settings').hide();
        }
    });
    
    // Add field option
    $('.add-field-option').on('click', function() {
        const optionRow = $('<div class="field-option-row">' +
            '<input type="text" placeholder="Option Key (lowercase, no spaces)" class="option-key">' +
            '<input type="text" placeholder="Option Label" class="option-label">' +
            '<button type="button" class="remove-option">Remove</button>' +
            '</div>');
        
        $('#field-options-list').append(optionRow);
    });
    
    // Remove field option
    $(document).on('click', '.remove-option', function() {
        $(this).closest('.field-option-row').remove();
    });
    
    // Auto-generate field key from label
    $('#field-label').on('input', function() {
        if (!$('#field-original-key').val()) { // Only for new fields
            const label = $(this).val();
            const key = label.toLowerCase()
                .replace(/[^a-z0-9\s]/g, '')
                .replace(/\s+/g, '_')
                .replace(/_+/g, '_')
                .replace(/^_|_$/g, '');
            
            $('#field-key').val(key);
        }
    });
    
    // Auto-generate group ID from label
    $('#group-label').on('input', function() {
        if (!$('#group-original-id').val()) { // Only for new groups
            const label = $(this).val();
            const id = label.toLowerCase()
                .replace(/[^a-z0-9\s]/g, '')
                .replace(/\s+/g, '_')
                .replace(/_+/g, '_')
                .replace(/^_|_$/g, '');
            
            $('#group-id').val(id);
        }
    });
    
    // Field form submission
    $('#field-edit-form').on('submit', function(e) {
        e.preventDefault();
        
        const fieldData = {
            action: 'save_field',
            nonce: vinDecoderAjax.nonce,
            key: $('#field-key').val(),
            label: $('#field-label').val(),
            type: $('#field-type').val(),
            group: $('#field-group').val(),
            description: $('#field-description').val(),
            required: $('#field-required').is(':checked') ? 1 : 0,
            show_in_admin: $('#field-show-admin').is(':checked') ? 1 : 0,
            ai_fillable: $('#field-ai-fillable').is(':checked') ? 1 : 0,
        };
        
        // Add original key for updates
        if ($('#field-original-key').val()) {
            fieldData.original_key = $('#field-original-key').val();
        }
        
        // Collect options if applicable
        if ($('#field-options-container').is(':visible')) {
            fieldData.options = [];
            $('.field-option-row').each(function() {
                const key = $(this).find('.option-key').val();
                const label = $(this).find('.option-label').val();
                if (key && label) {
                    fieldData.options.push({ key: key, label: label });
                }
            });
        }
        
        // Add number settings if applicable
        if ($('#field-number-settings').is(':visible')) {
            fieldData.min = $('#field-min').val();
            fieldData.max = $('#field-max').val();
            fieldData.step = $('#field-step').val();
        }
        
        $.post(vinDecoderAjax.ajaxurl, fieldData, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });
    
    // Group form submission
    $('#group-edit-form').on('submit', function(e) {
        e.preventDefault();
        
        const groupData = {
            action: 'save_field_group',
            nonce: vinDecoderAjax.nonce,
            id: $('#group-id').val(),
            label: $('#group-label').val(),
            context: $('#group-context').val(),
            priority: $('#group-priority').val()
        };
        
        // Add original ID for updates
        if ($('#group-original-id').val()) {
            groupData.original_id = $('#group-original-id').val();
        }
        
        $.post(vinDecoderAjax.ajaxurl, groupData, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });
    
    // Modal close handlers
    $('.vin-modal-close, .cancel-field-edit, .cancel-group-edit').on('click', function() {
        $('.vin-modal').hide();
    });
    
    // Click outside modal to close
    $('.vin-modal').on('click', function(e) {
        if ($(e.target).hasClass('vin-modal')) {
            $(this).hide();
        }
    });
    
    // Functions
    function openFieldModal(fieldKey = null) {
        $('#field-modal-title').text(fieldKey ? 'Edit Field' : 'Add New Field');
        $('#field-edit-form')[0].reset();
        $('#field-original-key').val('');
        $('#field-options-list').empty();
        $('#field-type').trigger('change');
        $('#field-edit-modal').show();
    }
    
    function openGroupModal(groupId = null) {
        $('#group-modal-title').text(groupId ? 'Edit Group' : 'Add New Group');
        $('#group-edit-form')[0].reset();
        $('#group-original-id').val('');
        $('#group-edit-modal').show();
    }
    
    function loadFieldData(fieldKey) {
        // In a real implementation, you would fetch this data via AJAX
        // For now, we'll just open the modal
        openFieldModal(fieldKey);
        $('#field-original-key').val(fieldKey);
    }
    
    function loadGroupData(groupId) {
        // In a real implementation, you would fetch this data via AJAX
        // For now, we'll just open the modal
        openGroupModal(groupId);
        $('#group-original-id').val(groupId);
    }
    
    function deleteField(fieldKey) {
        $.post(vinDecoderAjax.ajaxurl, {
            action: 'delete_field',
            nonce: vinDecoderAjax.nonce,
            field_key: fieldKey
        }, function(response) {
            if (response.success) {
                $('.field-card[data-field-key="' + fieldKey + '"]').fadeOut(function() {
                    $(this).remove();
                });
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    }
    
    function deleteGroup(groupId) {
        $.post(vinDecoderAjax.ajaxurl, {
            action: 'delete_field_group',
            nonce: vinDecoderAjax.nonce,
            group_id: groupId
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    }
    
    // Make fields sortable
    if ($.fn.sortable) {
        $('.fields-grid').sortable({
            handle: '.field-card-header',
            update: function(event, ui) {
                // Update field positions
                const positions = [];
                $('.field-card').each(function(index) {
                    positions.push({
                        key: $(this).data('field-key'),
                        position: index
                    });
                });
                
                $.post(vinDecoderAjax.ajaxurl, {
                    action: 'update_field_positions',
                    nonce: vinDecoderAjax.nonce,
                    positions: positions
                });
            }
        });
    }
});

// AI Fill function (called from inline onclick)
function fillFieldWithAI(fieldName, postId, button) {
    const originalText = button.innerHTML;
    button.innerHTML = 'Searching...';
    button.disabled = true;
    button.className = button.className.replace('ai-fill-btn', 'ai-fill-btn loading');
    
    jQuery.post(vinDecoderAjax.ajaxurl, {
        action: 'fill_field_with_ai',
        field_name: fieldName,
        post_id: postId,
        nonce: vinDecoderAjax.nonce
    }, function(response) {
        if (response.success) {
            document.getElementById(fieldName).value = response.data.value;
            button.innerHTML = '✓ Found';
            button.className = button.className.replace('loading', 'success');
        } else {
            button.innerHTML = '✗ Failed';
            button.className = button.className.replace('loading', 'error');
        }
        
        setTimeout(function() {
            button.innerHTML = originalText;
            button.disabled = false;
            button.className = 'ai-fill-btn';
        }, 3000);
    }).fail(function() {
        button.innerHTML = '✗ Error';
        button.className = button.className.replace('loading', 'error');
        setTimeout(function() {
            button.innerHTML = originalText;
            button.disabled = false;
            button.className = 'ai-fill-btn';
        }, 3000);
    });
}
