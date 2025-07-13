/**
 * Admin JavaScript for Cost Calculator WordPress Plugin
 */

(function($) {
    'use strict';
    
    let annotationTypes = [];
    let statusFilter = 'all';
    let editingType = null;
    let faqItems = [];
    let faqStatusFilter = 'all';
    let editingFaq = null;
    
    $(document).ready(function() {
        initAdmin();
        bindEvents();
        loadAnnotationTypes();
        
        // Load FAQ items if we're on the FAQ page
        if ($('#faq-items-list').length > 0) {
            loadFaqItems();
            bindFaqEvents();
        }
        
        // Important Notes page initialization
        if ($('#save-notes-settings-page').length > 0) {
            loadImportantNotesPage();
            $('#save-notes-settings-page').on('click', function(e) {
                e.preventDefault();
                saveImportantNotes();
            });
        }
        
        // Quote Requests page initialization
        if ($('#quotes-table-body').length > 0) {
            initQuotesPage();
        }
    });
    
    function initAdmin() {
        console.log('Cost Calculator Admin initialized');
    }
    
    function bindEvents() {
        // Add new type button
        $('#add-new-type').on('click', function() {
            openEditModal(null);
        });
        
        // Modal close
        $('.modal-close, #modal-cancel').on('click', function() {
            closeEditModal();
        });
        
        // Modal save
        $('#modal-save').on('click', function() {
            saveAnnotationType();
        });
        
        // Search and filter for annotation types
        $('#search-types').on('input', function() {
            filterTypes();
        });
        
        // Status filter boxes click for annotation types
        $(document).on('click', '.calc-status-box', function() {
            $('.calc-status-box').removeClass('active');
            $(this).addClass('active');
            
            const filter = $(this).data('filter');
            statusFilter = filter;
            filterTypes();
        });
        
        // FAQ search and filter
        $('#search-faq').on('input', function() {
            filterFaqItems();
        });
        
        // FAQ status filter boxes click  
        $(document).on('click', '.calc-status-box', function() {
            if ($(this).closest('.admin-filters').find('#search-faq').length > 0) {
                // This is FAQ page
                $('.calc-status-box').removeClass('active');
                $(this).addClass('active');
                
                const filter = $(this).data('filter');
                faqStatusFilter = filter;
                filterFaqItems();
            }
        });
        
        // Close modal when clicking outside
        $('#edit-modal').on('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
        
        // Appearance controls
        $('input[name="header-style"]').on('change', function() {
            if ($(this).val() === 'gradient') {
                $('#gradient-controls').show();
            } else {
                $('#gradient-controls').hide();
            }
        });
        
        // Color picker changes
        $('#header-background, #notes-background, #gradient-start, #gradient-end').on('change', function() {
            updateAppearancePreview();
        });
        
        // Form submission
        $('#edit-form').on('submit', function(e) {
            e.preventDefault();
            saveAnnotationType();
        });
    }
    
    function loadAnnotationTypes() {
        $('#annotation-types-list').html('<div class="loading">Loading annotation types...</div>');
        
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'get_admin_annotation_types',
                nonce: costCalcAdmin.ajaxNonce
            },
            success: function(response) {
                if (response.success) {
                    annotationTypes = response.data;
                    renderAnnotationTypes();
                    updateStats();
                } else {
                    showMessage('error', 'Failed to load annotation types');
                    $('#annotation-types-list').html('<div class="error">Failed to load annotation types. Please refresh the page.</div>');
                }
            },
            error: function(xhr, status, error) {
                showMessage('error', 'Failed to load annotation types: ' + error);
                $('#annotation-types-list').html('<div class="error">Failed to load annotation types. Please refresh the page.</div>');
            }
        });
    }
    
    function renderAnnotationTypes() {
        const container = $('#annotation-types-list');
        
        if (annotationTypes.length === 0) {
            container.html('<div class="no-types">No annotation types found. Click "Add New Type" to create one.</div>');
            return;
        }
        
        const html = annotationTypes.map(type => renderAnnotationTypeCard(type)).join('');
        container.html(html);
        
        // Bind events for the rendered cards
        bindCardEvents();
    }
    
    function updateStats() {
        if (annotationTypes.length === 0) return;
        
        const total = annotationTypes.length;
        const active = annotationTypes.filter(t => t.is_active === true || t.is_active === 1).length;
        const inactive = total - active;
        
        // Update stats if stats elements exist
        $('#total-count').text(total);
        $('#active-count').text(active);
        $('#inactive-count').text(inactive);
        
        console.log(`Stats updated: ${total} total, ${active} active, ${inactive} inactive`);
    }
    
    function renderAnnotationTypeCard(type) {
        // Ensure boolean conversion for is_active
        const isActive = type.is_active === true || type.is_active === 1;
        const activeClass = isActive ? 'active' : 'inactive';
        const statusText = isActive ? 'Active' : 'Inactive';
        const altPricing = type.alt_rate ? ` or $${parseFloat(type.alt_rate).toFixed(2)} ${type.alt_unit}` : '';
        
        return `
            <div class="type-card ${activeClass}" data-type-id="${type.id}">
                <div class="type-card-header">
                    <div class="type-info">
                        <h3 class="type-name">${type.name}</h3>
                        <span class="type-status status-${activeClass}">${statusText}</span>
                    </div>
                    <div class="type-actions">
                        <button class="toggle-btn ${isActive ? 'active' : ''}" 
                                data-type-id="${type.id}" 
                                title="Toggle active status">
                            <span class="dashicons dashicons-lightbulb"></span>
                        </button>
                        <button class="edit-btn" data-type-id="${type.id}" title="Edit">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        ${!isActive ? `<button class="delete-btn" data-type-id="${type.id}" title="Delete (only available when inactive)">
                            <span class="dashicons dashicons-trash"></span>
                        </button>` : ''}
                    </div>
                </div>
                <div class="type-content">
                    <p class="type-description">${type.description || ''}</p>
                    <div class="type-pricing">
                        <strong>$${parseFloat(type.rate).toFixed(2)} ${type.unit}${altPricing}</strong>
                    </div>
                    ${type.language_tiers ? '<div class="type-feature">Language Tiers Enabled</div>' : ''}
                    ${(type.is_image_based === true || type.is_image_based === 1) ? '<div class="type-feature">Image-based</div>' : ''}
                </div>
            </div>
        `;
    }
    
    function bindCardEvents() {
        // Edit button
        $(document).off('click', '.edit-btn').on('click', '.edit-btn', function(e) {
            e.stopPropagation();
            const typeId = $(this).data('type-id');
            const type = annotationTypes.find(t => t.id === typeId);
            if (type) {
                openEditModal(type);
            }
        });
        
        // Toggle button - using AJAX instead of REST API
        $(document).off('click', '.toggle-btn').on('click', '.toggle-btn', function(e) {
            e.stopPropagation();
            const typeId = $(this).data('type-id');
            toggleAnnotationType(typeId, $(this));
        });
        
        // Delete button - using AJAX instead of REST API
        $(document).off('click', '.delete-btn').on('click', '.delete-btn', function(e) {
            e.stopPropagation();
            const typeId = $(this).data('type-id');
            if (confirm('Are you sure you want to delete this annotation type?')) {
                deleteAnnotationType(typeId);
            }
        });
    }
    
    function toggleAnnotationType(typeId, button) {
        const type = annotationTypes.find(t => t.id === typeId);
        if (!type) return;
        
        // Show loading state
        button.prop('disabled', true);
        
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'toggle_annotation_type',
                nonce: costCalcAdmin.ajaxNonce,
                type_id: typeId
            },
            success: function(response) {
                // Update the local data immediately for instant feedback
                const type = annotationTypes.find(t => t.id === typeId);
                if (type) {
                    type.is_active = response.new_status;
                }
                
                // Re-render the specific card for instant visual feedback
                const card = $(`.type-card[data-type-id="${typeId}"]`);
                const newCardHtml = renderAnnotationTypeCard(type);
                card.replaceWith(newCardHtml);
                
                // Re-bind events for the new card
                bindCardEvents();
                
                // Update stats counter
                updateStats();
                
                showMessage('success', response.message || 'Status updated successfully');
                
                // Full reload after a short delay to ensure complete consistency
                setTimeout(function() {
                    loadAnnotationTypes();
                }, 500);
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Failed to toggle annotation type status.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showMessage('error', errorMessage);
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    }
    
    function deleteAnnotationType(typeId) {
        const type = annotationTypes.find(t => t.id === typeId);
        if (!type) return;
        
        if (!confirm(`Are you sure you want to delete "${type.name}"? This action cannot be undone.`)) {
            return;
        }
        
        // Show loading indicator
        showMessage('info', 'Deleting annotation type...');
        
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'delete_annotation_type',
                nonce: costCalcAdmin.ajaxNonce,
                type_id: typeId
            },
            success: function(response) {
                showMessage('success', 'Annotation type deleted successfully!');
                // Force reload of annotation types
                setTimeout(function() {
                    loadAnnotationTypes();
                }, 100);
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Failed to delete annotation type.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showMessage('error', errorMessage);
            }
        });
    }
    
    function openEditModal(type) {
        editingType = type;
        
        if (type) {
            // Edit existing type
            $('#modal-title').text('Edit Annotation Type');
            $('#edit-id').val(type.id);
            $('#edit-name').val(type.name);
            $('#edit-description').val(type.description);
            $('#edit-rate').val(type.rate);
            $('#edit-unit').val(type.unit);
            $('#edit-alt-rate').val(type.alt_rate || '');
            $('#edit-alt-unit').val(type.alt_unit || '');
            $('#edit-image-based').prop('checked', type.is_image_based);
            $('#edit-active').prop('checked', type.is_active);
        } else {
            // Add new type
            $('#modal-title').text('Add New Annotation Type');
            $('#edit-form')[0].reset();
            $('#edit-id').val('');
            $('#edit-active').prop('checked', true); // Default to active
        }
        
        $('#edit-modal').show();
    }
    
    function closeEditModal() {
        $('#edit-modal').hide();
        $('#edit-form')[0].reset();
        editingType = null;
    }
    
    function saveAnnotationType() {
        const formData = {
            id: $('#edit-id').val() || generateId($('#edit-name').val()),
            name: $('#edit-name').val(),
            description: $('#edit-description').val(),
            rate: parseFloat($('#edit-rate').val()),
            unit: $('#edit-unit').val(),
            alt_rate: $('#edit-alt-rate').val(),
            alt_unit: $('#edit-alt-unit').val(),
            is_image_based: $('#edit-image-based').is(':checked') ? 'true' : 'false',
            is_active: $('#edit-active').is(':checked') ? 'true' : 'false'
        };
        
        // Validate required fields
        if (!formData.name || !formData.rate || !formData.unit) {
            showMessage('error', 'Please fill in all required fields');
            return;
        }
        
        // Disable save button
        $('#modal-save').prop('disabled', true).text('Saving...');
        
        // Use AJAX endpoint for saving annotation types
        const ajaxData = {
            action: 'save_annotation_type',
            nonce: costCalcAdmin.ajaxNonce,
            ...formData
        };
        
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: ajaxData,
            success: function(response) {
                if (response.success) {
                    showMessage('success', editingType ? 'Annotation type updated successfully!' : 'Annotation type created successfully!');
                    closeEditModal();
                    // Force refresh after save
                    setTimeout(function() {
                        loadAnnotationTypes();
                    }, 200);
                } else {
                    showMessage('error', response.data || 'Failed to save annotation type');
                }
            },
            error: function(xhr, status, error) {
                console.error('Save error details:', {xhr, status, error});
                let errorMessage = 'Failed to save annotation type.';
                if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                } else if (xhr.responseText) {
                    errorMessage = 'Error: ' + xhr.responseText;
                }
                showMessage('error', errorMessage);
            },
            complete: function() {
                $('#modal-save').prop('disabled', false).text('Save Changes');
            }
        });
    }
    
    function generateId(name) {
        return name.toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
    }
    
    function updateStats() {
        const total = annotationTypes.length;
        const active = annotationTypes.filter(t => t.is_active === true || t.is_active === 1).length;
        const inactive = total - active;
        
        // Update status counts
        $('#total-count').text(total);
        $('#active-count').text(active);
        $('#inactive-count').text(inactive);
        
        console.log('Stats updated - Total:', total, 'Active:', active, 'Inactive:', inactive);
    }
    
    function filterTypes() {
        const searchTerm = $('#search-types').val().toLowerCase();
        
        let filteredTypes = [...annotationTypes]; // Create a copy
        
        // Filter by search term
        if (searchTerm) {
            filteredTypes = filteredTypes.filter(type => 
                type.name.toLowerCase().includes(searchTerm) || 
                type.description.toLowerCase().includes(searchTerm)
            );
        }
        
        // Filter by status
        if (statusFilter === 'active') {
            filteredTypes = filteredTypes.filter(type => type.is_active === true || type.is_active === 1);
        } else if (statusFilter === 'inactive') {
            filteredTypes = filteredTypes.filter(type => type.is_active === false || type.is_active === 0);
        }
        
        // Render filtered types
        const container = $('#annotation-types-list');
        if (filteredTypes.length === 0) {
            container.html('<div class="no-types">No annotation types match your search criteria.</div>');
        } else {
            const html = filteredTypes.map(type => renderAnnotationTypeCard(type)).join('');
            container.html(html);
            bindCardEvents();
        }
        
        console.log('Filtered types:', filteredTypes.length, 'Status filter:', statusFilter);
    }
    
    function showMessage(type, message) {
        const messageClass = type === 'success' ? 'notice-success' : 'notice-error';
        const messageHtml = `
            <div class="notice ${messageClass} is-dismissible">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `;
        
        $('#admin-messages').html(messageHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $('#admin-messages .notice').fadeOut();
        }, 5000);
        
        // Handle dismiss button
        $('#admin-messages .notice-dismiss').on('click', function() {
            $(this).closest('.notice').fadeOut();
        });
    }
    
    // FAQ Management Functions
    
    function loadFaqItems() {
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'get_admin_faq_items',
                nonce: costCalcAdmin.ajaxNonce
            },
            success: function(response) {
                if (response.success) {
                    faqItems = response.data;
                    renderFaqItems();
                    updateFaqStats();
                } else {
                    showMessage('error', response.data || 'Failed to load FAQ items');
                }
            },
            error: function(xhr, status, error) {
                showMessage('error', 'Failed to load FAQ items: ' + error);
            }
        });
    }
    
    function renderFaqItems() {
        const container = $('#faq-items-list');
        if (faqItems.length === 0) {
            container.html('<div class="no-types">No FAQ items found.</div>');
            return;
        }
        
        // Group items by category
        const categories = {};
        faqItems.forEach(item => {
            const category = item.category || 'General';
            if (!categories[category]) {
                categories[category] = [];
            }
            categories[category].push(item);
        });
        
        // Sort items within each category by order_num
        Object.keys(categories).forEach(category => {
            categories[category].sort((a, b) => (a.order_num || 0) - (b.order_num || 0));
        });
        
        // Render grouped FAQ items
        let html = '';
        Object.keys(categories).sort().forEach(category => {
            html += `
                <div class="faq-category-group" data-category="${category}">
                    <div class="faq-category-header">
                        <h4 class="faq-category-title">${category}</h4>
                        <span class="faq-category-count">${categories[category].length} items</span>
                    </div>
                    <div class="faq-category-items" data-category="${category}">
                        ${categories[category].map(item => renderFaqCard(item)).join('')}
                    </div>
                </div>
            `;
        });
        
        container.html(html);
        bindFaqCardEvents();
    }
    
    function renderFaqCard(item) {
        const isActive = item.is_active === true || item.is_active === 1;
        const activeClass = isActive ? 'active' : 'inactive';
        const statusText = isActive ? 'Active' : 'Inactive';
        
        return `
            <div class="faq-item-card ${activeClass}" data-faq-id="${item.id}" data-order="${item.order_num || 0}">
                <div class="faq-drag-handle">
                    <span class="dashicons dashicons-menu"></span>
                </div>
                <div class="faq-toggle-status" data-id="${item.id}" title="${isActive ? 'Click to deactivate' : 'Click to activate'}">
                    <span class="dashicons dashicons-lightbulb" style="color: ${isActive ? '#f39c12' : '#95a5a6'};"></span>
                </div>
                <div class="faq-item-content">
                    <div class="faq-item-question">${item.question}</div>
                    <div class="faq-item-answer">${item.answer}</div>
                    <div class="faq-item-meta">
                        Category: ${item.category} | Order: ${item.order_num || 0} | Status: ${statusText}
                    </div>
                </div>
                <div class="faq-item-actions">
                    <button class="button button-small edit-faq" data-id="${item.id}">Edit</button>
                    ${!isActive ? `<button class="button button-small button-link-delete delete-faq" data-id="${item.id}">Delete</button>` : ''}
                </div>
            </div>`;
    }
    
    function bindFaqEvents() {
        $('#add-new-faq').on('click', function() {
            openFaqModal(null);
        });
        
        // Status filter box clicks for FAQ items (matching annotation types behavior)
        $(document).on('click', '.calc-status-box', function() {
            $('.calc-status-box').removeClass('active');
            $(this).addClass('active');
            
            const filter = $(this).data('filter');
            faqStatusFilter = filter;
            filterFaqItems();
        });
        
        $('#faq-category-filter').on('change', filterFaqItems);
        $('#search-faq').on('input', filterFaqItems);
        
        $(document).on('click', '#faq-modal-save', function() {
            saveFaqItem();
        });
        $(document).on('click', '#faq-modal-cancel, .modal-close', function() {
            closeFaqModal();
        });
    }
    
    function bindFaqCardEvents() {
        // Make FAQ items sortable with drag & drop between categories
        $('.faq-category-items').sortable({
            handle: '.faq-drag-handle',
            placeholder: 'faq-sort-placeholder',
            connectWith: '.faq-category-items',
            tolerance: 'pointer',
            cursor: 'move',
            update: function(event, ui) {
                updateFaqOrder();
            },
            receive: function(event, ui) {
                // When item is moved to a new category, update its category
                const newCategory = $(this).data('category');
                const faqId = ui.item.data('faq-id');
                updateFaqCategory(faqId, newCategory);
            }
        }).disableSelection();
        
        // Edit FAQ
        $(document).off('click', '.edit-faq').on('click', '.edit-faq', function(e) {
            e.stopPropagation();
            const faqId = $(this).data('id');
            const faq = faqItems.find(f => f.id === faqId);
            if (faq) {
                openFaqModal(faq);
            }
        });
        
        // Toggle FAQ status
        $(document).off('click', '.faq-toggle-status').on('click', '.faq-toggle-status', function(e) {
            e.stopPropagation();
            const faqId = $(this).data('id');
            toggleFaqItem(faqId, $(this));
        });
        
        // Delete FAQ
        $(document).off('click', '.delete-faq').on('click', '.delete-faq', function(e) {
            e.stopPropagation();
            const faqId = $(this).data('id');
            deleteFaqItem(faqId);
        });
    }
    
    function updateFaqOrder() {
        const newOrder = [];
        $('.faq-category-items').each(function() {
            const category = $(this).data('category');
            $(this).find('.faq-item-card').each(function(index) {
                const faqId = $(this).data('faq-id');
                newOrder.push({
                    id: faqId,
                    order: index,
                    category: category
                });
            });
        });
        
        // Update order via AJAX
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'reorder_faq_items',
                nonce: costCalcAdmin.ajaxNonce,
                items: JSON.stringify(newOrder)
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', 'FAQ order updated successfully');
                    // Update local data
                    newOrder.forEach(item => {
                        const faq = faqItems.find(f => f.id === item.id);
                        if (faq) {
                            faq.order_num = item.order;
                            faq.category = item.category;
                        }
                    });
                } else {
                    showMessage('error', response.data || 'Failed to update FAQ order');
                }
            },
            error: function(xhr, status, error) {
                showMessage('error', 'Failed to update FAQ order');
                loadFaqItems(); // Reload to reset order
            }
        });
    }
    
    function updateFaqCategory(faqId, newCategory) {
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'update_faq_category',
                nonce: costCalcAdmin.ajaxNonce,
                id: faqId,
                category: newCategory
            },
            success: function(response) {
                if (response.success) {
                    // Update local data
                    const faq = faqItems.find(f => f.id === faqId);
                    if (faq) {
                        faq.category = newCategory;
                    }
                    showMessage('success', 'FAQ item moved to ' + newCategory);
                } else {
                    showMessage('error', response.data || 'Failed to update FAQ category');
                    // Reload to reset positions
                    loadFaqItems();
                }
            },
            error: function(xhr, status, error) {
                showMessage('error', 'Failed to update FAQ category');
                // Reload to reset positions
                loadFaqItems();
            }
        });
    }
    
    function filterFaqItems() {
        const searchTerm = $('#search-faq').val().toLowerCase();
        const statusFilter = faqStatusFilter || 'all';
        const categoryFilter = $('#faq-category-filter').val();
        
        let filteredItems = [...faqItems];
        
        if (searchTerm) {
            filteredItems = filteredItems.filter(item => 
                item.question.toLowerCase().includes(searchTerm) || 
                item.answer.toLowerCase().includes(searchTerm)
            );
        }
        
        if (statusFilter === 'active') {
            filteredItems = filteredItems.filter(item => item.is_active === true || item.is_active === 1);
        } else if (statusFilter === 'inactive') {
            filteredItems = filteredItems.filter(item => item.is_active === false || item.is_active === 0);
        }
        
        if (categoryFilter !== 'all') {
            filteredItems = filteredItems.filter(item => item.category === categoryFilter);
        }
        
        // When filtering, use the grouped display
        if (filteredItems.length === 0) {
            $('#faq-items-list').html('<div class="no-types">No FAQ items match your criteria.</div>');
        } else {
            // Store filtered items temporarily and re-render
            const originalItems = faqItems;
            faqItems = filteredItems;
            renderFaqItems();
            faqItems = originalItems; // Restore original for subsequent operations
        }
    }
    
    function updateFaqStats() {
        const total = faqItems.length;
        const active = faqItems.filter(f => f.is_active === true || f.is_active === 1).length;
        const inactive = total - active;
        
        // Update status counts
        $('#faq-total-count').text(total);
        $('#faq-active-count').text(active);
        $('#faq-inactive-count').text(inactive);
    }
    
    function openFaqModal(faq) {
        editingFaq = faq;
        
        if (faq) {
            $('#faq-modal-title').text('Edit FAQ Item');
            $('#faq-edit-id').val(faq.id);
            $('#faq-edit-question').val(faq.question);
            $('#faq-edit-answer').val(faq.answer);
            $('#faq-edit-category').val(faq.category);
            $('#faq-edit-order').val(faq.order_num || 0);
            $('#faq-edit-active').prop('checked', faq.is_active === true || faq.is_active === 1);
        } else {
            $('#faq-modal-title').text('Add New FAQ Item');
            $('#faq-edit-form')[0].reset();
            $('#faq-edit-id').val('');
            $('#faq-edit-active').prop('checked', true);
        }
        
        $('#faq-edit-modal').show();
    }
    
    function closeFaqModal() {
        $('#faq-edit-modal').hide();
        $('#faq-edit-form')[0].reset();
        editingFaq = null;
    }
    
    function saveFaqItem() {
        const question = $('#faq-edit-question').val().trim();
        const answer = $('#faq-edit-answer').val().trim();
        
        if (!question || !answer) {
            showMessage('error', 'Question and answer are required');
            return;
        }
        
        let id = $('#faq-edit-id').val();
        if (!id) {
            id = question.toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
        }
        
        const data = {
            id: id,
            question: question,
            answer: answer,
            category: $('#faq-edit-category').val(),
            order: parseInt($('#faq-edit-order').val()) || 0,
            isActive: $('#faq-edit-active').prop('checked') ? 'true' : 'false'
        };
        
        // Use AJAX endpoint for saving FAQ items
        const ajaxData = {
            action: 'calc_admin_save_faq_item',
            nonce: costCalcAdmin.ajaxNonce,
            ...data
        };
        
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: ajaxData,
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data);
                    closeFaqModal();
                    loadFaqItems();
                } else {
                    showMessage('error', response.data || 'Failed to save FAQ item');
                }
            },
            error: function(xhr, status, error) {
                showMessage('error', 'Error saving FAQ item: ' + error);
            }
        });
    }
    
    function toggleFaqItem(faqId, button) {
        button.prop('disabled', true);
        
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'calc_admin_toggle_faq_item',
                nonce: costCalcAdmin.ajaxNonce,
                id: faqId
            },
            success: function(response) {
                showMessage('success', response.message || 'FAQ status updated successfully');
                // Force reload to ensure consistency
                setTimeout(function() {
                    loadFaqItems();
                }, 100);
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Failed to toggle FAQ status.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showMessage('error', errorMessage);
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    }
    
    function deleteFaqItem(faqId) {
        const faq = faqItems.find(f => f.id === faqId);
        if (!faq) return;
        
        if (!confirm(`Are you sure you want to delete FAQ: "${faq.question}"? This action cannot be undone.`)) {
            return;
        }
        
        // Show loading indicator
        showMessage('info', 'Deleting FAQ item...');
        
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'calc_admin_delete_faq',
                nonce: costCalcAdmin.ajaxNonce,
                id: faqId
            },
            success: function(response) {
                showMessage('success', 'FAQ item deleted successfully!');
                // Force reload to ensure consistency
                setTimeout(function() {
                    loadFaqItems();
                }, 100);
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Failed to delete FAQ item.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showMessage('error', errorMessage);
            }
        });
    }
    
    // Settings Functions
    function loadSettings() {
        // Load contact settings via AJAX
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'get_contact_settings',
                nonce: costCalcAdmin.ajaxNonce
            },
            success: function(data) {
                $('#contact-title').val(data.title || '');
                $('#contact-description').val(data.description || '');
                $('#contact-button-text').val(data.buttonText || '');
                $('#contact-button-url').val(data.buttonUrl || '');
                $('#show-contact').prop('checked', data.contactEnabled !== false);
            },
            error: function(xhr, status, error) {
                showMessage('error', 'Failed to load contact settings: ' + error);
            }
        });
        
        // Load site settings via AJAX
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'get_site_settings',
                nonce: costCalcAdmin.ajaxNonce
            },
            success: function(data) {
                $('#calculator-title').val(data.calculatorTitle || '');
                $('#calculator-description').val(data.calculatorDescription || '');
                $('#show-calculator-header').prop('checked', data.showCalculatorHeader !== false);
            },
            error: function(xhr, status, error) {
                showMessage('error', 'Failed to load site settings: ' + error);
            }
        });

        // Load important notes via AJAX
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'get_important_notes',
                nonce: costCalcAdmin.ajaxNonce
            },
            success: function(data) {
                $('#notes-title').val(data.title || '');
                $('#notes-content').val(data.content || '');
                $('#show-notes').prop('checked', data.enabled !== false);
            },
            error: function(xhr, status, error) {
                showMessage('error', 'Failed to load important notes: ' + error);
            }
        });
    }
    
    function bindSettingsEvents() {
        $('#settings-form').on('submit', function(e) {
            e.preventDefault();
            saveSettings();
        });
    }
    
    function saveSettings() {
        // Save contact settings via AJAX
        const contactData = {
            action: 'save_contact_settings',
            nonce: costCalcAdmin.ajaxNonce,
            title: $('#contact-title').val(),
            description: $('#contact-description').val(),
            buttonText: $('#contact-button-text').val(),
            buttonUrl: $('#contact-button-url').val(),
            contactEnabled: $('#show-contact').is(':checked')
        };
        
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: contactData,
            success: function(response) {
                showMessage('success', 'Contact settings saved successfully!');
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Failed to save contact settings.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showMessage('error', errorMessage);
            }
        });
        
        // Save site settings via AJAX
        const siteData = {
            action: 'save_site_settings',
            nonce: costCalcAdmin.ajaxNonce,
            calculatorTitle: $('#calculator-title').val(),
            calculatorDescription: $('#calculator-description').val(),
            showCalculatorHeader: $('#show-calculator-header').is(':checked')
        };
        
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: siteData,
            success: function(response) {
                showMessage('success', 'Site settings saved successfully!');
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Failed to save site settings.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showMessage('error', errorMessage);
            }
        });

        // Save important notes via AJAX
        const notesData = {
            action: 'save_important_notes',
            nonce: costCalcAdmin.ajaxNonce,
            title: $('#notes-title').val(),
            content: $('#notes-content').val(),
            enabled: $('#show-notes').is(':checked')
        };
        
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: notesData,
            success: function(response) {
                showMessage('success', 'Important notes saved successfully!');
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Failed to save important notes.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showMessage('error', errorMessage);
            }
        });
    }
    
    // Important Notes Page Functions
    function loadImportantNotesPage() {
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'get_important_notes',
                nonce: costCalcAdmin.ajaxNonce
            },
            success: function(data) {
                $('#show-notes').prop('checked', data.enabled !== false);  // Fixed ID to match HTML
                $('#notes-title').val(data.title || 'Important Notes');
                
                // Set content in TinyMCE editor if available, otherwise in textarea
                const content = data.content || '';
                if (typeof tinyMCE !== 'undefined' && tinyMCE.get('notes-content')) {
                    tinyMCE.get('notes-content').setContent(content);
                } else {
                    $('#notes-content').val(content);
                }
            },
            error: function(xhr, status, error) {
                showMessage('error', 'Failed to load important notes: ' + error);
            }
        });
    }
    
    function saveImportantNotes() {
        // Get content from TinyMCE editor if available, otherwise from textarea
        let content = '';
        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('notes-content')) {
            content = tinyMCE.get('notes-content').getContent();
        } else {
            content = $('#notes-content').val() || '';
        }
        
        const notesData = {
            enabled: $('#show-notes').is(':checked'),  // Fixed ID to match HTML
            title: $('#notes-title').val() || 'Important Notes',
            content: content
        };
        
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'save_important_notes',
                nonce: costCalcAdmin.ajaxNonce,
                ...notesData
            },
            success: function(response) {
                showMessage('success', 'Important notes saved successfully!');
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Failed to save important notes.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showMessage('error', errorMessage);
            }
        });
    }
    
    // Contact Settings Page Functions
    function loadContactPage() {
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'get_contact_settings',
                nonce: costCalcAdmin.ajaxNonce
            },
            success: function(data) {
                $('#contact-enabled').prop('checked', data.contactEnabled !== false);
                $('#contact-title').val(data.title || 'Get Your Custom Quote');
                $('#contact-description').val(data.description || '');
                $('#contact-button-text').val(data.buttonText || 'Schedule Consultation');
                $('#contact-button-url').val(data.buttonUrl || 'https://calendly.com/your-link');
            },
            error: function(xhr, status, error) {
                showMessage('error', 'Failed to load contact settings: ' + error);
            }
        });
    }
    
    function saveContactSettings() {
        const contactData = {
            contactEnabled: $('#contact-enabled').is(':checked'),
            title: $('#contact-title').val() || 'Get Your Custom Quote',
            description: $('#contact-description').val() || '',
            buttonText: $('#contact-button-text').val() || 'Schedule Consultation',
            buttonUrl: $('#contact-button-url').val() || 'https://calendly.com/your-link'
        };
        
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'save_contact_settings',
                nonce: costCalcAdmin.ajaxNonce,
                ...contactData
            },
            success: function(response) {
                showMessage('success', 'Contact settings saved successfully!');
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Failed to save contact settings.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showMessage('error', errorMessage);
            }
        });
    }
    
    function resetImportantNotes() {
        $('#notes-enabled').prop('checked', true);
        $('#notes-title').val('Important Notes');
        $('#notes-content').val('<ul><li><strong>Data Access:</strong> Clients provide annotation platform access or DeeLab suggests/sets up platforms as separate project</li><li><strong>Project Scope:</strong> Pricing covers annotation work only, platform setup quoted separately if needed</li><li><strong>Data Requirements:</strong> Clients provide data via cloud folder with access rights or connect cloud to annotation platform</li></ul>');
        showMessage('info', 'Important notes reset to default values');
    }
    
    // Site Settings Page Functions
    function loadSiteSettingsPage() {
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'get_site_settings',
                nonce: costCalcAdmin.ajaxNonce
            },
            success: function(data) {
                $('#site-title').val(data.title || 'DeeLab Annotation Cost Calculator');
                $('#site-description').val(data.description || '');
                $('#notification-emails').val(data.notificationEmails || '');
                $('#theme-setting').val(data.theme || 'light');
                $('#enable-animations').prop('checked', data.enableAnimations !== false);
                $('#title-alignment').val(data.titleAlignment || 'center');
                $('#show-calculator-header').prop('checked', data.showCalculatorHeader !== false);
            },
            error: function(xhr, status, error) {
                showMessage('error', 'Failed to load site settings: ' + error);
            }
        });
    }
    
    function saveSiteSettingsPage() {
        const siteData = {
            title: $('#site-title').val() || 'DeeLab Annotation Cost Calculator',
            description: $('#site-description').val() || '',
            notificationEmails: $('#notification-emails').val() || '',
            theme: $('#theme-setting').val() || 'light',
            enableAnimations: $('#enable-animations').is(':checked'),
            titleAlignment: $('#title-alignment').val() || 'center',
            showCalculatorHeader: $('#show-calculator-header').is(':checked')
        };
        
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'save_site_settings',
                nonce: costCalcAdmin.ajaxNonce,
                ...siteData
            },
            success: function(response) {
                showMessage('success', 'Site settings saved successfully!');
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Failed to save site settings.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showMessage('error', errorMessage);
            }
        });
    }
    
    function resetSiteSettingsPage() {
        $('#site-title').val('DeeLab Annotation Cost Calculator');
        $('#site-description').val('Calculate the cost of your annotation project with our transparent pricing tool. Get instant estimates for various annotation types and request a detailed quote.');
        $('#notification-emails').val('admin@yoursite.com');
        $('#theme-setting').val('light');
        $('#enable-animations').prop('checked', true);
        $('#title-alignment').val('center');
        $('#show-calculator-header').prop('checked', true);
        showMessage('info', 'Site settings reset to default values');
    }
    
    // Appearance Page Functions
    function loadAppearancePage() {
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'get_appearance_settings',
                nonce: costCalcAdmin.ajaxNonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    $('#primary-color').val(data.primary_color || '#2563eb');
                    $('#secondary-color').val(data.secondary_color || '#10b981');
                    $('#background-color').val(data.background_color || '#f8fafc');
                    $('#text-color').val(data.text_color || '#1f2937');
                    $('#border-radius').val(data.border_radius || 8);
                    $('#font-size').val(data.font_size || 16);
                    $('#spacing').val(data.spacing || 16);
                    $('#shadow-enabled').prop('checked', data.shadow_enabled !== false);
                    $('#animations-enabled').prop('checked', data.animations_enabled !== false);
                    $('#custom-css').val(data.custom_css || '');
                    updatePreview();
                } else {
                    showMessage('error', 'Failed to load appearance settings');
                }
            },
            error: function(xhr, status, error) {
                showMessage('error', 'Failed to load appearance settings: ' + error);
            }
        });
    }
    
    function saveAppearanceSettings() {
        const settings = {
            action: 'save_appearance_settings',
            nonce: costCalcAdmin.ajaxNonce,
            primary_color: $('#primary-color').val(),
            secondary_color: $('#secondary-color').val(),
            background_color: $('#background-color').val(),
            text_color: $('#text-color').val(),
            border_radius: $('#border-radius').val(),
            font_size: $('#font-size').val(),
            spacing: $('#spacing').val(),
            shadow_enabled: $('#shadow-enabled').is(':checked') ? 1 : 0,
            animations_enabled: $('#animations-enabled').is(':checked') ? 1 : 0,
            custom_css: $('#custom-css').val()
        };
        
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: settings,
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message || 'Appearance settings saved successfully!');
                    updatePreview();
                } else {
                    showMessage('error', 'Failed to save appearance settings');
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Failed to save appearance settings: ' + error;
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showMessage('error', errorMessage);
            }
        });
    }
    
    function resetAppearanceSettings() {
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'reset_appearance_settings',
                nonce: costCalcAdmin.ajaxNonce
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data.settings;
                    $('#primary-color').val(data.primary_color);
                    $('#secondary-color').val(data.secondary_color);
                    $('#background-color').val(data.background_color);
                    $('#text-color').val(data.text_color);
                    $('#border-radius').val(data.border_radius);
                    $('#font-size').val(data.font_size);
                    $('#spacing').val(data.spacing);
                    $('#shadow-enabled').prop('checked', data.shadow_enabled);
                    $('#animations-enabled').prop('checked', data.animations_enabled);
                    $('#custom-css').val(data.custom_css);
                    updatePreview();
                    showMessage('success', response.data.message || 'Appearance settings reset to default values');
                } else {
                    showMessage('error', 'Failed to reset appearance settings');
                }
            },
            error: function(xhr, status, error) {
                showMessage('error', 'Failed to reset appearance settings: ' + error);
            }
        });
    }
    
    function updatePreview() {
        // Create preview URL with current settings
        var previewUrl = window.location.origin + '/?annotation_calculator_preview=1';
        $('#calculator-preview').attr('src', previewUrl);
    }
    
    function resetContactSettings() {
        $('#contact-title').val('Get Your Custom Quote');
        $('#contact-description').val('Ready to start your annotation project? Schedule a consultation to discuss your specific requirements and get a detailed quote.');
        $('#contact-button-text').val('Schedule Consultation');
        $('#contact-button-url').val('https://calendly.com/your-link');
        showMessage('info', 'Contact settings reset to default values');
    }
    
    function initNotesEditor() {
        // Rich text editor toolbar functionality
        $('.calc-editor-btn').on('click', function(e) {
            e.preventDefault();
            const action = $(this).data('action');
            const tag = $(this).data('tag');
            const textarea = $('#notes-content');
            
            if (action === 'preview') {
                toggleNotesPreview();
            } else if (action === 'ul') {
                insertListAtCursor(textarea[0]);
            } else if (action === 'link') {
                insertLinkAtCursor(textarea[0]);
            } else if (tag) {
                wrapSelectionWithTag(textarea[0], tag);
            }
            
            updateLivePreview();
        });
        
        // Live preview updates
        $('#notes-content, #notes-title').on('input', function() {
            updateLivePreview();
        });
        
        $('#notes-enabled').on('change', function() {
            updateLivePreview();
        });
        
        // Initial preview update
        setTimeout(updateLivePreview, 100);
    }
    
    function toggleNotesPreview() {
        const preview = $('#notes-preview');
        const textarea = $('#notes-content');
        
        if (preview.is(':visible')) {
            preview.hide();
            textarea.show();
            $('.calc-editor-btn[data-action="preview"] .dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
        } else {
            updateNotesPreview();
            textarea.hide();
            preview.show();
            $('.calc-editor-btn[data-action="preview"] .dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
        }
    }
    
    function updateNotesPreview() {
        const content = $('#notes-content').val();
        $('#notes-preview .calc-preview-content').html(content || '<p><em>No content to preview</em></p>');
    }
    
    function updateLivePreview() {
        const title = $('#notes-title').val() || 'Important Notes';
        const content = $('#notes-content').val() || '<p>Configure your important notes content above to see the preview.</p>';
        const enabled = $('#notes-enabled').is(':checked');
        
        $('#preview-title').text(title);
        $('#preview-content').html(content);
        
        if (enabled) {
            $('#notes-live-preview').removeClass('disabled');
        } else {
            $('#notes-live-preview').addClass('disabled');
        }
    }
    
    function wrapSelectionWithTag(textarea, tag) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selectedText = textarea.value.substring(start, end);
        const beforeText = textarea.value.substring(0, start);
        const afterText = textarea.value.substring(end);
        
        const wrappedText = selectedText ? `<${tag}>${selectedText}</${tag}>` : `<${tag}></${tag}>`;
        const newValue = beforeText + wrappedText + afterText;
        
        textarea.value = newValue;
        
        // Set cursor position
        const newCursorPos = selectedText ? end + tag.length * 2 + 5 : start + tag.length + 2;
        textarea.setSelectionRange(newCursorPos, newCursorPos);
        textarea.focus();
    }
    
    function insertListAtCursor(textarea) {
        const start = textarea.selectionStart;
        const beforeText = textarea.value.substring(0, start);
        const afterText = textarea.value.substring(start);
        
        const listHTML = '<ul>\n<li></li>\n<li></li>\n</ul>';
        const newValue = beforeText + listHTML + afterText;
        
        textarea.value = newValue;
        textarea.setSelectionRange(start + 9, start + 9); // Position cursor in first <li>
        textarea.focus();
    }
    
    function insertLinkAtCursor(textarea) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selectedText = textarea.value.substring(start, end);
        const beforeText = textarea.value.substring(0, start);
        const afterText = textarea.value.substring(end);
        
        const linkText = selectedText || 'Link Text';
        const linkHTML = `<a href="https://example.com">${linkText}</a>`;
        const newValue = beforeText + linkHTML + afterText;
        
        textarea.value = newValue;
        
        // Select the URL part for easy editing
        const urlStart = start + 9; // After '<a href="'
        const urlEnd = urlStart + 19; // Length of 'https://example.com'
        textarea.setSelectionRange(urlStart, urlEnd);
        textarea.focus();
    }

    // Initialize based on current page
    $(document).ready(function() {
        if ($('#annotation-types-list').length) {
            loadAnnotationTypes();
            bindEvents();
        } else if ($('#faq-items-list').length) {
            loadFaqItems();
            loadFaqSettings();
            bindFaqEvents();
            bindFaqSettingsEvents();
        } else if ($('#settings-form').length) {
            loadSettings();
            bindSettingsEvents();
        } else if ($('#notes-enabled').length) {
            // Important Notes admin page
            loadImportantNotesPage();
            initNotesEditor();
            $('#save-important-notes').on('click', function(e) {
                e.preventDefault();
                saveImportantNotes();
            });
            $('#reset-important-notes').on('click', function(e) {
                e.preventDefault();
                if (confirm('Reset to default important notes?')) {
                    resetImportantNotes();
                }
            });
        } else if ($('#contact-title').length && !$('#settings-form').length) {
            // Contact Settings admin page
            loadContactPage();
            bindContactSettingsEvents();
        } else if ($('#site-title').length) {
            // Site Settings admin page
            loadSiteSettingsPage();
            $('#save-site-settings').on('click', function(e) {
                e.preventDefault();
                saveSiteSettingsPage();
            });
            $('#reset-site-settings').on('click', function(e) {
                e.preventDefault();
                if (confirm('Reset to default site settings?')) {
                    resetSiteSettingsPage();
                }
            });
        } else if ($('#primary-color').length) {
            // Appearance admin page
            loadAppearancePage();
            $('#save-appearance').on('click', function(e) {
                e.preventDefault();
                saveAppearanceSettings();
            });
            $('#reset-appearance').on('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to reset all appearance settings to default?')) {
                    resetAppearanceSettings();
                }
            });
            $('#preview-appearance').on('click', function(e) {
                e.preventDefault();
                updatePreview();
            });
            
            // Update preview when settings change
            $('.color-controls input, .typography-controls select, .layout-controls select').on('change', function() {
                updatePreview();
            });
            
            $('#custom-css').on('input', function() {
                updatePreview();
            });
        }
    });
    
    // Load FAQ Settings
    function loadFaqSettings() {
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'get_faq_settings',
                nonce: costCalcAdmin.ajaxNonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    $('#faq-enabled').prop('checked', response.data.faqEnabled !== false);
                    $('#faq-title').val(response.data.faqTitle || 'Frequently Asked Questions');
                }
            },
            error: function(xhr, status, error) {
                console.log('Failed to load FAQ settings:', error);
            }
        });
    }
    
    // FAQ Settings Event Binding
    function bindFaqSettingsEvents() {
        $('#save-faq-settings').on('click', function(e) {
            e.preventDefault();
            saveFaqSettings();
        });
    }
    
    function saveFaqSettings() {
        const faqData = {
            faqEnabled: $('#faq-enabled').is(':checked'),
            faqTitle: $('#faq-title').val() || 'Frequently Asked Questions'
        };
        
        $.ajax({
            url: costCalcAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'save_faq_settings',
                nonce: costCalcAdmin.ajaxNonce,
                ...faqData
            },
            success: function(response) {
                showMessage('success', 'FAQ settings saved successfully!');
                $('#faq-settings-save-status').html('<span class="success">Settings saved!</span>');
                setTimeout(function() {
                    $('#faq-settings-save-status').html('');
                }, 3000);
            },
            error: function(xhr, status, error) {
                let errorMessage = 'Failed to save FAQ settings.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showMessage('error', errorMessage);
                $('#faq-settings-save-status').html('<span class="error">Failed to save settings</span>');
                setTimeout(function() {
                    $('#faq-settings-save-status').html('');
                }, 3000);
            }
        });
    }
    
    // Contact Settings Event Binding  
    function bindContactSettingsEvents() {
        $('#save-contact-settings').on('click', function(e) {
            e.preventDefault();
            saveContactSettings();
        });
        
        $('#reset-contact-settings').on('click', function(e) {
            e.preventDefault();
            if (confirm('Reset to default contact settings?')) {
                resetContactSettings();
            }
        });
    }
    
    // Quote Requests Page Functions
    function initQuotesPage() {
        console.log('Quote Requests page initialized');
        loadQuotes();
        bindQuoteEvents();
    }
    
    function bindQuoteEvents() {
        // Filter and search quotes
        $('#date-filter, #search-quotes').on('input change', function() {
            loadQuotes();
        });
        
        // Export CSV
        $('#export-quotes').on('click', function() {
            exportQuotesCSV();
        });
        
        // View quote details
        $(document).on('click', '.view-quote', function() {
            const quoteId = $(this).data('quote-id');
            showQuoteDetails(quoteId);
        });
        
        // Delete quote
        $(document).on('click', '.delete-quote', function() {
            const quoteId = $(this).data('quote-id');
            if (confirm('Are you sure you want to delete this quote request?')) {
                deleteQuote(quoteId);
            }
        });
        
        // Close quote details modal
        $('#close-quote-details, .modal-close').on('click', function() {
            $('#quote-details-modal').hide();
        });
        
        // Contact client
        $('#contact-client').on('click', function() {
            const email = $(this).data('email');
            if (email) {
                window.location.href = 'mailto:' + email;
            }
        });
    }
    
    function loadQuotes() {
        const dateFilter = $('#date-filter').val();
        const search = $('#search-quotes').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_quotes',
                nonce: costCalcAdmin.nonce,
                date_filter: dateFilter,
                search: search
            },
            beforeSend: function() {
                $('#quotes-table-body').html('<tr class="loading-row"><td colspan="7" class="loading-cell">Loading quotes...</td></tr>');
            },
            success: function(response) {
                if (response.success) {
                    renderQuotesTable(response.data.quotes);
                } else {
                    showMessage('Error loading quotes: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Network error loading quotes', 'error');
            }
        });
    }
    
    function renderQuotesTable(quotes) {
        const tbody = $('#quotes-table-body');
        tbody.empty();
        
        if (quotes.length === 0) {
            tbody.html('<tr><td colspan="7" style="text-align: center; padding: 2rem; color: #666;">No quote requests found.</td></tr>');
            return;
        }
        
        quotes.forEach(function(quote) {
            const selectedTypes = Array.isArray(quote.selected_types) 
                ? quote.selected_types.map(type => type.name).join(', ')
                : 'N/A';
                
            const row = `
                <tr>
                    <td>${formatDate(quote.created_at)}</td>
                    <td>${escapeHtml(quote.name)}</td>
                    <td><a href="mailto:${escapeHtml(quote.email)}">${escapeHtml(quote.email)}</a></td>
                    <td>${escapeHtml(quote.company || 'N/A')}</td>
                    <td class="quote-types-preview" title="${escapeHtml(selectedTypes)}">${escapeHtml(selectedTypes)}</td>
                    <td><strong>$${parseFloat(quote.total_cost).toFixed(2)}</strong></td>
                    <td class="quote-actions">
                        <button type="button" class="button view-quote" data-quote-id="${quote.id}">View</button>
                        <button type="button" class="button button-secondary delete-quote" data-quote-id="${quote.id}">Delete</button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }
    
    function showQuoteDetails(quoteId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_quote_details',
                nonce: costCalcAdmin.nonce,
                quote_id: quoteId
            },
            success: function(response) {
                if (response.success) {
                    renderQuoteDetailsModal(response.data);
                    $('#quote-details-modal').show();
                } else {
                    showMessage('Error loading quote details: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Network error loading quote details', 'error');
            }
        });
    }
    
    function renderQuoteDetailsModal(quote) {
        const selectedTypes = Array.isArray(quote.selected_types)
            ? quote.selected_types.map(type => `
                <div class="quote-type-item">
                    <strong>${escapeHtml(type.name)}</strong><br>
                    Quantity: ${type.quantity}<br>
                    Cost: $${parseFloat(type.cost).toFixed(2)}
                </div>
            `).join('')
            : '<p>No annotation types selected</p>';
            
        const content = `
            <div class="quote-details">
                <div class="quote-header">
                    <h4>Contact Information</h4>
                    <p><strong>Name:</strong> ${escapeHtml(quote.name)}</p>
                    <p><strong>Email:</strong> <a href="mailto:${escapeHtml(quote.email)}">${escapeHtml(quote.email)}</a></p>
                    <p><strong>Company:</strong> ${escapeHtml(quote.company || 'Not provided')}</p>
                    <p><strong>Phone:</strong> ${escapeHtml(quote.phone || 'Not provided')}</p>
                    <p><strong>Date:</strong> ${formatDate(quote.created_at)}</p>
                </div>
                
                <div class="quote-types">
                    <h4>Selected Annotation Types</h4>
                    ${selectedTypes}
                </div>
                
                <div class="quote-total">
                    <h4>Total Cost: $${parseFloat(quote.total_cost).toFixed(2)}</h4>
                </div>
                
                ${quote.message ? `
                    <div class="quote-message">
                        <h4>Message</h4>
                        <p>${escapeHtml(quote.message)}</p>
                    </div>
                ` : ''}
            </div>
        `;
        
        $('#quote-details-content').html(content);
        $('#contact-client').data('email', quote.email);
    }
    
    function deleteQuote(quoteId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_quote',
                nonce: costCalcAdmin.nonce,
                quote_id: quoteId
            },
            success: function(response) {
                if (response.success) {
                    showMessage('Quote deleted successfully', 'success');
                    loadQuotes(); // Reload the quotes table
                } else {
                    showMessage('Error deleting quote: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('Network error deleting quote', 'error');
            }
        });
    }
    
    function exportQuotesCSV() {
        // Create a form and submit to trigger CSV download
        const form = $('<form>', {
            'method': 'POST',
            'action': ajaxurl
        });
        
        form.append($('<input>', {
            'type': 'hidden',
            'name': 'action',
            'value': 'export_quotes_csv'
        }));
        
        form.append($('<input>', {
            'type': 'hidden',
            'name': 'nonce',
            'value': costCalcAdmin.nonce
        }));
        
        $('body').append(form);
        form.submit();
        form.remove();
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
})(jQuery);