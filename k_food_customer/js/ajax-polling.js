/**
 * AjaxPolling module for handling real-time-like updates through AJAX
 * ES5-compatible implementation
 */
var AjaxPolling = (function () {
  "use strict";

  // Private variables
  var intervals = {};
  var defaultConfig = {
    interval: 10000, // 10 seconds default
    maxAttempts: 3,
    backoffMultiplier: 1.5,
  };

  /**
   * Creates a new polling instance
   * @param {string} id - Unique identifier for this polling instance
   * @param {Object} config - Configuration object
   * @param {Function} callback - Function to call on each poll
   * @returns {Object} Polling control methods
   */
  function createPoll(id, config, callback) {
    if (!id || !callback) {
      throw new Error("ID and callback are required");
    }

    // Stop any existing poll with this ID
    if (intervals[id]) {
      stopPoll(id);
    }

    var settings = Object.assign({}, defaultConfig, config || {});
    var attempts = 0;
    var currentInterval = settings.interval;

    function executePoll() {
      callback()
        .then(function (response) {
          // Reset attempts and interval on success
          attempts = 0;
          currentInterval = settings.interval;
        })
        .catch(function (error) {
          attempts++;
          if (attempts >= settings.maxAttempts) {
            stopPoll(id);
            console.error(
              "Polling stopped after",
              attempts,
              "failed attempts:",
              error
            );
            return;
          }
          // Increase interval for next attempt
          currentInterval = Math.min(
            currentInterval * settings.backoffMultiplier,
            settings.interval *
              Math.pow(settings.backoffMultiplier, settings.maxAttempts)
          );
        });
    }

    intervals[id] = setInterval(executePoll, settings.interval);

    return {
      stop: function () {
        stopPoll(id);
      },
      updateInterval: function (newInterval) {
        if (intervals[id]) {
          clearInterval(intervals[id]);
          settings.interval = newInterval;
          currentInterval = newInterval;
          intervals[id] = setInterval(executePoll, newInterval);
        }
      },
    };
  }

  /**
   * Stops a polling instance
   * @param {string} id - ID of the polling instance to stop
   */
  function stopPoll(id) {
    if (intervals[id]) {
      clearInterval(intervals[id]);
      delete intervals[id];
    }
  }

  /**
   * Stops all polling instances
   */
  function stopAllPolls() {
    Object.keys(intervals).forEach(function (id) {
      stopPoll(id);
    });
  }

  // Public API
  return {
    create: createPoll,
    stop: stopPoll,
    stopAll: stopAllPolls,
  };
})();
