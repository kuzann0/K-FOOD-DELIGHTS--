// Live reload functionality
(function () {
  let isConnected = false;
  const MAX_RETRIES = 3;
  let retryCount = 0;
  let ws = null;

  // WebSocket configuration
  const WS_PORT = "8080"; // WebSocket server port
  const WS_PATH = "/ws"; // WebSocket endpoint path

  function connect() {
    if (isConnected || retryCount >= MAX_RETRIES) return;

    try {
      const protocol = window.location.protocol === "https:" ? "wss:" : "ws:";
      const host = "127.0.0.1";
      const wsUrl = `${protocol}//${host}:${WS_PORT}${WS_PATH}`;

      console.log("Connecting to WebSocket server at:", wsUrl);
      ws = new WebSocket(wsUrl);

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
