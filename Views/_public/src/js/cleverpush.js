(function() {
    if (typeof cleverPushConfig === 'object' && cleverPushConfig.channelId) {
        CleverPush = window.CleverPush || [];

        CleverPush.push(['init', {
            channelId: cleverPushConfig.channelId,
            autoRegister: false
        }]);

        CleverPush.push(['triggerOptIn', function(err, subscriptionId) {
            if (subscriptionId) {
                var xhr = new XMLHttpRequest();
                xhr.onreadystatechange = function() {
                    if (xhr.readyState == XMLHttpRequest.DONE) {
                        if (xhr.responseText && xhr.responseText.length) {

                        }
                    }
                };
                xhr.open('POST', '/cleverpush/set-subscription', true);
                xhr.setRequestHeader('X-CSRF-Token', CSRF.getToken());
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.send('subscriptionId=' + subscriptionId);
            } else {
                if (err) {
                    console.error('CleverPush:', err);
                } else {
                    console.error('CleverPush: subscription ID not found');
                }
            }
        }]);
    }
})();
