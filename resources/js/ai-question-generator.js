/**
 * AI Question Generator
 * Handles the AI question generation form and modal interactions
 * Pure JavaScript - no PHP dependencies
 */

class AIQuestionGenerator {
    constructor() {
        this.config = null;
        this.form = null;
        this.init();
    }

    /**
     * Initialize the component
     */
    init() {
        this.loadConfig();
        this.bindElements();
        this.attachEventListeners();
        this.calculateCost();
    }

    /**
     * Load configuration from data attribute
     */
    loadConfig() {
        const configElement = document.getElementById('question-generator-config');
        if (!configElement) {
            console.error('Configuration element not found');
            return;
        }
        try {
            this.config = JSON.parse(configElement.textContent);
        } catch (error) {
            console.error('Failed to parse configuration:', error);
        }
    }

    /**
     * Bind DOM elements for easy access
     */
    bindElements() {
        this.form = document.getElementById('aiQuestionForm');
        this.subjectSelect = document.getElementById('subject');
        this.difficultySelect = document.getElementById('difficulty');
        this.costCalculation = document.getElementById('costCalculation');
        this.generateBtn = document.getElementById('generateBtn');
        
        // Cost display elements
        this.baseCostDisplay = document.getElementById('baseCost');
        this.difficultyCostDisplay = document.getElementById('difficultyMultiplier');
        this.subjectCostDisplay = document.getElementById('subjectCost');
        this.totalCostDisplay = document.getElementById('totalCost');
        this.userBalanceDisplay = document.getElementById('userBalance');
        
        // Modal elements
        this.modalTitle = document.getElementById('modalTitle');
        this.processingIndicator = document.getElementById('processingIndicator');
        this.successResult = document.getElementById('successResult');
        this.errorResult = document.getElementById('errorResult');
        this.errorReasons = document.getElementById('errorReasons');
        this.viewQuestionBtn = document.getElementById('viewQuestionBtn');
        
        // New error handling elements
        this.errorTypeIndicator = document.getElementById('errorTypeIndicator');
        this.errorTypeBadge = document.getElementById('errorTypeBadge');
        this.errorActions = document.getElementById('errorActions');
        this.defaultRetryBtn = document.getElementById('defaultRetryBtn');
        
        // Balance error modal elements
        this.balanceErrorModal = document.getElementById('balanceErrorModal');
        this.requiredCoins = document.getElementById('requiredCoins');
        this.availableCoins = document.getElementById('availableCoins');
    }

    /**
     * Attach event listeners
     */
    attachEventListeners() {
        if (this.form) {
            this.form.addEventListener('submit', this.handleFormSubmit.bind(this));
        }

        if (this.subjectSelect) {
            this.subjectSelect.addEventListener('change', this.calculateCost.bind(this));
            // Clear validation errors when user makes a selection
            this.subjectSelect.addEventListener('change', this.clearFieldValidationError.bind(this));
        }

        if (this.difficultySelect) {
            this.difficultySelect.addEventListener('change', this.calculateCost.bind(this));
            // Clear validation errors when user makes a selection
            this.difficultySelect.addEventListener('change', this.clearFieldValidationError.bind(this));
        }
    }

    /**
     * Clear validation error for a specific field
     */
    clearFieldValidationError(event) {
        const field = event.target;
        const fieldName = field.name || field.id;
        
        // Remove error styling
        field.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
        
        // Remove error message
        const errorMessage = field.parentNode.querySelector('.field-error-message');
        if (errorMessage) {
            errorMessage.remove();
        }
    }

