# Notifications

Notifications are integrated into important business events rather than embedded directly into controllers.

## Project notifications

The project has a `ProjectNotificationService` used by `ProjectStatusService` for events such as:

- project started;
- project put on hold;
- project resumed;
- project cancelled;
- project completed.

## Transaction rule

For transactional lifecycle changes, notifications are triggered after database commit.

## Other notification areas

The project planning also includes task start notifications and other domain notifications. Exact channels/templates should be synchronized with the current notification classes.
