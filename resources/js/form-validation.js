/**
 * OvertimeStaff Form Validation Utilities
 *
 * A reusable form validation library using Alpine.js
 * Provides common validators, error handling, and accessibility features
 *
 * Usage:
 * Include this file in your page and use the validation helpers in your Alpine.js components
 */

window.OTSValidation = {
    /**
     * Common validation rules
     */
    rules: {
        /**
         * Validate that a field is not empty
         * @param {string} value - The value to validate
         * @param {string} fieldName - Human-readable field name for error messages
         * @returns {string|null} Error message or null if valid
         */
        required(value, fieldName = 'This field') {
            if (!value || (typeof value === 'string' && value.trim() === '')) {
                return `${fieldName} is required`;
            }
            return null;
        },

        /**
         * Validate email format
         * @param {string} value - The email to validate
         * @returns {string|null} Error message or null if valid
         */
        email(value) {
            if (!value) return null; // Use required rule for empty check
            const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!emailRegex.test(value.trim())) {
                return 'Please enter a valid email address';
            }
            return null;
        },

        /**
         * Validate minimum length
         * @param {string} value - The value to validate
         * @param {number} min - Minimum length
         * @param {string} fieldName - Human-readable field name
         * @returns {string|null} Error message or null if valid
         */
        minLength(value, min, fieldName = 'This field') {
            if (!value) return null;
            if (value.length < min) {
                return `${fieldName} must be at least ${min} characters`;
            }
            return null;
        },

        /**
         * Validate maximum length
         * @param {string} value - The value to validate
         * @param {number} max - Maximum length
         * @param {string} fieldName - Human-readable field name
         * @returns {string|null} Error message or null if valid
         */
        maxLength(value, max, fieldName = 'This field') {
            if (!value) return null;
            if (value.length > max) {
                return `${fieldName} must be less than ${max} characters`;
            }
            return null;
        },

        /**
         * Validate minimum value for numbers
         * @param {number} value - The value to validate
         * @param {number} min - Minimum value
         * @param {string} fieldName - Human-readable field name
         * @returns {string|null} Error message or null if valid
         */
        min(value, min, fieldName = 'Value') {
            if (value === null || value === undefined || value === '') return null;
            if (parseFloat(value) < min) {
                return `${fieldName} must be at least ${min}`;
            }
            return null;
        },

        /**
         * Validate maximum value for numbers
         * @param {number} value - The value to validate
         * @param {number} max - Maximum value
         * @param {string} fieldName - Human-readable field name
         * @returns {string|null} Error message or null if valid
         */
        max(value, max, fieldName = 'Value') {
            if (value === null || value === undefined || value === '') return null;
            if (parseFloat(value) > max) {
                return `${fieldName} must not exceed ${max}`;
            }
            return null;
        },

        /**
         * Validate that value matches a pattern
         * @param {string} value - The value to validate
         * @param {RegExp} pattern - The pattern to match
         * @param {string} message - Custom error message
         * @returns {string|null} Error message or null if valid
         */
        pattern(value, pattern, message = 'Invalid format') {
            if (!value) return null;
            if (!pattern.test(value)) {
                return message;
            }
            return null;
        },

        /**
         * Validate that two values match (e.g., password confirmation)
         * @param {string} value - The value to validate
         * @param {string} compareValue - The value to compare against
         * @param {string} message - Custom error message
         * @returns {string|null} Error message or null if valid
         */
        matches(value, compareValue, message = 'Values do not match') {
            if (!value || !compareValue) return null;
            if (value !== compareValue) {
                return message;
            }
            return null;
        },

        /**
         * Validate US ZIP code format
         * @param {string} value - The ZIP code to validate
         * @returns {string|null} Error message or null if valid
         */
        zipCode(value) {
            if (!value) return null;
            if (!/^[0-9]{5}(-[0-9]{4})?$/.test(value)) {
                return 'Please enter a valid ZIP code (e.g., 02110 or 02110-1234)';
            }
            return null;
        },

        /**
         * Validate phone number format
         * @param {string} value - The phone number to validate
         * @returns {string|null} Error message or null if valid
         */
        phone(value) {
            if (!value) return null;
            // Remove all non-numeric characters for validation
            const digits = value.replace(/\D/g, '');
            if (digits.length < 10 || digits.length > 15) {
                return 'Please enter a valid phone number';
            }
            return null;
        },

        /**
         * Validate URL format
         * @param {string} value - The URL to validate
         * @returns {string|null} Error message or null if valid
         */
        url(value) {
            if (!value) return null;
            try {
                new URL(value);
                return null;
            } catch {
                return 'Please enter a valid URL';
            }
        },

        /**
         * Validate date is in the future
         * @param {string} value - The date string to validate (YYYY-MM-DD format)
         * @returns {string|null} Error message or null if valid
         */
        futureDate(value) {
            if (!value) return null;
            const selectedDate = new Date(value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (selectedDate < today) {
                return 'Date must be today or in the future';
            }
            return null;
        },

        /**
         * Validate integer value
         * @param {any} value - The value to validate
         * @returns {string|null} Error message or null if valid
         */
        integer(value) {
            if (value === null || value === undefined || value === '') return null;
            if (!Number.isInteger(Number(value))) {
                return 'Please enter a whole number';
            }
            return null;
        }
    },

    /**
     * Password strength checker
     * @param {string} password - The password to check
     * @returns {Object} Password strength details
     */
    checkPasswordStrength(password) {
        const requirements = {
            minLength: password.length >= 8,
            hasUppercase: /[A-Z]/.test(password),
            hasLowercase: /[a-z]/.test(password),
            hasNumber: /[0-9]/.test(password),
            hasSpecial: /[!@#$%^&*(),.?":{}|<>]/.test(password)
        };

        let score = 0;
        if (requirements.minLength) score++;
        if (requirements.hasUppercase) score++;
        if (requirements.hasLowercase) score++;
        if (requirements.hasNumber) score++;
        if (requirements.hasSpecial) score++;

        let strength, label, color, percent;
        if (score <= 1) {
            strength = 'weak';
            label = 'Weak';
            color = '#EF4444';
            percent = 25;
        } else if (score === 2) {
            strength = 'fair';
            label = 'Fair';
            color = '#F59E0B';
            percent = 50;
        } else if (score === 3) {
            strength = 'good';
            label = 'Good';
            color = '#3B82F6';
            percent = 75;
        } else {
            strength = 'strong';
            label = 'Strong';
            color = '#10B981';
            percent = 100;
        }

        return {
            requirements,
            score,
            strength,
            label,
            color,
            percent,
            isValid: requirements.minLength && requirements.hasUppercase && requirements.hasLowercase && requirements.hasNumber
        };
    },

    /**
     * Announce message to screen readers
     * @param {string} message - The message to announce
     * @param {string} announcerId - ID of the live region element
     */
    announce(message, announcerId = 'validation-announcer') {
        const announcer = document.getElementById(announcerId);
        if (announcer) {
            announcer.textContent = message;
            setTimeout(() => {
                announcer.textContent = '';
            }, 1000);
        }
    },

    /**
     * Add shake animation to an element
     * @param {string} elementId - ID of the element to shake
     */
    shakeElement(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.classList.add('shake');
            setTimeout(() => {
                element.classList.remove('shake');
            }, 400);
        }
    },

    /**
     * Focus the first element with an error
     * @param {Object} errors - Object with field names as keys and error messages as values
     */
    focusFirstError(errors) {
        const errorFields = Object.keys(errors);
        if (errorFields.length > 0) {
            const firstErrorField = errorFields[0];
            const element = document.getElementById(firstErrorField);
            if (element) {
                element.focus();
                this.shakeElement(firstErrorField);
            }
        }
    },

    /**
     * Create a validator function that combines multiple rules
     * @param {Array} rules - Array of rule configurations
     * @returns {Function} Validator function
     *
     * Example:
     * const validator = OTSValidation.createValidator([
     *   { rule: 'required', fieldName: 'Email' },
     *   { rule: 'email' },
     *   { rule: 'maxLength', params: [255], fieldName: 'Email' }
     * ]);
     */
    createValidator(rules) {
        return (value) => {
            for (const config of rules) {
                const ruleName = config.rule;
                const params = config.params || [];
                const fieldName = config.fieldName || 'This field';

                let error = null;

                switch (ruleName) {
                    case 'required':
                        error = this.rules.required(value, fieldName);
                        break;
                    case 'email':
                        error = this.rules.email(value);
                        break;
                    case 'minLength':
                        error = this.rules.minLength(value, params[0], fieldName);
                        break;
                    case 'maxLength':
                        error = this.rules.maxLength(value, params[0], fieldName);
                        break;
                    case 'min':
                        error = this.rules.min(value, params[0], fieldName);
                        break;
                    case 'max':
                        error = this.rules.max(value, params[0], fieldName);
                        break;
                    case 'pattern':
                        error = this.rules.pattern(value, params[0], params[1]);
                        break;
                    case 'matches':
                        error = this.rules.matches(value, params[0], params[1]);
                        break;
                    case 'zipCode':
                        error = this.rules.zipCode(value);
                        break;
                    case 'phone':
                        error = this.rules.phone(value);
                        break;
                    case 'url':
                        error = this.rules.url(value);
                        break;
                    case 'futureDate':
                        error = this.rules.futureDate(value);
                        break;
                    case 'integer':
                        error = this.rules.integer(value);
                        break;
                }

                if (error) {
                    return error;
                }
            }
            return null;
        };
    }
};

