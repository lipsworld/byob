#!/bin/bash
#
# Queue exhaustion script suitable for crontab / launchd execution.
#
PATH=/usr/local/bin:/bin:/sbin:/usr/bin:/usr/sbin:$PATH

cd $(dirname $0)/..;

# Try to prevent multiple parallel queues, not supported by repack script.
if [ -f queue-running.txt ]; then
    exit;
fi;
touch workspace/queue-running.txt

# Run queue exhaustion, touching stamps for start/stop
touch workspace/queue-status-start.txt
/usr/local/php5/bin/php index.php messagequeue/exhaust >workspace/queue-status-last.txt 2>&1
touch workspace/queue-status-stop.txt

# Delete the queue lock.
rm workspace/queue-running.txt
