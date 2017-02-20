# server-logs-checker

Cron:
```
# Error notifier
0    *    *    *    *    php /root/scripts/logs-checker/err-logs-checker.php >/dev/null 2>&1
```
