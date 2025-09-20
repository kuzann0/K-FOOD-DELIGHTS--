// Live reload functionality
(function () {
  let isConnected = false;
  const MAX_RETRIES = 3;
  let retryCount = 0;
  let ws = null;

  function connect() {
    if (isConnected || retryCount >= MAX_RETRIES) return;

    try {
      // Use proper WebSocket URL format with single forward slash
      const protocol = window.location.protocol === "https:" ? "wss:" : "ws:";
      const host = "127.0.0.1:5500";
      // Fix: Remove double slashes and ensure correct WebSocket URL format
      ws = new WebSocket(`${protocol}//${host}/ws`);

      ws.onopen = () => {
        console.log("Development server connected");
        isConnected = true;
        retryCount = 0;
      };

      ws.onclose = () => {
        isConnected = false;
        if (retryCount < MAX_RETRIES) {
          retryCount++;
          setTimeout(connect, 1000 * retryCount); // Exponential backoff
        }
      };

      ws.onerror = (error) => {
        console.log("WebSocket error:", error);
        ws.close();
      };

      // Handle reload message
      ws.onmessage = (event) => {
        if (event.data === "reload") {
          window.location.reload();
        }
      };
    } catch (error) {
      console.log("WebSocket connection error:", error);
    }
  }

  // Initialize connection
  connect();
})();
