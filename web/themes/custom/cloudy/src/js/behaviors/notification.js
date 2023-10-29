const COOKIE_PREFIX = "Drupal.visitor.cloudy_notification_dismissed.";

Drupal.behaviors.notificationHandler = {
  attach() {
    const notifications = document.querySelectorAll(".cloudy-notification");
    notifications.forEach((notification) => {
      const notificationId = notification.dataset["nid"];
      const notificationChanged = notification.dataset["changed"];
      const cookieStr =
        COOKIE_PREFIX + notificationId + "=" + notificationChanged;

      // If we haven't found a dismissal cookie for the notification and changed timestamp,
      // show the notification.
      if (!document.cookie.includes(cookieStr)) {
        notification.classList.add("cloudy-notification--active-dismissible");
      }

      const closeButton = notification.querySelector(
        ".cloudy-notification__close"
      );
      closeButton.addEventListener("click", (e) => {
        e.preventDefault();
        notification.classList.remove("cloudy-notification--active-dismissible");
        // Set dismissal cookie
        document.cookie = cookieStr;
      });
    });
  },
};
