/**
 * AjaxManager - Handles AJAX communication for K-Food Delights
 * Requires: ui-utilities.js
 */
var AjaxManager = {
    config: {
        baseUrl: "",
        pollingInterval: 5000, // 5 seconds default
        maxRetries: 3,
        retryDelay: 2000
    },

    activePolls: {},
    eventHandlers: {},

    /**
     * Initialize the AjaxManager with configuration
     * @param {Object} config Configuration options
     */
    init: function(config) {
        for (var key in config) {
            if (config.hasOwnProperty(key)) {
                this.config[key] = config[key];
            }
        }
    },

    /**
     * Makes an AJAX request with automatic retry on failure
     * @param {string} url The URL to request
     * @param {Object} options Request options
     */
    request: function(url, options, callback) {
        var self = this;
        options = options || {};
        var retries = 0;
        var maxRetries = options.maxRetries || this.config.maxRetries;

        function makeRequest() {
            var xhr = new XMLHttpRequest();
            xhr.open(options.method || 'GET', self.config.baseUrl + url, true);

            // Set headers
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            if (options.headers) {
                for (var header in options.headers) {
                    if (options.headers.hasOwnProperty(header)) {
                        xhr.setRequestHeader(header, options.headers[header]);
                    }
                }
            }

            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        callback(null, response);
                    } catch (e) {
                        callback(new Error('Invalid JSON response'));
                    }
                } else {
                    handleError(new Error('HTTP Error: ' + xhr.status));
                }
            };

            xhr.onerror = function() {
                handleError(new Error('Network Error'));
            };

            xhr.send(options.body ? JSON.stringify(options.body) : null);
        }

        function handleError(error) {
            console.error('AJAX request failed (attempt ' + (retries + 1) + '):', error);
            
            if (retries < maxRetries) {
                retries++;
                setTimeout(makeRequest, self.config.retryDelay * retries);
            } else {
                callback(error);
            }
        }

        makeRequest();
    },

    /**
     * Makes a GET request
     * @param {string} url The URL to request
     * @param {Object} options Request options
     */
    get: function(url, options, callback) {
        if (typeof options === 'function') {
            callback = options;
            options = {};
        }
        options.method = 'GET';
        this.request(url, options, callback);
    },

    /**
     * Makes a POST request
     * @param {string} url The URL to request
     * @param {Object} data The data to send
     * @param {Object} options Request options
     */
    post: function(url, data, options, callback) {
        if (typeof options === 'function') {
            callback = options;
            options = {};
        }
        options.method = 'POST';
        options.body = data;
        this.request(url, options, callback);
    },

    /**
     * Starts polling an endpoint at regular intervals
     * @param {string} key Unique identifier for this polling operation
     * @param {string} url The URL to poll
     * @param {number} interval Optional custom interval
     */
    startPolling: function(key, url, interval) {
        if (this.activePolls[key]) {
            console.warn('Polling already active for key: ' + key);
            return;
        }

        var self = this;
        interval = interval || this.config.pollingInterval;

        function poll() {
            self.get(url, function(error, data) {
                if (error) {
                    self.emit('error', { key: key, error: error });
                } else {
                    self.emit(key, data);
                }
            });
        }

        // Initial poll
        poll();

        // Set up interval
        this.activePolls[key] = setInterval(poll, interval);
    },

    /**
     * Stops polling for a specific key
     * @param {string} key The polling key to stop
     */
    stopPolling: function(key) {
        if (this.activePolls[key]) {
            clearInterval(this.activePolls[key]);
            delete this.activePolls[key];
        }
    },

    /**
     * Registers an event handler
     * @param {string} event Event name
     * @param {Function} handler Event handler function
     */
    on: function(event, handler) {
        if (!this.eventHandlers[event]) {
            this.eventHandlers[event] = [];
        }
        this.eventHandlers[event].push(handler);
    },

    /**
     * Removes an event handler
     * @param {string} event Event name
     * @param {Function} handler Event handler function to remove
     */
    off: function(event, handler) {
        if (!this.eventHandlers[event]) return;
        
        var index = this.eventHandlers[event].indexOf(handler);
        if (index !== -1) {
            this.eventHandlers[event].splice(index, 1);
        }
    },

    /**
     * Emits an event
     * @param {string} event Event name
     * @param {*} data Event data
     */
    emit: function(event, data) {
        if (!this.eventHandlers[event]) return;
        
        this.eventHandlers[event].forEach(function(handler) {
            setTimeout(function() {
                handler(data);
            }, 0);
        });
    },

    /**
     * Handles a form submission with file uploads
     * @param {HTMLFormElement} form The form to submit
     * @param {Object} options Additional options
     * @param {Function} callback Callback function
     */
    submitForm: function(form, options, callback) {
        if (typeof options === 'function') {
            callback = options;
            options = {};
        }

        var formData = new FormData(form);
        var self = this;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', this.config.baseUrl + (options.url || form.action), true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        if (options.headers) {
            for (var header in options.headers) {
                if (options.headers.hasOwnProperty(header)) {
                    xhr.setRequestHeader(header, options.headers[header]);
                }
            }
        }

        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    callback(null, response);
                } catch (e) {
                    callback(new Error('Invalid JSON response'));
                }
            } else {
                callback(new Error('HTTP Error: ' + xhr.status));
            }
        };

        xhr.onerror = function() {
            callback(new Error('Network Error'));
        };

        if (options.onProgress && xhr.upload) {
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    var percentage = (e.loaded / e.total) * 100;
                    options.onProgress(percentage);
                }
            };
        }

        xhr.send(formData);
    }
};

// Add default error handler that uses UIUtilities
AjaxManager.on('error', function(error) {
    if (window.UIUtilities) {
        UIUtilities.showError(error.message || 'An error occurred while communicating with the server');
    } else {
        console.error('Ajax Error:', error);
    }
});