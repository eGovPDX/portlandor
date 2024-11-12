const COOKIE_PREFIX = "Drupal.visitor.cloudy_notification_dismissed.";

Drupal.behaviors.cloudyNotificationHandler = {
  attach(context) {
    once("cloudyNotificationHandler", ".cloudy-notification", context).forEach((notification) => {
      const notificationId = notification.dataset["nid"];
      const notificationChanged = notification.dataset["changed"];
      const cookieStr = COOKIE_PREFIX + notificationId + "=" + notificationChanged;
      // If we haven't found a dismissal cookie for the notification and changed timestamp,
      // show the notification. Notifications are hidden by default to prevent flashing content.
      if (!document.cookie.includes(cookieStr)) {
        notification.classList.remove("d-none");
      }

      notification.addEventListener("closed.bs.alert", () => {
        // Set dismissal cookie
        document.cookie = cookieStr;
      });
    });
  },
};
