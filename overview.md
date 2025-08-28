**Orange PHP Framework – File Overview Documentation**

This document provides a high-level overview of the main files in the Orange PHP MVC framework and their roles in the request lifecycle.

---

### **Entry Point**

* **index.php**

  * Acts as the **front controller**.
  * Defines the root path and loads autoloaders.
  * Bootstraps the application by calling `Application::http($config)`.
  * All web requests pass through this file.

---

### **Core Framework Classes**

* **Application.php**

  * The **framework bootstrapper**.
  * Loads environment variables, constants, and configuration.
  * Initializes the Dependency Injection (DI) Container.
  * Starts the HTTP lifecycle and triggers framework events.
  * Directs control to the Router.

* **Container.php**

  * Implements the **Dependency Injection container**.
  * Registers, manages, and resolves services.
  * Supports closures, singletons, and auto-wiring.
  * Provides services like router, dispatcher, input, output, logger.

* **Config.php**

  * Central configuration manager.
  * Loads config files from multiple directories.
  * Merges environment-specific overrides.
  * Provides easy access to config values via object or array syntax.

* **Data.php**

  * A **shared data container** for application state.
  * Extends `SingletonArrayObject` to allow array- and property-style access.
  * Used for storing request/response data and variables passed to views.

* **Event.php**

  * Implements the framework’s **event system**.
  * Allows registration of listeners for triggers like `before.router`, `before.controller`, `before.output`.
  * Executes listeners with priority-based ordering.
  * Enables extensibility without modifying core code.

* **Dispatcher.php**

  * Executes the **controller method** defined by the router.
  * Instantiates controllers with DI container.
  * Passes route parameters to the controller method.
  * Ensures return values are valid (must be string output).

* **Input.php**

  * Handles all **incoming request data**.
  * Normalizes GET, POST, FILES, SERVER, and raw body.
  * Detects request type (HTML, AJAX, CLI) and HTTP method.
  * Provides helper methods to safely access input values.

* **Output.php**

  * Manages **response content** and headers.
  * Sets status codes, content type, and charset.
  * Handles redirects and HTTPS enforcement.
  * Sends the final response to the client.

* **Error.php**

  * Centralized **error and exception handler**.
  * Captures uncaught exceptions and error codes.
  * Loads error views if available, otherwise falls back to raw output.
  * Sends appropriate HTTP response codes (404, 500, etc.).

* **Log.php**

  * Provides a **logging service**.
  * Implements PSR-3 `LoggerInterface` for compatibility.
  * Supports file-based logging or external logging handlers.
  * Logs framework and application-level events, errors, and debug info.

* **ViewAbstract.php**

  * Base class for **view rendering engines**.
  * Manages view paths, aliases, caching, and dynamic view resolution.
  * Provides consistent rendering API (`render()`, `renderString()`).
  * Used by concrete view implementations (e.g., PHP templates).

* **Security.php**

  * Provides **cryptographic utilities**.
  * Encryption/decryption, key management, and HMAC signatures.
  * Secure password hashing and verification (Argon2).
  * Input sanitation (filenames, invisible characters).

---

### **Request Lifecycle Summary**

1. **index.php** – All requests enter through front controller.
2. **Application.php** – Bootstraps environment and container.
3. **Input.php** – Captures request data.
4. **Router.php** – Matches URI + method to a route (not shown above but critical).
5. **Dispatcher.php** – Calls the controller action.
6. **Controller** – Application logic, retrieves data via models.
7. **View (ViewAbstract)** – Renders template with provided data.
8. **Output.php** – Sends final response to client.
9. **Error.php** – Handles errors/exceptions if encountered.

---

### **Supporting Services**

* **Event.php** – Adds extensibility hooks.
* **Log.php** – Provides system logging.
* **Security.php** – Ensures secure cryptographic handling.
* **Data.php** – Shared data storage for request lifecycle.

---

**Big Picture**

These files work together to implement a **lightweight MVC framework**. Requests are funneled through `index.php`, processed by the core (Application, Router, Dispatcher), passed to controllers and views, and finalized by Output. Along the way, supporting services (Config, Container, Event, Log, Security, Data) provide structure, security, and extensibility.