    /**
     * Calculate and display the cost based on selections
     */
    calculateCost() {
        if (!this.config || !this.subjectSelect || !this.difficultySelect) {
            return;
        }

        const subject = this.subjectSelect.value;
        const difficulty = this.difficultySelect.value;
        
        // Hide cost calculation if no selections made
        if (!subject && !difficulty) {
            this.costCalculation?.classList.add('hidden');
            return;
        }
        
        // Calculate costs
        const baseCost = this.config.costs.base;
        const subjectCost = (subject === 'random' || subject === '') ? 0 : this.config.costs.subject;
        const difficultyCost = (difficulty === 'random' || difficulty === '') ? 0 : this.config.costs.difficulty;
        const totalCost = baseCost + difficultyCost + subjectCost;
        
        // Update display
        this.updateCostDisplay(baseCost, subjectCost, difficultyCost, totalCost);
        
        // Show cost calculation
        this.costCalculation?.classList.remove('hidden');
        
        // Check if user has enough coins and update button state
        this.updateGenerateButton(totalCost);
    }

    /**
     * Update cost display elements
     */
    updateCostDisplay(baseCost, subjectCost, difficultyCost, totalCost) {
        if (this.baseCostDisplay) {
            this.baseCostDisplay.textContent = `${baseCost} coins`;
        }
        if (this.difficultyCostDisplay) {
            this.difficultyCostDisplay.textContent = `${difficultyCost} coins`;
        }
        if (this.subjectCostDisplay) {
            this.subjectCostDisplay.textContent = `${subjectCost} coins`;
        }
        if (this.totalCostDisplay) {
            this.totalCostDisplay.textContent = `${totalCost} coins`;
        }
        if (this.userBalanceDisplay) {
            this.userBalanceDisplay.textContent = `${this.config.user.balance} coins`;
        }
    }

