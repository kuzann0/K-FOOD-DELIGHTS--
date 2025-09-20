// Custom error for validation failures// Custom error for validation failures

class OrderValidationError extends Error {class OrderValidationError extends Error {

    constructor(message) {    constructor(message) {

        super(message);        super(message);

        this.name = 'OrderValidationError';        this.name = 'OrderValidationError';

    }    }

}}



// Input sanitization utilities// Custom error for validation failures

const Sanitizer = {class OrderValidationError extends Error {

    // Remove potentially dangerous characters and standardize input    constructor(message) {

    sanitizeGeneral(value, options = {}) {        super(message);

        const {        this.name = 'OrderValidationError';

            allowNumbers = true,    }

            allowLetters = true,}

            allowSpaces = true,

            allowPunctuation = false,// Input sanitization utilities

            maxLength = nullconst Sanitizer = {

        } = options;    // Remove potentially dangerous characters and standardize input

    sanitizeGeneral(value, options = {}) {

        let sanitized = String(value || '').trim();        const {

            allowNumbers = true,

        // Remove invisible characters and standardize spaces            allowLetters = true,

        sanitized = sanitized            allowSpaces = true,

            .replace(/[\u200B-\u200D\uFEFF]/g, '') // Remove zero-width spaces            allowPunctuation = false,

            .replace(/[\u00A0\u1680\u180e\u2000-\u200a\u202f\u205f\u3000]/g, ' ') // Replace special spaces            maxLength = null

            .replace(/\s+/g, ' '); // Standardize spaces        } = options;



        // Apply character filters        let sanitized = String(value || '').trim();

        let allowedChars = '';

        if (allowLetters) allowedChars += 'a-zA-ZÀ-ÿ';        // Remove invisible characters and standardize spaces

        if (allowNumbers) allowedChars += '0-9';        sanitized = sanitized

        if (allowSpaces) allowedChars += '\\s';            .replace(/[\u200B-\u200D\uFEFF]/g, '') // Remove zero-width spaces

        if (allowPunctuation) allowedChars += '.,!?\'"-_()[]{}';            .replace(/[\u00A0\u1680\u180e\u2000-\u200a\u202f\u205f\u3000]/g, ' ') // Replace special spaces

            .replace(/\s+/g, ' '); // Standardize spaces

        const regex = new RegExp(`[^${allowedChars}]`, 'g');

        sanitized = sanitized.replace(regex, '');        // Apply character filters

        let allowedChars = '';

        // Enforce maximum length if specified        if (allowLetters) allowedChars += 'a-zA-ZÀ-ÿ';

        if (maxLength && sanitized.length > maxLength) {        if (allowNumbers) allowedChars += '0-9';

            sanitized = sanitized.substring(0, maxLength);        if (allowSpaces) allowedChars += '\\s';

        }        if (allowPunctuation) allowedChars += '.,!?\'"-_()[]{}';



        return sanitized;        const regex = new RegExp(`[^${allowedChars}]`, 'g');

    },        sanitized = sanitized.replace(regex, '');



    // Sanitize and format a name        // Enforce maximum length if specified

    sanitizeName(name) {        if (maxLength && sanitized.length > maxLength) {

        let sanitized = this.sanitizeGeneral(name, {            sanitized = sanitized.substring(0, maxLength);

            allowLetters: true,        }

            allowSpaces: true,

            allowNumbers: false,        return sanitized;

            maxLength: 100    },

        });

    // Sanitize and format a name

        // Proper case conversion with special character handling    sanitizeName(name) {

        return sanitized.replace(/\w\S*/g, txt =>         let sanitized = this.sanitizeGeneral(name, {

            txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase()            allowLetters: true,

        );            allowSpaces: true,

    },            allowNumbers: false,

            maxLength: 100

    // Sanitize and format a phone number        });

    sanitizePhone(phone) {

        let digits = String(phone || '').replace(/[^\d+]/g, '');        // Proper case conversion with special character handling

                return sanitized.replace(/\w\S*/g, txt => 

        // Handle international format            txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase()

        if (digits.startsWith('+63')) {        );

            digits = '0' + digits.substring(3);    },

        }

            // Sanitize and format a phone number

        // Enforce exactly 11 digits for PH numbers    sanitizePhone(phone) {

        if (digits.length === 11 && digits.startsWith('09')) {        let digits = String(phone || '').replace(/[^\d+]/g, '');

            return digits;        

        }        // Handle international format

                if (digits.startsWith('+63')) {

        return '';            digits = '0' + digits.substring(3);

    },        }

        

    // Sanitize and format an email address        // Enforce exactly 11 digits for PH numbers

    sanitizeEmail(email) {        if (digits.length === 11 && digits.startsWith('09')) {

        let sanitized = String(email || '')            return digits;

            .trim()        }

            .toLowerCase()        

            .replace(/\s+/g, '');        return '';

    },

        // Basic email format validation

        if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(sanitized)) {    // Sanitize and format an email address

            return '';    sanitizeEmail(email) {

        }        let sanitized = String(email || '')

            .trim()

        return sanitized;            .toLowerCase()

    },            .replace(/\s+/g, '');



    // Sanitize and format an address        // Basic email format validation

    sanitizeAddress(address) {        if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(sanitized)) {

        let sanitized = this.sanitizeGeneral(address, {            return '';

            allowLetters: true,        }

            allowNumbers: true,

            allowSpaces: true,        return sanitized;

            allowPunctuation: true,    },

            maxLength: 200

        });    // Sanitize and format an address

    sanitizeAddress(address) {

        // Standardize common abbreviations        let sanitized = this.sanitizeGeneral(address, {

        const abbreviations = {            allowLetters: true,

            'st\\.': 'Street',            allowNumbers: true,

            'rd\\.': 'Road',            allowSpaces: true,

            'ave\\.': 'Avenue',            allowPunctuation: true,

            'apt\\.': 'Apartment',            maxLength: 200

            'bldg\\.': 'Building',        });

            'brgy\\.': 'Barangay',

            'bg\\.': 'Barangay'        // Standardize common abbreviations

        };        const abbreviations = {

            'st\\.': 'Street',

        Object.entries(abbreviations).forEach(([abbr, full]) => {            'rd\\.': 'Road',

            const regex = new RegExp(`\\b${abbr}\\b`, 'gi');            'ave\\.': 'Avenue',

            sanitized = sanitized.replace(regex, full);            'apt\\.': 'Apartment',

        });            'bldg\\.': 'Building',

            'brgy\\.': 'Barangay',

        // Proper spacing after punctuation            'bg\\.': 'Barangay'

        sanitized = sanitized        };

            .replace(/,(?!\s)/g, ', ')

            .replace(/\.(?!\s)/g, '. ')        Object.entries(abbreviations).forEach(([abbr, full]) => {

            .replace(/\s+/g, ' ')            const regex = new RegExp(`\\b${abbr}\\b`, 'gi');

            .trim();            sanitized = sanitized.replace(regex, full);

        });

        // Capitalize first letter of each sentence

        sanitized = sanitized.replace(/(^\w|\.\s+\w)/g, letter => letter.toUpperCase());        // Proper spacing after punctuation

        sanitized = sanitized

        return sanitized;            .replace(/,(?!\s)/g, ', ')

    },            .replace(/\.(?!\s)/g, '. ')

            .replace(/\s+/g, ' ')

    // Sanitize and format GCash reference number            .trim();

    sanitizeGcashRef(ref) {

        let sanitized = String(ref || '')        // Capitalize first letter of each sentence

            .trim()        sanitized = sanitized.replace(/(^\w|\.\s+\w)/g, letter => letter.toUpperCase());

            .toUpperCase()

            .replace(/[^A-Z0-9]/g, '');        return sanitized;

    },

        // GCash reference format validation (letter followed by 12 numbers)

        if (/^[A-Z][0-9]{12}$/.test(sanitized)) {    // Sanitize and format GCash reference number

            return sanitized;    sanitizeGcashRef(ref) {

        }        let sanitized = String(ref || '')

            .trim()

        return '';            .toUpperCase()

    }            .replace(/[^A-Z0-9]/g, '');

};

        // GCash reference format validation (letter followed by 12 numbers)

