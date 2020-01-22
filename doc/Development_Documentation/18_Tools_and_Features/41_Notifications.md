# Notifications

Feature allows to send notifications to user. `Notifications` adds to status bar new clickable icon, on click it opens new tab with all notifications, also it contains badge with unread notifications count.

When there's new notification for user, it shows as window with possibility to close it, mark as read or open details.

![Notifications](../img/notifications_example.png)

User can use `Share via Notifications` button in order to open a new notification window and pre-fill the attachment field with desired element.

![Notifications](../img/share_via_notifications_example.png)

## Working with Notifications via API

### Overview 

For accessing and working with tags via API, take a look into `Pimcore\Model\Notification\Service\NotificationService`.
```php
<?php
 /**
     * @param int $id
     * @return Notification
     * @throws \UnexpectedValueException
     */
    public function find(int $id): Notification
    {
        $notification = Notification::getById($id);

        if (!$notification instanceof Notification) {
            throw new \UnexpectedValueException("Notification with the ID {$id} doesn't exists");
        }

        return $notification;
    }
    
    /**
     * @param array $filter
     * @param array $options
     * @return array
     */
    public function findAll(array $filter = [], array $options = []): array
    {
        $listing = new Listing();

        if (!empty($filter)) {
            $condition          = implode(' AND ', array_keys($filter));
            $conditionVariables = array_values($filter);
            $listing->setCondition($condition, $conditionVariables);
        }

        $listing->setOrderKey('creationDate');
        $listing->setOrder('DESC');
        $offset = (int) $options['offset'] ?? 0;
        $limit  = (int) $options['limit'] ?? 0;

        $this->beginTransaction();

        $result = [
            'total' => $listing->count(),
            'data' => $listing->getItems($offset, $limit)
        ];

        $this->commit();

        return $result;
    }
```

If you want to send some notifications to user:
```php
<?php

use Pimcore\Model\Notification\Service\NotificationService;

public function defaultAction(
    Request $request,
    NotificationService $notificationService
) {
    $element = Asset::getById(1); // Optional
    
    $notificationService->sendToUser(
        4, // User recipient
        2, // User sender 0 - system
        'Example notification',
        'Example message', 
        $element // Optional linked element
    );
}
```

If you want to send some notifications to users from group:
```php
<?php

use Pimcore\Model\Notification\Service\NotificationService;

public function defaultAction(
    Request $request,
    NotificationService $notificationService
) {
    $element = Asset::getById(1); // Optional
    
    $notificationService->sendToGroup(
        4, // Group recipient
        2, // User sender 0 - system
        'Example notification',
        'Example message', 
        $element // Optional linked element
    );
}
```