/**
 * Alpine.js component factory for form validation
 *
 * Usage in Blade templates:
 * <form x-data="createFormValidation({
 *     fields: {
 *         email: [
 *             { rule: 'required', fieldName: 'Email' },
 *             { rule: 'email' }
 *         ],
 *         password: [
 *             { rule: 'required', fieldName: 'Password' },
 *             { rule: 'minLength', params: [8], fieldName: 'Password' }
 *         ]
 *     },
 *     initialValues: { email: '', password: '' }
 * })">
 */
window.createFormValidation = function(config) {
    const { fields, initialValues = {}, onSubmit } = config;

    // Build validators for each field
    const validators = {};
    for (const [fieldName, rules] of Object.entries(fields)) {
        validators[fieldName] = OTSValidation.createValidator(rules);
    }

    return {
        form: { ...initialValues },
        errors: {},
        touched: {},
        isSubmitting: false,

        init() {
            // Initialize touched state for fields with initial values
            for (const [field, value] of Object.entries(this.form)) {
                if (value) {
                    this.touched[field] = true;
                }
            }
        },

        validateField(field) {
            this.touched[field] = true;
            const validator = validators[field];

            if (validator) {
                const error = validator(this.form[field]);
                if (error) {
                    this.errors[field] = error;
                    OTSValidation.announce(error);
                    return false;
                } else {
                    delete this.errors[field];
                    return true;
                }
            }
            return true;
        },

        clearError(field) {
            if (this.touched[field] && this.errors[field]) {
                this.validateField(field);
            }
        },

        validateAll() {
            let isValid = true;
            for (const field of Object.keys(fields)) {
                if (!this.validateField(field)) {
                    isValid = false;
                }
            }
            return isValid;
        },

        handleSubmit(event) {
            if (!this.validateAll()) {
                event.preventDefault();
                OTSValidation.focusFirstError(this.errors);
                return false;
            }

            if (onSubmit) {
                event.preventDefault();
                this.isSubmitting = true;
                onSubmit(this.form);
            } else {
                this.isSubmitting = true;
            }
        },

        hasError(field) {
            return !!this.errors[field];
        },

        isValid(field) {
            return this.touched[field] && !this.errors[field] && this.form[field];
        },

        getInputClass(field) {
            if (this.errors[field]) {
                return 'form-input-error';
            }
            if (this.touched[field] && !this.errors[field] && this.form[field]) {
                return 'form-input-valid';
            }
            return '';
        }
    };
};

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { OTSValidation, createFormValidation };
}
