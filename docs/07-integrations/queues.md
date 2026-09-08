# Queues

Redis was configured as the queue backend during development.

Queues are appropriate for work that does not need to block the HTTP response, such as non-critical notifications or other asynchronous operations when the current implementation dispatches them.

The exact job classes and retry strategy should be verified from the repository.
