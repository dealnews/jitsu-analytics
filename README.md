# Jitsu Event Sending for PHP

This library is for sending events to [Jitsu](https://jitsu.com/).

## Example

```php
$jitsu = new DealNews\JitsuAnalytics\Send("YOUR WRITE KEY", "YOUR JITSU DOMAIN");
$jitsu->track(
  'some_event', 
  [
      'some_prop' => 'some_value'
  ]
);
```