class OrderValidator {        if (/^[A-Z][0-9]{12}$/.test(sanitized)) {

    constructor(form) {            return sanitized;

        this.form = form;        }

    }

        return '';

    async validateAmounts() {    },

        const cartItems = this.getCartItems();

        const amounts = this.getAmounts();    // Sanitize and format amount values

        const errors = [];    sanitizeAmount(amount) {

        let sanitized = String(amount || '')

        // Calculate expected totals            .replace(/[^\d.]/g, '')

        const calculatedSubtotal = cartItems.reduce(            .replace(/\.(?=.*\.)/g, ''); // Keep only last decimal point

            (sum, item) => sum + (item.price * item.quantity),

            0        const parts = sanitized.split('.');

        );        if (parts[1]) {

            parts[1] = parts[1].slice(0, 2); // Max 2 decimal places

        const calculatedTotal = calculatedSubtotal +         }

            (amounts.deliveryFee || 0) - 

            (amounts.discount || 0);        return parts.join('.');

    },

        // Validate subtotal

        if (Math.abs(calculatedSubtotal - amounts.subtotal) > 0.01) {    // Sanitize text content for display (e.g., order notes)

            errors.push(`Cart total mismatch (expected: ₱${calculatedSubtotal.toFixed(2)}, got: ₱${amounts.subtotal.toFixed(2)})`);    sanitizeText(text, maxLength = 500) {

            console.error('Subtotal mismatch:', {        return this.sanitizeGeneral(text, {

                calculated: calculatedSubtotal,            allowLetters: true,

                displayed: amounts.subtotal            allowNumbers: true,

            });            allowSpaces: true,

        }            allowPunctuation: true,

            maxLength: maxLength

        // Validate total        });

        if (Math.abs(calculatedTotal - amounts.total) > 0.01) {    }

            errors.push(`Order total mismatch (expected: ₱${calculatedTotal.toFixed(2)}, got: ₱${amounts.total.toFixed(2)})`);};

            console.error('Total mismatch:', {

                calculated: calculatedTotal,// Function to highlight input fields with errors

                displayed: amounts.totalfunction highlightError(element, message) {

            });    if (!element) return;

        }

    element.classList.add("error");

        // Validate individual amounts    element.setAttribute("aria-invalid", "true");

        if (amounts.subtotal < 0) errors.push('Subtotal cannot be negative');

        if (amounts.deliveryFee < 0) errors.push('Delivery fee cannot be negative');    try {

        if (amounts.discount < 0) errors.push('Discount cannot be negative');        // Disable submit button and show loading state

        if (amounts.total < 0) errors.push('Total amount cannot be negative');        submitButton.disabled = true;

        if (amounts.discount > amounts.subtotal) errors.push('Discount cannot exceed subtotal');        submitButton.textContent = 'Processing...';



        return {        // Validate all fields

            isValid: errors.length === 0,        const validationResult = await validateOrderForm(form);

            errors        if (!validationResult.isValid) {

        };            throw new Error(validationResult.errors.join('\n'));

    }        }



    getAmounts() {        // Prepare order data

        return {        const orderData = await prepareOrderData(form);

            subtotal: this.parseAmount('#subtotal'),

            deliveryFee: this.parseAmount('#delivery-fee'),        // Submit order

            discount: this.parseAmount('#discount'),        const response = await submitOrder(orderData);

            total: this.parseAmount('#total')

        };        // Process response

    }        await handleOrderResponse(response);



    parseAmount(selector) {    } catch (error) {

        const element = this.form.querySelector(selector);        handleOrderError(error);

        return element ? parseFloat(element.value || '0') : 0;    } finally {

    }        // Reset button state

        submitButton.disabled = false;

    getCartItems() {        submitButton.textContent = originalButtonText;

        try {    }

            return JSON.parse(localStorage.getItem('cart') || '[]');}

        } catch (e) {

            console.error('Failed to parse cart items:', e);// Attach validation and submission handling

            return [];document.addEventListener("DOMContentLoaded", function () {

        }    const checkoutForm = document.getElementById('checkout-form');

    }    if (checkoutForm) {

        checkoutForm.addEventListener('submit', handleOrderSubmission);

    validateGcashReference() {    }

        const element = this.form.querySelector('#gcashReference');

        if (!element) return { isValid: true };    // Attach validation to each input field

    Object.entries(validationRules).forEach(([fieldId, rules]) => {

        const value = element.value.trim();        const element = document.getElementById(fieldId);

        let isValid = true;        if (element) {

        let errorMessage = '';            element.addEventListener("blur", function () {

                validateField(this, rules);

        if (!value) {            });

            isValid = false;

            errorMessage = 'GCash reference number is required';            element.addEventListener("input", function () {

        } else if (!/^[A-Z][0-9]{12}$/.test(value.toUpperCase())) {                if (this.classList.contains("error")) {

            isValid = false;                    validateField(this, rules);

            errorMessage = 'Please enter a valid GCash reference number (1 letter followed by 12 numbers)';    // Create or update error message

        }    let errorDiv = element.nextElementSibling;

    if (!errorDiv || !errorDiv.classList.contains("error-message")) {

        if (!isValid) {        errorDiv = document.createElement("div");

            highlightError(element, errorMessage);        errorDiv.className = "error-message";

        } else {        errorDiv.setAttribute("role", "alert");

            removeError(element);        element.parentNode.insertBefore(errorDiv, element.nextSibling);

        }    }

    errorDiv.textContent = message;

        return { isValid, errorMessage };}

    }

// Function to remove error highlighting

    validateCustomerInfo() {function removeError(element) {

        const fields = {    if (!element) return;

            fullName: { 

                selector: '#fullName',    element.classList.remove("error");

                sanitizer: Sanitizer.sanitizeName.bind(Sanitizer),    element.setAttribute("aria-invalid", "false");

                message: 'Please enter a valid full name'

            },    // Remove error message if it exists

            email: {    const errorDiv = element.nextElementSibling;

                selector: '#email',    if (errorDiv && errorDiv.classList.contains("error-message")) {

                sanitizer: Sanitizer.sanitizeEmail.bind(Sanitizer),        errorDiv.remove();

                message: 'Please enter a valid email address'    }

            },}

            phone: {

                selector: '#phone',// Enhanced real-time field validation with sanitization

                sanitizer: Sanitizer.sanitizePhone.bind(Sanitizer),function validateField(element, validationRules, form = null) {

                message: 'Please enter a valid phone number (e.g., 09123456789)'  if (!element) return true;

            },

            address: {  // Get the form if not provided

                selector: '#address',  form = form || element.closest("form");

                sanitizer: Sanitizer.sanitizeAddress.bind(Sanitizer),  if (!form) return true;

                message: 'Please enter a valid delivery address'

            }  // Check if field is required based on current form state

        };  const isRequired = typeof validationRules.required === "function"

    ? validationRules.required(form)

        const errors = [];    : validationRules.required;

        const customerInfo = {};

  // Sanitize value first

        for (const [field, config] of Object.entries(fields)) {  let value = element.value;

            const element = this.form.querySelector(config.selector);  if (validationRules.sanitize) {

            if (!element) continue;    value = validationRules.sanitize(value);

    element.value = value; // Update field with sanitized value

            const sanitized = config.sanitizer(element.value);  }

            if (!sanitized) {

                errors.push(config.message);  let isValid = true;

                highlightError(element, config.message);  let errorMessage = "";

            } else {

                removeError(element);  try {

                customerInfo[field] = sanitized;    // Required field validation with improved empty check

            }    const trimmedValue = value.trim();

        }    if (isRequired && !trimmedValue) {

      isValid = false;

        return {      errorMessage = `${validationRules.name} is required`;

            isValid: errors.length === 0,    } 

            errors,    // If field has value (required or optional), validate it

            data: customerInfo    else if (trimmedValue) {

        };      // Length validation if applicable

    }      if (validationRules.minLength && trimmedValue.length < validationRules.minLength) {

}        isValid = false;

        errorMessage = `${validationRules.name} must be at least ${validationRules.minLength} characters`;

class OrderSubmissionHandler {      }

    constructor(form) {      else if (validationRules.maxLength && trimmedValue.length > validationRules.maxLength) {

        this.form = form;        isValid = false;

        this.validator = new OrderValidator(form);        errorMessage = `${validationRules.name} cannot exceed ${validationRules.maxLength} characters`;

        this.submitButton = form.querySelector('button[type="submit"]');      }

        this.originalButtonText = this.submitButton?.textContent || 'Place Order';      // Pattern validation

              else if (validationRules.pattern && !validationRules.pattern.test(trimmedValue)) {

        // Bind event handlers        isValid = false;

        this.handleSubmit = this.handleSubmit.bind(this);        errorMessage = validationRules.errorMessage;

        this.form.addEventListener('submit', this.handleSubmit);      }

    }      // Custom validation if defined and previous validations passed

      else if (isValid && validationRules.customValidation) {

    setButtonState(state) {        const customValidation = validationRules.customValidation(trimmedValue, form);

        if (!this.submitButton) return;        if (customValidation !== true) {

          isValid = false;

        const states = {          errorMessage = customValidation;

            initial: {        }

                text: this.originalButtonText,      }

                disabled: false    }

            },

            processing: {    // Handle conditional validation based on other field values

                text: 'Processing...',    if (isValid && validationRules.conditionalValidation) {

                disabled: true      const conditionalResult = validationRules.conditionalValidation(trimmedValue, form);

            },      if (conditionalResult !== true) {

            validating: {        isValid = false;

                text: 'Validating...',        errorMessage = conditionalResult;

                disabled: true      }

            },    }

            error: {

                text: this.originalButtonText,    // Set ARIA attributes for accessibility

                disabled: false    element.setAttribute("aria-invalid", !isValid);

            }    if (!isValid) {

        };      element.setAttribute("aria-describedby", `error-${element.id}`);

      highlightError(element, errorMessage);

        const newState = states[state] || states.initial;    } else {

        this.submitButton.textContent = newState.text;      element.removeAttribute("aria-describedby");

        this.submitButton.disabled = newState.disabled;      removeError(element);

    }    }



    async handleSubmit(event) {  } catch (error) {

        event.preventDefault();    console.error("Validation error:", error);

            isValid = false;

        try {    errorMessage = "An error occurred during validation";

            this.setButtonState('validating');    highlightError(element, errorMessage);

              }

            // Validate customer information

            const customerValidation = this.validator.validateCustomerInfo();  // Set data attribute for form validation state tracking

            if (!customerValidation.isValid) {  element.dataset.validationState = isValid ? "valid" : "invalid";

                throw new OrderValidationError(customerValidation.errors[0]);  

            }  return isValid;

}

            // Validate cart items

            const cartItems = this.validator.getCartItems();// Define validation rules with improved consistency and error handling

            if (!cartItems.length) {const validationRules = {

                throw new OrderValidationError('Your cart is empty');  fullName: {

            }    required: true,

    name: "Full Name",

            // Validate amounts    pattern: /^[a-zA-ZÀ-ÿ\s'\-]{2,100}$/,

            const amountValidation = await this.validator.validateAmounts();    errorMessage: "Please enter a valid name (2-100 characters, letters, spaces, hyphens, and apostrophes only)",

            if (!amountValidation.isValid) {    sanitize: (value) => {

                throw new OrderValidationError(amountValidation.errors[0]);      // Remove extra spaces and standardize special characters

            }      return value.trim()

        .replace(/\s+/g, " ")

            // Get payment method        .replace(/['']/g, "'")

            const paymentMethod = this.form.querySelector('input[name="paymentMethod"]:checked');        .replace(/[""]/g, '"');

            if (!paymentMethod) {    },

                throw new OrderValidationError('Please select a payment method');    customValidation: (value) => {

            }      const words = value.trim().split(/\s+/);

      

            // Validate GCash reference if applicable      // Validate minimum word count

            if (paymentMethod.value === 'gcash') {      if (words.length < 2) {

                const gcashValidation = this.validator.validateGcashReference();        return "Please enter both first and last name";

                if (!gcashValidation.isValid) {      }

                    throw new OrderValidationError(gcashValidation.errorMessage);      

                }      // Check each word length

            }      const invalidWords = words.filter(word => word.length < 2);

      if (invalidWords.length > 0) {

            // Prepare order data        return "Each name part must be at least 2 characters long";

            const orderData = {      }

                customer: customerValidation.data,

                items: cartItems,      // Validate character distribution

                payment: {      const letterCount = value.replace(/[^a-zA-ZÀ-ÿ]/g, "").length;

                    method: paymentMethod.value,      if (letterCount < value.length * 0.5) {

                    reference: this.form.querySelector('#gcashReference')?.value || null        return "Name must consist primarily of letters";

                },      }

                amounts: this.validator.getAmounts()

            };      return true;

    }

            // Submit order  },

            this.setButtonState('processing');  email: {

            const response = await fetch('process_order.php', {    required: true,

                method: 'POST',    name: "Email",

                headers: {    pattern: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,

                    'Content-Type': 'application/json',    errorMessage: "Please enter a valid email address",

                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''    sanitize: (value) => value.trim().toLowerCase(),

                },    customValidation: (value) => {

                body: JSON.stringify(orderData)      // Maximum length check

            });      const maxLength = 100;

      if (value.length > maxLength) {

            if (!response.ok) {        return `Email address cannot exceed ${maxLength} characters`;

                throw new Error(`Server error: ${response.status}`);      }

            }

      // Check for common typos in domain

            const result = await response.json();      const commonDomains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com'];

            if (!result.success) {      const [localPart, domain] = value.split('@');

                throw new Error(result.message || 'Failed to process order');      

            }      if (domain) {

        const similarDomain = commonDomains.find(d => 

            // Show success and redirect          d !== domain && 

            showNotification('success', 'Order placed successfully!');          (d.length - domain.length <= 3) && 

            setTimeout(() => {          (d.includes(domain) || domain.includes(d))

                window.location.href = `order_confirmation.php?order_id=${result.orderId}`;        );

            }, 1500);        

        if (similarDomain) {

        } catch (error) {          return `Did you mean ${localPart}@${similarDomain}?`;

            this.handleError(error);        }

        }      }

    }

      // Check local part format

    handleError(error) {      if (localPart && !/^[a-zA-Z0-9][a-zA-Z0-9._%+-]{0,63}$/.test(localPart)) {

        console.error('Order submission error:', error);        return "Email username can only contain letters, numbers, and certain special characters";

        this.setButtonState('error');      }

        

        const message = error instanceof OrderValidationError      return true;

            ? error.message    }

            : 'An error occurred while processing your order. Please try again.';  },

          phone: {

        showNotification('error', message);    required: true,

    }    name: "Phone Number",

}    pattern: /^(09|\+639)[0-9]{9}$/,

    errorMessage: "Please enter a valid Philippine mobile number (format: 09XX-XXX-XXXX or +639XX-XXX-XXXX)",

// UI helper functions    sanitize: (value) => {

function highlightError(element, message) {      // Standardize format and remove non-numeric characters

    element.classList.add('is-invalid');      value = value.replace(/[\s\-\(\)]/g, "");

    const feedback = element.nextElementSibling;      if (value.startsWith("+63")) {

    if (feedback?.classList.contains('invalid-feedback')) {        value = "0" + value.slice(3);

        feedback.textContent = message;      }

    } else {      return value;

        const errorDiv = document.createElement('div');    },

        errorDiv.className = 'invalid-feedback';    customValidation: (value) => {

        errorDiv.textContent = message;      const sanitized = value.replace(/[^0-9]/g, "");

        element.parentNode.insertBefore(errorDiv, element.nextSibling);      

    }      // Length validation

}      if (sanitized.length !== 11) {

        return "Phone number must be exactly 11 digits";

function removeError(element) {      }

    element.classList.remove('is-invalid');

    const feedback = element.nextElementSibling;      // Network prefix validation

    if (feedback?.classList.contains('invalid-feedback')) {      const prefix = sanitized.slice(0, 4);

        feedback.textContent = '';      const validPrefixes = ['0905', '0906', '0907', '0908', '0909', '0910', '0911', 

    }                            '0912', '0913', '0914', '0915', '0916', '0917', '0918', 

}                            '0919', '0920', '0921', '0922', '0923', '0924', '0925', 

                            '0926', '0927', '0928', '0929', '0930', '0931', '0932', 

// Initialize form handling                            '0933', '0934', '0935', '0936', '0937', '0938', '0939', 

document.addEventListener('DOMContentLoaded', () => {                            '0940', '0941', '0942', '0943', '0944', '0945', '0946', 

    const checkoutForm = document.getElementById('checkoutForm');                            '0947', '0948', '0949', '0950', '0951', '0952', '0953', 

    if (checkoutForm) {                            '0954', '0955', '0956', '0957', '0958', '0959', '0960', 

        new OrderSubmissionHandler(checkoutForm);                            '0961', '0962', '0963', '0964', '0965', '0966', '0967', 

    }                            '0968', '0969', '0970', '0971', '0972', '0973', '0974', 

});                            '0975', '0976', '0977', '0978', '0979', '0980', '0981', 
                            '0982', '0983', '0984', '0985', '0986', '0987', '0988', 
                            '0989', '0990', '0991', '0992', '0993', '0994', '0995', 
                            '0996', '0997', '0998', '0999'];

      if (!validPrefixes.includes(prefix)) {
        return "Invalid network prefix. Please check your number.";
      }

      return true;
    }
  },
  address: {
    required: true,
    name: "Delivery Address",
    pattern: /^[a-zA-Z0-9\s,.\-#()/&]{10,200}$/,
    errorMessage: "Please enter a complete delivery address (10-200 characters)",
    sanitize: (value) => {
      return value.trim()
        .replace(/\s+/g, " ")
        .replace(/,\s*/g, ", ")
        .replace(/\s*,\s*/g, ", ")
        .replace(/\s*\.\s*/g, ". ");
    },
    customValidation: (value) => {
      const addressLower = value.toLowerCase();
      
      // Check for required components with variations
      const streetIndicators = ['street', 'st.', 'st', 'road', 'rd.', 'rd', 'avenue', 'ave.', 'ave'];
      const barangayIndicators = ['barangay', 'brgy.', 'brgy', 'bg.', 'bg'];
      const cityIndicators = ['city', 'municipality', 'town'];

      const hasStreet = streetIndicators.some(ind => addressLower.includes(ind));
      const hasBarangay = barangayIndicators.some(ind => addressLower.includes(ind));
      const hasCity = cityIndicators.some(ind => addressLower.includes(ind));

      const missing = [];
      if (!hasStreet) missing.push('street name');
      if (!hasBarangay) missing.push('barangay');
      if (!hasCity) missing.push('city');

      if (missing.length > 0) {
        return `Please include ${missing.join(', ')} in the address`;
      }

      // Check for suspicious patterns
      if (value.match(/[A-Z]{5,}/)) {
        return "Please avoid using all capital letters";
      }

      // Check for proper number format
      if (value.match(/(\d+)(st|nd|rd|th)/i) && !value.match(/[0-9]+(st|nd|rd|th)\s+(Street|St\.)/i)) {
        return "Please format street numbers properly (e.g., '123 Street' instead of '123rd')";
      }

      return true;
    }
  },
  deliveryInstructions: {
    required: false,
    name: "Delivery Instructions",
    pattern: /^[a-zA-Z0-9\s,.\-#()/&]{0,500}$/,
    errorMessage: "Special instructions cannot exceed 500 characters",
    sanitize: (value) => value.trim().replace(/\s+/g, " "),
    customValidation: (value) => {
      if (!value) return true;

      // Check for excessive punctuation
      if (value.match(/[!?.,]{2,}/)) {
        return "Please avoid using multiple punctuation marks in sequence";
      }

      // Check for proper capitalization
      if (value.match(/[.!?]\s+[a-z]/)) {
        return "Please capitalize the first letter of each sentence";
      }

      // Check for prohibited content
      const prohibitedWords = ['asap', 'urgent', 'immediately', 'rush'];
      const found = prohibitedWords.find(word => value.toLowerCase().includes(word));
      if (found) {
        return `Please avoid using demanding terms like "${found}". All orders are processed as quickly as possible.`;
      }

      return true;
    }
  },
  gcashNumber: {
    required: (form) => form.querySelector('input[name="paymentMethod"]:checked')?.value === "gcash",
    name: "GCash Number",
    pattern: /^(09|\+639)[0-9]{9}$/,
    errorMessage: "Please enter a valid GCash number (11 digits starting with 09)",
    sanitize: (value) => {
      value = value.replace(/[\s\-\(\)]/g, "");
      if (value.startsWith("+63")) {
        value = "0" + value.slice(3);
      }
      return value;
    },
    customValidation: (value, form) => {
      if (!form.querySelector('input[name="paymentMethod"]:checked')?.value === "gcash") {
        return true;
      }

      const sanitized = value.replace(/[^0-9]/g, "");
      
      if (sanitized.length !== 11) {
        return "GCash number must be exactly 11 digits";
      }

      // Verify it's different from contact number
      const contactPhone = document.getElementById('phone')?.value;
      if (contactPhone && sanitized === contactPhone.replace(/[^0-9]/g, "")) {
        return "Please provide the GCash account number, which may be different from your contact number";
      }

      return true;
    }
  },
  gcashReference: {
    required: (form) => form.querySelector('input[name="paymentMethod"]:checked')?.value === "gcash",
    name: "GCash Reference",
    pattern: /^[A-Z0-9]{13}$/,
    errorMessage: "Please enter a valid GCash reference number (13 characters)",
    sanitize: (value) => value.trim().toUpperCase(),
    customValidation: (value, form) => {
      if (!form.querySelector('input[name="paymentMethod"]:checked')?.value === "gcash") {
        return true;
      }

      if (!value) return true;

      // Validate GCash reference format (typically starts with a letter followed by numbers)
      if (!/^[A-Z][0-9]{12}$/.test(value)) {
        return "GCash reference should start with a letter followed by 12 numbers";
      }

      // Check for common typos (0 vs O, 1 vs I)
      if (value.slice(1).includes('O') || value.slice(1).includes('I')) {
        return "Please check for typos: use '0' (zero) instead of 'O' and '1' (one) instead of 'I' in the reference number";
      }

      return true;
    }
  }
};

// Form validation initialization and event handling
class FormValidator {
    constructor(formId) {
        this.form = document.getElementById(formId);
        this.validationState = new Map();
        this.submitButton = null;
        
        if (this.form) {
            this.initialize();
        } else {
            console.error(`Form with ID '${formId}' not found`);
        }
    }

    initialize() {
        try {
            // Initialize form elements
            this.submitButton = this.form.querySelector('button[type="submit"]');
            this.setupFieldValidation();
            this.setupPaymentMethodHandling();
            this.setupFormSubmission();
            
            // Initialize validation states
            Object.keys(validationRules).forEach(fieldId => {
                const element = document.getElementById(fieldId);
                if (element) {
                    this.validationState.set(fieldId, false);
                }
            });

            console.log('Form validation initialized successfully');
        } catch (error) {
            console.error('Error initializing form validation:', error);
        }
    }

    setupFieldValidation() {
        Object.entries(validationRules).forEach(([fieldId, rules]) => {
            const element = document.getElementById(fieldId);
            if (!element) {
                console.warn(`Field ${fieldId} not found in the form`);
                return;
            }

            // Validate on blur
            element.addEventListener("blur", () => {
                const isValid = validateField(element, rules, this.form);
                this.validationState.set(fieldId, isValid);
                this.updateFormState();
            });

            // Handle input changes
            element.addEventListener("input", () => {
                if (element.dataset.validationState === "invalid") {
                    const isValid = validateField(element, rules, this.form);
                    this.validationState.set(fieldId, isValid);
                    this.updateFormState();
                }
            });

            // Set initial aria attributes
            element.setAttribute('aria-required', rules.required === true);
            element.setAttribute('aria-invalid', 'false');
        });
    }

    setupPaymentMethodHandling() {
        const paymentMethodInputs = this.form.querySelectorAll('input[name="paymentMethod"]');
        const gcashFields = ['gcashNumber', 'gcashReference']
            .map(id => document.getElementById(id))
            .filter(Boolean);

        if (!paymentMethodInputs.length) {
            console.warn('No payment method inputs found');
            return;
        }

        paymentMethodInputs.forEach(input => {
            input.addEventListener("change", () => {
                const isGcash = input.value === "gcash";
                this.handleGcashFieldsVisibility(isGcash, gcashFields);
            });
        });

        // Set initial state
        const selectedMethod = this.form.querySelector('input[name="paymentMethod"]:checked');
        if (selectedMethod) {
            const isGcash = selectedMethod.value === "gcash";
            this.handleGcashFieldsVisibility(isGcash, gcashFields);
        }
    }

    handleGcashFieldsVisibility(isGcash, gcashFields) {
        gcashFields.forEach(field => {
            if (field) {
                const fieldContainer = field.closest('.form-group') || field.parentElement;
                if (fieldContainer) {
                    fieldContainer.style.display = isGcash ? 'block' : 'none';
                    field.required = isGcash;
                    field.setAttribute('aria-required', isGcash);
                    
                    if (!isGcash) {
                        removeError(field);
                        field.value = '';
                        this.validationState.set(field.id, true);
                    } else {
                        this.validationState.set(field.id, false);
                    }
                }
            }
        });
        this.updateFormState();
    }

    setupFormSubmission() {
        if (this.submitButton) {
            this.submitButton.setAttribute('aria-disabled', 'true');
        }
        
        this.form.addEventListener('submit', async (event) => {
            event.preventDefault();
            
            if (await this.validateAllFields()) {
                this.handleOrderSubmission(event);
            } else {
                this.showFormError('Please correct the errors before submitting.');
            }
        });
    }

    async validateAllFields() {
        let isValid = true;
        const promises = [];

        for (const [fieldId, rules] of Object.entries(validationRules)) {
            const element = document.getElementById(fieldId);
            if (element) {
                // Skip validation for hidden GCash fields when not using GCash
                if (fieldId.startsWith('gcash')) {
                    const isGcashSelected = this.form
                        .querySelector('input[name="paymentMethod"]:checked')?.value === "gcash";
                    if (!isGcashSelected) continue;
                }

                promises.push(
                    Promise.resolve(validateField(element, rules, this.form))
                        .then(fieldValid => {
                            this.validationState.set(fieldId, fieldValid);
                            if (!fieldValid) isValid = false;
                        })
                );
            }
        }

        await Promise.all(promises);
        return isValid;
    }

    updateFormState() {
        if (!this.submitButton) return;

        const isFormValid = Array.from(this.validationState.values()).every(state => state);
        this.submitButton.disabled = !isFormValid;
        this.submitButton.setAttribute('aria-disabled', (!isFormValid).toString());
    }

    showFormError(message) {
        const errorContainer = document.getElementById('form-error-container') || 
            this.createErrorContainer();
        
        errorContainer.textContent = message;
        errorContainer.style.display = 'block';
        
        setTimeout(() => {
            errorContainer.style.display = 'none';
        }, 5000);
    }

    createErrorContainer() {
        const container = document.createElement('div');
        container.id = 'form-error-container';
        container.className = 'form-error-message';
        container.setAttribute('role', 'alert');
        this.form.insertBefore(container, this.form.firstChild);
        return container;
    }
}

// Initialize form validation when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
    const formValidator = new FormValidator('checkoutForm');
});
    gcashFields.forEach(field => {
      if (field) {
        field.disabled = !isGcash;
        if (!isGcash) {
          removeError(field);
          field.value = '';
        }
      }
    });
  }

  // Form submission handler
  class OrderSubmissionHandler {
    constructor(form) {
        this.form = form;
        this.submitButton = form.querySelector('button[type="submit"]');
        this.originalButtonText = this.submitButton?.textContent || 'Place Order';
        this.setupEventListeners();
    }

    setupEventListeners() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    async handleSubmit(e) {
        e.preventDefault();
        
        // Clear previous errors
        document.querySelectorAll('.error-message').forEach(el => el.remove());
        
        try {
            await this.processOrder();
        } catch (error) {
            this.handleError(error);
        }
    }

    async processOrder() {
        this.setButtonState('processing');

        // Validate form
        const validationResult = await this.validateForm();
        if (!validationResult.isValid) {
            throw new OrderValidationError(validationResult.errors);
        }

        // Prepare and submit order
        const orderData = await this.prepareOrderData();
        const response = await this.submitOrder(orderData);
        await this.handleResponse(response);
    }

    async validateForm() {
        const errors = [];
        let isValid = true;

        // Field validation
        for (const [fieldId, rules] of Object.entries(validationRules)) {
            const element = this.form.querySelector(`#${fieldId}`);
            if (element && !validateField(element, rules, this.form)) {
                isValid = false;
                errors.push(`Invalid ${rules.name.toLowerCase()}`);
            }
        }

        // Cart validation
        const cartValidation = await this.validateCart();
        if (!cartValidation.isValid) {
            isValid = false;
            errors.push(...cartValidation.errors);
        }

        // Amount validation
        const amountValidation = await this.validateAmounts();
        if (!amountValidation.isValid) {
            isValid = false;
            errors.push(...amountValidation.errors);
        }

        return { isValid, errors };
    }

    async validateCart() {
        const cartItems = this.getCartItems();
        const errors = [];
        
        if (!cartItems.length) {
            return { isValid: false, errors: ['Your cart is empty'] };
        }

        for (const item of cartItems) {
            if (!this.validateCartItem(item)) {
                errors.push(`Invalid item data: ${item.name || 'Unknown product'}`);
            }
        }

        return {
            isValid: errors.length === 0,
            errors
        };
    }

    validateCartItem(item) {
        return (
            item.product_id &&
            item.name &&
            typeof item.price === 'number' &&
            item.price >= 0 &&
            Number.isInteger(item.quantity) &&
            item.quantity > 0
        );
    }

    getCartItems() {
        try {
            return JSON.parse(localStorage.getItem('cart') || '[]');
        } catch (e) {
            console.error('Failed to parse cart items:', e);
            return [];
        }
    }

    setButtonState(state) {
        if (!this.submitButton) return;

        const states = {
            initial: {
                text: this.originalButtonText,
                disabled: false
            },
            processing: {
                text: 'Processing...',
                disabled: true
            },
            validating: {
                text: 'Validating...',
                disabled: true
            },
            error: {
                text: this.originalButtonText,
                disabled: false
            }
        };

        const newState = states[state] || states.initial;
        this.submitButton.textContent = newState.text;
        this.submitButton.disabled = newState.disabled;
    }

    handleError(error) {
        console.error('Order submission error:', error);
        this.setButtonState('error');
        
        const message = error instanceof OrderValidationError
            ? error.message
            : 'An error occurred while processing your order. Please try again.';
        
        showNotification('error', message);
    }
}

class OrderValidator {
    constructor(form) {
        this.form = form;
    }

    async validateAmounts() {
        const cartItems = this.getCartItems();
        const amounts = this.getAmounts();
        const errors = [];

        // Calculate expected totals
        const calculatedSubtotal = cartItems.reduce(
            (sum, item) => sum + (item.price * item.quantity),
            0
        );

        const calculatedTotal = calculatedSubtotal + 
            (amounts.deliveryFee || 0) - 
            (amounts.discount || 0);

        // Validate subtotal
        if (Math.abs(calculatedSubtotal - amounts.subtotal) > 0.01) {
            errors.push(`Cart total mismatch (expected: ₱${calculatedSubtotal.toFixed(2)}, got: ₱${amounts.subtotal.toFixed(2)})`);
            console.error('Subtotal mismatch:', {
                calculated: calculatedSubtotal,
                displayed: amounts.subtotal
            });
        }

        // Validate total
        if (Math.abs(calculatedTotal - amounts.total) > 0.01) {
            errors.push(`Order total mismatch (expected: ₱${calculatedTotal.toFixed(2)}, got: ₱${amounts.total.toFixed(2)})`);
            console.error('Total mismatch:', {
                calculated: calculatedTotal,
                displayed: amounts.total
            });
        }

        // Validate individual amounts
        if (amounts.subtotal < 0) errors.push('Subtotal cannot be negative');
        if (amounts.deliveryFee < 0) errors.push('Delivery fee cannot be negative');
        if (amounts.discount < 0) errors.push('Discount cannot be negative');
        if (amounts.total < 0) errors.push('Total amount cannot be negative');
        if (amounts.discount > amounts.subtotal) errors.push('Discount cannot exceed subtotal');

        return {
            isValid: errors.length === 0,
            errors
        };
    }

    getAmounts() {
        return {
            subtotal: this.parseAmount('#subtotal'),
            deliveryFee: this.parseAmount('#delivery-fee'),
            discount: this.parseAmount('#discount'),
            total: this.parseAmount('#total')
        };
    }

    parseAmount(selector) {
        const element = this.form.querySelector(selector);
        return element ? parseFloat(element.value || '0') : 0;
    }

    getCartItems() {
        try {
            return JSON.parse(localStorage.getItem('cart') || '[]');
        } catch (e) {
            console.error('Failed to parse cart items:', e);
            return [];
        }
    }

    validateGcashReference() {
        const element = this.form.querySelector('#gcashReference');
        if (!element) return { isValid: true };

        const value = element.value.trim();
        let isValid = true;
        let errorMessage = '';

        if (!value) {
            isValid = false;
            errorMessage = 'GCash reference number is required';
        } else if (!/^[A-Z][0-9]{12}$/.test(value.toUpperCase())) {
            isValid = false;
            errorMessage = 'Please enter a valid GCash reference number (1 letter followed by 12 numbers)';
        }

        if (!isValid) {
            highlightError(element, errorMessage);
        } else {
            removeError(element);
        }

        return { isValid, errorMessage };
    }
}

// Enhanced order validation and submission handler
async function validateAndPrepareOrderData(form) {
  try {
    // Disable submit button and show loading state
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.textContent = "Validating...";

    // Validate all fields
    let isValid = true;
    const errors = [];

    // Validate each field defined in validationRules
    for (const [fieldId, rules] of Object.entries(validationRules)) {
      const element = form.querySelector(`#${fieldId}`);
      if (element && !validateField(element, rules, form)) {
        isValid = false;
        errors.push(`Invalid ${rules.name.toLowerCase()}`);
      }
    }

    // Validate payment selection
    const selectedPayment = form.querySelector(
      'input[name="paymentMethod"]:checked'
    );
    if (!selectedPayment) {
      isValid = false;
      errors.push("Please select a payment method");
    }

    // Validate cart items with detailed checks
    const cartItems = JSON.parse(
      document.getElementById("cart-data")?.value || "[]"
    );
    if (!cartItems.length) {
      isValid = false;
      errors.push("Your cart is empty");
    } else {
      // Validate each cart item
      cartItems.forEach((item) => {
        if (!item.product_id || !item.name || !item.price || !item.quantity) {
          isValid = false;
          errors.push(`Invalid item data: ${item.name || "Unknown product"}`);
        }
        if (item.quantity < 1 || !Number.isInteger(item.quantity)) {
          isValid = false;
          errors.push(`Invalid quantity for: ${item.name}`);
        }
        if (item.price < 0 || isNaN(item.price)) {
          isValid = false;
          errors.push(`Invalid price for: ${item.name}`);
        }
      });

      // Validate cart total matches item totals
      const calculatedItemsTotal = cartItems.reduce(
        (sum, item) => sum + item.price * item.quantity,
        0
      );
      if (Math.abs(calculatedItemsTotal - amounts.subtotal) > 0.01) {
        isValid = false;
        errors.push("Cart total mismatch");
        console.error("Cart total mismatch:", {
          calculated: calculatedItemsTotal,
          displayed: amounts.subtotal,
        });
      }
    } // Initialize order validator and handler
const validator = new OrderValidator(form);
const handler = new OrderSubmissionHandler(form);

// Validate amounts using the validator
const amountValidation = await validator.validateAmounts();
if (!amountValidation.isValid) {
    throw new OrderValidationError(amountValidation.errors[0]);
}

    if (!isValid) {
      throw new Error(`Please correct the following:\n${errors.join("\n")}`);
    }

    // Update button state for submission
    submitButton.textContent = "Processing Order...";

    // Prepare order data
    const orderData = {
      customerInfo: {
        name: form.querySelector("#fullName").value,
        email: form.querySelector("#email").value,
        phone: form.querySelector("#phone").value,
        address: form.querySelector("#address").value,
      },
      payment: {
        method: selectedPayment.value,
      },
      items: cartItems,
      amounts: amounts,
    };

    // Add GCash details if applicable
    if (orderData.payment.method === "gcash") {
      orderData.payment.gcashNumber = form.querySelector("#gcashNumber").value;
      orderData.payment.gcashReference =
        form.querySelector("#gcashReference").value;
    }

    // Submit order to backend
    const response = await fetch("process_order.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token":
          document.querySelector('meta[name="csrf-token"]')?.content || "",
      },
      body: JSON.stringify(orderData),
    });

    if (!response.ok) {
      throw new Error(`Server error: ${response.status}`);
    }

    const result = await response.json();

    if (!result.success) {
      throw new Error(result.message || "Failed to process order");
    }

    // Show success notification
    showNotification("success", "Order placed successfully!");

    // Redirect to confirmation page
    setTimeout(() => {
      window.location.href = `order_confirmation.php?order_id=${result.orderId}`;
    }, 1500);
  } catch (error) {
    // Show error notification
    showNotification("error", error.message);
    console.error("Order processing error:", error);

    // Reset submit button
    submitButton.disabled = false;
    submitButton.textContent = originalButtonText;

    throw error; // Re-throw for form handler
  }
}