    /**
     * Update generate button state based on user balance
     */
    updateGenerateButton(totalCost) {
        if (!this.generateBtn) return;

        const userBalance = this.config.user.balance;
        
        if (totalCost > userBalance) {
            this.generateBtn.disabled = true;
            this.generateBtn.classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            this.generateBtn.disabled = false;
            this.generateBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    /**
     * Handle form submission
     */
    async handleFormSubmit(e) {
        e.preventDefault();
        
        // Clear any previous validation errors
        this.removeValidationErrorHighlighting();
        
        const formData = new FormData(this.form);
        const subject = formData.get('subject');
        const difficulty = formData.get('difficulty');
        
        // Validation
        if (!subject || !difficulty) {
            alert(this.config.i18n.please_select_all_fields);
            return;
        }
        
        // Show processing modal
        this.showProcessingModal();
        
        try {
            const response = await this.submitQuestion(subject, difficulty);
            const data = await response.json();
                        
            if (data.success) {
                this.showSuccessModal(data);
            } else {
                // Handle different exception types
                if (data.exception_type) {
                    this.handleExceptionByType(data.exception_type, data);
                } else {
                    // Fallback to general error
                    this.showErrorModal(data.reasons || [data.message || this.config.i18n.unknown_error]);
                }
            }
        } catch (error) {
            console.error('Error:', error);
            this.showErrorModal([this.config.i18n.network_error]);
        }
    }

    /**
     * Submit question generation request
     */
    async submitQuestion(subject, difficulty) {
        const csrfToken = document.querySelector('input[name="_token"]')?.value;
        
        return fetch(this.config.routes.store, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                subject: subject,
                difficulty: difficulty
            })
        });
    }

    /**
     * Handle different exception types with appropriate UI responses
     */
    handleExceptionByType(exceptionType, data) {
        switch (exceptionType) {
            case 'validation_error':
                this.handleValidationException(data);
                break;
            case 'paid_service':
                this.handlePaidServiceException(data);
                break;
            case 'llm_connection':
                this.handleLLMConnectionException(data);
                break;
            case 'llm_code':
                this.handleLLMCodeException(data);
                break;
            case 'question_generation_process':
                this.handleQuestionProcessException(data);
                break;
            default:
                this.showErrorModal([data.message || this.config.i18n.unknown_error]);
        }
    }

    /**
     * Handle validation exceptions (field validation errors)
     */
    handleValidationException(data) {
        // Highlight invalid fields
        this.highlightValidationErrors(data.context?.validation_errors || {});
        
        // Show validation error modal
        this.showValidationErrorModal(data);
    }

    /**
     * Highlight validation errors on form fields
     */
    highlightValidationErrors(validationErrors) {
        // Remove previous error highlighting
        this.removeValidationErrorHighlighting();
        
        // Highlight each field with errors
        Object.keys(validationErrors).forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) {
                // Add error styling
                field.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
                
                // Add error message below field
                this.addFieldErrorMessage(field, validationErrors[fieldName][0]);
            }
        });
    }

    /**
     * Remove validation error highlighting
     */
    removeValidationErrorHighlighting() {
        // Remove error styling from all form fields
        const formFields = this.form.querySelectorAll('input, select, textarea');
        formFields.forEach(field => {
            field.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
        });
        
        // Remove error messages
        const errorMessages = this.form.querySelectorAll('.field-error-message');
        errorMessages.forEach(message => message.remove());
    }

    /**
     * Add error message below a field
     */
    addFieldErrorMessage(field, message) {
        // Check if error message already exists
        const existingError = field.parentNode.querySelector('.field-error-message');
        if (existingError) {
            existingError.remove();
        }
        
        // Create error message element
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error-message text-red-600 text-sm mt-1 flex items-center gap-2';
        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-circle text-red-500"></i>
            <span>${this.escapeHtml(message)}</span>
        `;
        
        // Insert after the field
        field.parentNode.appendChild(errorDiv);
    }

    /**
     * Show validation error modal
     */
    showValidationErrorModal(data) {
        this.processingIndicator?.classList.add('hidden');
        this.successResult?.classList.add('hidden');
        this.errorResult?.classList.remove('hidden');
        this.balanceErrorModal?.classList.add('hidden');
        
        // Update modal title
        if (this.modalTitle) {
            this.modalTitle.textContent = this.config.i18n.validation_error_title;
        }
        
        // Show validation error message
        this.showErrorModal([
            data.user_message || this.config.i18n.please_check_input_fields,
            this.config.i18n.please_fix_errors_below
        ]);
    }

    /**
     * Handle paid service exceptions (balance, parameters)
     */
    handlePaidServiceException(data) {
        if (data.error_type === 'insufficient_balance') {
            // Show balance error with specific message
            this.showBalanceErrorModal(data);
        } else if (data.error_type === 'invalid_parameters') {
            // Show parameter validation error
            this.showParameterErrorModal(data);
        } else {
            // Fallback to general paid service error
            this.showErrorModal([data.message || this.config.i18n.unknown_error]);
        }
    }

    /**
     * Handle LLM connection exceptions (infrastructure issues)
     */
    handleLLMConnectionException(data) {
        // Always show unified "service unavailable" message
        this.showServiceUnavailableModal(data.user_message || this.config.i18n.service_unavailable);
    }

    /**
     * Handle LLM code exceptions (logic errors)
     */
    handleLLMCodeException(data) {
        // Show technical but user-friendly message
        this.showErrorModal([data.message || this.config.i18n.llm_error]);
    }

    /**
     * Handle question generation process exceptions
     */
    handleQuestionProcessException(data) {
        // Show specific process error message
        this.showErrorModal([data.message || this.config.i18n.process_error]);
    }

    /**
     * Show processing modal
     */
    showProcessingModal() {
        // Reset modal state
        this.processingIndicator?.classList.remove('hidden');
        this.successResult?.classList.add('hidden');
        this.errorResult?.classList.add('hidden');
        this.balanceErrorModal?.classList.add('hidden');
        
        // Update modal title
        if (this.modalTitle) {
            this.modalTitle.textContent = this.config.i18n.generating_question;
        }
        
        // Open modal
        this.openModal();
    }

    /**
     * Show success modal
     */
    showSuccessModal(data = {}) {
        this.processingIndicator?.classList.add('hidden');
        this.successResult?.classList.remove('hidden');
        this.errorResult?.classList.add('hidden');
        this.balanceErrorModal?.classList.add('hidden');
        
        // Update modal title
        if (this.modalTitle) {
            this.modalTitle.textContent = this.config.i18n.question_generated;
        }

        // Update view question link if data contains URL
        if (data.view_url && this.viewQuestionBtn) {
            this.viewQuestionBtn.href = data.view_url;
        }
    }

    /**
     * Show error modal
     */
    showErrorModal(reasons = []) {
        this.processingIndicator?.classList.add('hidden');
        this.successResult?.classList.add('hidden');
        this.errorResult?.classList.remove('hidden');
        this.balanceErrorModal?.classList.add('hidden');
        
        // Update modal title
        if (this.modalTitle) {
            this.modalTitle.textContent = this.config.i18n.generation_failed;
        }
        
        // Hide error type indicator for general errors
        if (this.errorTypeIndicator) {
            this.errorTypeIndicator.classList.add('hidden');
        }
        
        // Display error reasons
        if (this.errorReasons && Array.isArray(reasons)) {
            const reasonsHtml = reasons.map(reason => `
                <div class="flex items-center gap-2 text-red-700">
                    <i class="fas fa-times-circle"></i>
                    <span>${this.escapeHtml(reason)}</span>
                </div>
            `).join('');
            
            this.errorReasons.innerHTML = reasonsHtml;
        }
    }

    /**
     * Show balance error modal with specific actions
     */
    showBalanceErrorModal(data) {
        this.processingIndicator?.classList.add('hidden');
        this.successResult?.classList.add('hidden');
        this.errorResult?.classList.add('hidden');
        this.balanceErrorModal?.classList.remove('hidden');
        
        // Update modal title
        if (this.modalTitle) {
            this.modalTitle.textContent = this.config.i18n.insufficient_balance_title;
        }
        
        // Update balance details
        if (this.requiredCoins && data.context?.required) {
            this.requiredCoins.textContent = `${data.context.required} coins`;
        }
        if (this.availableCoins && data.context?.available) {
            this.availableCoins.textContent = `${data.context.available} coins`;
        }
    }

    /**
     * Show parameter validation error modal
     */
    showParameterErrorModal(data) {
        this.showErrorModal([
            data.message || this.config.i18n.invalid_parameters,
            this.config.i18n.please_check_selections
        ]);
    }

    /**
     * Show service unavailable modal
     */
    showServiceUnavailableModal(message) {
        this.processingIndicator?.classList.add('hidden');
        this.successResult?.classList.add('hidden');
        this.errorResult?.classList.remove('hidden');
        this.balanceErrorModal?.classList.add('hidden');
        
        // Update modal title
        if (this.modalTitle) {
            this.modalTitle.textContent = this.config.i18n.service_unavailable_title;
        }
        
        // Show service unavailable message
        this.showErrorModal([
            message,
            this.config.i18n.try_again_later,
            this.config.i18n.contact_support_if_persistent
        ]);
    }

    /**
     * Open modal (using the existing modal system)
     */
    openModal() {
        window.dispatchEvent(new CustomEvent('open-modal', {
            detail: {
                detail: 'ai-question-generation',
                value: '',
                input_detail: {}
            }
        }));
    }

    /**
     * Close modal (global function for onclick handlers)
     */
    closeModal() {
        window.dispatchEvent(new CustomEvent('close-modal', {
            detail: 'ai-question-generation'
        }));
    }

    /**
     * Add coins action (placeholder for future implementation)
     */
    addCoins() {
        // TODO: Implement add coins functionality
        alert(this.config.i18n.add_coins_coming_soon || 'Add coins functionality coming soon!');
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Global function for modal close button
function closeModal() {
    if (window.aiQuestionGenerator) {
        window.aiQuestionGenerator.closeModal();
    }
}

// Global function for add coins button
function addCoins() {
    if (window.aiQuestionGenerator) {
        window.aiQuestionGenerator.addCoins();
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.aiQuestionGenerator = new AIQuestionGenerator();
});