
# FormatD.UserActionHistory

A request history for Neos FLow applications.
This package was mainly build for CRUD like backend interfaces but can be used for a lot of use cases.

## What does it do?

It provides a session scope object with the possibility to add requests into a history stack. 
With this history it is possible for example to handle back-links or redirects back to the previous page after some action.

## Adding Entries

Inject the UserActionHistory into your controller and add entries like that. You should not add unsafe requests (e.g. POST) to the history because if you redirect to them later it will have unexpected results.

```
    $this->userActionHistory->addEntry('Edit Backend-User: ' . $user->getName(), $this->request, $user);
```

## Redirecting to previous request

If you want to redirect to the previous request for example in an updateAction you can do the following. 
Notice that you can provide action patterns to skip (for example if you do not want to redirect to the editAction but to the request previous to that).

```
    if ($lastRequest = $this->userActionHistory->getLastActionRequest('UserManagement->edit')) {
        $this->redirectToRequest($lastRequest);
    } else {
        $this->redirect('index');
    }
```


## Displaying a linked list of history items

If you want to build a menu of your last visited pages (or last edited records in a CRUD application) you would to it like that.

In Controller:
```
    $this->view->assign('userActionHistoryEntries', $this->userActionHistory->getLastEntries(15, '*->index'));
```

In Template:
```
    <ul>
        <f:for each="{userActionHistoryEntries}" as="entry">
            <li>
                <f:link.action controller="History" action="redirectToActionHistoryEntry" package="FormatD.UserActionHistory" arguments="{entryId: entry.id}">
                    {entry.description}
                </f:link.action>
            </li>
        </f:for>
    </ul>
```
(Don't forget to make the HistoryController accessable in your routes configuration and if necessary in your policy configuration)