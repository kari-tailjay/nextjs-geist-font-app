/**
 * Frontend JavaScript for Cost Calculator WordPress Plugin
 */

(function($) {
    'use strict';
    
    let annotationTypes = [];
    let selectedTypes = new Set();
    let quantities = {};
    let altQuantities = {};
    let imageCount = {};
    let avgPerImage = {};
    let videoDuration = {};
    let videoObjects = {};
    let complexityTiers = {};
    let languageTiers = {};
    
    // Make initCalculator globally available
    window.initCalculator = initCalculator;
    
    $(document).ready(function() {
        console.log('Frontend script loaded');
        console.log('costCalcConfig available:', typeof costCalcConfig !== 'undefined');
        
        // Auto-initialize if config is available
        if (typeof costCalcConfig !== 'undefined') {
            console.log('Auto-initializing calculator...');
            initCalculator();
        } else {
            console.log('Waiting for costCalcConfig...');
            // Wait for config to be available
            let attempts = 0;
            const checkConfig = setInterval(function() {
                attempts++;
                if (typeof costCalcConfig !== 'undefined') {
                    console.log('Config found, initializing calculator...');
                    clearInterval(checkConfig);
                    initCalculator();
                } else if (attempts > 50) { // 5 seconds max
                    console.error('Config not found after 5 seconds');
                    clearInterval(checkConfig);
                }
            }, 100);
        }
    });
    
    function initCalculator() {
        loadData();
        bindEvents();
    }
    
    function loadData() {
        Promise.all([
            loadAnnotationTypes(),
            loadImportantNotes(),
            loadContactSettings(),
            loadFaqItems()
        ]).then(() => {
            renderCalculator();
        }).catch((error) => {
            console.error('Failed to load calculator data:', error);
            $('.calc-loading').html('<div style="color: #dc2626; text-align: center; padding: 2rem;">Failed to load calculator. Please refresh the page.</div>');
        });
    }
    
    function bindEvents() {
        // Type selection
        $(document).on('click', '.type-card', function() {
            const typeId = $(this).data('type-id');
            toggleTypeSelection(typeId);
        });
        
        // Quantity controls
        $(document).on('click', '.quantity-btn', function() {
            const action = $(this).data('action');
            const typeId = $(this).data('type-id');
            const isAlt = $(this).data('alt') === true;
            
            if (action === 'increase') {
                updateQuantity(typeId, 1, isAlt);
            } else if (action === 'decrease') {
                updateQuantity(typeId, -1, isAlt);
            }
        });
        
        // Input changes
        $(document).on('change', '.calc-input', function() {
            const typeId = $(this).data('type-id');
            const field = $(this).data('field');
            const value = parseFloat($(this).val()) || 0;
            updateInputValue(typeId, field, value);
        });
        
        // Complexity changes
        $(document).on('change', 'input[name^="complexity"]', function() {
            const typeId = $(this).data('type-id');
            const value = $(this).val();
            complexityTiers[typeId] = value;
            updateCalculation();
        });
        
        // Language tier changes
        $(document).on('change', 'input[name^="language-tier"]', function() {
            const typeId = $(this).data('type-id');
            const value = $(this).val();
            languageTiers[typeId] = value;
            updateCalculation();
        });
        
        // FAQ toggles
        $(document).on('click', '.faq-question', function() {
            const faqItem = $(this).closest('.faq-item');
            const isOpen = faqItem.hasClass('open');
            
            $('.faq-item').removeClass('open');
            
            if (!isOpen) {
                faqItem.addClass('open');
            }
        });
        
        // Quote modal
        $(document).on('click', '.request-quote-btn', function() {
            openQuoteModal();
        });
        
        $(document).on('click', '.quote-modal-close', function() {
            closeQuoteModal();
        });
        
        $(document).on('submit', '#quote-form', function(e) {
            e.preventDefault();
            submitQuoteRequest();
        });
    }
    
    function loadAnnotationTypes() {
        // Check if costCalcConfig is available
        if (typeof costCalcConfig === 'undefined') {
            console.error('costCalcConfig is not defined');
            $('.calc-loading').html('<div style="color: #dc2626; text-align: center; padding: 2rem;">Error: Calculator configuration not loaded. Please refresh the page or contact the site administrator.</div>');
            return Promise.reject('Configuration not available');
        }
        
        return $.ajax({
            url: costCalcConfig.apiUrl + 'annotation-types?cache_bust=' + Date.now(),
            method: 'GET',
            beforeSend: function(xhr) {
                if (costCalcConfig.nonce) {
                    xhr.setRequestHeader('X-WP-Nonce', costCalcConfig.nonce);
                }
            },
            success: function(data) {
                // API already filters to active types only
                annotationTypes = Array.isArray(data) ? data : [];
                console.log('Loaded annotation types:', annotationTypes.length);
                
                if (annotationTypes.length === 0) {
                    console.warn('No annotation types found');
                    $('.calc-loading').html('<div style="color: #f59e0b; text-align: center; padding: 2rem;">No annotation types available. Please contact the administrator.</div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to load annotation types:', error, xhr.responseText);
                $('.calc-loading').html('<div style="color: #dc2626; text-align: center; padding: 2rem;">Error loading calculator data. Please refresh the page.</div>');
                annotationTypes = [];
            }
        });
    }
    
    function loadImportantNotes() {
        return $.ajax({
            url: costCalcConfig.apiUrl + 'important-notes',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', costCalcConfig.nonce);
            },
            success: function(data) {
                window.importantNotesData = data;
            },
            error: function() {
                window.importantNotesData = { show_notes: false };
            }
        });
    }
    
    function loadContactSettings() {
        return $.ajax({
            url: costCalcConfig.apiUrl + 'contact-settings',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', costCalcConfig.nonce);
            },
            success: function(data) {
                window.contactSettingsData = data;
            },
            error: function() {
                window.contactSettingsData = { showContactSection: false };
            }
        });
    }
    
    function loadFaqItems() {
        return $.ajax({
            url: costCalcConfig.apiUrl + 'faq',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', costCalcConfig.nonce);
            },
            success: function(data) {
                window.faqItemsData = data;
            },
            error: function() {
                window.faqItemsData = [];
            }
        });
    }
    
    function renderCalculator() {
        const container = $('.cost-calculator-container');
        
        // Load site settings to check if title/description should be shown
        loadSiteSettings().then(() => {
            const html = `
                ${renderCalculatorHeader()}
                
                ${renderImportantNotes()}
                
                <div class="calc-card">
                    ${renderTypeSelection()}
                    ${renderQuantityInputs()}
                    ${renderCostBreakdown()}
                    ${renderTotalCost()}
                </div>
                
                ${renderContactSection()}
                ${renderFaqSection()}
                
                ${renderQuoteModal()}
            `;
            
            container.html(html);
            updateCalculation();
        });
    }
    
    function loadSiteSettings() {
        return $.ajax({
            url: costCalcConfig.apiUrl + 'site-settings',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', costCalcConfig.nonce);
            },
            success: function(data) {
                window.siteSettingsData = data;
            },
            error: function() {
                window.siteSettingsData = { 
                    showCalculatorHeader: true, 
                    calculatorTitle: 'Cost Calculator',
                    calculatorDescription: 'Get instant pricing estimates for your data annotation projects.',
                    titleAlignment: 'center'
                };
            }
        });
    }
    
    function renderCalculatorHeader() {
        // Check if calculator header should be shown
        if (!window.siteSettingsData || 
            !window.siteSettingsData.showCalculatorHeader || 
            window.siteSettingsData.showCalculatorHeader === false || 
            window.siteSettingsData.showCalculatorHeader === 'false') {
            return '';
        }
        
        const alignment = window.siteSettingsData.titleAlignment || 'center';
        const alignmentStyle = alignment === 'left' ? 'text-align: left;' : 
                              alignment === 'right' ? 'text-align: right;' : 
                              'text-align: center;';
        
        return `
            <div class="calc-header" style="${alignmentStyle}">
                <h1>${window.siteSettingsData.calculatorTitle || 'Cost Calculator'}</h1>
                <p>${window.siteSettingsData.calculatorDescription || 'Get instant pricing estimates for your data annotation projects.'}</p>
            </div>
        `;
    }
    
    function renderImportantNotes() {
        // Check if Important Notes are enabled and data exists
        if (!window.importantNotesData || 
            !window.importantNotesData.enabled || 
            window.importantNotesData.enabled === false || 
            window.importantNotesData.enabled === 'false') {
            return '';
        }
        
        // Use official default content as fallback
        const defaultContent = '<p><strong>Platform Requirements:</strong> Clients provide annotation platform access or DeeLab can suggest/set up platforms as a separate project.</p><p><strong>Data Access:</strong> Clients provide data via cloud folder with access rights or connect cloud to annotation platform.</p>';
        
        return `
            <div class="calc-important-notes">
                <h3>${window.importantNotesData.title || 'Important Notes'}</h3>
                <div class="notes-content">${window.importantNotesData.content || defaultContent}</div>
            </div>
        `;
    }
    
    function renderTypeSelection() {
        return `
            <div class="calc-card-content">
                <div class="step-header">
                    <div class="step-number">1</div>
                    <h2 class="step-title">Select Annotation Types</h2>
                </div>
                
                <div class="annotation-types-grid">
                    ${annotationTypes.map(type => renderTypeCard(type)).join('')}
                </div>
            </div>
        `;
    }
    
    function renderTypeCard(type) {
        const isSelected = selectedTypes.has(type.id);
        const altPricing = type.alt_rate ? ` or $${type.alt_rate.toFixed(2)} ${type.alt_unit}` : '';
        
        return `
            <div class="type-card ${isSelected ? 'selected' : ''}" data-type-id="${type.id}">
                <div class="type-card-header">
                    <div class="type-checkbox">
                        ${isSelected ? '<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>' : ''}
                    </div>
                    <div class="type-name">${type.name}</div>
                </div>
                <div class="type-description">${type.description}</div>
                <div class="type-pricing">$${type.rate.toFixed(2)} ${type.unit}${altPricing}</div>
            </div>
        `;
    }
    
    function renderQuantityInputs() {
        if (selectedTypes.size === 0) return '';
        
        return `
            <div class="calc-card-content">
                <div class="step-header">
                    <div class="step-number">2</div>
                    <h2 class="step-title">Enter Quantities</h2>
                </div>
                
                <div class="quantity-sections">
                    ${Array.from(selectedTypes).map(typeId => {
                        const type = annotationTypes.find(t => t.id === typeId);
                        return type ? renderQuantitySection(type) : '';
                    }).join('')}
                </div>
            </div>
        `;
    }
    
    function renderQuantitySection(type) {
        if (type.is_image_based) {
            return renderImageBasedInputs(type);
        } else if (type.id === 'video') {
            return renderVideoInputs(type);
        } else if (type.id.startsWith('audio-')) {
            return renderAudioInputs(type);
        } else {
            return renderStandardInputs(type);
        }
    }
    
    function renderImageBasedInputs(type) {
        const images = imageCount[type.id] || 100;
        const avgPer = avgPerImage[type.id] || 1;
        const total = images * avgPer;
        
        return `
            <div class="quantity-section" data-type-id="${type.id}">
                <h4>${type.name}</h4>
                <div class="input-grid">
                    <div class="input-group">
                        <label>Number of Images</label>
                        <input type="number" class="calc-input" data-type-id="${type.id}" 
                               data-field="imageCount" value="${images}" min="1" step="1">
                    </div>
                    <div class="input-group">
                        <label>Average ${type.unit.replace('per ', '')}${type.unit.includes('per') ? 's' : ''} per Image</label>
                        <input type="number" class="calc-input" data-type-id="${type.id}" 
                               data-field="avgPerImage" value="${avgPer}" min="0.1" step="0.1">
                    </div>
                </div>
                <div class="quantity-total">Total ${type.unit.replace('per ', '')}${total !== 1 ? 's' : ''}: ${total.toLocaleString()}</div>
                ${renderComplexityTiers(type)}
            </div>
        `;
    }
    
    function renderVideoInputs(type) {
        const duration = videoDuration[type.id] || 5;
        const objects = videoObjects[type.id] || 2;
        const total = duration * objects;
        
        return `
            <div class="quantity-section" data-type-id="${type.id}">
                <h4>${type.name}</h4>
                <div class="input-grid">
                    <div class="input-group">
                        <label>Video Duration (minutes)</label>
                        <input type="number" class="calc-input" data-type-id="${type.id}" 
                               data-field="videoDuration" value="${duration}" min="0.1" step="0.1">
                    </div>
                    <div class="input-group">
                        <label>Objects to Track</label>
                        <input type="number" class="calc-input" data-type-id="${type.id}" 
                               data-field="videoObjects" value="${objects}" min="1" step="1">
                    </div>
                </div>
                <div class="quantity-total">Total object-minutes: ${total.toLocaleString()}</div>
                ${renderComplexityTiers(type)}
            </div>
        `;
    }
    
    function renderAudioInputs(type) {
        const currentQuantity = quantities[type.id] || 1;
        
        return `
            <div class="quantity-section" data-type-id="${type.id}">
                <h4>${type.name}</h4>
                <div class="input-group">
                    <label>Quantity (${type.unit})</label>
                    <div class="quantity-controls">
                        <button type="button" class="quantity-btn" data-action="decrease" data-type-id="${type.id}">-</button>
                        <div class="quantity-display">${currentQuantity}</div>
                        <button type="button" class="quantity-btn" data-action="increase" data-type-id="${type.id}">+</button>
                    </div>
                </div>
                ${type.language_tiers ? renderLanguageTiers(type) : ''}
                ${renderComplexityTiers(type)}
            </div>
        `;
    }
    
    function renderStandardInputs(type) {
        const currentQuantity = quantities[type.id] || 1;
        const currentAltQuantity = altQuantities[type.id] || 0;
        
        return `
            <div class="quantity-section" data-type-id="${type.id}">
                <h4>${type.name}</h4>
                <div class="input-group">
                    <label>Quantity (${type.unit})</label>
                    <div class="quantity-controls">
                        <button type="button" class="quantity-btn" data-action="decrease" data-type-id="${type.id}">-</button>
                        <div class="quantity-display">${currentQuantity}</div>
                        <button type="button" class="quantity-btn" data-action="increase" data-type-id="${type.id}">+</button>
                    </div>
                </div>
                
                ${type.alt_rate ? `
                    <div class="input-group">
                        <label>Alternative Quantity (${type.alt_unit})</label>
                        <div class="quantity-controls">
                            <button type="button" class="quantity-btn" data-action="decrease" data-type-id="${type.id}" data-alt="true">-</button>
                            <div class="quantity-display">${currentAltQuantity}</div>
                            <button type="button" class="quantity-btn" data-action="increase" data-type-id="${type.id}" data-alt="true">+</button>
                        </div>
                    </div>
                ` : ''}
                ${renderComplexityTiers(type)}
            </div>
        `;
    }
    
    function renderLanguageTiers(type) {
        if (!type.language_tiers) return '';
        
        const currentTier = languageTiers[type.id] || 'tier1';
        
        return `
            <div class="language-tiers">
                <h5>Language Group</h5>
                <div class="tier-options">
                    <label class="tier-option">
                        <input type="radio" name="language-tier-${type.id}" value="tier1" 
                               data-type-id="${type.id}" ${currentTier === 'tier1' ? 'checked' : ''}>
                        <span>${type.language_tiers.tier1.name} (${type.language_tiers.tier1.multiplier}x)</span>
                    </label>
                    <label class="tier-option">
                        <input type="radio" name="language-tier-${type.id}" value="tier2" 
                               data-type-id="${type.id}" ${currentTier === 'tier2' ? 'checked' : ''}>
                        <span>${type.language_tiers.tier2.name} (${type.language_tiers.tier2.multiplier}x)</span>
                    </label>
                    <label class="tier-option">
                        <input type="radio" name="language-tier-${type.id}" value="tier3" 
                               data-type-id="${type.id}" ${currentTier === 'tier3' ? 'checked' : ''}>
                        <span>${type.language_tiers.tier3.name} (${type.language_tiers.tier3.multiplier}x)</span>
                    </label>
                </div>
            </div>
        `;
    }
    
    function renderComplexityTiers(type) {
        const currentTier = complexityTiers[type.id] || 'standard';
        
        return `
            <div class="complexity-tiers">
                <h5>Complexity</h5>
                <div class="tier-options">
                    <label class="tier-option">
                        <input type="radio" name="complexity-${type.id}" value="standard" 
                               data-type-id="${type.id}" ${currentTier === 'standard' ? 'checked' : ''}>
                        <span>Standard (1.0x)</span>
                    </label>
                    <label class="tier-option">
                        <input type="radio" name="complexity-${type.id}" value="complex" 
                               data-type-id="${type.id}" ${currentTier === 'complex' ? 'checked' : ''}>
                        <span>Complex (1.25x)</span>
                    </label>
                </div>
            </div>
        `;
    }
    
    function renderCostBreakdown() {
        if (selectedTypes.size === 0) return '';
        
        const breakdown = getCostBreakdown();
        
        return `
            <div class="calc-card-content">
                <div class="step-header">
                    <div class="step-number">3</div>
                    <h2 class="step-title">Cost Breakdown</h2>
                </div>
                
                <div class="cost-breakdown">
                    ${breakdown.map(item => renderBreakdownItem(item)).join('')}
                </div>
            </div>
        `;
    }
    
    function renderBreakdownItem(item) {
        return `
            <div class="breakdown-item">
                <div class="breakdown-details">
                    <div class="breakdown-type-name">${item.type.name}</div>
                    <div class="breakdown-calculation">${item.calculation}</div>
                </div>
                <div class="breakdown-cost">$${item.cost.toFixed(2)}</div>
            </div>
        `;
    }
    
    function renderTotalCost() {
        if (selectedTypes.size === 0) return '';
        
        const total = getTotalCost();
        
        return `
            <div class="calc-card-content total-section">
                <div class="step-header">
                    <div class="step-number">4</div>
                    <h2 class="step-title">Total Project Cost</h2>
                </div>
                
                <div class="total-header">
                    <div class="total-amount">$${total.toFixed(2)}</div>
                    <button type="button" class="calc-button request-quote-btn">Request Quote</button>
                </div>
            </div>
        `;
    }
    
    function renderContactSection() {
        // Check if contact section is enabled
        if (!window.contactSettingsData || 
            !window.contactSettingsData.contactEnabled || 
            window.contactSettingsData.contactEnabled === false || 
            window.contactSettingsData.contactEnabled === 'false') {
            return '';
        }
        
        return `
            <div class="calc-contact-section">
                <div class="contact-content">
                    <h3>${window.contactSettingsData.title || 'Ready to Get Started?'}</h3>
                    <p>${window.contactSettingsData.description || 'Contact us for a personalized quote and timeline.'}</p>
                    <a href="${window.contactSettingsData.buttonUrl || '#'}" 
                       target="_blank" 
                       rel="noopener noreferrer" 
                       class="calc-contact-button">
                        ${window.contactSettingsData.buttonText || 'Get Quote'}
                    </a>
                </div>
            </div>
        `;
    }
    
    function renderFaqSection() {
        if (!window.faqItemsData || window.faqItemsData.length === 0) {
            return '';
        }
        
        // Group FAQ items by category
        const groupedFaq = {};
        window.faqItemsData.forEach(item => {
            if (!groupedFaq[item.category]) {
                groupedFaq[item.category] = [];
            }
            groupedFaq[item.category].push(item);
        });
        
        const categoriesHtml = Object.entries(groupedFaq).map(([category, items]) => `
            <div class="faq-category">
                <h3 class="faq-category-title">${category}</h3>
                <div class="faq-items">
                    ${items.map(item => `
                        <div class="faq-item" data-faq-id="${item.id}">
                            <div class="faq-question">
                                <h4>${item.question}</h4>
                                <svg class="faq-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                            </div>
                            <div class="faq-answer">
                                <div class="faq-answer-content">
                                    ${item.answer}
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `).join('');
        
        return `
            <div class="calc-faq-section">
                <div class="faq-header">
                    <h2>Frequently Asked Questions</h2>
                    <p>Get answers to common questions about our annotation services.</p>
                </div>
                <div class="faq-categories">
                    ${categoriesHtml}
                </div>
            </div>
        `;
    }
    
    function renderQuoteModal() {
        return `
            <div class="quote-modal" style="display: none;">
                <div class="quote-modal-content">
                    <div class="quote-modal-header">
                        <h3>Request Quote</h3>
                        <span class="quote-modal-close">&times;</span>
                    </div>
                    
                    <div class="quote-modal-body">
                        <form id="quote-form">
                            <div class="form-group">
                                <label for="quote-name">Name *</label>
                                <input type="text" id="quote-name" name="name" required />
                            </div>
                            
                            <div class="form-group">
                                <label for="quote-email">Email *</label>
                                <input type="email" id="quote-email" name="email" required />
                            </div>
                            
                            <div class="form-group">
                                <label for="quote-company">Company</label>
                                <input type="text" id="quote-company" name="company" />
                            </div>
                            
                            <div class="form-group">
                                <label for="quote-message">Project Details</label>
                                <textarea id="quote-message" name="message" rows="4" 
                                          placeholder="Tell us about your project..."></textarea>
                            </div>
                            
                            <div class="quote-summary">
                                <h4>Selected Services:</h4>
                                <div id="quote-types-list"></div>
                                <div class="quote-total">Total: $${getTotalCost().toFixed(2)}</div>
                            </div>
                            
                            <button type="submit" class="calc-button">Submit Request</button>
                        </form>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Calculation functions
    function toggleTypeSelection(typeId) {
        if (selectedTypes.has(typeId)) {
            selectedTypes.delete(typeId);
            delete quantities[typeId];
            delete altQuantities[typeId];
            delete imageCount[typeId];
            delete avgPerImage[typeId];
            delete videoDuration[typeId];
            delete videoObjects[typeId];
            delete complexityTiers[typeId];
            delete languageTiers[typeId];
        } else {
            selectedTypes.add(typeId);
            quantities[typeId] = 1;
            altQuantities[typeId] = 0;
            
            const type = annotationTypes.find(t => t.id === typeId);
            if (type && type.is_image_based) {
                imageCount[typeId] = 100;
                avgPerImage[typeId] = 1;
            }
            if (type && type.id === 'video') {
                videoDuration[typeId] = 5;
                videoObjects[typeId] = 2;
            }
            
            complexityTiers[typeId] = 'standard';
            if (type && type.language_tiers) {
                languageTiers[typeId] = 'tier1';
            }
        }
        
        renderCalculator();
    }
    
    function updateQuantity(typeId, change, isAlt = false) {
        if (isAlt) {
            altQuantities[typeId] = Math.max(0, (altQuantities[typeId] || 0) + change);
        } else {
            quantities[typeId] = Math.max(1, (quantities[typeId] || 1) + change);
        }
        updateCalculation();
    }
    
    function updateInputValue(typeId, field, value) {
        switch(field) {
            case 'imageCount':
                imageCount[typeId] = Math.max(1, value);
                break;
            case 'avgPerImage':
                avgPerImage[typeId] = Math.max(0.1, value);
                break;
            case 'videoDuration':
                videoDuration[typeId] = Math.max(0.1, value);
                break;
            case 'videoObjects':
                videoObjects[typeId] = Math.max(1, value);
                break;
        }
        updateCalculation();
    }
    
    function getCostBreakdown() {
        const breakdown = [];
        
        selectedTypes.forEach(typeId => {
            const type = annotationTypes.find(t => t.id === typeId);
            if (!type) return;
            
            const tierMultiplier = (complexityTiers[typeId] || 'standard') === 'complex' ? 1.25 : 1;
            let quantity = quantities[typeId] || 0;
            let calculation = '';
            
            // Calculate quantity based on type
            if (type.is_image_based) {
                const images = imageCount[typeId] || 100;
                const avgPer = avgPerImage[typeId] || 1;
                quantity = images * avgPer;
                calculation = `${images.toLocaleString()} images × ${avgPer} avg/image = ${quantity.toLocaleString()} total`;
            } else if (type.id === 'video') {
                const duration = videoDuration[typeId] || 5;
                const objects = videoObjects[typeId] || 2;
                quantity = duration * objects;
                calculation = `${duration} minutes × ${objects} objects = ${quantity.toLocaleString()} object-minutes`;
            } else {
                calculation = `${quantity.toFixed(1)} ${type.unit.replace('per ', '')}${quantity !== 1 ? 's' : ''}`;
            }
            
            // Convert audio hours to minutes for calculation
            if (type.id.startsWith('audio-')) {
                quantity = quantity * 60;
            }
            
            const altQuantity = altQuantities[typeId] || 0;
            const baseCost = (quantity * type.rate) + (altQuantity * (type.alt_rate || 0));
            const languageMultiplier = type.language_tiers ? 
                (type.language_tiers[languageTiers[typeId] || 'tier1']?.multiplier || 1) : 1;
            const cost = baseCost * tierMultiplier * languageMultiplier;
            
            breakdown.push({
                type,
                quantity: type.id.startsWith('audio-') ? quantity / 60 : quantity,
                altQuantity,
                cost,
                calculation
            });
        });
        
        return breakdown;
    }
    
    function getTotalCost() {
        return getCostBreakdown().reduce((total, item) => total + item.cost, 0);
    }
    
    function updateCalculation() {
        // Update quantity displays
        $('.quantity-display').each(function() {
            const $this = $(this);
            const $section = $this.closest('.quantity-section');
            const typeId = $section.data('type-id');
            const isAlt = $this.closest('.quantity-controls').find('[data-alt="true"]').length > 0;
            
            if (typeId) {
                const value = isAlt ? (altQuantities[typeId] || 0) : (quantities[typeId] || 0);
                $this.text(value);
            }
        });
        
        // Update cost breakdown and total
        const breakdown = getCostBreakdown();
        const total = getTotalCost();
        
        $('.cost-breakdown').html(breakdown.map(item => renderBreakdownItem(item)).join(''));
        $('.total-amount').text(`$${total.toFixed(2)}`);
        
        // Update quote modal if open
        if ($('.quote-modal').is(':visible')) {
            updateQuoteModal();
        }
    }
    
    function openQuoteModal() {
        updateQuoteModal();
        $('.quote-modal').show();
    }
    
    function closeQuoteModal() {
        $('.quote-modal').hide();
        $('#quote-form')[0].reset();
    }
    
    function updateQuoteModal() {
        const selectedTypesHtml = Array.from(selectedTypes).map(typeId => {
            const type = annotationTypes.find(t => t.id === typeId);
            return type ? `<div class="quote-type">${type.name}</div>` : '';
        }).join('');
        
        $('#quote-types-list').html(selectedTypesHtml);
        $('.quote-total').text(`Total: $${getTotalCost().toFixed(2)}`);
    }
    
    function submitQuoteRequest() {
        const formData = new FormData($('#quote-form')[0]);
        const data = {
            name: formData.get('name'),
            email: formData.get('email'),
            company: formData.get('company'),
            message: formData.get('message'),
            selected_types: Array.from(selectedTypes),
            total_cost: getTotalCost()
        };
        
        $('button[type="submit"]').prop('disabled', true).text('Submitting...');
        
        $.ajax({
            url: costCalcConfig.apiUrl + 'quote-request',
            method: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', costCalcConfig.nonce);
            },
            success: function(response) {
                alert('Quote request submitted successfully! We\'ll get back to you soon.');
                closeQuoteModal();
            },
            error: function(xhr, status, error) {
                alert('Failed to submit quote request. Please try again.');
            },
            complete: function() {
                $('button[type="submit"]').prop('disabled', false).text('Submit Request');
            }
        });
    }
    
})(jQuery);